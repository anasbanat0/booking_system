@extends('layouts.app')

@section('content')
@php
    $statusStyles = [
        'booked' => 'bg-sky-100 text-sky-800 ring-sky-200',
        'rescheduled' => 'bg-violet-100 text-violet-800 ring-violet-200',
        'completed' => 'bg-emerald-100 text-emerald-800 ring-emerald-200',
        'cancelled' => 'bg-rose-100 text-rose-800 ring-rose-200',
        'no_show' => 'bg-amber-100 text-amber-900 ring-amber-200',
    ];

    $periodLabels = [
        1 => 'First period',
        2 => 'Second period',
        3 => 'Third period',
    ];
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
                    <a href="{{ route('admin.users-calendar.index', ['week' => $previousWeek, 'view' => 'week', 'location_id' => $selectedLocationId]) }}"
                       class="rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-bold text-slate-700 hover:bg-slate-100">
                        Previous
                    </a>
                    <a href="{{ route('admin.users-calendar.index', ['location_id' => $selectedLocationId]) }}"
                       class="rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-bold text-slate-700 hover:bg-slate-100">
                        This week
                    </a>
                    <a href="{{ route('admin.users-calendar.index', ['week' => $nextWeek, 'view' => 'week', 'location_id' => $selectedLocationId]) }}"
                       class="rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-bold text-slate-700 hover:bg-slate-100">
                        Next
                    </a>
                </div>
            </div>

            @if(session('success'))
                <div class="mb-5 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="mb-5 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-800">
                    {{ session('error') }}
                </div>
            @endif

            @if($errors->any())
                <div class="mb-5 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-800">
                    {{ $errors->first() }}
                </div>
            @endif

            <div class="mb-5 rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                <form method="GET" class="grid gap-3 md:grid-cols-[140px_180px_1fr_auto] md:items-end">
                    <label class="block">
                        <span class="text-sm font-semibold text-slate-700">View</span>
                        <select name="view" class="mt-1 block w-full rounded-md border-slate-300 text-sm">
                            <option value="week" @selected($view === 'week')>Weekly</option>
                            <option value="day" @selected($view === 'day')>Daily</option>
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
                                    <option value="{{ $location->id }}" @selected($selectedLocationId === $location->id)>{{ $location->name }}</option>
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
                            <p class="text-xs font-bold uppercase tracking-wide text-slate-500">{{ str_replace('_', ' ', $status) }}</p>
                            <p class="mt-2 text-2xl font-extrabold text-slate-950">{{ number_format($statusCounts[$status] ?? 0) }}</p>
                        </div>
                    @endforeach
                </div>

            </div>

            <section class="overflow-x-auto rounded-lg border border-slate-200 bg-white shadow-sm">
                <div class="{{ $view === 'day' ? 'min-w-[760px]' : 'min-w-[1180px]' }}">
                    <div class="grid {{ $view === 'day' ? 'grid-cols-[170px_minmax(0,1fr)]' : 'grid-cols-[170px_repeat(7,minmax(0,1fr))]' }} border-b border-slate-200 bg-slate-100">
                        <div class="px-4 py-4 text-sm font-extrabold text-slate-700">
                            {{ $weekStart->format('M d') }} - {{ $weekStart->copy()->endOfWeek()->format('M d, Y') }}
                        </div>
                        @foreach($days as $day)
                            <div class="border-s border-slate-200 px-4 py-4 text-center">
                                <p class="text-xs font-bold uppercase tracking-wide text-slate-500">{{ $day->format('D') }}</p>
                                <p class="mt-1 text-xl font-extrabold text-slate-950">{{ $day->format('d') }}</p>
                            </div>
                        @endforeach
                    </div>

                    @foreach($periods as $period)
                        <div class="grid min-h-44 {{ $view === 'day' ? 'grid-cols-[170px_minmax(0,1fr)]' : 'grid-cols-[170px_repeat(7,minmax(0,1fr))]' }} border-b border-slate-200 last:border-b-0">
                            <div class="bg-slate-50 px-4 py-4">
                                <p class="text-sm font-extrabold text-slate-950">{{ $periodLabels[$period] }}</p>
                                <p class="mt-1 text-xs text-slate-500">Period {{ $period }}</p>
                            </div>

                            @foreach($days as $day)
                                @php
                                    $cellSlots = $slotsByDayPeriod->get($day->toDateString() . '|' . $period, collect());
                                @endphp

                                <div class="border-s border-slate-200 p-3">
                                    @forelse($cellSlots as $slot)
                                        <div class="mb-3 last:mb-0">
                                            <div class="mb-2 flex items-center justify-between gap-2">
                                                <p class="truncate text-xs font-extrabold text-slate-600">{{ $slot->location?->name }}</p>
                                                <p class="shrink-0 text-[11px] font-bold text-slate-400">
                                                    {{ substr($slot->start_time, 0, 5) }}-{{ substr($slot->end_time, 0, 5) }}
                                                </p>
                                            </div>

                                            <div class="flex flex-wrap content-start gap-1.5">
                                                @forelse($slot->bookings as $booking)
                                                    <button type="button"
                                                            x-data
                                                            @click="$dispatch('open-booking-modal', { id: 'booking-{{ $booking->id }}' })"
                                                            class="max-w-full rounded-md px-2.5 py-1 text-left text-xs font-bold ring-1 ring-inset transition hover:scale-[1.02] {{ $statusStyles[$booking->status] ?? 'bg-slate-100 text-slate-700 ring-slate-200' }}">
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
        </div>
    </main>
</div>
@endsection
