@extends('layouts.app')

@section('content')
@php
    $statusStyles = [
        'booked' => 'background-color: #16a34a; color: #ffffff; border-color: #15803d;',
        'rescheduled' => 'background-color: #7c3aed; color: #ffffff; border-color: #6d28d9;',
        'completed' => 'background-color: #2563eb; color: #ffffff; border-color: #1d4ed8;',
        'cancelled' => 'background-color: #dc2626; color: #ffffff; border-color: #b91c1c;',
        'no_show' => 'background-color: #facc15; color: #111827; border-color: #eab308;',
    ];

    $periodLabels = [
        1 => 'First period',
        2 => 'Second period',
        3 => 'Third period',
    ];
    $gridColumns = $view === 'day'
        ? '170px minmax(0, 1fr)'
        : '170px repeat(' . $days->count() . ', minmax(150px, 1fr))';
    $minWidth = $view === 'day'
        ? '760px'
        : (170 + ($days->count() * 150)) . 'px';
@endphp

<div class="min-h-screen bg-slate-50 lg:flex">
    @include('admin.partials.sidebar')

    <main class="min-w-0 flex-1">
        <div class="mx-auto max-w-[1600px] px-4 py-8 sm:px-6 lg:px-8">
            @include('admin.partials.topbar')

            <div class="mb-6 flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-wide text-blue-700">SimplyBook Style Calendar</p>
                    <h1 class="mt-2 text-3xl font-bold text-slate-950">Users Calendar</h1>
                    <p class="mt-2 max-w-3xl text-sm text-slate-600">
                        Manage students by day and time period. Click any student card to update profile details and booking status.
                    </p>
                </div>

                <div class="flex flex-col gap-2 sm:flex-row">
                    <a href="{{ route('admin.users-calendar.index', ['date' => $previousDate, 'view' => $view, 'location_id' => $selectedLocationId]) }}"
                       class="rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-bold text-slate-700 hover:bg-slate-100">
                        Previous
                    </a>
                    <a href="{{ route('admin.users-calendar.index', ['view' => $view, 'location_id' => $selectedLocationId]) }}"
                       class="rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-bold text-slate-700 hover:bg-slate-100">
                        Today
                    </a>
                    <a href="{{ route('admin.users-calendar.index', ['date' => $nextDate, 'view' => $view, 'location_id' => $selectedLocationId]) }}"
                       class="rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-bold text-slate-700 hover:bg-slate-100">
                        Next
                    </a>
                </div>
            </div>

            <div class="mb-5 rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                <form method="GET" class="grid gap-3 md:grid-cols-[140px_180px_1fr_auto] md:items-end">
                    <label class="block">
                        <span class="text-sm font-semibold text-slate-700">View</span>
                        <select name="view" class="mt-1 block w-full rounded-md border-slate-300 text-sm">
                            <option value="week" @selected($view === 'week')>Weekly</option>
                            <option value="day" @selected($view === 'day')>Daily</option>
                            <option value="month" @selected($view === 'month')>Monthly</option>
                        </select>
                    </label>
                    <label class="block">
                        <span class="text-sm font-semibold text-slate-700">Date</span>
                        <input type="date" name="date" value="{{ $selectedDate->toDateString() }}" class="mt-1 block w-full rounded-md border-slate-300 text-sm">
                    </label>
                    @if(Auth::user()?->canManageAllBranches())
                        <label class="block">
                            <span class="text-sm font-semibold text-slate-700">Branch</span>
                            <select name="location_id" class="mt-1 block w-full rounded-md border-slate-300 text-sm">
                                <option value="">All branches</option>
                                @foreach($locations as $location)
                                    <option value="{{ $location->id }}" @selected((int) $selectedLocationId === (int) $location->id)>{{ $location->name }}</option>
                                @endforeach
                            </select>
                        </label>
                    @else
                        <div class="rounded-md bg-slate-50 px-3 py-2 text-sm font-bold text-slate-600">
                            Branch: {{ Auth::user()?->managedLocation?->name ?? 'Not assigned' }}
                        </div>
                    @endif
                    <button class="rounded-md bg-slate-950 px-4 py-2 text-sm font-bold text-white hover:bg-slate-800">Apply</button>
                </form>
            </div>

            <div class="mb-6">
                <div class="grid grid-cols-2 gap-3 md:grid-cols-5">
                    @foreach(['booked', 'rescheduled', 'completed', 'cancelled', 'no_show'] as $status)
                        <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                            <div class="flex items-center gap-2">
                                <span class="h-3 w-3 rounded-full border" style="{{ $statusStyles[$status] }}"></span>
                                <p class="text-xs font-bold uppercase tracking-wide text-slate-500">{{ str_replace('_', ' ', $status) }}</p>
                            </div>
                            <p class="mt-2 text-2xl font-extrabold text-slate-950">{{ number_format($statusCounts[$status] ?? 0) }}</p>
                        </div>
                    @endforeach
                </div>

            </div>

            @if(false && $view === 'month')
            <section class="overflow-x-auto rounded-lg border border-slate-200 bg-white shadow-sm">
                <div class="min-w-[1180px]">
                    <div class="grid grid-cols-7 border-b border-slate-200 bg-slate-100">
                        @foreach(['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'] as $weekday)
                            <div class="border-s border-slate-200 px-4 py-3 first:border-s-0">
                                <p class="text-center text-xs font-extrabold uppercase tracking-wide text-slate-500">{{ $weekday }}</p>
                            </div>
                        @endforeach
                    </div>

                    @foreach($calendarWeeks as $week)
                        <div class="grid min-h-48 grid-cols-7 border-b border-slate-200 last:border-b-0">
                            @foreach($week as $day)
                                @php
                                    $daySlots = $slotsByDay->get($day->toDateString(), collect());
                                    $isCurrentMonth = $day->isSameMonth($selectedDate);
                                @endphp
                                <div class="border-s border-slate-200 p-3 first:border-s-0 {{ $isCurrentMonth ? 'bg-white' : 'bg-slate-50' }}">
                                    <div class="mb-2 flex items-center justify-between gap-2">
                                        <p class="text-sm font-extrabold {{ $day->isToday() ? 'text-blue-700' : ($isCurrentMonth ? 'text-slate-950' : 'text-slate-400') }}">
                                            {{ $day->format('d') }}
                                        </p>
                                        <p class="text-[11px] font-bold text-slate-400">{{ $daySlots->sum(fn ($slot) => $slot->bookings->count()) }} bookings</p>
                                    </div>

                                    <div class="space-y-2">
                                        @forelse($daySlots as $slot)
                                            @if($slot->bookings->isNotEmpty())
                                                <div>
                                                    <div class="mb-1 flex items-center justify-between gap-2 text-[11px] font-bold text-slate-500">
                                                        <span class="truncate">{{ $slot->location?->name }}</span>
                                                        <span class="shrink-0">{{ substr($slot->start_time, 0, 5) }}</span>
                                                    </div>
                                                    <div class="flex flex-wrap gap-1">
                                                        @foreach($slot->bookings as $booking)
                                                            <button type="button"
                                                                    x-data
                                                                    @click="$dispatch('open-booking-modal', { id: 'booking-{{ $booking->id }}' })"
                                                                    class="max-w-full rounded-md px-2 py-1 text-left text-[11px] font-bold ring-1 ring-inset {{ $statusStyles[$booking->status] ?? 'bg-slate-600 text-white ring-slate-700' }}">
                                                                <span class="block max-w-28 truncate">{{ $booking->user?->name ?? 'Deleted user' }}</span>
                                                            </button>

                                                            <div x-data="{ open: false }"
                                                                 x-on:open-booking-modal.window="open = ($event.detail.id === 'booking-{{ $booking->id }}')"
                                                                 x-show="open"
                                                                 x-cloak
                                                                 class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/55 px-4">
                                                                <div @click.outside="open = false" class="w-full max-w-xl rounded-lg bg-white shadow-2xl">
                                                                    <form method="POST" action="{{ route('admin.users-calendar.bookings.update', $booking) }}">
                                                                        @csrf
                                                                        @method('PATCH')

                                                                        <div class="border-b border-slate-200 px-5 py-4">
                                                                            <div class="flex items-start justify-between gap-3">
                                                                                <div>
                                                                                    <p class="text-sm font-bold uppercase tracking-wide text-blue-700">Booking details</p>
                                                                                    <h2 class="mt-1 text-2xl font-extrabold text-slate-950">{{ $booking->user?->name }}</h2>
                                                                                    <p class="mt-1 text-sm text-slate-500">
                                                                                        {{ $slot->location?->name }} | {{ $slot->date }} | {{ substr($slot->start_time, 0, 5) }} - {{ substr($slot->end_time, 0, 5) }}
                                                                                    </p>
                                                                                </div>
                                                                                <button type="button" @click="open = false" class="rounded-md p-2 text-slate-400 hover:bg-slate-100 hover:text-slate-700">
                                                                                    X
                                                                                </button>
                                                                            </div>
                                                                        </div>

                                                                        <div class="grid gap-4 p-5 sm:grid-cols-2">
                                                                            <label class="block">
                                                                                <span class="text-sm font-semibold text-slate-700">Name</span>
                                                                                <input name="name" value="{{ $booking->user?->name }}"
                                                                                       class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                                                            </label>

                                                                            <label class="block">
                                                                                <span class="text-sm font-semibold text-slate-700">Email</span>
                                                                                <input type="email" name="email" value="{{ $booking->user?->email }}"
                                                                                       class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                                                            </label>

                                                                            <label class="block">
                                                                                <span class="text-sm font-semibold text-slate-700">Phone</span>
                                                                                <input name="phone" value="{{ $booking->user?->phone }}"
                                                                                       class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                                                            </label>

                                                                            <label class="block">
                                                                                <span class="text-sm font-semibold text-slate-700">Status / Attendance</span>
                                                                                <select name="status" class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                                                                    @foreach($statuses as $status)
                                                                                        <option value="{{ $status }}" @selected($booking->status === $status)>
                                                                                            {{ ucwords(str_replace('_', ' ', $status)) }}
                                                                                        </option>
                                                                                    @endforeach
                                                                                </select>
                                                                            </label>
                                                                        </div>

                                                                        <div class="flex justify-end gap-2 border-t border-slate-200 px-5 py-4">
                                                                            <button type="button" @click="open = false" class="rounded-md border border-slate-300 px-4 py-2 text-sm font-bold text-slate-700 hover:bg-slate-100">
                                                                                Close
                                                                            </button>
                                                                            <button class="rounded-md bg-slate-950 px-4 py-2 text-sm font-bold text-white hover:bg-slate-800">
                                                                                Save changes
                                                                            </button>
                                                                        </div>
                                                                    </form>
                                                                </div>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            @endif
                                        @empty
                                            <div class="flex min-h-24 items-center justify-center rounded-md border border-dashed border-slate-200 text-xs font-semibold text-slate-400">
                                                No slot
                                            </div>
                                        @endforelse
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endforeach
                </div>
            </section>
            @else
            <section class="overflow-x-auto rounded-lg border border-slate-200 bg-white shadow-sm">
                <div style="min-width: {{ $minWidth }};">
                    <div class="grid border-b border-slate-200 bg-slate-100" style="grid-template-columns: {{ $gridColumns }};">
                        <div class="px-4 py-4 text-sm font-extrabold text-slate-700">
                            {{ $view === 'day' ? $selectedDate->format('M d, Y') : $days->first()->format('M d') . ' - ' . $rangeEnd->format('M d, Y') }}
                        </div>
                        @foreach($days as $day)
                            <div class="border-s border-slate-200 px-4 py-4 text-center">
                                <p class="text-xs font-bold uppercase tracking-wide text-slate-500">{{ $day->format('D') }}</p>
                                <p class="mt-1 text-xl font-extrabold text-slate-950">{{ $day->format('d') }}</p>
                            </div>
                        @endforeach
                    </div>

                    @foreach($periods as $period)
                        <div class="grid min-h-44 border-b border-slate-200 last:border-b-0" style="grid-template-columns: {{ $gridColumns }};">
                            <div class="bg-slate-50 px-4 py-4">
                                <p class="text-sm font-extrabold text-slate-950">{{ $periodLabels[$period] ?? 'Period ' . $period }}</p>
                                <p class="mt-1 text-xs text-slate-500">Period {{ $period }}</p>
                            </div>

                            @foreach($days as $day)
                                @php
                                    $cellSlots = $slotsByDayPeriod->get($day->toDateString() . '|' . $period, collect());
                                @endphp

                                <div class="border-s border-slate-200 p-3">
                                    @forelse($cellSlots as $slot)
                                        <div x-data="{ manualOpen: false }"
                                             @click="manualOpen = true"
                                             class="mb-3 cursor-pointer rounded-lg border border-transparent p-2 transition hover:border-blue-200 hover:bg-blue-50/60 last:mb-0">
                                            <div class="mb-2 flex items-center justify-between gap-2">
                                                <div class="min-w-0">
                                                    <p class="truncate text-xs font-extrabold text-slate-600">{{ $slot->location?->name }}</p>
                                                    <p class="mt-0.5 text-[11px] font-bold text-slate-400">{{ $slot->capacity - $slot->booked_count }} seats left</p>
                                                </div>
                                                <div class="shrink-0 text-right">
                                                    <p class="text-[11px] font-bold text-slate-400">
                                                        {{ substr($slot->start_time, 0, 5) }}-{{ substr($slot->end_time, 0, 5) }}
                                                    </p>
                                                    <button type="button"
                                                            @click.stop="manualOpen = ! manualOpen"
                                                            class="mt-1 rounded-md bg-blue-600 px-2 py-1 text-[11px] font-extrabold text-white shadow-sm hover:bg-blue-700">
                                                        Add booking
                                                    </button>
                                                </div>
                                            </div>

                                            <div class="flex flex-wrap content-start gap-1.5">
                                                @forelse($slot->bookings as $booking)
                                                    <button type="button"
                                                            x-data
                                                            @click="$dispatch('open-booking-modal', { id: 'booking-{{ $booking->id }}' })"
                                                            class="max-w-full rounded-md border px-2.5 py-1 text-left text-xs font-bold transition hover:scale-[1.02]"
                                                            style="{{ $statusStyles[$booking->status] ?? 'background-color: #475569; color: #ffffff; border-color: #334155;' }}">
                                                        <span class="block truncate">{{ $booking->user?->name ?? 'Deleted user' }}</span>
                                                    </button>

                                                    <div x-data="{ open: false }"
                                                         x-on:open-booking-modal.window="open = ($event.detail.id === 'booking-{{ $booking->id }}')"
                                                         x-show="open"
                                                         x-cloak
                                                         class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/55 px-4">
                                                        <div @click.outside="open = false" class="w-full max-w-xl rounded-lg bg-white shadow-2xl">
                                                            <form method="POST" action="{{ route('admin.users-calendar.bookings.update', $booking) }}">
                                                                @csrf
                                                                @method('PATCH')

                                                                <div class="border-b border-slate-200 px-5 py-4">
                                                                    <div class="flex items-start justify-between gap-3">
                                                                        <div>
                                                                            <p class="text-sm font-bold uppercase tracking-wide text-blue-700">Booking details</p>
                                                                            <h2 class="mt-1 text-2xl font-extrabold text-slate-950">{{ $booking->user?->name }}</h2>
                                                                            <p class="mt-1 text-sm text-slate-500">
                                                                                {{ $slot->location?->name }} | {{ $slot->date }} | {{ substr($slot->start_time, 0, 5) }} - {{ substr($slot->end_time, 0, 5) }}
                                                                            </p>
                                                                        </div>
                                                                        <button type="button" @click="open = false" class="rounded-md p-2 text-slate-400 hover:bg-slate-100 hover:text-slate-700">
                                                                            X
                                                                        </button>
                                                                    </div>
                                                                </div>

                                                                <div class="grid gap-4 p-5 sm:grid-cols-2">
                                                                    <label class="block">
                                                                        <span class="text-sm font-semibold text-slate-700">Name</span>
                                                                        <input name="name" value="{{ $booking->user?->name }}"
                                                                               class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                                                    </label>

                                                                    <label class="block">
                                                                        <span class="text-sm font-semibold text-slate-700">Email</span>
                                                                        <input type="email" name="email" value="{{ $booking->user?->email }}"
                                                                               class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                                                    </label>

                                                                    <label class="block">
                                                                        <span class="text-sm font-semibold text-slate-700">Phone</span>
                                                                        <input name="phone" value="{{ $booking->user?->phone }}"
                                                                               class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                                                    </label>

                                                                    <label class="block">
                                                                        <span class="text-sm font-semibold text-slate-700">Status / Attendance</span>
                                                                        <select name="status" class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                                                            @foreach($statuses as $status)
                                                                                <option value="{{ $status }}" @selected($booking->status === $status)>
                                                                                    {{ ucwords(str_replace('_', ' ', $status)) }}
                                                                                </option>
                                                                            @endforeach
                                                                        </select>
                                                                    </label>
                                                                </div>

                                                                <div class="flex justify-end gap-2 border-t border-slate-200 px-5 py-4">
                                                                    <button type="button" @click="open = false" class="rounded-md border border-slate-300 px-4 py-2 text-sm font-bold text-slate-700 hover:bg-slate-100">
                                                                        Close
                                                                    </button>
                                                                    <button class="rounded-md bg-slate-950 px-4 py-2 text-sm font-bold text-white hover:bg-slate-800">
                                                                        Save changes
                                                                    </button>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>
                                                @empty
                                                    <span class="rounded-md border border-dashed border-slate-200 px-2 py-1 text-xs font-semibold text-slate-400">
                                                        Empty
                                                    </span>
                                                @endforelse
                                            </div>

                                            <form x-show="manualOpen"
                                                  x-cloak
                                                  @click.stop
                                                  method="POST"
                                                  action="{{ route('admin.users-calendar.slots.bookings.store', $slot) }}"
                                                  class="mt-3 rounded-lg border border-blue-200 bg-white p-3 shadow-sm">
                                                @csrf
                                                <input type="hidden" name="user_id" data-calendar-student-id>

                                                <div class="relative">
                                                    <label class="block text-[11px] font-extrabold uppercase tracking-wide text-slate-500">
                                                        Student
                                                    </label>
                                                    <input type="text"
                                                           autocomplete="off"
                                                           data-calendar-student-search-input
                                                           placeholder="Search name, phone, email"
                                                           class="mt-1 block w-full rounded-md border-slate-300 text-xs shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                                    <div data-calendar-student-search-results class="absolute z-30 mt-1 hidden max-h-56 w-full overflow-y-auto rounded-lg border border-slate-200 bg-white shadow-xl"></div>
                                                    <p data-calendar-student-search-selected class="mt-1 hidden truncate text-[11px] font-bold text-blue-700"></p>
                                                </div>

                                                <div class="mt-3 flex items-center gap-2">
                                                    <button class="flex-1 rounded-md bg-slate-950 px-3 py-2 text-xs font-extrabold text-white hover:bg-slate-800">
                                                        Confirm booking
                                                    </button>
                                                    <button type="button"
                                                            @click="manualOpen = false"
                                                            class="rounded-md border border-slate-300 px-3 py-2 text-xs font-bold text-slate-600 hover:bg-slate-100">
                                                        Close
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    @empty
                                        <div class="flex h-full min-h-28 items-center justify-center rounded-md border border-dashed border-slate-200 text-xs font-semibold text-slate-400">
                                            No slot
                                        </div>
                                    @endforelse
                                </div>
                            @endforeach
                        </div>
                    @endforeach
                </div>
            </section>
            @endif
        </div>
    </main>
</div>

@php
    $calendarManualBookingStudents = $users->map(function ($user) {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
        ];
    })->values();
@endphp

<script>
const calendarManualBookingStudents = @json($calendarManualBookingStudents);

document.addEventListener('DOMContentLoaded', function () {
    function closeCalendarStudentResults(scope) {
        scope.querySelector('[data-calendar-student-search-results]')?.classList.add('hidden');
    }

    function renderCalendarStudentResults(scope, query = '') {
        const input = scope.querySelector('[data-calendar-student-search-input]');
        const idInput = scope.querySelector('[data-calendar-student-id]');
        const results = scope.querySelector('[data-calendar-student-search-results]');

        if (!input || !idInput || !results) {
            return;
        }

        const normalizedQuery = query.trim().toLowerCase();
        const matches = calendarManualBookingStudents
            .filter(student => {
                const haystack = `${student.name || ''} ${student.email || ''} ${student.phone || ''}`.toLowerCase();
                return normalizedQuery === '' || haystack.includes(normalizedQuery);
            })
            .slice(0, 12);

        results.innerHTML = '';

        if (matches.length === 0) {
            results.innerHTML = '<div class="px-3 py-3 text-xs font-semibold text-slate-500">No students found.</div>';
            results.classList.remove('hidden');
            return;
        }

        matches.forEach(student => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'block w-full border-b border-slate-100 px-3 py-2 text-left last:border-b-0 hover:bg-blue-50';
            button.innerHTML = `
                <span class="block text-xs font-extrabold text-slate-950">${student.name || 'Unnamed student'}</span>
                <span class="mt-0.5 block text-[11px] font-semibold text-slate-500">${student.phone || 'No phone'} · ${student.email || 'No email'}</span>
            `;
            button.addEventListener('click', function () {
                const selected = scope.querySelector('[data-calendar-student-search-selected]');
                idInput.value = student.id;
                input.value = `${student.name || 'Student'}${student.phone ? ' - ' + student.phone : ''}`;

                if (selected) {
                    selected.textContent = `Selected: ${student.name || 'Student'}${student.phone ? ' · ' + student.phone : ''}`;
                    selected.className = 'mt-1 truncate text-[11px] font-bold text-blue-700';
                    selected.classList.remove('hidden');
                }

                closeCalendarStudentResults(scope);
            });
            results.appendChild(button);
        });

        results.classList.remove('hidden');
    }

    document.querySelectorAll('[data-calendar-student-search-input]').forEach(input => {
        const form = input.closest('form');
        const idInput = form?.querySelector('[data-calendar-student-id]');
        const selected = form?.querySelector('[data-calendar-student-search-selected]');

        input.addEventListener('input', function () {
            if (idInput) {
                idInput.value = '';
            }

            selected?.classList.add('hidden');
            renderCalendarStudentResults(form, this.value);
        });

        input.addEventListener('focus', function () {
            renderCalendarStudentResults(form, this.value);
        });
    });

    document.addEventListener('click', event => {
        document.querySelectorAll('[data-calendar-student-search-results]').forEach(results => {
            const form = results.closest('form');
            const input = form?.querySelector('[data-calendar-student-search-input]');

            if (form && input && !results.contains(event.target) && event.target !== input) {
                closeCalendarStudentResults(form);
            }
        });
    });

    document.querySelectorAll('form[action*="/admin/users-calendar/slots/"]').forEach(form => {
        form.addEventListener('submit', event => {
            const idInput = form.querySelector('[data-calendar-student-id]');
            const input = form.querySelector('[data-calendar-student-search-input]');
            const selected = form.querySelector('[data-calendar-student-search-selected]');

            if (!idInput?.value) {
                event.preventDefault();
                input?.focus();
                renderCalendarStudentResults(form, input?.value || '');

                if (selected) {
                    selected.textContent = 'Please choose a student from the search results.';
                    selected.className = 'mt-1 truncate text-[11px] font-bold text-rose-700';
                    selected.classList.remove('hidden');
                }
            }
        });
    });
});
</script>
@endsection
