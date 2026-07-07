<?php

namespace App\Http\Controllers;

use App\Models\BookingLocation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminManageUserController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->canManageAllBranches(), 403);

        $query = User::with('managedLocation')->orderBy('name');

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

        return view('admin.manage.users', [
            'users' => $query->paginate(15)->withQueryString(),
            'locations' => BookingLocation::orderBy('name')->get(),
            'roles' => ['student', 'staff', 'admin'],
        ]);
    }

    public function store(Request $request)
    {
        abort_unless($request->user()->canManageAllBranches(), 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:40'],
            'role' => ['required', Rule::in(['student', 'staff', 'admin'])],
            'booking_location_id' => ['nullable', 'exists:booking_locations,id'],
            'password' => ['nullable', 'string', 'min:6'],
        ]);

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'role' => $validated['role'],
            'booking_location_id' => $validated['role'] === 'staff' ? ($validated['booking_location_id'] ?? null) : null,
            'password' => $validated['password'] ?? 'password',
        ]);

        return back()->with('success', 'User created successfully.');
    }

    public function update(Request $request, User $user)
    {
        abort_unless($request->user()->canManageAllBranches(), 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:40'],
            'role' => ['required', Rule::in(['student', 'staff', 'admin'])],
            'booking_location_id' => ['nullable', 'exists:booking_locations,id'],
            'password' => ['nullable', 'string', 'min:6'],
        ]);

        $payload = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'role' => $validated['role'],
            'booking_location_id' => $validated['role'] === 'staff' ? ($validated['booking_location_id'] ?? null) : null,
        ];

        if (!empty($validated['password'])) {
            $payload['password'] = $validated['password'];
        }

        $user->update($payload);

        return back()->with('success', 'User updated successfully.');
    }

    public function export(): StreamedResponse
    {
        abort_unless(request()->user()->canManageAllBranches(), 403);

        $filename = 'users-' . now()->format('Y-m-d') . '.csv';

        return response()->streamDownload(function () {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['name', 'email', 'phone', 'role', 'branch', 'password']);

            User::with('managedLocation')->orderBy('name')->chunk(200, function ($users) use ($handle) {
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

    public function import(Request $request)
    {
        abort_unless($request->user()->canManageAllBranches(), 403);

        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt'],
        ]);

        $locations = BookingLocation::pluck('id', 'name');
        $handle = fopen($request->file('file')->getRealPath(), 'r');
        $header = fgetcsv($handle);

        if (!$header) {
            return back()->with('error', 'The uploaded file is empty.');
        }

        $header = array_map(fn ($value) => strtolower(trim($value)), $header);
        $imported = 0;

        while (($row = fgetcsv($handle)) !== false) {
            $row = array_slice(array_pad($row, count($header), null), 0, count($header));
            $data = array_combine($header, $row);
            $role = in_array($data['role'] ?? 'student', ['student', 'staff', 'admin'], true) ? $data['role'] : 'student';

            if (empty($data['email']) || empty($data['name'])) {
                continue;
            }

            User::updateOrCreate(
                ['email' => trim($data['email'])],
                [
                    'name' => trim($data['name']),
                    'phone' => $data['phone'] ?? null,
                    'role' => $role,
                    'booking_location_id' => $role === 'staff' ? ($locations[$data['branch'] ?? ''] ?? null) : null,
                    'password' => $data['password'] ?? 'password',
                ]
            );

            $imported++;
        }

        fclose($handle);

        return back()->with('success', $imported . ' users imported successfully.');
    }
}
