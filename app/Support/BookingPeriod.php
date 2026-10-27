<?php

namespace App\Support;

use Carbon\Carbon;

final class BookingPeriod
{
    public static function range(Carbon $date): array
    {
        $startDay = intdiv($date->day - 1, 7) * 7 + 1;
        $endDay = min($startDay + 6, $date->daysInMonth);

        return [
            $date->copy()->startOfMonth()->day($startDay)->startOfDay(),
            $date->copy()->startOfMonth()->day($endDay)->endOfDay(),
        ];
    }

    public static function nextStart(Carbon $periodStart): Carbon
    {
        [, $periodEnd] = self::range($periodStart);

        return $periodEnd->copy()->addDay()->startOfDay();
    }

    public static function previousStart(Carbon $periodStart): Carbon
    {
        [$currentStart] = self::range($periodStart);
        [$previousStart] = self::range($currentStart->copy()->subDay());

        return $previousStart;
    }

    public static function forMonth(Carbon $month): array
    {
        $periods = [];
        $cursor = $month->copy()->startOfMonth();

        while ($cursor->isSameMonth($month)) {
            $periods[] = self::range($cursor);
            $cursor = self::nextStart($cursor);
        }

        return $periods;
    }
}
