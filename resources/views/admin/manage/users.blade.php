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
                    Add users manually, import them from Excel-compatible CSV, export users, and assign staff to a branch.
                </p>
            </div>

            @if(session('success'))
                <div class="mb-5 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">{{ session('success') }}</div>
            @endif
            @if(session('error') || $errors->any())
                <div class="mb-5 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-800">{{ session('error') ?? $errors->first() }}</div>
            @endif

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
                                <option value="{{ $location->id }}">{{ $location->name }}</option>
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
                        Roles: <span class="font-bold">student</span>, <span class="font-bold">staff</span>, <span class="font-bold">admin</span>.
                        Staff should have a branch value like Gaza or Khan Younis.
                    </div>
                    <form method="POST" action="{{ route('admin.manage.users.import') }}" enctype="multipart/form-data" class="mt-4 flex gap-2">
                        @csrf
                        <input type="file" name="file" accept=".csv,.txt" class="min-w-0 flex-1 rounded-md border-slate-300 text-sm">
                        <button class="rounded-md border border-slate-300 px-3 py-2 text-sm font-bold text-slate-700 hover:bg-slate-100">Import</button>
                    </form>
                </section>
            </div>

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
                    </form>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-5 py-3 text-left text-xs font-bold uppercase text-slate-500">User</th>
                                <th class="px-5 py-3 text-left text-xs font-bold uppercase text-slate-500">Phone</th>
                                <th class="px-5 py-3 text-left text-xs font-bold uppercase text-slate-500">Role</th>
                                <th class="px-5 py-3 text-left text-xs font-bold uppercase text-slate-500">Branch</th>
                                <th class="px-5 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($users as $user)
                                <tr>
                                    <form method="POST" action="{{ route('admin.manage.users.update', $user) }}">
                                        @csrf
                                        @method('PATCH')
                                        <td class="px-5 py-4">
                                            <input name="name" value="{{ $user->name }}" class="mb-2 w-56 rounded-md border-slate-300 text-sm">
                                            <input type="email" name="email" value="{{ $user->email }}" class="block w-56 rounded-md border-slate-300 text-sm">
                                        </td>
                                        <td class="px-5 py-4"><input name="phone" value="{{ $user->phone }}" class="w-40 rounded-md border-slate-300 text-sm"></td>
                                        <td class="px-5 py-4">
                                            <select name="role" class="w-32 rounded-md border-slate-300 text-sm">
                                                @foreach($roles as $role)
                                                    <option value="{{ $role }}" @selected($user->role === $role)>{{ ucfirst($role) }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td class="px-5 py-4">
                                            <select name="booking_location_id" class="w-44 rounded-md border-slate-300 text-sm">
                                                <option value="">All / none</option>
                                                @foreach($locations as $location)
                                                    <option value="{{ $location->id }}" @selected($user->booking_location_id === $location->id)>{{ $location->name }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td class="px-5 py-4 text-right">
                                            <input name="password" placeholder="New password" class="mb-2 w-36 rounded-md border-slate-300 text-sm">
                                            <button class="block rounded-md border border-slate-300 px-3 py-2 text-sm font-bold text-slate-700 hover:bg-slate-100">Save</button>
                                        </td>
                                    </form>
                                    <td class="hidden">
                                        <form method="POST" action="{{ route('admin.manage.users.password-link', $user) }}">
                                            @csrf
                                        </form>
                                    </td>
                                </tr>
                                <tr class="bg-slate-50">
                                    <td colspan="5" class="px-5 py-2 text-right">
                                        <form method="POST" action="{{ route('admin.manage.users.password-link', $user) }}">
                                            @csrf
                                            <button class="text-xs font-bold text-blue-700 hover:text-blue-900">Resend password setup link</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-slate-200 px-5 py-4">{{ $users->links() }}</div>
            </section>
        </div>
    </main>
</div>
@endsection
