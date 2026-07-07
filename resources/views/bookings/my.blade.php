@extends('layouts.app')

@section('content')
@php
    $statusStyles = [
        'booked' => 'bg-teal-50 text-teal-700 ring-teal-200',
        'rescheduled' => 'bg-blue-50 text-blue-700 ring-blue-200',
        'completed' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
        'cancelled' => 'bg-rose-50 text-rose-700 ring-rose-200',
        'no_show' => 'bg-amber-50 text-amber-800 ring-amber-200',
    ];

    $activeStatuses = ['booked', 'rescheduled'];
    $selectedType = request('type', 'upcoming');
    $upcomingBookings = $bookings->filter(fn ($booking) => $booking->slot && in_array($booking->status, $activeStatuses, true) && \Carbon\Carbon::parse($booking->slot->date)->endOfDay()->isFuture());
    $historyBookings = $bookings->reject(fn ($booking) => $upcomingBookings->contains('id', $booking->id));
    $visibleBookings = $selectedType === 'history' ? $historyBookings : $upcomingBookings;
@endphp

<div class="min-h-screen bg-[#f6f7f4]">
    <div class="mx-auto max-w-6xl px-4 py-8 sm:px-6 lg:px-8">
        <div class="mb-8 rounded-lg bg-stone-950 p-6 text-white shadow-xl shadow-stone-300/40">
            <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <p class="text-sm font-bold uppercase tracking-wide text-teal-200">Client area</p>
                    <h1 class="mt-3 text-4xl font-extrabold">My bookings</h1>
                    <p class="mt-3 max-w-2xl text-sm leading-6 text-white/70">
                        Review upcoming appointments, cancel anytime, or reschedule at least 12 hours before your booking.
                    </p>
                </div>
                <a href="{{ route('calendar.index') }}"
                   class="inline-flex items-center justify-center rounded-full bg-white px-5 py-3 text-sm font-extrabold text-stone-950 hover:bg-teal-50">
                    Book another slot
                </a>
            </div>
        </div>

        @if(session('success'))
            <div class="mb-6 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="mb-6 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-800">
                {{ session('error') }}
            </div>
        @endif

        @if($errors->any())
            <div class="mb-6 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-800">
                {{ $errors->first() }}
            </div>
        @endif

        @if(Auth::user()?->booking_warning_at)
            <div class="mb-6 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                <span class="font-bold">Account notice:</span>
                {{ Auth::user()->booking_warning_reason }}
            </div>
        @endif

        <div class="mb-6 grid gap-3 sm:grid-cols-3">
            <div class="rounded-lg border border-stone-200 bg-white p-4 shadow-sm">
                <p class="text-xs font-extrabold uppercase tracking-wide text-stone-500">Weekly remaining</p>
                <p class="mt-2 text-3xl font-extrabold text-stone-950">{{ $remaining['weekly'] }}</p>
                <p class="mt-1 text-xs text-stone-500">of {{ $remaining['weeklyLimit'] }} weekly bookings</p>
            </div>
            <div class="rounded-lg border border-stone-200 bg-white p-4 shadow-sm">
                <p class="text-xs font-extrabold uppercase tracking-wide text-stone-500">Monthly remaining</p>
                <p class="mt-2 text-3xl font-extrabold text-stone-950">{{ $remaining['monthly'] }}</p>
                <p class="mt-1 text-xs text-stone-500">of {{ $remaining['monthlyLimit'] }} monthly bookings</p>
            </div>
            <div class="rounded-lg border border-stone-200 bg-white p-4 shadow-sm">
                <p class="text-xs font-extrabold uppercase tracking-wide text-stone-500">Warning status</p>
                <p class="mt-2 text-sm font-bold text-stone-950">{{ Auth::user()?->booking_warning_reason ?? 'No violations' }}</p>
                <p class="mt-1 text-xs text-stone-500">Reschedule cutoff: {{ $remaining['rescheduleCutoffHours'] }} hours</p>
            </div>
        </div>

        <div class="mb-5 flex rounded-lg border border-stone-200 bg-white p-1 shadow-sm">
            <a href="{{ route('bookings.my', ['type' => 'upcoming']) }}"
               class="flex-1 rounded-md px-4 py-2 text-center text-sm font-extrabold {{ $selectedType === 'upcoming' ? 'bg-stone-950 text-white' : 'text-stone-600 hover:bg-stone-100' }}">
                Upcoming
                <span class="ms-1 rounded-full bg-white/15 px-2 py-0.5 text-xs">{{ $upcomingBookings->count() }}</span>
            </a>
            <a href="{{ route('bookings.my', ['type' => 'history']) }}"
               class="flex-1 rounded-md px-4 py-2 text-center text-sm font-extrabold {{ $selectedType === 'history' ? 'bg-stone-950 text-white' : 'text-stone-600 hover:bg-stone-100' }}">
                History
                <span class="ms-1 rounded-full bg-white/15 px-2 py-0.5 text-xs">{{ $historyBookings->count() }}</span>
            </a>
        </div>

        <section class="overflow-hidden rounded-lg border border-stone-200 bg-white shadow-sm">
            @if($visibleBookings->isEmpty())
                <div class="px-6 py-16 text-center">
                    <p class="text-xl font-extrabold text-stone-950">No {{ $selectedType }} bookings</p>
                    <p class="mt-2 text-sm text-stone-500">Your bookings will appear here once you reserve a weekly slot.</p>
                </div>
            @else
                <div class="divide-y divide-stone-100">
                    @foreach($visibleBookings as $booking)
                        @php
                            $slotDateTime = $booking->slot ? \Carbon\Carbon::parse($booking->slot->date . ' ' . $booking->slot->start_time) : null;
                            $canReschedule = in_array($booking->status, $activeStatuses, true) && $slotDateTime && now()->diffInHours($slotDateTime, false) >= 12;
                            $isActive = in_array($booking->status, $activeStatuses, true);
                            $hasAvailableSlot = (bool) $booking->slot;
                        @endphp

                        <article class="grid gap-4 px-5 py-5 lg:grid-cols-[1fr_320px] lg:items-start">
                            <div class="flex gap-4">
                                <div class="flex h-16 w-16 shrink-0 flex-col items-center justify-center rounded-lg bg-stone-100">
                                    <span class="text-xs font-bold uppercase text-stone-500">{{ $booking->slot?->date ? \Carbon\Carbon::parse($booking->slot->date)->format('M') : '--' }}</span>
                                    <span class="text-2xl font-extrabold text-stone-950">{{ $booking->slot?->date ? \Carbon\Carbon::parse($booking->slot->date)->format('d') : '--' }}</span>
                                </div>

                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <h2 class="text-lg font-extrabold text-stone-950">{{ $booking->slot?->location?->name ?? 'No branch' }}</h2>
                                        <span class="rounded-full px-2.5 py-1 text-xs font-bold ring-1 ring-inset {{ $statusStyles[$booking->status] ?? 'bg-stone-100 text-stone-700 ring-stone-200' }}">
                                            {{ strtoupper(str_replace('_', ' ', $booking->status)) }}
                                        </span>
                                    </div>
                                    <p class="mt-2 text-sm font-semibold text-stone-600">
                                        {{ $booking->slot?->date ?? 'No date' }}
                                        <span class="text-stone-300">|</span>
                                        {{ $booking->slot?->start_time ? substr($booking->slot->start_time, 0, 5) : '--' }}
                                        -
                                        {{ $booking->slot?->end_time ? substr($booking->slot->end_time, 0, 5) : '--' }}
                                    </p>
                                    @unless($hasAvailableSlot)
                                        <p class="mt-3 rounded-md bg-amber-50 px-3 py-2 text-sm font-semibold text-amber-800">
                                            This booking is linked to a slot that is no longer available. You can keep it in history or cancel it.
                                        </p>
                                    @endunless
                                    <p class="mt-2 text-sm text-stone-500">Booking #{{ $booking->id }}</p>
                                </div>
                            </div>

                            @if($isActive)
                                <div class="rounded-lg bg-stone-50 p-4">
                                    <form method="POST" action="{{ route('bookings.reschedule', $booking) }}" class="space-y-3">
                                        @csrf
                                        <label class="block">
                                            <span class="text-xs font-extrabold uppercase tracking-wide text-stone-500">Reschedule to</span>
                                            <select name="slot_id" @disabled(!$canReschedule || $availableSlots->isEmpty())
                                                    class="mt-1 block w-full rounded-md border-stone-300 text-sm shadow-sm focus:border-teal-600 focus:ring-teal-600 disabled:cursor-not-allowed disabled:bg-stone-100 disabled:text-stone-400">
                                                @foreach($availableSlots as $slot)
                                                    @if($slot->id !== $booking->slot_id)
                                                        <option value="{{ $slot->id }}">
                                                            {{ $slot->location?->name }} | {{ $slot->date }} | {{ substr($slot->start_time, 0, 5) }} - {{ substr($slot->end_time, 0, 5) }}
                                                        </option>
                                                    @endif
                                                @endforeach
                                            </select>
                                        </label>

                                        <div class="grid grid-cols-2 gap-2">
                                            <button @disabled(!$canReschedule || $availableSlots->isEmpty())
                                                    class="rounded-md bg-stone-950 px-3 py-2 text-sm font-extrabold text-white hover:bg-stone-800 disabled:cursor-not-allowed disabled:bg-stone-300">
                                                Reschedule
                                            </button>
                                            <button formaction="{{ route('bookings.cancel', $booking) }}"
                                                    class="rounded-md border border-rose-300 px-3 py-2 text-sm font-extrabold text-rose-700 hover:bg-rose-50">
                                                Cancel
                                            </button>
                                        </div>
                                    </form>

                                    @if($availableSlots->isEmpty())
                                        <p class="mt-3 text-xs font-medium text-stone-500">There are no available slots to reschedule right now.</p>
                                    @elseif(!$canReschedule)
                                        <p class="mt-3 text-xs font-medium text-stone-500">Rescheduling opens until {{ $remaining['rescheduleCutoffHours'] }} hours before the booking.</p>
                                    @endif
                                </div>
                            @endif
                        </article>
                    @endforeach
                </div>
            @endif
        </section>
    </div>
</div>
@endsection
