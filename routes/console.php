<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schedule;
use App\Models\Booking;
use App\Models\BookingRule;
use App\Services\SlotGenerationService;
use App\Services\WhatsAppService;
use Carbon\Carbon;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('bookings:send-reminders', function () {
    $rules = BookingRule::current();
    $now = now();
    $deadline = $now->copy()->addHours($rules->reminder_hours_before);
    $sent = 0;

    Booking::with(['user', 'slot.location'])
        ->whereNull('reminder_sent_at')
        ->whereIn('status', ['booked', 'rescheduled'])
        ->whereHas('slot', function ($query) use ($now, $deadline) {
            $query->whereBetween('date', [
                $now->toDateString(),
                $deadline->toDateString(),
            ]);
        })
        ->chunkById(100, function ($bookings) use ($now, $deadline, &$sent) {
            foreach ($bookings as $booking) {
                if (!$booking->user || !$booking->slot) {
                    continue;
                }

                $startsAt = Carbon::parse($booking->slot->date . ' ' . $booking->slot->start_time);

                if ($startsAt->lessThan($now) || $startsAt->greaterThan($deadline)) {
                    continue;
                }

                $attempted = false;

                if ($booking->user->email) {
                    try {
                        Mail::raw(
                            'Reminder: your booking is scheduled for ' . $booking->slot->date . ' from ' . $booking->slot->start_time . ' to ' . $booking->slot->end_time . ' at ' . ($booking->slot->location?->name ?? 'the hub') . '.',
                            function ($mail) use ($booking) {
                                $mail->to($booking->user->email)->subject('Booking reminder');
                            }
                        );

                        $attempted = true;
                    } catch (\Throwable $exception) {
                        $this->warn('Reminder email failed for booking #' . $booking->id . ': ' . $exception->getMessage());
                    }
                }

                if (app(WhatsAppService::class)->sendBookingReminder($booking)) {
                    $attempted = true;
                }

                if ($attempted) {
                    $booking->update(['reminder_sent_at' => now()]);
                    $sent++;
                }
            }
        });

    $this->info($sent . ' booking reminders sent.');
})->purpose('Send booking reminders before upcoming appointments');

Schedule::command('bookings:send-reminders')->hourly()->withoutOverlapping();

Artisan::command('slots:generate-upcoming', function () {
    $result = app(SlotGenerationService::class)->generateUpcomingPeriod(now());

    if (!$result['generated']) {
        $this->info($result['reason']);
        return;
    }

    $this->info(sprintf(
        'Slots generated for %s to %s. Created: %d. Updated: %d.',
        $result['start_date'],
        $result['end_date'],
        $result['created'],
        $result['updated'],
    ));
})->purpose('Generate booking slots for the next visible booking period 48 hours before it opens');

Schedule::command('slots:generate-upcoming')->hourly()->withoutOverlapping();
