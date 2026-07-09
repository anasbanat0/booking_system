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

    $bookingTrendLabels = $bookingsPerDay->pluck('date');
    $bookingTrendTotals = $bookingsPerDay->pluck('total');
    $statusChartLabels = $statusCounts->pluck('status')->map(function ($status) use ($statusLabels) {
        return $statusLabels[$status] ?? ucfirst($status);
    })->values();
    $statusChartTotals = $statusCounts->pluck('total')->values();
    $peakHourLabels = $peakHours->pluck('hour')->map(function ($hour) {
        return str_pad($hour, 2, '0', STR_PAD_LEFT) . ':00';
    })->values();
    $peakHourTotals = $peakHours->pluck('total');
@endphp

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="min-h-screen bg-slate-50 lg:flex">
    @include('admin.partials.sidebar')

    <main class="min-w-0 flex-1">
        <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        @include('admin.partials.topbar')
        <div class="mb-8">
            <div>
                <p class="text-sm font-semibold uppercase tracking-wide text-blue-700">Admin Workspace</p>
                <h1 class="mt-2 text-3xl font-bold text-slate-950">Dashboard</h1>
                <p class="mt-2 max-w-2xl text-sm text-slate-600">
                    Track booking activity, slot availability, and recent customer movement from one focused screen.
                </p>
            </div>

            <form method="GET" class="mt-5 grid gap-3 border-y border-slate-200 bg-white/70 py-4 sm:grid-cols-2 xl:grid-cols-[auto_160px_160px_220px_auto] xl:items-end">
                    <div>
                        <span class="text-xs font-bold uppercase tracking-wide text-slate-500">Range</span>
                        <div class="mt-1 grid grid-cols-3 overflow-hidden rounded-md border border-slate-300 bg-white text-sm font-bold">
                            <button type="submit" name="period" value="today" class="px-3 py-2 {{ $period === 'today' ? 'bg-slate-950 text-white' : 'text-slate-600 hover:bg-slate-50' }}">Today</button>
                            <button type="submit" name="period" value="week" class="border-x border-slate-300 px-3 py-2 {{ $period === 'week' ? 'bg-slate-950 text-white' : 'text-slate-600 hover:bg-slate-50' }}">Week</button>
                            <button type="submit" name="period" value="month" class="px-3 py-2 {{ $period === 'month' ? 'bg-slate-950 text-white' : 'text-slate-600 hover:bg-slate-50' }}">Month</button>
                        </div>
                    </div>
                    <label class="block">
                        <span class="text-xs font-bold uppercase tracking-wide text-slate-500">From</span>
                        <input type="date" name="start_date" value="{{ $startDate->toDateString() }}" class="mt-1 block w-full rounded-md border-slate-300 text-sm">
                    </label>
                    <label class="block">
                        <span class="text-xs font-bold uppercase tracking-wide text-slate-500">To</span>
                        <input type="date" name="end_date" value="{{ $endDate->toDateString() }}" class="mt-1 block w-full rounded-md border-slate-300 text-sm">
                    </label>
                    @if(Auth::user()?->canManageAllBranches())
                        <label class="block">
                            <span class="text-xs font-bold uppercase tracking-wide text-slate-500">Branch</span>
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
                    <button name="period" value="custom" class="rounded-md bg-slate-950 px-4 py-2 text-sm font-bold text-white hover:bg-slate-800">Apply</button>
                </form>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-5">
            <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-medium text-slate-500">Students</p>
                <p class="mt-3 text-3xl font-bold text-slate-950">{{ number_format($totalUsers) }}</p>
            </div>

            <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-medium text-slate-500">Bookings in Range</p>
                <p class="mt-3 text-3xl font-bold text-slate-950">{{ number_format($totalBookings) }}</p>
            </div>

            <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-medium text-slate-500">Today Bookings</p>
                <p class="mt-3 text-3xl font-bold text-blue-700">{{ number_format($todayBookings) }}</p>
            </div>

            <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-medium text-slate-500">Upcoming</p>
                <p class="mt-3 text-3xl font-bold text-emerald-700">{{ number_format($upcomingBookings) }}</p>
            </div>

            <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-medium text-slate-500">Active Slots</p>
                <p class="mt-3 text-3xl font-bold text-slate-950">{{ number_format($activeSlots) }}</p>
            </div>
        </div>

        <div class="mt-8 grid grid-cols-1 gap-6 xl:grid-cols-3">
            <section class="rounded-lg border border-slate-200 bg-white shadow-sm xl:col-span-2">
                <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
                    <div>
                        <h2 class="text-base font-semibold text-slate-950">Bookings Trend</h2>
                        <p class="mt-1 text-sm text-slate-500">{{ $startDate->format('M d, Y') }} - {{ $endDate->format('M d, Y') }}</p>
                    </div>
                </div>
                <div class="h-80 p-5">
                    <canvas id="bookingsChart"></canvas>
                </div>
            </section>

            <section class="rounded-lg border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 px-5 py-4">
                    <h2 class="text-base font-semibold text-slate-950">Status Mix</h2>
                    <p class="mt-1 text-sm text-slate-500">Filtered booking distribution</p>
                </div>
                <div class="h-80 p-5">
                    <canvas id="statusChart"></canvas>
                </div>
            </section>
        </div>

        <div class="mt-8 grid grid-cols-1 gap-6 xl:grid-cols-3">
            <section class="rounded-lg border border-slate-200 bg-white shadow-sm xl:col-span-2">
                <div class="flex flex-col gap-3 border-b border-slate-200 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="text-base font-semibold text-slate-950">Latest Bookings</h2>
                        <p class="mt-1 text-sm text-slate-500">The newest records that need admin awareness.</p>
                    </div>
                    <a href="{{ route('admin.bookings.index') }}"
                       class="inline-flex items-center justify-center rounded-md border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100">
                        View all
                    </a>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">User</th>
                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Slot</th>
                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Status</th>
                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Created</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @forelse($latestBookings as $booking)
                                <tr class="hover:bg-slate-50">
                                    <td class="whitespace-nowrap px-5 py-4">
                                        <div class="font-semibold text-slate-900">{{ $booking->user?->name ?? 'Deleted user' }}</div>
                                        <div class="text-sm text-slate-500">{{ $booking->user?->email }}</div>
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-4 text-sm text-slate-700">
                                        {{ $booking->slot?->location?->name ?? 'No branch' }}
                                        <span class="text-slate-400">/</span>
                                        {{ $booking->slot?->date ?? 'No date' }}
                                        <span class="text-slate-400">/</span>
                                        {{ $booking->slot?->start_time ?? 'No time' }}
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-4">
                                        <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset {{ $statusStyles[$booking->status] ?? 'bg-slate-100 text-slate-700 ring-slate-200' }}">
                                            {{ $statusLabels[$booking->status] ?? ucfirst($booking->status) }}
                                        </span>
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-4 text-sm text-slate-500">
                                        {{ $booking->created_at?->format('M d, Y') }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-5 py-12 text-center text-sm text-slate-500">
                                        No bookings yet.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="rounded-lg border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 px-5 py-4">
                    <h2 class="text-base font-semibold text-slate-950">Peak Hours</h2>
                    <p class="mt-1 text-sm text-slate-500">Most common booked slot start times.</p>
                </div>
                <div class="h-80 p-5">
                    <canvas id="hoursChart"></canvas>
                </div>
            </section>
        </div>
        </div>
    </main>
</div>

<script>
const chartTextColor = '#475569';
const gridColor = '#e2e8f0';

new Chart(document.getElementById('bookingsChart'), {
    type: 'line',
    data: {
        labels: @json($bookingTrendLabels),
        datasets: [{
            label: 'Bookings',
            data: @json($bookingTrendTotals),
            borderColor: '#2563eb',
            backgroundColor: 'rgba(37, 99, 235, 0.12)',
            borderWidth: 3,
            tension: 0.35,
            fill: true,
            pointRadius: 4,
            pointBackgroundColor: '#2563eb'
        }]
    },
    options: {
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
            x: { ticks: { color: chartTextColor }, grid: { color: gridColor } },
            y: { beginAtZero: true, ticks: { color: chartTextColor, precision: 0 }, grid: { color: gridColor } }
        }
    }
});

new Chart(document.getElementById('statusChart'), {
    type: 'doughnut',
    data: {
        labels: @json($statusChartLabels),
        datasets: [{
            data: @json($statusChartTotals),
            backgroundColor: ['#2563eb', '#059669', '#f59e0b', '#e11d48', '#7c3aed'],
            borderWidth: 0
        }]
    },
    options: {
        maintainAspectRatio: false,
        cutout: '68%',
        plugins: {
            legend: {
                position: 'bottom',
                labels: { color: chartTextColor, boxWidth: 12, padding: 18 }
            }
        }
    }
});

new Chart(document.getElementById('hoursChart'), {
    type: 'bar',
    data: {
        labels: @json($peakHourLabels),
        datasets: [{
            label: 'Bookings',
            data: @json($peakHourTotals),
            backgroundColor: '#0f766e',
            borderRadius: 6
        }]
    },
    options: {
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
            x: { ticks: { color: chartTextColor }, grid: { display: false } },
            y: { beginAtZero: true, ticks: { color: chartTextColor, precision: 0 }, grid: { color: gridColor } }
        }
    }
});
</script>
@endsection
