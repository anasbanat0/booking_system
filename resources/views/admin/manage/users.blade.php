@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-slate-50 lg:flex">
    @include('admin.partials.sidebar')

    <main class="min-w-0 flex-1">
        <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
            @include('admin.partials.topbar')
            <div class="mb-8">
                <p class="text-sm font-semibold uppercase tracking-wide text-blue-700">Manage</p>
                <h1 class="mt-2 text-3xl font-bold text-slate-950">Users</h1>
                <p class="mt-2 max-w-3xl text-sm text-slate-600">
                    Admins manage all users. Staff can manage students only inside their assigned branch.
                </p>
            </div>

            <div class="mb-6 grid gap-6 xl:grid-cols-[1fr_420px]">
                <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="text-lg font-bold text-slate-950">Add user manually</h2>
                    <form method="POST" action="{{ route('admin.manage.users.store') }}" class="mt-4 grid gap-4 md:grid-cols-3">
                        @csrf
                        <input name="name" placeholder="Full name" class="rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <input type="email" name="email" placeholder="Email" class="rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <input name="phone" placeholder="Phone" class="rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <select name="role" class="rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            @foreach($roles as $role)
                                <option value="{{ $role }}">{{ ucfirst($role) }}</option>
                            @endforeach
                        </select>
                        <select name="booking_location_id" class="rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">No branch / all branches</option>
                            @foreach($locations as $location)
                                <option value="{{ $location->id }}" @selected(Auth::user()?->role === 'staff')>{{ $location->name }}</option>
                            @endforeach
                        </select>
                        <input name="password" placeholder="Password, default: password" class="rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <div class="md:col-span-3">
                            <button class="rounded-md bg-slate-950 px-4 py-2 text-sm font-bold text-white hover:bg-slate-800">Add user</button>
                        </div>
                    </form>
                </section>

                <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h2 class="text-lg font-bold text-slate-950">Import / Export</h2>
                            <p class="mt-1 text-sm text-slate-500">CSV columns: name, email, phone, role, branch, password.</p>
                        </div>
                        <a href="{{ route('admin.manage.users.export') }}" class="rounded-md bg-slate-950 px-4 py-2 text-sm font-bold text-white hover:bg-slate-800">Export</a>
                    </div>
                    <div class="mt-4 rounded-md bg-slate-50 p-3 text-xs text-slate-600">
                        Duplicates are checked by email and phone. Existing users are skipped, not overwritten.
                    </div>
                    <form method="POST" action="{{ route('admin.manage.users.import') }}" enctype="multipart/form-data" class="mt-4 flex gap-2">
                        @csrf
                        <input type="file" name="file" accept=".csv,.txt" class="min-w-0 flex-1 rounded-md border-slate-300 text-sm">
                        <button class="rounded-md border border-slate-300 px-3 py-2 text-sm font-bold text-slate-700 hover:bg-slate-100">Import</button>
                    </form>
                </section>
            </div>

            <form id="bulk-delete-form" method="POST" action="{{ route('admin.manage.users.bulk-destroy') }}">
                @csrf
                @method('DELETE')
            </form>

            <section class="rounded-lg border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 px-5 py-4">
                    <form method="GET" class="flex flex-col gap-3 md:flex-row">
                        <input name="search" value="{{ request('search') }}" placeholder="Search name, email, phone" class="rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 md:w-80">
                        <select name="role" class="rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 md:w-48">
                            <option value="">All roles</option>
                            @foreach($roles as $role)
                                <option value="{{ $role }}" @selected(request('role') === $role)>{{ ucfirst($role) }}</option>
                            @endforeach
                        </select>
                        <button class="rounded-md bg-slate-950 px-4 py-2 text-sm font-bold text-white hover:bg-slate-800">Filter</button>
                        <button type="button" data-open-modal="bulk-delete-modal" class="rounded-md border border-rose-200 px-4 py-2 text-sm font-bold text-rose-700 hover:bg-rose-50">Delete selected</button>
                    </form>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-5 py-3"><input id="select-all-users" type="checkbox" class="rounded border-slate-300"></th>
                                <th class="px-5 py-3 text-left text-xs font-bold uppercase text-slate-500">User</th>
                                <th class="px-5 py-3 text-left text-xs font-bold uppercase text-slate-500">Phone</th>
                                <th class="px-5 py-3 text-left text-xs font-bold uppercase text-slate-500">Role</th>
                                <th class="px-5 py-3 text-left text-xs font-bold uppercase text-slate-500">Branch</th>
                                <th class="px-5 py-3 text-right text-xs font-bold uppercase text-slate-500">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($users as $user)
                                <tr>
                                    <td class="px-5 py-4">
                                        <input form="bulk-delete-form" name="user_ids[]" value="{{ $user->id }}" type="checkbox" class="user-checkbox rounded border-slate-300">
                                    </td>
                                    <td class="px-5 py-4">
                                        <div class="font-semibold text-slate-950">{{ $user->name }}</div>
                                        <div class="text-sm text-slate-500">{{ $user->email }}</div>
                                    </td>
                                    <td class="px-5 py-4 text-sm text-slate-700">{{ $user->phone ?? '-' }}</td>
                                    <td class="px-5 py-4 text-sm font-semibold text-slate-700">{{ ucfirst($user->role) }}</td>
                                    <td class="px-5 py-4 text-sm text-slate-700">{{ $user->managedLocation?->name ?? 'All / none' }}</td>
                                    <td class="px-5 py-4">
                                        <div class="flex justify-end gap-2">
                                            <button type="button" data-open-modal="edit-user-{{ $user->id }}" class="rounded-md border border-slate-300 px-3 py-2 text-sm font-bold text-slate-700 hover:bg-slate-100">Edit</button>
                                            <button type="button" data-open-modal="delete-user-{{ $user->id }}" class="rounded-md border border-rose-200 px-3 py-2 text-sm font-bold text-rose-700 hover:bg-rose-50">Delete</button>
                                            <form method="POST" action="{{ route('admin.manage.users.password-link', $user) }}">
                                                @csrf
                                                <button class="rounded-md border border-blue-200 px-3 py-2 text-sm font-bold text-blue-700 hover:bg-blue-50">Reset link</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-slate-200 px-5 py-4">{{ $users->links() }}</div>
            </section>

            @if($trashedUsers->count())
                <section class="mt-6 rounded-lg border border-slate-200 bg-white shadow-sm">
                    <div class="border-b border-slate-200 px-5 py-4">
                        <h2 class="text-lg font-bold text-slate-950">Trash</h2>
                    </div>
                    <div class="divide-y divide-slate-100">
                        @foreach($trashedUsers as $user)
                            <div class="flex items-center justify-between gap-3 px-5 py-4">
                                <div>
                                    <div class="font-semibold text-slate-950">{{ $user->name }}</div>
                                    <div class="text-sm text-slate-500">{{ $user->email }} · {{ $user->managedLocation?->name ?? 'No branch' }}</div>
                                </div>
                                <form method="POST" action="{{ route('admin.manage.users.restore', $user->id) }}">
                                    @csrf
                                    <button class="rounded-md border border-emerald-200 px-3 py-2 text-sm font-bold text-emerald-700 hover:bg-emerald-50">Restore</button>
                                </form>
                            </div>
                        @endforeach
                    </div>
                    <div class="border-t border-slate-200 px-5 py-4">{{ $trashedUsers->links() }}</div>
                </section>
            @endif
        </div>
    </main>
</div>

@foreach($users as $user)
    <div id="edit-user-{{ $user->id }}" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/50 p-4" data-modal>
        <div class="w-full max-w-2xl rounded-lg bg-white p-5 shadow-xl">
            <div class="flex items-start justify-between gap-3">
                <h2 class="text-lg font-bold text-slate-950">Edit {{ $user->name }}</h2>
                <button type="button" data-close-modal class="text-sm font-bold text-slate-500">Close</button>
            </div>
            <form method="POST" action="{{ route('admin.manage.users.update', $user) }}" class="mt-4 grid gap-4 md:grid-cols-2">
                @csrf
                @method('PATCH')
                <input name="name" value="{{ $user->name }}" class="rounded-md border-slate-300 text-sm">
                <input type="email" name="email" value="{{ $user->email }}" class="rounded-md border-slate-300 text-sm">
                <input name="phone" value="{{ $user->phone }}" class="rounded-md border-slate-300 text-sm">
                <select name="role" class="rounded-md border-slate-300 text-sm">
                    @foreach($roles as $role)
                        <option value="{{ $role }}" @selected($user->role === $role)>{{ ucfirst($role) }}</option>
                    @endforeach
                </select>
                <select name="booking_location_id" class="rounded-md border-slate-300 text-sm">
                    <option value="">All / none</option>
                    @foreach($locations as $location)
                        <option value="{{ $location->id }}" @selected($user->booking_location_id === $location->id)>{{ $location->name }}</option>
                    @endforeach
                </select>
                <input name="password" placeholder="New password" class="rounded-md border-slate-300 text-sm">
                <div class="md:col-span-2 flex justify-end gap-2">
                    <button type="button" data-close-modal class="rounded-md border border-slate-300 px-4 py-2 text-sm font-bold text-slate-700">Cancel</button>
                    <button class="rounded-md bg-slate-950 px-4 py-2 text-sm font-bold text-white">Save changes</button>
                </div>
            </form>
        </div>
    </div>

    <div id="delete-user-{{ $user->id }}" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/50 p-4" data-modal>
        <div class="w-full max-w-md rounded-lg bg-white p-5 shadow-xl">
            <h2 class="text-lg font-bold text-slate-950">Delete user?</h2>
            <p class="mt-2 text-sm text-slate-600">{{ $user->name }} will move to trash and can be restored later.</p>
            <div class="mt-5 flex justify-end gap-2">
                <button type="button" data-close-modal class="rounded-md border border-slate-300 px-4 py-2 text-sm font-bold text-slate-700">Cancel</button>
                <form method="POST" action="{{ route('admin.manage.users.destroy', $user) }}">
                    @csrf
                    @method('DELETE')
                    <button class="rounded-md bg-rose-700 px-4 py-2 text-sm font-bold text-white">Confirm delete</button>
                </form>
            </div>
        </div>
    </div>
@endforeach

<div id="bulk-delete-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/50 p-4" data-modal>
    <div class="w-full max-w-md rounded-lg bg-white p-5 shadow-xl">
        <h2 class="text-lg font-bold text-slate-950">Delete selected users?</h2>
        <p class="mt-2 text-sm text-slate-600">Selected users will move to trash and can be restored later.</p>
        <div class="mt-5 flex justify-end gap-2">
            <button type="button" data-close-modal class="rounded-md border border-slate-300 px-4 py-2 text-sm font-bold text-slate-700">Cancel</button>
            <button form="bulk-delete-form" class="rounded-md bg-rose-700 px-4 py-2 text-sm font-bold text-white">Confirm delete</button>
        </div>
    </div>
</div>

<script>
document.querySelectorAll('[data-open-modal]').forEach(button => {
    button.addEventListener('click', () => {
        const modal = document.getElementById(button.dataset.openModal);
        if (modal) {
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }
    });
});

document.querySelectorAll('[data-close-modal]').forEach(button => {
    button.addEventListener('click', () => {
        const modal = button.closest('[data-modal]');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    });
});

document.getElementById('select-all-users')?.addEventListener('change', event => {
    document.querySelectorAll('.user-checkbox').forEach(checkbox => {
        checkbox.checked = event.target.checked;
    });
});
</script>
@endsection
