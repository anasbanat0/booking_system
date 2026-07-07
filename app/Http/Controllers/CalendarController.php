<?php

namespace App\Http\Controllers;

use App\Models\Slot;
use App\Models\Holiday;
use Carbon\Carbon;
use Illuminate\Http\Request;

class CalendarController extends Controller
{
    public function index(Request $request)
    {
        $startOfWeek = Carbon::now()->startOfWeek();
        $studentLocationId = $request->user()->booking_location_id;

        $weekDays = [];

        for ($i = 0; $i < 7; $i++) {

            $date = $startOfWeek->copy()->addDays($i);

            $isFriday = $date->isFriday();

            $isHoliday = Holiday::where('date', $date->toDateString())
                ->where(function ($query) use ($studentLocationId) {
                    $query->whereNull('booking_location_id')
                        ->orWhere('booking_location_id', $studentLocationId);
                })
                ->exists();

            $slots = [];

            if (!$isFriday && !$isHoliday) {
                $slots = Slot::with('location')
                    ->where('date', $date->toDateString())
                    ->where('is_active', true)
                    ->when($studentLocationId, fn ($query) => $query->where('booking_location_id', $studentLocationId))
                    ->orderBy('booking_location_id')
                    ->orderBy('start_time')
                    ->get();
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
