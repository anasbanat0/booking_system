<?php

namespace App\Http\Controllers;

use App\Models\BookingLocation;
use App\Models\BookingRule;
use App\Models\ClosedPeriod;
use App\Models\Holiday;
use App\Models\Slot;
use App\Models\SlotTemplate;
use Illuminate\Http\Request;

class AdminSlotSettingsController extends Controller
{
    public function index()
    {
        $locations = BookingLocation::with(['slotTemplates' => function ($query) {
            $query->orderBy('start_time');
        }])->orderBy('name')->get();
        $visibleLocations = request()->user()->canManageAllBranches()
            ? $locations
            : $locations->where('id', request()->user()->booking_location_id)->values();
        $holidays = Holiday::with('location')
            ->when(!request()->user()->canManageAllBranches(), function ($query) {
                $query->where('booking_location_id', request()->user()->booking_location_id);
            })
            ->whereDate('date', '>=', now()->subDays(7)->toDateString())
            ->orderBy('date')
            ->get();
        $closedPeriods = ClosedPeriod::with('location')
            ->when(!request()->user()->canManageAllBranches(), function ($query) {
                $query->where('booking_location_id', request()->user()->booking_location_id);
            })
            ->whereDate('date', '>=', now()->subDays(7)->toDateString())
            ->orderBy('date')
            ->orderBy('start_time')
            ->get();
        $closurePeriods = SlotTemplate::query()
            ->select('start_time', 'end_time')
            ->distinct()
            ->when(!request()->user()->canManageAllBranches(), function ($query) {
                $query->where('booking_location_id', request()->user()->booking_location_id);
            })
            ->orderBy('start_time')
            ->get();

        $bookingRules = BookingRule::current();

        return view('admin.slots.index', [
            'locations' => $visibleLocations,
            'allLocations' => $locations,
            'holidays' => $holidays,
            'closedPeriods' => $closedPeriods,
            'closurePeriods' => $closurePeriods,
            'bookingRules' => $bookingRules,
        ]);
    }

    public function updateRules(Request $request)
    {
        abort_unless($request->user()->canManageAllBranches(), 403);

        $validated = $request->validate([
            'weekly_limit' => ['required', 'integer', 'min:1', 'max:100'],
            'monthly_limit' => ['required', 'integer', 'min:1', 'max:500'],
            'reschedule_cutoff_hours' => ['required', 'integer', 'min:1', 'max:168'],
            'reminder_hours_before' => ['required', 'integer', 'min:1', 'max:168'],
            'advance_booking_days' => ['required', 'integer', 'min:0', 'max:7'],
            'enforce_one_booking_per_day' => ['nullable', 'boolean'],
            'enforce_unique_time_period' => ['nullable', 'boolean'],
        ]);

        BookingRule::current()->update([
            'weekly_limit' => $validated['weekly_limit'],
            'monthly_limit' => $validated['monthly_limit'],
            'reschedule_cutoff_hours' => $validated['reschedule_cutoff_hours'],
            'reminder_hours_before' => $validated['reminder_hours_before'],
            'advance_booking_days' => $validated['advance_booking_days'],
            'enforce_one_booking_per_day' => $request->boolean('enforce_one_booking_per_day'),
            'enforce_unique_time_period' => $request->boolean('enforce_unique_time_period'),
        ]);

        return back()->with('success', 'Booking rules updated successfully.');
    }

    public function updateLocation(Request $request, BookingLocation $location)
    {
        $this->authorizeBranchAccess($request, $location->id);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:booking_locations,name,' . $location->id],
            'default_capacity' => ['required', 'integer', 'min:1', 'max:500'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $location->update([
            'name' => $validated['name'],
            'default_capacity' => $validated['default_capacity'],
            'is_active' => $request->boolean('is_active'),
        ]);

        return back()->with('success', 'Location updated successfully.');
    }

    public function storeTemplate(Request $request)
    {
        $validated = $request->validate([
            'booking_location_id' => ['required', 'exists:booking_locations,id'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'capacity' => ['required', 'integer', 'min:1', 'max:500'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $this->authorizeBranchAccess($request, (int) $validated['booking_location_id']);

        $exists = SlotTemplate::where('booking_location_id', $validated['booking_location_id'])
            ->where('start_time', $validated['start_time'])
            ->where('end_time', $validated['end_time'])
            ->exists();

        if ($exists) {
            return back()->withErrors([
                'start_time' => 'This time period already exists for this branch.',
            ])->withInput();
        }

        SlotTemplate::create([
            'booking_location_id' => $validated['booking_location_id'],
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time'],
            'capacity' => $validated['capacity'],
            'is_active' => $request->boolean('is_active', true),
        ]);

        return back()->with('success', 'Slot time added successfully.');
    }

    public function updateTemplate(Request $request, SlotTemplate $template)
    {
        $this->authorizeBranchAccess($request, (int) $template->booking_location_id);

        $validated = $request->validate([
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'capacity' => ['required', 'integer', 'min:1', 'max:500'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $exists = SlotTemplate::where('booking_location_id', $template->booking_location_id)
            ->where('start_time', $validated['start_time'])
            ->where('end_time', $validated['end_time'])
            ->whereKeyNot($template->id)
            ->exists();

        if ($exists) {
            return back()->withErrors([
                'start_time' => 'This time period already exists for this branch.',
            ])->withInput();
        }

        $template->update([
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time'],
            'capacity' => $validated['capacity'],
            'is_active' => $request->boolean('is_active'),
        ]);

        return back()->with('success', 'Slot time updated successfully.');
    }

    public function destroyTemplate(Request $request, SlotTemplate $template)
    {
        $this->authorizeBranchAccess($request, (int) $template->booking_location_id);

        $template->delete();

        return back()->with('success', 'Slot time deleted successfully.');
    }

    public function storeHoliday(Request $request)
    {
        $validated = $request->validate([
            'date' => ['required', 'date'],
            'booking_location_id' => ['nullable', 'exists:booking_locations,id'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $locationId = $request->user()->canManageAllBranches()
            ? ($validated['booking_location_id'] ?? null)
            : $request->user()->booking_location_id;

        Holiday::updateOrCreate(
            [
                'booking_location_id' => $locationId,
                'date' => $validated['date'],
            ],
            [
                'reason' => $validated['reason'] ?? 'Hub closed',
            ]
        );

        return back()->with('success', 'Off day added successfully.');
    }

    public function updateHoliday(Request $request, Holiday $holiday)
    {
        if (!$request->user()->canManageAllBranches()) {
            abort_unless((int) $holiday->booking_location_id === (int) $request->user()->booking_location_id, 403);
        }

        $validated = $request->validate([
            'date' => ['required', 'date'],
            'booking_location_id' => ['nullable', 'exists:booking_locations,id'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $locationId = $request->user()->canManageAllBranches()
            ? ($validated['booking_location_id'] ?? null)
            : $request->user()->booking_location_id;

        $holiday->update([
            'booking_location_id' => $locationId,
            'date' => $validated['date'],
            'reason' => $validated['reason'] ?? 'Hub closed',
        ]);

        return back()->with('success', 'Closed day updated successfully.');
    }

    public function destroyHoliday(Request $request, Holiday $holiday)
    {
        if (!$request->user()->canManageAllBranches()) {
            abort_unless((int) $holiday->booking_location_id === (int) $request->user()->booking_location_id, 403);
        }

        $holiday->delete();

        return back()->with('success', 'Closed day deleted successfully.');
    }

    public function storeClosedPeriod(Request $request)
    {
        $validated = $request->validate([
            'date' => ['required', 'date'],
            'booking_location_id' => ['nullable', 'exists:booking_locations,id'],
            'periods' => ['required', 'array', 'min:1'],
            'periods.*' => ['required', 'string'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $locationId = $request->user()->canManageAllBranches()
            ? ($validated['booking_location_id'] ?? null)
            : $request->user()->booking_location_id;

        foreach ($validated['periods'] as $period) {
            [$startTime, $endTime] = array_pad(explode('|', $period, 2), 2, null);

            if (!$startTime || !$endTime) {
                continue;
            }

            $closedPeriod = ClosedPeriod::updateOrCreate(
                [
                    'booking_location_id' => $locationId,
                    'date' => $validated['date'],
                    'start_time' => $startTime,
                    'end_time' => $endTime,
                ],
                [
                    'reason' => $validated['reason'] ?? 'Period closed',
                ]
            );

            $this->deactivateClosedSlots($closedPeriod);
        }

        return back()->with('success', 'Closed period added successfully.');
    }

    public function destroyClosedPeriod(Request $request, ClosedPeriod $closedPeriod)
    {
        if (!$request->user()->canManageAllBranches()) {
            abort_unless((int) $closedPeriod->booking_location_id === (int) $request->user()->booking_location_id, 403);
        }

        $date = $closedPeriod->date;
        $locationId = $closedPeriod->booking_location_id;
        $startTime = $closedPeriod->start_time;
        $endTime = $closedPeriod->end_time;

        $closedPeriod->delete();
        $this->reactivateOpenSlots($date, $locationId, $startTime, $endTime);

        return back()->with('success', 'Closed period deleted successfully.');
    }

    private function authorizeBranchAccess(Request $request, int $locationId): void
    {
        abort_unless(
            $request->user()->canManageAllBranches() || (int) $request->user()->booking_location_id === $locationId,
            403
        );
    }

    private function deactivateClosedSlots(ClosedPeriod $closedPeriod): void
    {
        Slot::query()
            ->whereDate('date', $closedPeriod->date)
            ->when($closedPeriod->booking_location_id, function ($query) use ($closedPeriod) {
                $query->where('booking_location_id', $closedPeriod->booking_location_id);
            })
            ->whereTime('start_time', '<', $closedPeriod->end_time)
            ->whereTime('end_time', '>', $closedPeriod->start_time)
            ->update(['is_active' => false]);
    }

    private function reactivateOpenSlots(string $date, ?int $locationId, string $startTime, string $endTime): void
    {
        Slot::query()
            ->whereDate('date', $date)
            ->when($locationId, fn ($query) => $query->where('booking_location_id', $locationId))
            ->whereTime('start_time', '<', $endTime)
            ->whereTime('end_time', '>', $startTime)
            ->get()
            ->each(function (Slot $slot) {
                $hasHoliday = Holiday::whereDate('date', $slot->date)
                    ->where(function ($query) use ($slot) {
                        $query->whereNull('booking_location_id')
                            ->orWhere('booking_location_id', $slot->booking_location_id);
                    })
                    ->exists();

                $hasClosedPeriod = ClosedPeriod::whereDate('date', $slot->date)
                    ->where(function ($query) use ($slot) {
                        $query->whereNull('booking_location_id')
                            ->orWhere('booking_location_id', $slot->booking_location_id);
                    })
                    ->whereTime('start_time', '<', $slot->end_time)
                    ->whereTime('end_time', '>', $slot->start_time)
                    ->exists();

                if (!$hasHoliday && !$hasClosedPeriod) {
                    $slot->update(['is_active' => true]);
                }
            });
    }
}
