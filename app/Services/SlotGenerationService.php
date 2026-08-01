<?php

namespace App\Services;

use App\Models\ClosedPeriod;
use App\Models\Holiday;
use App\Models\Slot;
use App\Models\SlotTemplate;
use Carbon\Carbon;

class SlotGenerationService
{
    public function generateUpcomingPeriod(?Carbon $now = null): array
    {
        $now ??= now();
        [$currentStart, $currentEnd] = $this->monthPeriodRange($now);
        $opensAt = $currentEnd->copy()->subHours(48);

        if ($now->lt($opensAt)) {
            return [
                'generated' => false,
                'reason' => 'Next period is not inside the 48-hour generation window yet.',
                'start_date' => null,
                'end_date' => null,
                'created' => 0,
                'updated' => 0,
            ];
        }

        [$nextStart, $nextEnd] = $this->nextPeriodRange($currentStart);

        return $this->generateRange($nextStart, $nextEnd);
    }

    public function generateRange(Carbon $startDate, Carbon $endDate): array
    {
        $slotTemplates = SlotTemplate::with('location')
            ->where('is_active', true)
            ->whereHas('location', fn ($query) => $query->where('is_active', true))
            ->get();

        $created = 0;
        $updated = 0;
        $skipped = 0;

        if ($slotTemplates->isEmpty()) {
            return [
                'generated' => false,
                'reason' => 'No active slot templates found.',
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
                'created' => 0,
                'updated' => 0,
                'skipped' => 0,
            ];
        }

        $date = $startDate->copy()->startOfDay();
        $rangeEnd = $endDate->copy()->startOfDay();

        while ($date->lte($rangeEnd)) {
            if ($date->isFriday()) {
                $skipped++;
                $date->addDay();
                continue;
            }

            foreach ($slotTemplates as $template) {
                if ($this->isHoliday($date, $template->booking_location_id) || $this->isClosedPeriod($date, $template)) {
                    continue;
                }

                $slot = Slot::updateOrCreate(
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

                $slot->wasRecentlyCreated ? $created++ : $updated++;
            }

            $date->addDay();
        }

        return [
            'generated' => true,
            'reason' => null,
            'start_date' => $startDate->toDateString(),
            'end_date' => $endDate->toDateString(),
            'created' => $created,
            'updated' => $updated,
            'skipped' => $skipped,
        ];
    }

    private function isHoliday(Carbon $date, int $locationId): bool
    {
        return Holiday::where('date', $date->toDateString())
            ->where(function ($query) use ($locationId) {
                $query->whereNull('booking_location_id')
                    ->orWhere('booking_location_id', $locationId);
            })
            ->exists();
    }

    private function isClosedPeriod(Carbon $date, SlotTemplate $template): bool
    {
        return ClosedPeriod::where('date', $date->toDateString())
            ->where(function ($query) use ($template) {
                $query->whereNull('booking_location_id')
                    ->orWhere('booking_location_id', $template->booking_location_id);
            })
            ->whereTime('start_time', '<', $template->end_time)
            ->whereTime('end_time', '>', $template->start_time)
            ->exists();
    }

    private function monthPeriodRange(Carbon $date): array
    {
        $startDay = match (true) {
            $date->day <= 7 => 1,
            $date->day <= 14 => 8,
            $date->day <= 21 => 15,
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

    private function nextPeriodRange(Carbon $periodStart): array
    {
        $nextStart = match ($periodStart->day) {
            1 => $periodStart->copy()->day(8),
            8 => $periodStart->copy()->day(15),
            15 => $periodStart->copy()->day(22),
            default => $periodStart->copy()->addMonthNoOverflow()->startOfMonth(),
        };

        return $this->monthPeriodRange($nextStart);
    }
}
