<?php

namespace App\Http\Controllers;

use App\Models\BookingRule;
use App\Models\Holiday;
use App\Models\Slot;
use App\Support\BookingPeriod;
use Carbon\Carbon;
use Illuminate\Http\Request;

class CalendarController extends Controller
{
    public function index(Request $request)
    {
        $selectedDate = $request->filled('period')
            ? Carbon::parse($request->period)
            : now();
        [$startOfWeek, $endOfWeek] = BookingPeriod::range($selectedDate);
        $openPeriods = $this->openBookingPeriods(BookingRule::current());
        $studentLocationId = $request->user()->booking_location_id;

        $weekDays = [];

        $cursor = $startOfWeek->copy();

        while ($cursor->lte($endOfWeek)) {
            $date = $cursor->copy();

            $isFriday = $date->isFriday();
            $isPast = $date->isBefore(now()->startOfDay());
            $isOpenBookingPeriod = collect($openPeriods)->contains(fn ($period) => $date->betweenIncluded($period[0], $period[1]));

            $holiday = Holiday::where('date', $date->toDateString())
                ->where(function ($query) use ($studentLocationId) {
                    $query->whereNull('booking_location_id')
                        ->orWhere('booking_location_id', $studentLocationId);
                })
                ->first();
            $isHoliday = (bool) $holiday;

            $slots = [];

            if (! $isFriday && ! $isHoliday && ! $isPast && $isOpenBookingPeriod) {
                $slots = Slot::with('location')
                    ->where('date', $date->toDateString())
                    ->where('is_active', true)
                    ->when($studentLocationId, fn ($query) => $query->where('booking_location_id', $studentLocationId))
                    ->when($date->isToday(), function ($query) {
                        $query->whereTime('start_time', '>', now()->format('H:i:s'));
                    })
                    ->orderBy('booking_location_id')
                    ->orderBy('start_time')
                    ->get();
            }

            $weekDays[] = [
                'date' => $date,
                'is_off' => $isFriday || $isHoliday,
                'is_past' => $isPast,
                'is_current_booking_period' => $isOpenBookingPeriod,
                'off_reason' => $isFriday ? 'Friday holiday' : ($holiday?->reason ?? null),
                'slots' => $slots,
            ];

            $cursor->addDay();
        }

        return view('calendar.index', [
            'weekDays' => $weekDays,
            'periodLabel' => $startOfWeek->format('M d').' - '.$endOfWeek->format('M d, Y'),
            'previousPeriod' => BookingPeriod::previousStart($startOfWeek)->toDateString(),
            'nextPeriod' => BookingPeriod::nextStart($startOfWeek)->toDateString(),
            'currentPeriod' => $startOfWeek->toDateString(),
        ]);
    }

    private function openBookingPeriods(BookingRule $bookingRule): array
    {
        [$currentStart, $currentEnd] = BookingPeriod::range(now());
        $periods = [[$currentStart, $currentEnd]];

        if ((int) $bookingRule->advance_booking_days > 0 && now()->startOfDay()->gte($currentEnd->copy()->subDays($bookingRule->advance_booking_days - 1)->startOfDay())) {
            $nextStart = BookingPeriod::nextStart($currentStart);
            [$nextPeriodStart, $nextPeriodEnd] = BookingPeriod::range($nextStart);
            $periods[] = [$nextPeriodStart, $nextPeriodEnd];
        }

        return $periods;
    }
}
