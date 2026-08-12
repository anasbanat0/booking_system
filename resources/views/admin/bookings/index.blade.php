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
    $statsRangeQuery = request()->only(['stats_period', 'stats_start_date', 'stats_end_date', 'location_id']);
    $hasStatsRangeFilters = request()->hasAny(['stats_period', 'stats_start_date', 'stats_end_date']);
    $authUser = Auth::user();
@endphp

<style>
    .manual-booking-inline-grid {
        display: grid;
        gap: 0.75rem;
    }

    @media (min-width: 900px) {
        .manual-booking-inline-grid {
            grid-template-columns: minmax(0, 1.15fr) 180px minmax(260px, 1fr) 150px;
            align-items: start;
        }

        .manual-booking-inline-grid > button {
            margin-top: 1.55rem;
        }
    }
</style>

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

        <form method="GET" class="mb-4 grid gap-3 border-y border-slate-200 bg-white/70 px-4 py-4 sm:grid-cols-2 xl:grid-cols-[minmax(330px,1.35fr)_minmax(170px,0.75fr)_minmax(170px,0.75fr)_minmax(210px,0.85fr)] xl:items-end">
            @foreach(request()->except(['page', 'stats_period', 'stats_start_date', 'stats_end_date']) as $key => $value)
                @if(is_array($value))
                    @foreach($value as $item)
                        <input type="hidden" name="{{ $key }}[]" value="{{ $item }}">
                    @endforeach
                @else
                    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                @endif
            @endforeach

            <div>
                <span class="text-xs font-bold uppercase tracking-wide text-slate-500">Stats Range</span>
                <div class="mt-1 grid grid-cols-3 overflow-hidden rounded-md border border-slate-300 bg-white text-sm font-bold">
                    <button type="submit" name="stats_period" value="today" class="px-3 py-2 {{ $statsRangePeriod === 'today' ? 'bg-slate-950 text-white' : 'text-slate-600 hover:bg-slate-50' }}">Today</button>
                    <button type="submit" name="stats_period" value="week" class="border-x border-slate-300 px-3 py-2 {{ $statsRangePeriod === 'week' ? 'bg-slate-950 text-white' : 'text-slate-600 hover:bg-slate-50' }}">Week</button>
                    <button type="submit" name="stats_period" value="month" class="px-3 py-2 {{ $statsRangePeriod === 'month' ? 'bg-slate-950 text-white' : 'text-slate-600 hover:bg-slate-50' }}">Month</button>
                </div>
            </div>
            <label class="block">
                <span class="text-xs font-bold uppercase tracking-wide text-slate-500">From</span>
                <input type="date" name="stats_start_date" value="{{ $statsStartDate->toDateString() }}" class="mt-1 block w-full rounded-md border-slate-300 text-sm">
            </label>
            <label class="block">
                <span class="text-xs font-bold uppercase tracking-wide text-slate-500">To</span>
                <input type="date" name="stats_end_date" value="{{ $statsEndDate->toDateString() }}" class="mt-1 block w-full rounded-md border-slate-300 text-sm">
            </label>
            <div class="flex flex-col gap-2 sm:flex-row">
                <button name="stats_period" value="custom" class="inline-flex h-10 flex-1 items-center justify-center rounded-md bg-slate-950 px-4 text-sm font-bold text-white hover:bg-slate-800">
                    Apply
                </button>
                @if($hasStatsRangeFilters)
                    <a href="{{ route('admin.bookings.index', request()->except(['page', 'stats_period', 'stats_start_date', 'stats_end_date'])) }}" class="inline-flex h-10 flex-1 items-center justify-center rounded-md border border-slate-300 bg-white px-4 text-sm font-bold text-slate-700 hover:bg-slate-100">
                        Clear
                    </a>
                @endif
            </div>
        </form>

        <p class="mb-3 text-sm font-semibold text-slate-500">
            Stats shown for {{ $statsStartDate->format('M d, Y') }} - {{ $statsEndDate->format('M d, Y') }}
        </p>

        <div class="mb-6 grid grid-cols-2 gap-3 md:grid-cols-5">
            @foreach($statuses as $status)
                <a href="{{ route('admin.bookings.index', array_merge(request()->except('page'), $statsRangeQuery, ['status' => $status])) }}"
                   class="rounded-lg border {{ request('status') === $status ? 'border-slate-950 bg-white' : 'border-slate-200 bg-white hover:border-slate-300' }} p-4 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $statusLabels[$status] }}</p>
                    <p class="mt-2 text-2xl font-bold text-slate-950">{{ number_format($statusCounts[$status] ?? 0) }}</p>
                </a>
            @endforeach
        </div>

        <section class="rounded-lg border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-5 py-4">
                <form method="GET" class="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
                    <div class="grid w-full gap-3 sm:grid-cols-2 lg:grid-cols-[minmax(0,210px)_150px_160px_minmax(0,210px)_minmax(0,190px)_auto]">
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

                        @if($authUser && $authUser->canManageAllBranches())
                            <label class="block">
                                <span class="text-sm font-medium text-slate-700">Branch</span>
                                <select name="location_id"
                                        class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <option value="">All branches</option>
                                    @foreach($locations as $location)
                                        <option value="{{ $location->id }}" @selected((int) $selectedLocationId === (int) $location->id)>
                                            {{ $location->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </label>
                        @else
                            <div class="block">
                                <span class="text-sm font-medium text-slate-700">Branch</span>
                                <div class="mt-1 flex h-10 items-center rounded-md border border-slate-200 bg-slate-50 px-3 text-sm font-semibold text-slate-600">
                                    {{ optional($authUser->managedLocation)->name ?? 'Assigned branch' }}
                                </div>
                            </div>
                        @endif

                        <div class="flex items-end gap-2">
                            <button class="inline-flex h-10 items-center justify-center rounded-md bg-slate-950 px-4 text-sm font-semibold text-white hover:bg-slate-800">
                                Filter
                            </button>
                            @if(request()->hasAny(['status', 'user_id', 'date', 'period', 'search', 'location_id']))
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
                <form method="POST" action="{{ route('admin.bookings.manual') }}" data-manual-booking-form class="manual-booking-inline-grid">
                    @csrf
                    <label class="relative block">
                        <span class="text-sm font-medium text-slate-700">Create booking for student</span>
                        <input type="hidden" name="user_id" data-student-search-id>
                        <input type="search"
                               autocomplete="off"
                               placeholder="Search name, phone, email"
                               data-student-search-input
                               class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <div data-student-search-results class="absolute z-30 mt-1 hidden max-h-72 w-full overflow-y-auto rounded-lg border border-slate-200 bg-white shadow-xl"></div>
                        <p data-student-search-selected class="mt-1 hidden truncate text-xs font-bold text-blue-700"></p>
                    </label>
                    <label class="block">
                        <span class="text-sm font-medium text-slate-700">Day</span>
                        <input type="hidden" name="slot_date" data-slot-date-hidden>
                        <input type="date"
                               min="{{ now()->toDateString() }}"
                               data-slot-date-input
                               class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </label>
                    <div class="block">
                        <span class="text-sm font-medium text-slate-700">Period</span>
                        <input type="hidden" name="slot_id" data-slot-id>
                        <input type="hidden" name="slot_template_id" data-slot-template-id>
                        <div data-slot-period-list class="mt-1 flex min-h-10 flex-wrap gap-2">
                            <div class="flex min-h-10 items-center rounded-md border border-dashed border-slate-300 px-3 text-sm font-semibold text-slate-500">
                                Choose a day first.
                            </div>
                        </div>
                        <p data-slot-selected class="mt-1 hidden text-xs font-bold text-blue-700"></p>
                    </div>
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
                                    <div class="font-semibold text-slate-900">{{ optional($booking->user)->name ?? 'Deleted user' }}</div>
                                    <div class="text-sm text-slate-500">{{ optional($booking->user)->email }}</div>
                                </td>

                                <td class="whitespace-nowrap px-5 py-4 text-sm text-slate-700">
                                    {{ optional(optional($booking->slot)->location)->name ?? 'No branch' }}
                                </td>

                                <td class="whitespace-nowrap px-5 py-4 text-sm text-slate-700">
                                    {{ optional($booking->slot)->date ?? 'No date' }}
                                </td>

                                <td class="whitespace-nowrap px-5 py-4 text-sm text-slate-700">
                                    {{ optional($booking->slot)->start_time ?? 'No time' }}
                                    @if(optional($booking->slot)->end_time)
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
                                    @php
                                        $currentWarningReason = $booking->user?->currentBookingWarningReason();
                                    @endphp
                                    @if($currentWarningReason)
                                        <span class="rounded-full bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-800 ring-1 ring-inset ring-amber-200">
                                            {{ $currentWarningReason }}
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

@php
    $manualBookingStudents = $users->map(function ($user) {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'location_id' => $user->booking_location_id,
        ];
    })->values();

    $manualBookingSlots = $slots->map(function ($slot) {
        return [
            'id' => $slot->id,
            'date' => \Carbon\Carbon::parse($slot->date)->toDateString(),
            'start_time' => $slot->start_time,
            'end_time' => $slot->end_time,
            'location' => optional($slot->location)->name,
            'location_id' => $slot->booking_location_id,
            'seats_left' => $slot->capacity - $slot->booked_count,
        ];
    })->values();

    $manualBookingSlotTemplates = $slotTemplates->map(function ($template) {
        return [
            'id' => $template->id,
            'start_time' => $template->start_time,
            'end_time' => $template->end_time,
            'location' => optional($template->location)->name,
            'location_id' => $template->booking_location_id,
            'capacity' => $template->capacity,
        ];
    })->values();

    $manualBookingHolidays = $holidays->map(function ($holiday) {
        return [
            'date' => \Carbon\Carbon::parse($holiday->date)->toDateString(),
            'location_id' => $holiday->booking_location_id,
        ];
    })->values();

    $manualBookingClosedPeriods = $closedPeriods->map(function ($closedPeriod) {
        return [
            'date' => \Carbon\Carbon::parse($closedPeriod->date)->toDateString(),
            'location_id' => $closedPeriod->booking_location_id,
            'start_time' => $closedPeriod->start_time,
            'end_time' => $closedPeriod->end_time,
        ];
    })->values();
@endphp

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

const manualBookingStudents = @json($manualBookingStudents);
const manualBookingSlots = @json($manualBookingSlots);
const manualBookingSlotTemplates = @json($manualBookingSlotTemplates);
const manualBookingHolidays = @json($manualBookingHolidays);
const manualBookingClosedPeriods = @json($manualBookingClosedPeriods);

document.addEventListener('DOMContentLoaded', function () {
    const studentSearchInput = document.querySelector('[data-student-search-input]');
    const studentSearchId = document.querySelector('[data-student-search-id]');
    const studentSearchResults = document.querySelector('[data-student-search-results]');
    const studentSearchSelected = document.querySelector('[data-student-search-selected]');
    const slotDateInput = document.querySelector('[data-slot-date-input]');
    const slotDateHidden = document.querySelector('[data-slot-date-hidden]');
    const slotIdInput = document.querySelector('[data-slot-id]');
    const slotTemplateIdInput = document.querySelector('[data-slot-template-id]');
    const slotPeriodList = document.querySelector('[data-slot-period-list]');
    const slotSelected = document.querySelector('[data-slot-selected]');

    function hideStudentResults() {
        studentSearchResults?.classList.add('hidden');
    }

    function renderStudentResults(query = '') {
        if (!studentSearchResults || !studentSearchInput || !studentSearchId) {
            return;
        }

        const normalizedQuery = query.trim().toLowerCase();
        const matches = manualBookingStudents
            .filter(student => {
                const haystack = `${student.name || ''} ${student.email || ''} ${student.phone || ''}`.toLowerCase();
                return normalizedQuery === '' || haystack.includes(normalizedQuery);
            })
            .slice(0, 18);

        studentSearchResults.innerHTML = '';

        if (matches.length === 0) {
            studentSearchResults.innerHTML = '<div class="px-3 py-3 text-sm font-semibold text-slate-500">No students found.</div>';
            studentSearchResults.classList.remove('hidden');
            return;
        }

        matches.forEach(student => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'block w-full border-b border-slate-100 px-3 py-3 text-left last:border-b-0 hover:bg-blue-50';
            button.innerHTML = `
                <span class="block text-sm font-extrabold text-slate-950">${student.name || 'Unnamed student'}</span>
                <span class="mt-0.5 block text-xs font-semibold text-slate-500">${student.phone || 'No phone'} · ${student.email || 'No email'}</span>
            `;
            button.addEventListener('click', () => {
                studentSearchId.value = student.id;
                studentSearchInput.value = `${student.name || 'Student'}${student.phone ? ' - ' + student.phone : ''}`;
                studentSearchInput.dataset.locationId = student.location_id || '';
                if (studentSearchSelected) {
                    studentSearchSelected.textContent = `Selected: ${student.name || 'Student'}${student.phone ? ' · ' + student.phone : ''}`;
                    studentSearchSelected.className = 'mt-1 truncate text-xs font-bold text-blue-700';
                    studentSearchSelected.classList.remove('hidden');
                }
                hideStudentResults();
                renderSlotPeriods();
            });
            studentSearchResults.appendChild(button);
        });

        studentSearchResults.classList.remove('hidden');
    }

    studentSearchInput?.addEventListener('input', function () {
        studentSearchId.value = '';
        studentSearchInput.dataset.locationId = '';
        slotIdInput.value = '';
        slotTemplateIdInput.value = '';
        slotSelected?.classList.add('hidden');
        studentSearchSelected?.classList.add('hidden');
        renderStudentResults(this.value);
        renderSlotPeriods();
    });

    studentSearchInput?.addEventListener('focus', function () {
        renderStudentResults(this.value);
    });

    document.addEventListener('click', event => {
        if (!studentSearchResults || !studentSearchInput) {
            return;
        }

        if (!studentSearchResults.contains(event.target) && event.target !== studentSearchInput) {
            hideStudentResults();
        }
    });

    document.querySelector('[data-manual-booking-form]')?.addEventListener('submit', event => {
        if (!studentSearchId?.value) {
            event.preventDefault();
            studentSearchInput?.focus();
            renderStudentResults(studentSearchInput?.value || '');
            if (studentSearchSelected) {
                studentSearchSelected.textContent = 'Please choose a student from the search results.';
                studentSearchSelected.classList.remove('hidden');
                studentSearchSelected.className = 'mt-1 truncate text-xs font-bold text-rose-700';
            }
        }

        if (!slotIdInput?.value && !slotTemplateIdInput?.value) {
            event.preventDefault();
            slotDateInput?.focus();
            renderSlotPeriods();
            if (slotSelected) {
                slotSelected.textContent = 'Please choose a day and period.';
                slotSelected.className = 'mt-1 text-xs font-bold text-rose-700';
                slotSelected.classList.remove('hidden');
            }
        }
    });

    function formatSlotTime(slot) {
        return `${String(slot.start_time).slice(0, 5)} - ${String(slot.end_time).slice(0, 5)}`;
    }

    function renderSlotPeriods() {
        if (!slotDateInput || !slotIdInput || !slotPeriodList) {
            return;
        }

        const selectedDate = slotDateInput.value;
        slotIdInput.value = '';
        slotSelected?.classList.add('hidden');
        slotPeriodList.innerHTML = '';

        if (!selectedDate) {
            slotPeriodList.innerHTML = '<div class="flex min-h-10 items-center rounded-md border border-dashed border-slate-300 px-3 text-sm font-semibold text-slate-500">Choose a day first.</div>';
            return;
        }

        const daySlots = manualBookingSlots.filter(slot => slot.date === selectedDate);

        if (daySlots.length === 0) {
            slotPeriodList.innerHTML = '<div class="flex min-h-10 items-center rounded-md border border-dashed border-amber-300 bg-amber-50 px-3 text-sm font-semibold text-amber-800">No periods available for this day.</div>';
            return;
        }

        daySlots.forEach(slot => {
            const button = document.createElement('button');
            const seatsLabel = Number(slot.seats_left) > 0 ? `${slot.seats_left} seats` : 'Full - override';
            button.type = 'button';
            button.className = 'min-h-10 min-w-36 rounded-md border border-slate-300 bg-white px-3 py-2 text-left text-sm font-bold text-slate-800 shadow-sm hover:border-blue-500 hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-blue-500';
            button.innerHTML = `
                <span class="block">${formatSlotTime(slot)}</span>
                <span class="mt-0.5 block text-xs font-semibold text-slate-500">${slot.location || 'Branch'} · ${seatsLabel}</span>
            `;
            button.addEventListener('click', () => {
                slotIdInput.value = slot.id;
                slotPeriodList.querySelectorAll('button').forEach(item => {
                    item.classList.remove('border-blue-700', 'bg-blue-700', 'text-white', 'ring-2', 'ring-blue-200');
                    item.classList.add('border-slate-300', 'bg-white', 'text-slate-800');
                    item.querySelectorAll('span')[1]?.classList.remove('text-blue-100');
                    item.querySelectorAll('span')[1]?.classList.add('text-slate-500');
                });
                button.classList.remove('border-slate-300', 'bg-white', 'text-slate-800');
                button.classList.add('border-blue-700', 'bg-blue-700', 'text-white', 'ring-2', 'ring-blue-200');
                button.querySelectorAll('span')[1]?.classList.remove('text-slate-500');
                button.querySelectorAll('span')[1]?.classList.add('text-blue-100');
                if (slotSelected) {
                    slotSelected.textContent = `Selected: ${slot.location || 'Branch'} · ${selectedDate} · ${formatSlotTime(slot)}`;
                    slotSelected.className = 'mt-1 text-xs font-bold text-blue-700';
                    slotSelected.classList.remove('hidden');
                }
            });
            slotPeriodList.appendChild(button);
        });
    }

    function isFriday(dateValue) {
        return new Date(`${dateValue}T00:00:00`).getDay() === 5;
    }

    function timesOverlap(firstStart, firstEnd, secondStart, secondEnd) {
        return String(firstStart).slice(0, 5) < String(secondEnd).slice(0, 5)
            && String(firstEnd).slice(0, 5) > String(secondStart).slice(0, 5);
    }

    function isTemplateClosedForDay(template, dateValue) {
        const locationId = Number(template.location_id);
        const hasHoliday = manualBookingHolidays.some(holiday => {
            return holiday.date === dateValue
                && (holiday.location_id === null || Number(holiday.location_id) === locationId);
        });

        if (hasHoliday) {
            return true;
        }

        return manualBookingClosedPeriods.some(period => {
            return period.date === dateValue
                && (period.location_id === null || Number(period.location_id) === locationId)
                && timesOverlap(period.start_time, period.end_time, template.start_time, template.end_time);
        });
    }

    renderSlotPeriods = function () {
        if (!slotDateInput || !slotIdInput || !slotTemplateIdInput || !slotPeriodList) {
            return;
        }

        const selectedDate = slotDateInput.value;
        slotIdInput.value = '';
        slotTemplateIdInput.value = '';
        if (slotDateHidden) {
            slotDateHidden.value = selectedDate;
        }
        slotSelected?.classList.add('hidden');
        slotPeriodList.innerHTML = '';

        if (!studentSearchId?.value) {
            slotPeriodList.innerHTML = '<div class="flex min-h-10 items-center rounded-md border border-dashed border-slate-300 px-3 text-sm font-semibold text-slate-500">Choose a student first.</div>';
            return;
        }

        if (!selectedDate) {
            slotPeriodList.innerHTML = '<div class="flex min-h-10 items-center rounded-md border border-dashed border-slate-300 px-3 text-sm font-semibold text-slate-500">Choose a day first.</div>';
            return;
        }

        if (isFriday(selectedDate)) {
            slotPeriodList.innerHTML = '<div class="flex min-h-10 items-center rounded-md border border-dashed border-rose-300 bg-rose-50 px-3 text-sm font-semibold text-rose-700">Friday is closed.</div>';
            return;
        }

        const selectedStudent = manualBookingStudents.find(student => Number(student.id) === Number(studentSearchId.value));
        const selectedLocationId = Number(selectedStudent?.location_id || 0);
        const availableTemplates = manualBookingSlotTemplates
            .filter(template => Number(template.location_id) === selectedLocationId)
            .filter(template => !isTemplateClosedForDay(template, selectedDate));

        if (availableTemplates.length === 0) {
            slotPeriodList.innerHTML = '<div class="flex min-h-10 items-center rounded-md border border-dashed border-amber-300 bg-amber-50 px-3 text-sm font-semibold text-amber-800">No periods available for this day.</div>';
            return;
        }

        availableTemplates.forEach(template => {
            const existingSlot = manualBookingSlots.find(slot => {
                return slot.date === selectedDate
                    && Number(slot.location_id) === Number(template.location_id)
                    && String(slot.start_time).slice(0, 5) === String(template.start_time).slice(0, 5)
                    && String(slot.end_time).slice(0, 5) === String(template.end_time).slice(0, 5);
            });
            const period = existingSlot || template;
            const button = document.createElement('button');
            const seatsLeft = existingSlot ? Number(existingSlot.seats_left) : Number(template.capacity);
            const seatsLabel = seatsLeft > 0 ? `${seatsLeft} seats` : 'Full - override';
            button.type = 'button';
            button.className = 'min-h-10 min-w-36 rounded-md border border-slate-300 bg-white px-3 py-2 text-left text-sm font-bold text-slate-800 shadow-sm hover:border-blue-500 hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-blue-500';
            button.innerHTML = `
                <span class="block">${formatSlotTime(period)}</span>
                <span class="mt-0.5 block text-xs font-semibold text-slate-500">${period.location || 'Branch'} - ${seatsLabel}</span>
            `;
            button.addEventListener('click', () => {
                slotIdInput.value = existingSlot?.id || '';
                slotTemplateIdInput.value = template.id;
                slotPeriodList.querySelectorAll('button').forEach(item => {
                    item.classList.remove('border-blue-700', 'bg-blue-700', 'text-white', 'ring-2', 'ring-blue-200');
                    item.classList.add('border-slate-300', 'bg-white', 'text-slate-800');
                    item.querySelectorAll('span')[1]?.classList.remove('text-blue-100');
                    item.querySelectorAll('span')[1]?.classList.add('text-slate-500');
                });
                button.classList.remove('border-slate-300', 'bg-white', 'text-slate-800');
                button.classList.add('border-blue-700', 'bg-blue-700', 'text-white', 'ring-2', 'ring-blue-200');
                button.querySelectorAll('span')[1]?.classList.remove('text-slate-500');
                button.querySelectorAll('span')[1]?.classList.add('text-blue-100');
                if (slotSelected) {
                    slotSelected.textContent = `Selected: ${period.location || 'Branch'} - ${selectedDate} - ${formatSlotTime(period)}`;
                    slotSelected.className = 'mt-1 text-xs font-bold text-blue-700';
                    slotSelected.classList.remove('hidden');
                }
            });
            slotPeriodList.appendChild(button);
        });
    };

    slotDateInput?.addEventListener('change', renderSlotPeriods);

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
