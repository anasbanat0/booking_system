<?php

namespace App\Http\Controllers;

use App\Models\Slot;
use App\Models\Holiday;
use App\Models\BookingRule;
use Carbon\Carbon;
use Illuminate\Http\Request;

class CalendarController extends Controller
{
    public function index(Request $request)
    {
        $selectedDate = $request->filled('period')
            ? Carbon::parse($request->period)
            : now();
        [$startOfWeek, $endOfWeek] = $this->monthWeekRange($selectedDate);
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

            if (!$isFriday && !$isHoliday && !$isPast && $isOpenBookingPeriod) {
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
                'slots' => $slots
            ];

            $cursor->addDay();
        }

        return view('calendar.index', [
            'weekDays' => $weekDays,
            'periodLabel' => $startOfWeek->format('M d') . ' - ' . $endOfWeek->format('M d, Y'),
            'previousPeriod' => $this->previousPeriodDate($startOfWeek)->toDateString(),
            'nextPeriod' => $this->nextPeriodDate($startOfWeek)->toDateString(),
            'currentPeriod' => $startOfWeek->toDateString(),
        ]);
    }

    private function monthWeekRange(Carbon $date): array
    {
        $day = $date->day;
        $startDay = match (true) {
            $day <= 7 => 1,
            $day <= 14 => 8,
            $day <= 21 => 15,
            default => 22,
        };
        $endDay = match ($startDay) {
            1 => 7,
            8 => 14,
            15 => 21,
            default => $date->copy()->endOfMonth()->day,
        };

        return [
            $date->copy()->startOfMonth()->day($startDay)->startOfDay(),
            $date->copy()->startOfMonth()->day($endDay)->endOfDay(),
        ];
    }

    private function nextPeriodDate(Carbon $periodStart): Carbon
    {
        return match ($periodStart->day) {
            1 => $periodStart->copy()->day(8),
            8 => $periodStart->copy()->day(15),
            15 => $periodStart->copy()->day(22),
            default => $periodStart->copy()->addMonthNoOverflow()->startOfMonth(),
        };
    }

    private function previousPeriodDate(Carbon $periodStart): Carbon
    {
        if ($periodStart->day === 1) {
            return $periodStart->copy()->subMonthNoOverflow()->day(22);
        }

        return match ($periodStart->day) {
            8 => $periodStart->copy()->day(1),
            15 => $periodStart->copy()->day(8),
            default => $periodStart->copy()->day(15),
        };
    }

    private function openBookingPeriods(BookingRule $bookingRule): array
    {
        [$currentStart, $currentEnd] = $this->monthWeekRange(now());
        $periods = [[$currentStart, $currentEnd]];

        if ((int) $bookingRule->advance_booking_days > 0 && now()->startOfDay()->gte($currentEnd->copy()->subDays($bookingRule->advance_booking_days - 1)->startOfDay())) {
            $nextStart = $this->nextPeriodDate($currentStart);
            [$nextPeriodStart, $nextPeriodEnd] = $this->monthWeekRange($nextStart);
            $periods[] = [$nextPeriodStart, $nextPeriodEnd];
        }

        return $periods;
    }
}
