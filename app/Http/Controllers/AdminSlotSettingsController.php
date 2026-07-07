<?php

namespace App\Http\Controllers;

use App\Models\BookingLocation;
use App\Models\BookingRule;
use App\Models\SlotTemplate;
use Illuminate\Http\Request;

class AdminSlotSettingsController extends Controller
{
    public function index()
    {
        $locations = BookingLocation::with(['slotTemplates' => function ($query) {
            $query->orderBy('start_time');
        }])->orderBy('name')->get();

        $bookingRules = BookingRule::current();

        return view('admin.slots.index', compact('locations', 'bookingRules'));
    }

    public function updateRules(Request $request)
    {
        $validated = $request->validate([
            'weekly_limit' => ['required', 'integer', 'min:1', 'max:100'],
            'monthly_limit' => ['required', 'integer', 'min:1', 'max:500'],
            'enforce_one_booking_per_day' => ['nullable', 'boolean'],
            'enforce_unique_time_period' => ['nullable', 'boolean'],
        ]);

        BookingRule::current()->update([
            'weekly_limit' => $validated['weekly_limit'],
            'monthly_limit' => $validated['monthly_limit'],
            'enforce_one_booking_per_day' => $request->boolean('enforce_one_booking_per_day'),
            'enforce_unique_time_period' => $request->boolean('enforce_unique_time_period'),
        ]);

        return back()->with('success', 'Booking rules updated successfully.');
    }

    public function updateLocation(Request $request, BookingLocation $location)
    {
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
}
