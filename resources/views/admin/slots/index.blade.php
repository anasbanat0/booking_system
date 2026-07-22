@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-slate-50 lg:flex">
    @include('admin.partials.sidebar')

    <main class="min-w-0 flex-1">
        <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
            @include('admin.partials.topbar')
            <div class="mb-8">
                <p class="text-sm font-semibold uppercase tracking-wide text-blue-700">System Booking</p>
                <h1 class="mt-2 text-3xl font-bold text-slate-950">Slots Time</h1>
                <p class="mt-2 max-w-2xl text-sm text-slate-600">
                    Manage branch capacities and the default time periods used when generating weekly booking slots.
                </p>
            </div>

            @if(Auth::user()?->canManageAllBranches())
            <section class="mb-6 rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                <div class="mb-5">
                    <h2 class="text-lg font-bold text-slate-950">Booking Rules</h2>
                    <p class="mt-1 text-sm text-slate-500">
                        These rules are enforced when students submit a new booking.
                    </p>
                </div>

                <form method="POST" action="{{ route('admin.booking-rules.update') }}" class="grid gap-4 md:grid-cols-4">
                    @csrf
                    @method('PATCH')

                    <label class="block">
                        <span class="text-sm font-medium text-slate-700">Weekly limit</span>
                        <input type="number" name="weekly_limit" min="1" max="100" value="{{ old('weekly_limit', $bookingRules->weekly_limit) }}"
                               class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </label>

                    <label class="block">
                        <span class="text-sm font-medium text-slate-700">Monthly limit</span>
                        <input type="number" name="monthly_limit" min="1" max="500" value="{{ old('monthly_limit', $bookingRules->monthly_limit) }}"
                               class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </label>

                    <label class="block">
                        <span class="text-sm font-medium text-slate-700">Reschedule cutoff hours</span>
                        <input type="number" name="reschedule_cutoff_hours" min="1" max="168" value="{{ old('reschedule_cutoff_hours', $bookingRules->reschedule_cutoff_hours) }}"
                               class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </label>

                    <label class="block">
                        <span class="text-sm font-medium text-slate-700">Reminder hours before</span>
                        <input type="number" name="reminder_hours_before" min="1" max="168" value="{{ old('reminder_hours_before', $bookingRules->reminder_hours_before) }}"
                               class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </label>

                    <label class="block">
                        <span class="text-sm font-medium text-slate-700">Open next week before days</span>
                        <input type="number" name="advance_booking_days" min="0" max="7" value="{{ old('advance_booking_days', $bookingRules->advance_booking_days) }}"
                               class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </label>

                    <div class="space-y-3 md:col-span-4">
                        <label class="flex items-center gap-2">
                            <input type="checkbox" name="enforce_one_booking_per_day" value="1" @checked($bookingRules->enforce_one_booking_per_day)
                                   class="rounded border-slate-300 text-blue-600 shadow-sm focus:ring-blue-500">
                            <span class="text-sm font-medium text-slate-700">Prevent more than one booking on the same day</span>
                        </label>

                        <label class="flex items-center gap-2">
                            <input type="checkbox" name="enforce_unique_time_period" value="1" @checked($bookingRules->enforce_unique_time_period)
                                   class="rounded border-slate-300 text-blue-600 shadow-sm focus:ring-blue-500">
                            <span class="text-sm font-medium text-slate-700">Prevent duplicate booking in the same time period</span>
                        </label>
                    </div>

                    <div class="md:col-span-4">
                        <button class="rounded-md bg-slate-950 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">
                            Save rules
                        </button>
                    </div>
                </form>
            </section>
            @endif

            <section class="mb-6 rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                    <div>
                        <h2 class="text-lg font-bold text-slate-950">Generate Booking Slots</h2>
                        <p class="mt-1 text-sm text-slate-500">
                            Creates daily slots from the active branch time templates and skips Fridays and holidays.
                        </p>
                    </div>

                    <form method="POST" action="{{ route('slots.generate') }}" class="grid gap-3 sm:grid-cols-[180px_120px_auto]">
                        @csrf

                        <label class="block">
                            <span class="text-sm font-medium text-slate-700">Start date</span>
                            <input type="date" name="start_date" required
                                   class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </label>

                        <label class="block">
                            <span class="text-sm font-medium text-slate-700">Days</span>
                            <input type="number" name="days" value="7" min="1" max="30"
                                   class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </label>

                        <div class="flex items-end">
                            <button class="h-10 rounded-md bg-slate-950 px-4 text-sm font-semibold text-white hover:bg-slate-800">
                                Generate
                            </button>
                        </div>
                    </form>
                </div>
            </section>

            <section class="mb-6 rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                <div class="grid gap-6 lg:grid-cols-[1fr_1.2fr]">
                    <div>
                        <h2 class="text-lg font-bold text-slate-950">Close hub / off day</h2>
                        <p class="mt-1 text-sm text-slate-500">
                            Close registration for all branches or a specific branch on a selected date.
                        </p>

                        <form method="POST" action="{{ route('admin.holidays.store') }}" class="mt-4 grid gap-3 sm:grid-cols-2">
                            @csrf
                            <label class="block">
                                <span class="text-sm font-medium text-slate-700">Date</span>
                                <input type="date" name="date" required class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            </label>

                            @if(Auth::user()?->canManageAllBranches())
                                <label class="block">
                                    <span class="text-sm font-medium text-slate-700">Branch</span>
                                    <select name="booking_location_id" class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                        <option value="">All branches</option>
                                        @foreach($allLocations as $location)
                                            <option value="{{ $location->id }}">{{ $location->name }}</option>
                                        @endforeach
                                    </select>
                                </label>
                            @endif

                            <label class="block sm:col-span-2">
                                <span class="text-sm font-medium text-slate-700">Reason</span>
                                <input name="reason" placeholder="Event, maintenance, hub closed..." class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            </label>

                            <div class="sm:col-span-2">
                                <button class="rounded-md bg-slate-950 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">
                                    Close registration
                                </button>
                            </div>
                        </form>
                    </div>

                    <div class="rounded-lg border border-slate-200">
                        <div class="border-b border-slate-200 bg-slate-50 px-4 py-3 text-sm font-bold text-slate-700">Upcoming closed days</div>
                        <div class="divide-y divide-slate-100">
                            @forelse($holidays as $holiday)
                                <div class="px-4 py-3">
                                    <form method="POST" action="{{ route('admin.holidays.update', $holiday) }}" class="grid gap-3 lg:grid-cols-[150px_180px_minmax(0,1fr)_auto_auto] lg:items-end">
                                        @csrf
                                        @method('PATCH')

                                        <label class="block">
                                            <span class="text-xs font-bold uppercase text-slate-500">Date</span>
                                            <input type="date" name="date" value="{{ $holiday->date }}"
                                                   class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                        </label>

                                        <label class="block">
                                            <span class="text-xs font-bold uppercase text-slate-500">Branch</span>
                                            @if(Auth::user()?->canManageAllBranches())
                                                <select name="booking_location_id" class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                                    <option value="">All branches</option>
                                                    @foreach($allLocations as $location)
                                                        <option value="{{ $location->id }}" @selected($holiday->booking_location_id === $location->id)>{{ $location->name }}</option>
                                                    @endforeach
                                                </select>
                                            @else
                                                <input value="{{ $holiday->location?->name ?? 'Branch' }}" disabled class="mt-1 block w-full rounded-md border-slate-200 bg-slate-100 text-sm text-slate-500">
                                            @endif
                                        </label>

                                        <label class="block">
                                            <span class="text-xs font-bold uppercase text-slate-500">Reason</span>
                                            <input name="reason" value="{{ $holiday->reason }}"
                                                   class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                        </label>

                                        <button class="rounded-md border border-slate-300 px-3 py-2 text-sm font-bold text-slate-700 hover:bg-slate-100">
                                            Save
                                        </button>
                                    </form>

                                    <form method="POST" action="{{ route('admin.holidays.destroy', $holiday) }}" class="mt-2 text-right" onsubmit="return confirm('Delete this closed day?');">
                                        @csrf
                                        @method('DELETE')
                                        <button class="rounded-md border border-rose-200 px-3 py-2 text-sm font-bold text-rose-700 hover:bg-rose-50">
                                            Delete
                                        </button>
                                    </form>
                                </div>
                            @empty
                                <div class="px-4 py-8 text-center text-sm text-slate-500">No closed days yet.</div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </section>

            <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
                @foreach($locations as $location)
                    <section class="rounded-lg border border-slate-200 bg-white shadow-sm">
                        <div class="border-b border-slate-200 px-5 py-4">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <h2 class="text-lg font-bold text-slate-950">{{ $location->name }}</h2>
                                    <p class="mt-1 text-sm text-slate-500">
                                        Default capacity: {{ $location->default_capacity }} seats
                                    </p>
                                </div>
                                <span class="rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset {{ $location->is_active ? 'bg-emerald-50 text-emerald-700 ring-emerald-200' : 'bg-slate-100 text-slate-600 ring-slate-200' }}">
                                    {{ $location->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </div>
                        </div>

                        <div class="space-y-6 p-5">
                            <form method="POST" action="{{ route('admin.locations.update', $location) }}" class="grid gap-4 sm:grid-cols-2">
                                @csrf
                                @method('PATCH')

                                <label class="block">
                                    <span class="text-sm font-medium text-slate-700">Branch name</span>
                                    <input name="name" value="{{ old('name', $location->name) }}"
                                           class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                </label>

                                <label class="block">
                                    <span class="text-sm font-medium text-slate-700">Default seats</span>
                                    <input type="number" name="default_capacity" min="1" max="500" value="{{ old('default_capacity', $location->default_capacity) }}"
                                           class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                </label>

                                <label class="flex items-center gap-2 sm:col-span-2">
                                    <input type="checkbox" name="is_active" value="1" @checked($location->is_active)
                                           class="rounded border-slate-300 text-blue-600 shadow-sm focus:ring-blue-500">
                                    <span class="text-sm font-medium text-slate-700">Active branch</span>
                                </label>

                                <div class="sm:col-span-2">
                                    <button class="rounded-md bg-slate-950 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">
                                        Save branch
                                    </button>
                                </div>
                            </form>

                            <div class="overflow-x-auto rounded-lg border border-slate-200">
                                <table class="min-w-full divide-y divide-slate-200">
                                    <thead class="bg-slate-50">
                                        <tr>
                                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Start</th>
                                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">End</th>
                                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Seats</th>
                                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Active</th>
                                            <th class="px-4 py-3"></th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100 bg-white">
                                        @foreach($location->slotTemplates as $template)
                                            <tr>
                                                <form method="POST" action="{{ route('admin.slot-templates.update', $template) }}">
                                                    @csrf
                                                    @method('PATCH')
                                                    <td class="px-4 py-3">
                                                        <input type="time" name="start_time" value="{{ substr($template->start_time, 0, 5) }}"
                                                               class="w-28 rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                                    </td>
                                                    <td class="px-4 py-3">
                                                        <input type="time" name="end_time" value="{{ substr($template->end_time, 0, 5) }}"
                                                               class="w-28 rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                                    </td>
                                                    <td class="px-4 py-3">
                                                        <input type="number" name="capacity" min="1" max="500" value="{{ $template->capacity }}"
                                                               class="w-24 rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                                    </td>
                                                    <td class="px-4 py-3">
                                                        <input type="checkbox" name="is_active" value="1" @checked($template->is_active)
                                                               class="rounded border-slate-300 text-blue-600 shadow-sm focus:ring-blue-500">
                                                    </td>
                                                    <td class="px-4 py-3 text-right">
                                                        <button class="rounded-md border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100">
                                                            Save
                                                        </button>
                                                        <button type="submit"
                                                                form="delete-template-{{ $template->id }}"
                                                                class="mt-2 rounded-md border border-rose-200 px-3 py-2 text-sm font-semibold text-rose-700 hover:bg-rose-50">
                                                            Delete
                                                        </button>
                                                    </td>
                                                </form>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            @foreach($location->slotTemplates as $template)
                                <form id="delete-template-{{ $template->id }}" method="POST" action="{{ route('admin.slot-templates.destroy', $template) }}" onsubmit="return confirm('Delete this slot time?');">
                                    @csrf
                                    @method('DELETE')
                                </form>
                            @endforeach

                            <form method="POST" action="{{ route('admin.slot-templates.store') }}" class="rounded-lg border border-dashed border-slate-300 bg-slate-50 p-4">
                                @csrf
                                <input type="hidden" name="booking_location_id" value="{{ $location->id }}">
                                <input type="hidden" name="is_active" value="1">

                                <div class="grid gap-3 sm:grid-cols-4">
                                    <label class="block">
                                        <span class="text-sm font-medium text-slate-700">Start</span>
                                        <input type="time" name="start_time"
                                               class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    </label>

                                    <label class="block">
                                        <span class="text-sm font-medium text-slate-700">End</span>
                                        <input type="time" name="end_time"
                                               class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    </label>

                                    <label class="block">
                                        <span class="text-sm font-medium text-slate-700">Seats</span>
                                        <input type="number" name="capacity" min="1" max="500" value="{{ $location->default_capacity }}"
                                               class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    </label>

                                    <div class="flex items-end">
                                        <button class="h-10 w-full rounded-md bg-blue-700 px-4 text-sm font-semibold text-white hover:bg-blue-800">
                                            Add time
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </section>
                @endforeach
            </div>
        </div>
    </main>
</div>
@endsection
