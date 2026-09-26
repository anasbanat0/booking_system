<?php

namespace App\Http\Controllers;

use App\Jobs\ImportUsersCsvChunk;
use App\Jobs\SendPasswordSetupLink;
use App\Models\ActivityLog;
use App\Models\BookingLocation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
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
                $builder->where('name', 'like', '%'.$request->search.'%')
                    ->orWhere('email', 'like', '%'.$request->search.'%')
                    ->orWhere('phone', 'like', '%'.$request->search.'%');
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
        $email = strtolower(trim($validated['email']));
        $phone = filled($validated['phone'] ?? null) ? trim($validated['phone']) : null;
        $deletedMatches = User::onlyTrashed()
            ->where(function ($query) use ($email, $phone) {
                $query->where('email', $email);

                if ($phone) {
                    $query->orWhere('phone', $phone);
                }
            })
            ->get();

        if ($deletedMatches->count() > 1) {
            throw ValidationException::withMessages([
                'email' => 'The email and phone belong to different deleted accounts. Restore or edit them from Trash first.',
                'phone' => 'The email and phone belong to different deleted accounts. Restore or edit them from Trash first.',
            ]);
        }

        $deletedUser = $deletedMatches->first();

        if (
            $deletedUser
            && ! $request->user()->canManageAllBranches()
            && (int) $deletedUser->booking_location_id !== (int) $request->user()->booking_location_id
        ) {
            throw ValidationException::withMessages([
                'email' => 'This deleted account belongs to another branch. Please contact the main admin to restore or move it.',
            ]);
        }

        if ($deletedUser) {
            $user = DB::transaction(function () use ($deletedUser, $validated, $branchId, $email, $phone) {
                $payload = [
                    'name' => $validated['name'],
                    'email' => $email,
                    'phone' => $phone,
                    'role' => $validated['role'],
                    'booking_location_id' => $branchId,
                ];

                if (filled($validated['password'] ?? null)) {
                    $payload['password'] = $validated['password'];
                }

                $deletedUser->update($payload);
                $deletedUser->restore();

                return $deletedUser->fresh();
            });

            $this->queuePasswordSetupLink($user->id, 0, true);
            ActivityLog::record('user_restored', 'Deleted user restored', $user->name.' was restored and updated from Add user manually.', [
                'user_id' => $user->id,
                'properties' => ['role' => $user->role],
            ]);

            return back()->with('success', 'The deleted account was restored and updated successfully. A password setup link was queued.');
        }

        $user = User::create([
            'name' => $validated['name'],
            'email' => $email,
            'phone' => $phone,
            'role' => $validated['role'],
            'booking_location_id' => $branchId,
            'password' => $validated['password'] ?? 'password',
        ]);

        $this->queuePasswordSetupLink($user->id, 0, true);
        ActivityLog::record('user_created', 'User created', $user->name.' was created from Manage Users.', [
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

        if ($this->wouldRemoveLastAdmin($user, $validated['role'])) {
            return back()->with('error', 'You must keep at least one admin account active.');
        }

        $payload = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'role' => $validated['role'],
            'booking_location_id' => $this->resolvedBranchId($request, $validated['role'], $validated['booking_location_id'] ?? null),
        ];

        if (! empty($validated['password'])) {
            $payload['password'] = $validated['password'];
        }

        $user->update($payload);
        ActivityLog::record('user_updated', 'User updated', $user->name.' was updated from Manage Users.', [
            'user_id' => $user->id,
            'properties' => ['role' => $user->role],
        ]);

        return back()->with('success', 'User updated successfully.');
    }

    public function export(): StreamedResponse
    {
        $filename = 'users-'.now()->format('Y-m-d').'.csv';

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

        $this->queuePasswordSetupLink($user->id, 0);
        ActivityLog::record('password_link_sent', 'Password setup link sent', 'A password setup link was sent to '.$user->email.'.', [
            'user_id' => $user->id,
        ]);

        return back()->with('success', 'Password setup link queued for '.$user->email.'.');
    }

    public function resendPasswordLinks(Request $request)
    {
        $query = $this->scopedUsers($request);

        if ($request->filled('role')) {
            $query->where('role', $request->string('role'));
        }

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where(function ($builder) use ($search) {
                $builder->where('name', 'like', '%'.$search.'%')
                    ->orWhere('email', 'like', '%'.$search.'%')
                    ->orWhere('phone', 'like', '%'.$search.'%');
            });
        }

        $queued = 0;

        $query->select('id')->orderBy('id')->chunkById(200, function ($users) use (&$queued) {
            foreach ($users as $user) {
                $this->queuePasswordSetupLink($user->id, $queued);
                $queued++;
            }
        });

        ActivityLog::record('password_links_queued', 'Password setup links queued', $queued.' password setup links were queued.', [
            'properties' => ['count' => $queued],
        ]);

        return back()->with('success', $queued.' password setup link(s) queued for delivery.');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:20480'],
        ]);

        $uploadedFile = $request->file('file');
        $handle = null;

        try {
            $handle = fopen($uploadedFile->getRealPath(), 'rb');

            if ($handle === false) {
                throw new \RuntimeException('The uploaded CSV file could not be opened.');
            }

            $header = fgetcsv($handle);

            if (! $header) {
                return back()->withErrors(['file' => 'The uploaded file is empty.']);
            }

            $header = array_map(
                fn ($value) => strtolower(trim((string) $value, "\xEF\xBB\xBF \t\n\r\0\x0B")),
                $header,
            );

            if (! in_array('name', $header, true) || ! in_array('email', $header, true)) {
                throw ValidationException::withMessages([
                    'file' => 'The CSV must contain name and email columns.',
                ]);
            }

            if (count(array_unique($header)) !== count($header)) {
                throw ValidationException::withMessages([
                    'file' => 'The CSV contains duplicate column names.',
                ]);
            }

            $locations = BookingLocation::pluck('id', 'name')
                ->mapWithKeys(fn ($id, $name) => [strtolower(trim($name)) => $id])
                ->all();
            $chunk = [];
            $rowCount = 0;
            $chunkCount = 0;
            $rowNumber = 1;

            while (($row = fgetcsv($handle)) !== false) {
                $rowNumber++;
                $row = array_slice(array_pad($row, count($header), null), 0, count($header));
                $data = array_combine($header, array_map(function ($value) {
                    $value = (string) ($value ?? '');

                    return mb_check_encoding($value, 'UTF-8')
                        ? $value
                        : mb_convert_encoding($value, 'UTF-8', ['Windows-1256', 'ISO-8859-1']);
                }, $row));

                if (blank($data['email'] ?? null) && blank($data['name'] ?? null)) {
                    continue;
                }

                $chunk[] = $data;
                $rowCount++;

                if (count($chunk) === 50) {
                    $this->dispatchImportChunk($request, $chunk, $locations, $rowCount - count($chunk));
                    $chunk = [];
                    $chunkCount++;
                }
            }

            if ($chunk !== []) {
                $this->dispatchImportChunk($request, $chunk, $locations, $rowCount - count($chunk));
                $chunkCount++;
            }

            if ($rowCount === 0) {
                return back()->withErrors(['file' => 'The CSV does not contain any user rows.']);
            }

            ActivityLog::record('user_import_queued', 'CSV user import queued', $rowCount.' CSV rows were queued for background import.', [
                'properties' => ['rows' => $rowCount, 'chunks' => $chunkCount],
            ]);

            Log::warning('CSV user import accepted.', [
                'actor_id' => $request->user()->id,
                'file' => $uploadedFile->getClientOriginalName(),
                'rows' => $rowCount,
                'chunks' => $chunkCount,
            ]);

            return back()->with('success', $rowCount.' CSV row(s) queued for import. Users will appear progressively while the background queue runs.');
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            Log::error('CSV import request failed.', [
                'actor_id' => $request->user()?->id,
                'file' => $uploadedFile?->getClientOriginalName(),
                'row' => $rowNumber ?? null,
                'exception' => $exception->getMessage(),
            ]);

            return back()->withErrors([
                'file' => 'The CSV could not be imported. No additional action is needed until the reported file error is corrected.',
            ]);
        } finally {
            if (is_resource($handle)) {
                fclose($handle);
            }
        }
    }

    private function dispatchImportChunk(Request $request, array $chunk, array $locations, int $offset): void
    {
        ImportUsersCsvChunk::dispatch(
            $chunk,
            $locations,
            $request->user()->id,
            $request->user()->canManageAllBranches(),
            $request->user()->booking_location_id,
            $offset,
        );
    }

    public function destroy(Request $request, User $user)
    {
        $this->authorizeUserManagement($request, $user);

        if ($request->user()->is($user)) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        if ($this->wouldRemoveLastAdmin($user)) {
            return back()->with('error', 'You must keep at least one admin account active.');
        }

        $user->delete();
        ActivityLog::record('user_deleted', 'User moved to trash', $user->name.' was moved to trash.', [
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

        $selectedAdminCount = $users->where('role', 'admin')->count();
        $activeAdminCount = User::where('role', 'admin')->count();

        if ($selectedAdminCount > 0 && $activeAdminCount - $selectedAdminCount < 1) {
            return back()->with('error', 'You must keep at least one admin account active.');
        }

        foreach ($users as $user) {
            $user->delete();
            ActivityLog::record('user_deleted', 'User moved to trash', $user->name.' was moved to trash.', [
                'user_id' => $user->id,
            ]);
        }

        return back()->with('success', $users->count().' users moved to trash.');
    }

    public function restore(Request $request, int $user)
    {
        $trashedUser = $this->scopedUsers($request)->onlyTrashed()->findOrFail($user);
        $trashedUser->restore();

        ActivityLog::record('user_restored', 'User restored', $trashedUser->name.' was restored from trash.', [
            'user_id' => $trashedUser->id,
        ]);

        return back()->with('success', 'User restored successfully.');
    }

    private function scopedUsers(Request $request)
    {
        return User::query()
            ->when(! $request->user()->canManageAllBranches(), function ($query) use ($request) {
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

    private function wouldRemoveLastAdmin(User $user, ?string $newRole = null): bool
    {
        if ($user->role !== 'admin') {
            return false;
        }

        if ($newRole === 'admin') {
            return false;
        }

        return ! User::where('role', 'admin')->whereKeyNot($user->id)->exists();
    }

    private function resolvedBranchId(Request $request, string $role, ?int $branchId): ?int
    {
        if ($role === 'admin') {
            return null;
        }

        if (! $request->user()->canManageAllBranches()) {
            return $request->user()->booking_location_id;
        }

        if (! $branchId) {
            throw ValidationException::withMessages([
                'booking_location_id' => 'Branch is required for students and staff.',
            ]);
        }

        return $branchId;
    }

    private function queuePasswordSetupLink(int $userId, int $position, bool $sendAccountCreatedMessage = false): void
    {
        SendPasswordSetupLink::dispatch($userId, $sendAccountCreatedMessage)
            ->delay(now()->addSeconds($position * 8));
    }
}
