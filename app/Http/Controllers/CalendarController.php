<?php

namespace App\Http\Controllers;

use App\Models\Slot;
use App\Models\Holiday;
use Carbon\Carbon;

class CalendarController extends Controller
{
    public function index()
    {
        $startOfWeek = Carbon::now()->startOfWeek();

        $weekDays = [];

        for ($i = 0; $i < 7; $i++) {

            $date = $startOfWeek->copy()->addDays($i);

            $isFriday = $date->isFriday();

            $isHoliday = Holiday::where('date', $date->toDateString())
                ->whereNull('booking_location_id')
                ->exists();

            $slots = [];

            if (!$isFriday && !$isHoliday) {
                $slots = Slot::with('location')
                    ->where('date', $date->toDateString())
                    ->where('is_active', true)
                    ->orderBy('booking_location_id')
                    ->orderBy('start_time')
                    ->get();

                $closedLocationIds = Holiday::where('date', $date->toDateString())
                    ->whereNotNull('booking_location_id')
                    ->pluck('booking_location_id');

                if ($closedLocationIds->isNotEmpty()) {
                    $slots = $slots->whereNotIn('booking_location_id', $closedLocationIds)->values();
                }
            }

            $weekDays[] = [
                'date' => $date,
                'is_off' => $isFriday || $isHoliday,
                'slots' => $slots
            ];
        }

        return view('calendar.index', compact('weekDays'));
    }
}
