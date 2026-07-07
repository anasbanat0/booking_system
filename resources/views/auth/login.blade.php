@php
    $portal = $portal ?? 'student';
    $portalConfig = [
        'student' => [
            'label' => 'Student Portal',
            'title' => 'Book your weekly appointment',
            'copy' => 'Sign in to view the weekly calendar, reserve a seat, and manage your bookings.',
            'accent' => 'text-teal-700',
            'badge' => 'SF',
        ],
        'staff' => [
            'label' => 'Staff Portal',
            'title' => 'Manage your branch calendar',
            'copy' => 'Staff members can manage bookings, statuses, closed days, and notifications for their assigned branch.',
            'accent' => 'text-blue-700',
            'badge' => 'ST',
        ],
        'admin' => [
            'label' => 'Admin Portal',
            'title' => 'Control all branches',
            'copy' => 'Admins can manage branches, staff, students, booking rules, imports, exports, and full calendar operations.',
            'accent' => 'text-slate-800',
            'badge' => 'AD',
        ],
    ][$portal] ?? null;
@endphp

<x-guest-layout>
    <div class="mb-6 text-center">
        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-lg bg-slate-950 text-sm font-extrabold text-white">
            {{ $portalConfig['badge'] }}
        </div>
        <p class="mt-4 text-sm font-extrabold uppercase tracking-wide {{ $portalConfig['accent'] }}">
            {{ $portalConfig['label'] }}
        </p>
        <h1 class="mt-2 text-2xl font-extrabold text-slate-950">{{ $portalConfig['title'] }}</h1>
        <p class="mt-2 text-sm leading-6 text-slate-500">{{ $portalConfig['copy'] }}</p>
    </div>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="space-y-4">
        @csrf

        <label class="block">
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="mt-1 block w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </label>

        <label class="block">
            <x-input-label for="password" :value="__('Password')" />
            <x-text-input id="password" class="mt-1 block w-full" type="password" name="password" required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </label>

        <div class="flex items-center justify-between gap-3">
            <label for="remember_me" class="inline-flex items-center">
                <input id="remember_me" type="checkbox" class="rounded border-gray-300 text-slate-900 shadow-sm focus:ring-slate-700" name="remember">
                <span class="ms-2 text-sm text-gray-600">{{ __('Remember me') }}</span>
            </label>

            @if (Route::has('password.request'))
                <a class="text-sm font-semibold text-slate-600 hover:text-slate-950" href="{{ route('password.request') }}">
                    Need help?
                </a>
            @endif
        </div>

        <button class="w-full rounded-md bg-slate-950 px-4 py-2.5 text-sm font-extrabold text-white hover:bg-slate-800">
            Continue
        </button>
    </form>

    <div class="mt-6 grid grid-cols-3 gap-2 text-center text-xs font-bold">
        <a href="{{ route('login') }}" class="rounded-md border border-slate-200 px-2 py-2 text-slate-600 hover:bg-slate-100">Student</a>
        <a href="{{ route('staff.login') }}" class="rounded-md border border-slate-200 px-2 py-2 text-slate-600 hover:bg-slate-100">Staff</a>
        <a href="{{ route('admin.login') }}" class="rounded-md border border-slate-200 px-2 py-2 text-slate-600 hover:bg-slate-100">Admin</a>
    </div>
</x-guest-layout>
