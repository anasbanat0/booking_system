@extends('layouts.app')

@section('content')
@php
    $accountNotice = Auth::user()?->currentBookingWarningReason();
@endphp

<div class="min-h-screen bg-slate-50">
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <div class="mb-8 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-sm font-semibold uppercase tracking-wide text-blue-700">Student Booking</p>
                <h1 class="mt-2 text-3xl font-bold text-slate-950">Weekly Calendar</h1>
                <p class="mt-2 max-w-2xl text-sm text-slate-600">
                    Choose one available slot from the weekly calendar. Booking limits and seat capacity are checked automatically.
                </p>
            </div>

            <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                <div class="rounded-md border border-slate-200 bg-white px-4 py-2 text-sm font-extrabold text-slate-800">
                    {{ $periodLabel }}
                </div>
                <div class="flex gap-2">
                    <a href="{{ route('calendar.index', ['period' => $previousPeriod]) }}"
                       class="inline-flex items-center justify-center rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100">
                        Previous
                    </a>
                    <a href="{{ route('calendar.index', ['period' => $nextPeriod]) }}"
                       class="inline-flex items-center justify-center rounded-md bg-slate-950 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">
                        Next
                    </a>
                    <a href="{{ route('bookings.my') }}"
                       class="inline-flex items-center justify-center rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100">
                        My Bookings
                    </a>
                </div>
            </div>
        </div>

        @if($accountNotice)
            <div class="mb-6 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                <span class="font-bold">Account notice:</span>
                {{ $accountNotice }}
            </div>
        @endif

        <div class="grid grid-cols-1 gap-4 lg:grid-cols-7">
            @foreach($weekDays as $day)
                <section class="rounded-lg border border-slate-200 bg-white shadow-sm">
                    <div class="border-b border-slate-200 bg-slate-50 px-4 py-4 text-center">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $day['date']->format('D') }}</p>
                        <p class="mt-1 text-2xl font-bold text-slate-950">{{ $day['date']->format('d') }}</p>
                        <p class="text-xs text-slate-500">{{ $day['date']->format('M Y') }}</p>
                    </div>

                    <div class="space-y-3 p-3">
                        @if($day['is_off'])
                            <div class="rounded-md bg-rose-50 px-3 py-4 text-center text-sm font-semibold text-rose-700">
                                {{ $day['off_reason'] ?? 'Off day' }}
                            </div>
                        @elseif($day['is_past'])
                            <div class="rounded-md bg-slate-100 px-3 py-4 text-center text-sm font-semibold text-slate-400">
                                Finished
                            </div>
                        @elseif(!$day['is_current_booking_period'])
                            <div class="rounded-md bg-amber-50 px-3 py-4 text-center text-sm font-semibold text-amber-700">
                                Opens in its week
                            </div>
                        @elseif($day['slots']->isEmpty())
                            <div class="rounded-md bg-slate-50 px-3 py-4 text-center text-sm font-semibold text-slate-500">
                                Not generated yet
                            </div>
                        @else
                            @foreach($day['slots'] as $slot)
                                @php
                                    $available = max($slot->capacity - $slot->booked_count, 0);
                                    $isFull = $available <= 0;
                                @endphp

                                <button type="button"
                                        @disabled($isFull)
                                        data-booking-slot-id="{{ $slot->id }}"
                                        data-booking-branch="{{ $slot->location?->name ?? 'Branch' }}"
                                        data-booking-date="{{ \Carbon\Carbon::parse($slot->date)->format('M d, Y') }}"
                                        data-booking-time="{{ \Carbon\Carbon::parse($slot->start_time)->format('H:i') }} - {{ \Carbon\Carbon::parse($slot->end_time)->format('H:i') }}"
                                        data-booking-seats="{{ $available }}"
                                        class="w-full rounded-lg border p-3 text-start transition {{ $isFull ? 'cursor-not-allowed border-slate-200 bg-slate-100 text-slate-400' : 'student-slot-available border-blue-200 bg-blue-50 text-slate-900 hover:border-blue-700 hover:bg-blue-700 hover:text-white' }}">
                                    <span class="block text-xs font-bold">{{ $slot->location?->name ?? 'Branch' }}</span>
                                    <span class="mt-1 block text-sm font-bold">
                                        {{ \Carbon\Carbon::parse($slot->start_time)->format('H:i') }}
                                        -
                                        {{ \Carbon\Carbon::parse($slot->end_time)->format('H:i') }}
                                    </span>
                                    <span class="mt-2 block text-xs font-semibold">
                                        {{ $available }} seats left
                                    </span>
                                </button>
                            @endforeach
                        @endif
                    </div>
                </section>
            @endforeach
        </div>
    </div>
</div>

<div id="booking-confirm-modal" class="fixed inset-0 z-[90] hidden items-center justify-center px-4 py-6" aria-hidden="true">
    <button type="button" class="absolute inset-0 bg-slate-950/60 backdrop-blur-sm" data-booking-confirm-close aria-label="Close booking confirmation"></button>

    <div class="relative w-full max-w-md overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl">
        <div class="absolute inset-x-0 top-0 h-1.5 bg-gradient-to-r from-[#2f6fa3] to-sky-400"></div>
        <div class="p-6">
            <div class="flex items-start gap-4">
                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-blue-50 text-[#2f6fa3] ring-1 ring-blue-100">
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3M5 11h14M6 5h12a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2Z" />
                    </svg>
                </div>
                <div class="min-w-0">
                    <p class="text-sm font-extrabold uppercase tracking-wide text-[#2f6fa3]">Confirm booking</p>
                    <h2 class="mt-1 text-2xl font-black text-slate-950">Book this slot?</h2>
                    <p class="mt-2 text-sm leading-6 text-slate-600">Please confirm the details before reserving your seat.</p>
                </div>
            </div>

            <dl class="mt-6 space-y-3 rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm">
                <div class="flex justify-between gap-4">
                    <dt class="font-bold text-slate-500">Branch</dt>
                    <dd class="text-right font-extrabold text-slate-950" data-booking-confirm-branch></dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="font-bold text-slate-500">Date</dt>
                    <dd class="text-right font-extrabold text-slate-950" data-booking-confirm-date></dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="font-bold text-slate-500">Time</dt>
                    <dd class="text-right font-extrabold text-slate-950" data-booking-confirm-time></dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="font-bold text-slate-500">Seats left</dt>
                    <dd class="text-right font-extrabold text-slate-950" data-booking-confirm-seats></dd>
                </div>
            </dl>

            <form method="POST" action="{{ route('book.slot') }}" class="mt-6 grid gap-3 sm:grid-cols-2">
                @csrf
                <input type="hidden" name="slot_id" data-booking-confirm-slot>
                <button type="button" class="rounded-md border border-slate-300 px-4 py-3 text-sm font-extrabold text-slate-700 hover:bg-slate-100" data-booking-confirm-close>
                    Cancel
                </button>
                <button class="rounded-md bg-[#2f6fa3] px-4 py-3 text-sm font-extrabold text-white hover:bg-[#255a84]">
                    Confirm booking
                </button>
            </form>
        </div>
    </div>
</div>

<script>
    const bookingConfirmModal = document.getElementById('booking-confirm-modal');
    const bookingConfirmSlot = document.querySelector('[data-booking-confirm-slot]');
    const bookingConfirmFields = {
        branch: document.querySelector('[data-booking-confirm-branch]'),
        date: document.querySelector('[data-booking-confirm-date]'),
        time: document.querySelector('[data-booking-confirm-time]'),
        seats: document.querySelector('[data-booking-confirm-seats]'),
    };

    function closeBookingConfirmModal() {
        bookingConfirmModal?.classList.add('hidden');
        bookingConfirmModal?.classList.remove('flex');
        bookingConfirmModal?.setAttribute('aria-hidden', 'true');
    }

    document.querySelectorAll('[data-booking-slot-id]').forEach(button => {
        button.addEventListener('click', () => {
            if (!bookingConfirmModal || !bookingConfirmSlot) {
                return;
            }

            bookingConfirmSlot.value = button.dataset.bookingSlotId || '';
            bookingConfirmFields.branch.textContent = button.dataset.bookingBranch || '';
            bookingConfirmFields.date.textContent = button.dataset.bookingDate || '';
            bookingConfirmFields.time.textContent = button.dataset.bookingTime || '';
            bookingConfirmFields.seats.textContent = button.dataset.bookingSeats || '0';

            bookingConfirmModal.classList.remove('hidden');
            bookingConfirmModal.classList.add('flex');
            bookingConfirmModal.setAttribute('aria-hidden', 'false');
        });
    });

    document.querySelectorAll('[data-booking-confirm-close]').forEach(button => {
        button.addEventListener('click', closeBookingConfirmModal);
    });

    document.addEventListener('keydown', event => {
        if (event.key === 'Escape') {
            closeBookingConfirmModal();
        }
    });
</script>
@endsection
