@extends('layouts.app')

@section('content')
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

        @if(session('success'))
            <div class="mb-6 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="mb-6 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-800">
                {{ session('error') }}
            </div>
        @endif

        @if($errors->any())
            <div class="mb-6 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-800">
                {{ $errors->first() }}
            </div>
        @endif

        @if(Auth::user()?->booking_warning_at)
            <div class="mb-6 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                <span class="font-bold">Account notice:</span>
                {{ Auth::user()->booking_warning_reason }}
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

                                <form method="POST" action="{{ route('book.slot') }}">
                                    @csrf
                                    <input type="hidden" name="slot_id" value="{{ $slot->id }}">

                                    <button @disabled($isFull)
                                            class="w-full rounded-lg border p-3 text-start transition {{ $isFull ? 'cursor-not-allowed border-slate-200 bg-slate-100 text-slate-400' : 'border-blue-200 bg-blue-50 text-slate-900 hover:border-blue-700 hover:bg-blue-700 hover:text-white' }}">
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
                                </form>
                            @endforeach
                        @endif
                    </div>
                </section>
            @endforeach
        </div>
    </div>
</div>
@endsection
