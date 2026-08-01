<?php

namespace App\Http\Controllers;

use App\Models\BookingLocation;
use App\Models\ActivityLog;
use App\Models\User;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminManageUserController extends Controller
{
    public function index(Request $request)
    {
        $query = $this->scopedUsers($request)->with('managedLocation')->orderBy('name');

        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        if ($request->filled('search')) {
            $query->where(function ($builder) use ($request) {
                $builder->where('name', 'like', '%' . $request->search . '%')
                    ->orWhere('email', 'like', '%' . $request->search . '%')
                    ->orWhere('phone', 'like', '%' . $request->search . '%');
            });
        }

        $trashedUsers = $this->scopedUsers($request)
            ->onlyTrashed()
            ->with('managedLocation')
            ->latest('deleted_at')
            ->paginate(10, ['*'], 'trash_page')
            ->withQueryString();
        $roles = $request->user()->canManageAllBranches() ? ['student', 'staff', 'admin'] : ['student'];

        return view('admin.manage.users', [
            'users' => $query->paginate(15)->withQueryString(),
            'trashedUsers' => $trashedUsers,
            'locations' => $request->user()->canManageAllBranches()
                ? BookingLocation::orderBy('name')->get()
                : BookingLocation::whereKey($request->user()->booking_location_id)->get(),
            'roles' => $roles,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->whereNull('deleted_at')],
            'phone' => ['nullable', 'string', 'max:40', Rule::unique('users', 'phone')->whereNull('deleted_at')],
            'role' => ['required', Rule::in($request->user()->canManageAllBranches() ? ['student', 'staff', 'admin'] : ['student'])],
            'booking_location_id' => ['nullable', 'required_if:role,student,staff', 'exists:booking_locations,id'],
            'password' => ['nullable', 'string', 'min:6'],
        ]);

        $branchId = $this->resolvedBranchId($request, $validated['role'], $validated['booking_location_id'] ?? null);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'role' => $validated['role'],
            'booking_location_id' => $branchId,
            'password' => $validated['password'] ?? 'password',
        ]);

        $this->sendAccountCreatedWhatsAppAfterResponse($user);
        $this->sendPasswordSetupLinkAfterResponse($user);
        ActivityLog::record('user_created', 'User created', $user->name . ' was created from Manage Users.', [
            'user_id' => $user->id,
            'properties' => ['role' => $user->role],
        ]);

        return back()->with('success', 'User created successfully.');
    }

    public function update(Request $request, User $user)
    {
        $this->authorizeUserManagement($request, $user);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:40', Rule::unique('users', 'phone')->ignore($user->id)],
            'role' => ['required', Rule::in($request->user()->canManageAllBranches() ? ['student', 'staff', 'admin'] : ['student'])],
            'booking_location_id' => ['nullable', 'required_if:role,student,staff', 'exists:booking_locations,id'],
            'password' => ['nullable', 'string', 'min:6'],
        ]);

        $payload = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'role' => $validated['role'],
            'booking_location_id' => $this->resolvedBranchId($request, $validated['role'], $validated['booking_location_id'] ?? null),
        ];

        if (!empty($validated['password'])) {
            $payload['password'] = $validated['password'];
        }

        $user->update($payload);
        ActivityLog::record('user_updated', 'User updated', $user->name . ' was updated from Manage Users.', [
            'user_id' => $user->id,
            'properties' => ['role' => $user->role],
        ]);

        return back()->with('success', 'User updated successfully.');
    }

    public function export(): StreamedResponse
    {
        $filename = 'users-' . now()->format('Y-m-d') . '.csv';

        return response()->streamDownload(function () {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['name', 'email', 'phone', 'role', 'branch', 'password']);

            $this->scopedUsers(request())->with('managedLocation')->orderBy('name')->chunk(200, function ($users) use ($handle) {
                foreach ($users as $user) {
                    fputcsv($handle, [
                        $user->name,
                        $user->email,
                        $user->phone,
                        $user->role,
                        $user->managedLocation?->name,
                        '',
                    ]);
                }
            });

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function resendPasswordLink(User $user)
    {
        $this->authorizeUserManagement(request(), $user);

        $this->sendPasswordSetupLinkAfterResponse($user);
        ActivityLog::record('password_link_sent', 'Password setup link sent', 'A password setup link was sent to ' . $user->email . '.', [
            'user_id' => $user->id,
        ]);

        return back()->with('success', 'Password setup link sent to ' . $user->email . ' and WhatsApp when a valid phone is available.');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt'],
        ]);

        $locations = BookingLocation::pluck('id', 'name')
            ->mapWithKeys(fn ($id, $name) => [strtolower(trim($name)) => $id]);
        $handle = fopen($request->file('file')->getRealPath(), 'r');
        $header = fgetcsv($handle);

        if (!$header) {
            return back()->with('error', 'The uploaded file is empty.');
        }

        $header = array_map(fn ($value) => strtolower(trim($value)), $header);
        $imported = 0;
        $skipped = 0;
        $skippedRows = [];
        $outsideBranchDuplicates = 0;
        $missingBranchRows = 0;

        while (($row = fgetcsv($handle)) !== false) {
            $row = array_slice(array_pad($row, count($header), null), 0, count($header));
            $data = array_combine($header, $row);
            $role = in_array($data['role'] ?? 'student', $request->user()->canManageAllBranches() ? ['student', 'staff', 'admin'] : ['student'], true) ? $data['role'] : 'student';

            if (empty($data['email']) || empty($data['name'])) {
                continue;
            }

            $email = trim($data['email']);
            $phone = trim($data['phone'] ?? '');
            $existingUser = User::withTrashed()->where(function ($query) use ($email, $phone) {
                $query->where('email', $email);

                if ($phone !== '') {
                    $query->orWhere('phone', $phone);
                }
            })->first();

            if ($existingUser) {
                $skipped++;
                $skippedRows[] = $email;

                if (
                    !$request->user()->canManageAllBranches()
                    && (int) $existingUser->booking_location_id !== (int) $request->user()->booking_location_id
                ) {
                    $outsideBranchDuplicates++;
                }

                continue;
            }

            $branchId = $request->user()->canManageAllBranches()
                ? ($locations[strtolower(trim($data['branch'] ?? ''))] ?? null)
                : $request->user()->booking_location_id;

            if ($role !== 'admin' && !$branchId) {
                $skipped++;
                $missingBranchRows++;
                $skippedRows[] = $email;
                continue;
            }

            $user = User::create([
                'name' => trim($data['name']),
                'email' => $email,
                'phone' => $phone !== '' ? $phone : null,
                'role' => $role,
                'booking_location_id' => $role === 'admin' ? null : $branchId,
                'password' => $data['password'] ?? 'password',
            ]);

            $this->sendAccountCreatedWhatsAppAfterResponse($user);
            $this->sendPasswordSetupLinkAfterResponse($user);
            ActivityLog::record('user_imported', 'User imported', $user->name . ' was imported or updated from CSV.', [
                'user_id' => $user->id,
                'properties' => ['role' => $user->role],
            ]);

            $imported++;
        }

        fclose($handle);

        $message = $imported . ' users imported successfully.';

        if ($skipped > 0) {
            $message .= ' ' . $skipped . ' duplicate users skipped: ' . implode(', ', array_slice($skippedRows, 0, 5)) . ($skipped > 5 ? '...' : '') . '.';
        }

        if ($outsideBranchDuplicates > 0) {
            $message .= ' ' . $outsideBranchDuplicates . ' student(s) already exist in another branch. To add or move them into your branch, please contact the main admin.';
        }

        if ($missingBranchRows > 0) {
            $message .= ' ' . $missingBranchRows . ' student/staff row(s) skipped because branch is required.';
        }

        return back()->with($skipped > 0 ? 'warning' : 'success', $message);
    }

    public function destroy(Request $request, User $user)
    {
        $this->authorizeUserManagement($request, $user);

        if ($request->user()->is($user)) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        $user->delete();
        ActivityLog::record('user_deleted', 'User moved to trash', $user->name . ' was moved to trash.', [
            'user_id' => $user->id,
        ]);

        return back()->with('success', 'User moved to trash.');
    }

    public function bulkDestroy(Request $request)
    {
        $validated = $request->validate([
            'user_ids' => ['required', 'array'],
            'user_ids.*' => ['exists:users,id'],
        ]);

        $users = $this->scopedUsers($request)
            ->whereIn('id', $validated['user_ids'])
            ->whereKeyNot($request->user()->id)
            ->get();

        foreach ($users as $user) {
            $user->delete();
            ActivityLog::record('user_deleted', 'User moved to trash', $user->name . ' was moved to trash.', [
                'user_id' => $user->id,
            ]);
        }

        return back()->with('success', $users->count() . ' users moved to trash.');
    }

    public function restore(Request $request, int $user)
    {
        $trashedUser = $this->scopedUsers($request)->onlyTrashed()->findOrFail($user);
        $trashedUser->restore();

        ActivityLog::record('user_restored', 'User restored', $trashedUser->name . ' was restored from trash.', [
            'user_id' => $trashedUser->id,
        ]);

        return back()->with('success', 'User restored successfully.');
    }

    private function scopedUsers(Request $request)
    {
        return User::query()
            ->when(!$request->user()->canManageAllBranches(), function ($query) use ($request) {
                $query->where('role', 'student')
                    ->where('booking_location_id', $request->user()->booking_location_id);
            });
    }

    private function authorizeUserManagement(Request $request, User $user): void
    {
        if ($request->user()->canManageAllBranches()) {
            return;
        }

        abort_unless(
            $user->role === 'student' && (int) $user->booking_location_id === (int) $request->user()->booking_location_id,
            403
        );
    }

    private function resolvedBranchId(Request $request, string $role, ?int $branchId): ?int
    {
        if ($role === 'admin') {
            return null;
        }

        if (!$request->user()->canManageAllBranches()) {
            return $request->user()->booking_location_id;
        }

        if (!$branchId) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'booking_location_id' => 'Branch is required for students and staff.',
            ]);
        }

        return $branchId;
    }

    private function sendAccountCreatedWhatsAppAfterResponse(User $user): void
    {
        app()->terminating(function () use ($user) {
            app(WhatsAppService::class)->sendAccountCreated($user);
        });
    }

    private function sendPasswordSetupLinkAfterResponse(User $user): void
    {
        app()->terminating(function () use ($user) {
            $this->sendPasswordSetupChannels($user);
        });
    }

    private function sendPasswordSetupChannels(User $user): void
    {
        $status = Password::sendResetLink(['email' => $user->email]);

        if ($status !== Password::RESET_LINK_SENT) {
            Log::warning('Password setup link could not be generated.', [
                'user_id' => $user->id,
                'status' => $status,
            ]);
        }
    }
}
