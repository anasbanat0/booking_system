@php
    $notificationQuery = \App\Models\AdminNotification::query()->whereNull('read_at');

    if (Auth::user()?->role === 'staff') {
        $notificationQuery->where('booking_location_id', Auth::user()->booking_location_id);
    }

    $unreadNotifications = $notificationQuery->count();
@endphp

<div class="mb-6 flex justify-end">
    <a href="{{ route('admin.notifications.index') }}"
       class="relative inline-flex h-11 w-11 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-700 shadow-sm hover:bg-slate-100"
       title="Notifications">
        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
            <path d="M12 22a2.5 2.5 0 0 0 2.45-2h-4.9A2.5 2.5 0 0 0 12 22Zm7-6V11a7 7 0 0 0-5-6.71V3a2 2 0 1 0-4 0v1.29A7 7 0 0 0 5 11v5l-2 2v1h18v-1l-2-2Z" />
        </svg>
        @if($unreadNotifications > 0)
            <span class="absolute -right-1 -top-1 rounded-full bg-rose-600 px-1.5 py-0.5 text-[10px] font-extrabold text-white">
                {{ $unreadNotifications > 99 ? '99+' : $unreadNotifications }}
            </span>
        @endif
    </a>
</div>
