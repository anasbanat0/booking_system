@extends('layouts.app')

@section('content')
@php
    $statusStyles = [
        'booked' => 'bg-blue-50 text-blue-700 ring-blue-200',
        'completed' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
        'no_show' => 'bg-amber-50 text-amber-800 ring-amber-200',
        'cancelled' => 'bg-rose-50 text-rose-700 ring-rose-200',
        'rescheduled' => 'bg-violet-50 text-violet-700 ring-violet-200',
    ];

    $statusLabels = [
        'booked' => 'Booked',
        'completed' => 'Completed',
        'no_show' => 'No Show',
        'cancelled' => 'Cancelled',
        'rescheduled' => 'Rescheduled',
    ];
@endphp

<div class="min-h-screen bg-slate-50 lg:flex">
    @include('admin.partials.sidebar')

    <main class="min-w-0 flex-1">
        <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        @include('admin.partials.topbar')
        <div class="mb-8">
            <div>
                <p class="text-sm font-semibold uppercase tracking-wide text-blue-700">Admin Workspace</p>
                <h1 class="mt-2 text-3xl font-bold text-slate-950">Bookings</h1>
                <p class="mt-2 max-w-2xl text-sm text-slate-600">
                    Review every reservation, filter by status, and update booking progress without leaving the table.
                </p>
            </div>
        </div>

        <div class="mb-6 grid grid-cols-2 gap-3 md:grid-cols-5">
            @foreach($statuses as $status)
                <a href="{{ route('admin.bookings.index', ['status' => $status]) }}"
                   class="rounded-lg border {{ request('status') === $status ? 'border-slate-950 bg-white' : 'border-slate-200 bg-white hover:border-slate-300' }} p-4 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $statusLabels[$status] }}</p>
                    <p class="mt-2 text-2xl font-bold text-slate-950">{{ number_format($statusCounts[$status] ?? 0) }}</p>
                </a>
            @endforeach
        </div>

        <section class="rounded-lg border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-5 py-4">
                <form method="GET" class="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
                    <div class="grid w-full gap-3 sm:grid-cols-2 lg:grid-cols-[minmax(0,220px)_150px_160px_minmax(0,220px)_auto]">
                        <label class="block">
                            <span class="text-sm font-medium text-slate-700">Search</span>
                            <input name="search"
                                   value="{{ request('search') }}"
                                   placeholder="Name, email, phone"
                                   class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </label>
                        <label class="block">
                            <span class="text-sm font-medium text-slate-700">Status</span>
                            <select name="status"
                                    class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="">All Statuses</option>
                                @foreach($statuses as $status)
                                    <option value="{{ $status }}" @selected(request('status') === $status)>
                                        {{ $statusLabels[$status] }}
                                    </option>
                                @endforeach
                            </select>
                        </label>
                        <label class="block">
                            <span class="text-sm font-medium text-slate-700">Date</span>
                            <input type="date"
                                   name="date"
                                   value="{{ request('date') }}"
                                   class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </label>
                        <label class="block">
                            <span class="text-sm font-medium text-slate-700">Period</span>
                            <select name="period"
                                    class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="">All periods</option>
                                @foreach($periods as $period)
                                    <option value="{{ $period->start_time }}" @selected(request('period') === $period->start_time)>
                                        {{ $period->start_time }} - {{ $period->end_time }}
                                    </option>
                                @endforeach
                            </select>
                        </label>

                        <div class="flex items-end gap-2">
                            <button class="inline-flex h-10 items-center justify-center rounded-md bg-slate-950 px-4 text-sm font-semibold text-white hover:bg-slate-800">
                                Filter
                            </button>
                            @if(request()->hasAny(['status', 'user_id', 'date', 'period', 'search']))
                                <a href="{{ route('admin.bookings.index') }}"
                                   class="inline-flex h-10 items-center justify-center rounded-md border border-slate-300 px-4 text-sm font-semibold text-slate-700 hover:bg-slate-100">
                                    Clear
                                </a>
                            @endif
                        </div>
                    </div>

                    <div class="text-sm text-slate-500">
                        Showing {{ $bookings->firstItem() ?? 0 }}-{{ $bookings->lastItem() ?? 0 }} of {{ $bookings->total() }}
                    </div>
                </form>
            </div>

            <div class="border-b border-slate-200 bg-slate-50 px-5 py-4">
                <form method="POST" action="{{ route('admin.bookings.manual') }}" class="grid gap-3 lg:grid-cols-[minmax(0,260px)_minmax(0,1fr)_auto] lg:items-end">
                    @csrf
                    <label class="block">
                        <span class="text-sm font-medium text-slate-700">Create booking for student</span>
                        <select name="user_id"
                                class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">Choose student</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}">{{ $user->name }} - {{ $user->email }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="block">
                        <span class="text-sm font-medium text-slate-700">Available slot</span>
                        <select name="slot_id"
                                class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">Choose slot</option>
                            @foreach($slots as $slot)
                                <option value="{{ $slot->id }}">
                                    {{ $slot->location?->name }} - {{ $slot->date }} - {{ $slot->start_time }} to {{ $slot->end_time }} ({{ $slot->capacity - $slot->booked_count }} seats)
                                </option>
                            @endforeach
                        </select>
                    </label>
                    <button class="inline-flex h-10 items-center justify-center rounded-md bg-blue-700 px-4 text-sm font-semibold text-white hover:bg-blue-800">
                        Add booking
                    </button>
                </form>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">User</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Branch</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Date</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Time</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Status</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Warning</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Change Status</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse($bookings as $booking)
                            <tr class="hover:bg-slate-50">
                                <td class="whitespace-nowrap px-5 py-4">
                                    <div class="font-semibold text-slate-900">{{ $booking->user?->name ?? 'Deleted user' }}</div>
                                    <div class="text-sm text-slate-500">{{ $booking->user?->email }}</div>
                                </td>

                                <td class="whitespace-nowrap px-5 py-4 text-sm text-slate-700">
                                    {{ $booking->slot?->location?->name ?? 'No branch' }}
                                </td>

                                <td class="whitespace-nowrap px-5 py-4 text-sm text-slate-700">
                                    {{ $booking->slot?->date ?? 'No date' }}
                                </td>

                                <td class="whitespace-nowrap px-5 py-4 text-sm text-slate-700">
                                    {{ $booking->slot?->start_time ?? 'No time' }}
                                    @if($booking->slot?->end_time)
                                        <span class="text-slate-400">-</span>
                                        {{ $booking->slot->end_time }}
                                    @endif
                                </td>

                                <td class="whitespace-nowrap px-5 py-4">
                                    <span data-status-badge
                                          class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset {{ $statusStyles[$booking->status] ?? 'bg-slate-100 text-slate-700 ring-slate-200' }}">
                                        {{ $statusLabels[$booking->status] ?? ucfirst($booking->status) }}
                                    </span>
                                </td>

                                <td class="whitespace-nowrap px-5 py-4 text-sm">
                                    @if($booking->user?->booking_warning_at)
                                        <span class="rounded-full bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-800 ring-1 ring-inset ring-amber-200">
                                            {{ $booking->user->booking_warning_reason }}
                                        </span>
                                    @else
                                        <span class="text-slate-400">None</span>
                                    @endif
                                </td>

                                <td class="min-w-56 px-5 py-4">
                                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                                        <select class="status-select block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:w-44"
                                                data-url="{{ route('admin.bookings.status', $booking) }}">
                                            @foreach($statuses as $status)
                                                <option value="{{ $status }}" @selected($booking->status === $status)>
                                                    {{ $statusLabels[$status] }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <span class="status-message text-xs font-medium text-slate-400"></span>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-5 py-12 text-center text-sm text-slate-500">
                                    No bookings match this filter.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="border-t border-slate-200 px-5 py-4">
                {{ $bookings->links() }}
            </div>
        </section>
        </div>
    </main>
</div>

<script>
const statusLabels = {
    booked: 'Booked',
    completed: 'Completed',
    no_show: 'No Show',
    cancelled: 'Cancelled',
    rescheduled: 'Rescheduled'
};

const statusClasses = {
    booked: 'bg-blue-50 text-blue-700 ring-blue-200',
    completed: 'bg-emerald-50 text-emerald-700 ring-emerald-200',
    no_show: 'bg-amber-50 text-amber-800 ring-amber-200',
    cancelled: 'bg-rose-50 text-rose-700 ring-rose-200',
    rescheduled: 'bg-violet-50 text-violet-700 ring-violet-200'
};

document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.status-select').forEach(select => {
        select.addEventListener('change', function () {
            const row = this.closest('tr');
            const badge = row.querySelector('[data-status-badge]');
            const message = row.querySelector('.status-message');
            const status = this.value;

            this.disabled = true;
            message.textContent = 'Saving...';
            message.className = 'status-message text-xs font-medium text-slate-400';

            fetch(this.dataset.url, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ status })
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Unable to update status.');
                }

                return response.json();
            })
            .then(data => {
                badge.className = `inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset ${statusClasses[data.status] || 'bg-slate-100 text-slate-700 ring-slate-200'}`;
                badge.textContent = statusLabels[data.status] || data.status;
                message.textContent = 'Saved';
                message.className = 'status-message text-xs font-semibold text-emerald-700';
            })
            .catch(() => {
                message.textContent = 'Failed';
                message.className = 'status-message text-xs font-semibold text-rose-700';
            })
            .finally(() => {
                this.disabled = false;
                window.setTimeout(() => {
                    message.textContent = '';
                }, 1800);
            });
        });
    });
});
</script>
@endsection
