<?php

namespace App\Http\Controllers;

use App\Models\Slot;
use App\Models\Holiday;
use App\Models\SlotTemplate;
use Carbon\Carbon;
use Illuminate\Http\Request;

class SlotController extends Controller
{
    public function generate(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'days' => 'required|integer|min:1|max:30',
        ]);

        $startDate = Carbon::parse($request->start_date);

        $slotTemplates = SlotTemplate::with('location')
            ->where('is_active', true)
            ->whereHas('location', function ($query) {
                $query->where('is_active', true);
            })
            ->get();

        if ($slotTemplates->isEmpty()) {
            return back()->with('error', 'No active slot templates found.');
        }

        for ($i = 0; $i < $request->days; $i++) {

            $date = $startDate->copy()->addDays($i);

            // Skip Friday
            if ($date->isFriday()) {
                continue;
            }

            foreach ($slotTemplates as $template) {
                $isHoliday = Holiday::where('date', $date->toDateString())
                    ->where(function ($query) use ($template) {
                        $query->whereNull('booking_location_id')
                            ->orWhere('booking_location_id', $template->booking_location_id);
                    })
                    ->exists();

                if ($isHoliday) {
                    continue;
                }

                Slot::updateOrCreate(
                    [
                        'booking_location_id' => $template->booking_location_id,
                        'date' => $date->toDateString(),
                        'start_time' => $template->start_time,
                        'end_time' => $template->end_time,
                    ],
                    [
                        'capacity' => $template->capacity,
                        'is_active' => true,
                    ]
                );
            }
        }

        return back()->with('success', 'Slots generated successfully!');
    }
}
