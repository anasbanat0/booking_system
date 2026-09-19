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
    $accountNotice = Auth::user()?->currentBookingWarningReason();
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

        @if($accountNotice)
            <div class="mb-6 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                <span class="font-bold">Account notice:</span>
                {{ $accountNotice }}
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
                <p class="mt-2 text-sm font-bold text-stone-950">{{ $accountNotice ?? 'No violations this month' }}</p>
                <p class="mt-1 text-xs text-stone-500">Reschedule cutoff: {{ $remaining['rescheduleCutoffHours'] }} hours</p>
            </div>
        </div>

        <div class="mb-5 flex rounded-lg border border-stone-200 bg-white p-1 shadow-sm">
            <a href="{{ route('bookings.my', ['type' => 'upcoming']) }}"
               class="flex-1 rounded-md px-4 py-2 text-center text-sm font-extrabold {{ $selectedType === 'upcoming' ? 'bg-stone-950 text-white' : 'text-stone-600 hover:bg-stone-100' }}">
                Upcoming
                <span class="ms-1 rounded-full bg-white/15 px-2 py-0.5 text-xs">{{ $upcomingBookingsCount }}</span>
            </a>
            <a href="{{ route('bookings.my', ['type' => 'history']) }}"
               class="flex-1 rounded-md px-4 py-2 text-center text-sm font-extrabold {{ $selectedType === 'history' ? 'bg-stone-950 text-white' : 'text-stone-600 hover:bg-stone-100' }}">
                History
                <span class="ms-1 rounded-full bg-white/15 px-2 py-0.5 text-xs">{{ $historyBookingsCount }}</span>
            </a>
        </div>

        <section class="overflow-hidden rounded-lg border border-stone-200 bg-white shadow-sm">
            @if($visibleBookings->count() === 0)
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
                                <div class="rounded-lg bg-stone-50 p-4"
                                     x-data="{ confirmOpen: false, confirmType: 'reschedule' }"
                                     @keydown.escape.window="confirmOpen = false">
                                    <form x-ref="bookingActionForm" method="POST" action="{{ route('bookings.reschedule', $booking) }}" class="space-y-3">
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
                                            <button type="button" @click="confirmType = 'reschedule'; confirmOpen = true" @disabled(!$canReschedule || $availableSlots->isEmpty())
                                                    class="rounded-md bg-stone-950 px-3 py-2 text-sm font-extrabold text-white hover:bg-stone-800 disabled:cursor-not-allowed disabled:bg-stone-300">
                                                Reschedule
                                            </button>
                                            <button type="button" @click="confirmType = 'cancel'; confirmOpen = true"
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

                                    <template x-teleport="body">
                                        <div x-show="confirmOpen" x-cloak
                                             class="fixed inset-0 z-[100] flex items-center justify-center bg-slate-950/65 p-4 backdrop-blur-sm"
                                             role="dialog" aria-modal="true"
                                             @click.self="confirmOpen = false">
                                            <div x-show="confirmOpen" x-transition
                                                 class="w-full max-w-md rounded-lg bg-white p-6 shadow-2xl">
                                                <p class="text-xs font-extrabold uppercase tracking-wide"
                                                   :class="confirmType === 'cancel' ? 'text-rose-700' : 'text-blue-700'"
                                                   x-text="confirmType === 'cancel' ? 'Confirm cancellation' : 'Confirm reschedule'"></p>
                                                <h3 class="mt-2 text-2xl font-extrabold text-slate-950"
                                                    x-text="confirmType === 'cancel' ? 'Cancel this booking?' : 'Reschedule this booking?'"></h3>
                                                <p class="mt-3 text-sm leading-6 text-slate-600"
                                                   x-text="confirmType === 'cancel' ? 'Your seat will be released. Please confirm that you want to cancel this booking.' : 'Please confirm the new date and time selected before changing your booking.'"></p>

                                                <div class="mt-5 rounded-md border border-slate-200 bg-slate-50 p-4 text-sm">
                                                    <div class="flex justify-between gap-4">
                                                        <span class="font-semibold text-slate-500">Current booking</span>
                                                        <span class="text-right font-extrabold text-slate-950">{{ $booking->slot?->date }} | {{ $booking->slot?->start_time ? substr($booking->slot->start_time, 0, 5) : '--' }} - {{ $booking->slot?->end_time ? substr($booking->slot->end_time, 0, 5) : '--' }}</span>
                                                    </div>
                                                </div>

                                                <div class="mt-6 grid grid-cols-2 gap-3">
                                                    <button type="button" @click="confirmOpen = false"
                                                            class="rounded-md border border-slate-300 px-4 py-3 text-sm font-extrabold text-slate-700 hover:bg-slate-50">
                                                        Go back
                                                    </button>
                                                    <button type="button"
                                                            @click="const form = $refs.bookingActionForm; if (confirmType === 'cancel') form.action = '{{ route('bookings.cancel', $booking) }}'; form.submit()"
                                                            class="rounded-md px-4 py-3 text-sm font-extrabold text-white"
                                                            :class="confirmType === 'cancel' ? 'bg-rose-700 hover:bg-rose-600' : 'bg-blue-700 hover:bg-blue-600'"
                                                            x-text="confirmType === 'cancel' ? 'Confirm cancellation' : 'Confirm reschedule'"></button>
                                                </div>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            @endif
                        </article>
                    @endforeach
                </div>

                @if($visibleBookings->hasPages())
                    <div class="border-t border-stone-200 px-5 py-4">
                        {{ $visibleBookings->links() }}
                    </div>
                @endif
            @endif
        </section>
    </div>
</div>
@endsection
