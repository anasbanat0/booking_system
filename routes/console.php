<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schedule;
use App\Models\Booking;
use App\Models\BookingRule;
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
                if (!$booking->user?->email || !$booking->slot) {
                    continue;
                }

                $startsAt = Carbon::parse($booking->slot->date . ' ' . $booking->slot->start_time);

                if ($startsAt->lessThan($now) || $startsAt->greaterThan($deadline)) {
                    continue;
                }

                Mail::raw(
                    'Reminder: your booking is scheduled for ' . $booking->slot->date . ' from ' . $booking->slot->start_time . ' to ' . $booking->slot->end_time . ' at ' . ($booking->slot->location?->name ?? 'the hub') . '.',
                    function ($mail) use ($booking) {
                        $mail->to($booking->user->email)->subject('Booking reminder');
                    }
                );

                $booking->update(['reminder_sent_at' => now()]);
                $sent++;
            }
        });

    $this->info($sent . ' booking reminders sent.');
})->purpose('Send booking reminders before upcoming appointments');

Schedule::command('bookings:send-reminders')->hourly()->withoutOverlapping();
