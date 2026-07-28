@php
    $adminLinks = [
        [
            'label' => 'Dashboard',
            'route' => 'admin.dashboard',
            'active' => request()->routeIs('admin.dashboard'),
            'icon' => 'M3 13h8V3H3v10Zm0 8h8v-6H3v6Zm10 0h8V11h-8v10Zm0-18v6h8V3h-8Z',
        ],
        [
            'label' => 'Bookings',
            'route' => 'admin.bookings.index',
            'active' => request()->routeIs('admin.bookings.*'),
            'icon' => 'M7 3v2H5a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2V3h-2v2H9V3H7Zm12 8H5v8h14v-8Z',
        ],
        [
            'label' => 'Users Calendar',
            'route' => 'admin.users-calendar.index',
            'active' => request()->routeIs('admin.users-calendar.*'),
            'icon' => 'M5 3a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V5a2 2 0 0 0-2-2H5Zm2 4h2v2H7V7Zm4 0h6v2h-6V7Zm-4 4h2v2H7v-2Zm4 0h6v2h-6v-2Zm-4 4h2v2H7v-2Zm4 0h6v2h-6v-2Z',
        ],
        [
            'label' => 'Notifications',
            'route' => 'admin.notifications.index',
            'active' => request()->routeIs('admin.notifications.*'),
            'icon' => 'M12 22a2.5 2.5 0 0 0 2.45-2h-4.9A2.5 2.5 0 0 0 12 22Zm7-6V11a7 7 0 0 0-5-6.71V3a2 2 0 1 0-4 0v1.29A7 7 0 0 0 5 11v5l-2 2v1h18v-1l-2-2Z',
        ],
        [
            'label' => 'Slots Time',
            'route' => 'admin.slots.index',
            'active' => request()->routeIs('admin.slots.*') || request()->routeIs('admin.locations.*') || request()->routeIs('admin.slot-templates.*') || request()->routeIs('admin.booking-rules.*'),
            'icon' => 'M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20Zm1 5h-2v6l5 3 1-1.73-4-2.27V7Z',
        ],
        [
            'label' => 'Manage Users',
            'route' => 'admin.manage.users.index',
            'active' => request()->routeIs('admin.manage.users.*'),
            'icon' => 'M8 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm8.5-1a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7ZM8 13c-3.31 0-6 1.79-6 4v2h12v-2c0-2.21-2.69-4-6-4Zm8.5-.5c-.83 0-1.62.11-2.33.31 1.14 1.02 1.83 2.46 1.83 4.19V19h6v-2c0-2.49-2.46-4.5-5.5-4.5Z',
        ],
    ];

    if (Auth::user()?->isAdminPanelUser()) {
        $adminLinks[] = [
            'label' => 'Homepage',
            'route' => 'admin.content.index',
            'active' => request()->routeIs('admin.content.*'),
            'icon' => 'M4 5a2 2 0 0 1 2-2h9l5 5v11a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V5Zm10 0v4h4l-4-4ZM7 13h10v-2H7v2Zm0 4h7v-2H7v2Z',
        ];
    }

    if (Auth::user()?->canManageAllBranches()) {
        $adminLinks[] = [
            'label' => 'Activity Log',
            'route' => 'admin.activity.index',
            'active' => request()->routeIs('admin.activity.*'),
            'icon' => 'M4 4h16v2H4V4Zm0 5h16v2H4V9Zm0 5h10v2H4v-2Zm0 5h16v2H4v-2Z',
        ];
    }
@endphp

<aside class="hidden w-72 shrink-0 border-r border-slate-200 bg-white lg:block">
    <div class="sticky top-0 flex h-screen flex-col px-5 py-6">
        <div class="mb-8">
            <div class="flex items-center gap-3">
                @if(Auth::user()?->profile_photo_path)
                    <img src="{{ route('profile.photo', Auth::user()) }}"
                         alt="{{ Auth::user()?->name }}"
                         class="h-14 w-14 rounded-full border-4 border-white object-cover shadow-lg shadow-slate-200 ring-1 ring-slate-200">
                @else
                    <div class="flex h-14 w-14 items-center justify-center rounded-full bg-gradient-to-br from-slate-950 via-blue-900 to-teal-700 text-lg font-extrabold text-white shadow-lg shadow-slate-200 ring-1 ring-slate-200">
                        {{ strtoupper(mb_substr(Auth::user()?->name ?? 'U', 0, 1)) }}
                    </div>
                @endif
                <div class="min-w-0">
                    <p class="truncate text-sm font-extrabold text-slate-950">{{ Auth::user()?->name }}</p>
                    <p class="truncate text-xs font-semibold text-slate-500">{{ Auth::user()?->email }}</p>
                </div>
            </div>
            <p class="mt-4 text-xs font-semibold uppercase tracking-wide text-blue-700">Booking System</p>
            <h2 class="mt-1 text-xl font-bold text-slate-950">
                {{ Auth::user()?->role === 'staff' ? 'Staff Panel' : 'Admin Panel' }}
            </h2>
            @if(Auth::user()?->role === 'staff')
                <p class="mt-2 rounded-md bg-blue-50 px-3 py-2 text-xs font-bold text-blue-700">
                    Branch: {{ Auth::user()?->managedLocation?->name ?? 'Not assigned' }}
                </p>
            @endif
        </div>

        <nav class="space-y-1">
            @foreach($adminLinks as $link)
                <a href="{{ route($link['route']) }}"
                   class="group flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-semibold transition {{ $link['active'] ? 'bg-slate-950 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-950' }}">
                    <svg class="h-5 w-5 {{ $link['active'] ? 'text-white' : 'text-slate-400 group-hover:text-slate-700' }}"
                         viewBox="0 0 24 24"
                         fill="currentColor"
                         aria-hidden="true">
                        <path d="{{ $link['icon'] }}" />
                    </svg>
                    {{ $link['label'] }}
                </a>
            @endforeach
        </nav>

        <div class="mt-auto rounded-lg border border-slate-200 bg-slate-50 p-4">
            <p class="text-sm font-semibold text-slate-900">{{ Auth::user()?->name }}</p>
            <p class="mt-1 truncate text-xs text-slate-500">{{ Auth::user()?->email }}</p>
            <p class="mt-2 text-xs font-bold uppercase tracking-wide text-slate-400">{{ Auth::user()?->role }}</p>
        </div>
    </div>
</aside>

<div class="border-b border-slate-200 bg-white px-4 py-3 lg:hidden">
    <div class="mb-3 flex items-center justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wide text-blue-700">Booking System</p>
            <p class="text-base font-bold text-slate-950">Admin Panel</p>
        </div>
        <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-slate-950 text-xs font-bold text-white">
            BS
        </div>
    </div>

    <nav class="grid grid-cols-2 gap-2">
        @foreach($adminLinks as $link)
            <a href="{{ route($link['route']) }}"
               class="flex items-center justify-center gap-2 rounded-md px-3 py-2 text-center text-sm font-semibold {{ $link['active'] ? 'bg-slate-950 text-white' : 'bg-slate-100 text-slate-700' }}">
                <svg class="h-4 w-4"
                     viewBox="0 0 24 24"
                     fill="currentColor"
                     aria-hidden="true">
                    <path d="{{ $link['icon'] }}" />
                </svg>
                {{ $link['label'] }}
            </a>
        @endforeach
    </nav>
</div>
