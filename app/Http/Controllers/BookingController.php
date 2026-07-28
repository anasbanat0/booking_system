<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\ActivityLog;
use App\Models\AdminNotification;
use App\Models\BookingRule;
use App\Models\Slot;
use App\Services\WhatsAppService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use PDOException;

class BookingController extends Controller
{
    private array $activeStatuses = ['booked', 'rescheduled'];
    private array $weeklyQuotaStatuses = ['booked', 'rescheduled', 'completed'];
    private array $monthlyQuotaStatuses = ['booked', 'rescheduled', 'completed', 'cancelled'];

    public function store(Request $request)
    {
        $request->validate([
            'slot_id' => ['required', 'exists:slots,id'],
        ]);

        $user = $request->user();

        $email = null;
        $whatsapp = null;

        $response = $this->transactionWithReconnect(function () use ($request, $user, &$email, &$whatsapp) {
            $slot = Slot::lockForUpdate()->findOrFail($request->slot_id);

            $this->validateNewBooking($user->id, $slot);

            $booking = Booking::create([
                'user_id' => $user->id,
                'slot_id' => $slot->id,
                'status' => 'booked',
            ]);

            $slot->increment('booked_count');
            $this->notifyAdmins($booking, 'booking_created', 'New booking', $user->name . ' booked ' . $slot->date . ' from ' . $slot->start_time . ' to ' . $slot->end_time . '.');
            $email = $this->emailPayload($booking, 'Booking confirmed', 'Your booking is confirmed for ' . $slot->date . ' from ' . $slot->start_time . ' to ' . $slot->end_time . '.');
            $whatsapp = ['type' => 'booking_confirmed', 'booking_id' => $booking->id];
            ActivityLog::record('booking_created', 'Booking created', $user->name . ' created a booking.', [
                'user_id' => $user->id,
                'booking_id' => $booking->id,
                'properties' => ['slot_id' => $slot->id],
            ]);

            return back()->with('success', 'Booking confirmed.');
        });

        $this->sendEmailAfterResponse($email);
        $this->sendWhatsAppAfterResponse($whatsapp);

        return $response;
    }

    public function reschedule(Request $request, Booking $booking)
    {
        $request->validate([
            'slot_id' => ['required', 'exists:slots,id'],
        ]);

        abort_unless($booking->user_id === $request->user()->id, 403);

        $email = null;
        $whatsapp = null;

        $response = $this->transactionWithReconnect(function () use ($request, $booking, &$email, &$whatsapp) {
            $booking = Booking::with('slot')->lockForUpdate()->findOrFail($booking->id);

            if (!in_array($booking->status, $this->activeStatuses, true)) {
                return back()->with('error', 'This booking cannot be rescheduled.');
            }

            if (!$booking->slot) {
                return back()->with('error', 'This booking is linked to an unavailable slot. Please contact support.');
            }

            $currentSlotDateTime = Carbon::parse($booking->slot->date . ' ' . $booking->slot->start_time);
            $bookingRules = BookingRule::current();

            if (now()->diffInHours($currentSlotDateTime, false) < $bookingRules->reschedule_cutoff_hours) {
                return back()->with('error', 'Rescheduling is allowed at least ' . $bookingRules->reschedule_cutoff_hours . ' hours before the booking.');
            }

            $newSlot = Slot::lockForUpdate()->findOrFail($request->slot_id);

            if ($booking->slot_id === $newSlot->id) {
                return back()->with('error', 'Please choose a different slot.');
            }

            $this->validateSlotIsFuture($newSlot);
            $this->validateSlotBranch($newSlot);
            $this->validateSlotAvailability($newSlot);
            $this->validateDuplicateRules($booking->user_id, $newSlot, $booking->id);

            if ($booking->slot && $booking->slot->booked_count > 0) {
                $booking->slot->decrement('booked_count');
            }

            $newSlot->increment('booked_count');

            $booking->update([
                'slot_id' => $newSlot->id,
                'status' => 'rescheduled',
                'rescheduled_at' => now(),
                'reminder_sent_at' => null,
            ]);
            $this->notifyAdmins($booking, 'booking_rescheduled', 'Booking rescheduled', $booking->user->name . ' rescheduled to ' . $newSlot->date . ' from ' . $newSlot->start_time . ' to ' . $newSlot->end_time . '.');
            $email = $this->emailPayload($booking, 'Booking rescheduled', 'Your booking was rescheduled to ' . $newSlot->date . ' from ' . $newSlot->start_time . ' to ' . $newSlot->end_time . '.');
            $whatsapp = ['type' => 'booking_rescheduled', 'booking_id' => $booking->id];
            ActivityLog::record('booking_rescheduled', 'Booking rescheduled', $booking->user->name . ' rescheduled a booking.', [
                'user_id' => $booking->user_id,
                'booking_id' => $booking->id,
                'properties' => ['slot_id' => $newSlot->id],
            ]);

            return back()->with('success', 'Booking rescheduled successfully.');
        });

        $this->sendEmailAfterResponse($email);
        $this->sendWhatsAppAfterResponse($whatsapp);

        return $response;
    }

    public function cancel(Request $request, Booking $booking)
    {
        abort_unless($booking->user_id === $request->user()->id, 403);

        $email = null;
        $whatsapp = null;

        $response = $this->transactionWithReconnect(function () use ($booking, &$email, &$whatsapp) {
            $booking = Booking::with('slot', 'user')->lockForUpdate()->findOrFail($booking->id);

            if (!in_array($booking->status, $this->activeStatuses, true)) {
                return back()->with('error', 'This booking cannot be cancelled.');
            }

            $booking->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
            ]);

            if ($booking->slot && $booking->slot->booked_count > 0) {
                $booking->slot->decrement('booked_count');
            }

            $this->refreshUserWarning($booking->user);
            $bookingDate = $booking->slot?->date ?? 'an unavailable slot';
            $this->notifyAdmins($booking, 'booking_cancelled', 'Booking cancelled', $booking->user->name . ' cancelled a booking on ' . $bookingDate . '.');
            $email = $this->emailPayload($booking, 'Booking cancelled', 'Your booking on ' . $bookingDate . ' has been cancelled.');
            $whatsapp = ['type' => 'booking_cancelled', 'booking_id' => $booking->id];
            ActivityLog::record('booking_cancelled', 'Booking cancelled', $booking->user->name . ' cancelled a booking.', [
                'user_id' => $booking->user_id,
                'booking_id' => $booking->id,
            ]);

            return back()->with('success', 'Booking cancelled.');
        });

        $this->sendEmailAfterResponse($email);
        $this->sendWhatsAppAfterResponse($whatsapp);

        return $response;
    }

    public function myBookings(Request $request)
    {
        $bookings = Booking::with('slot.location')
            ->where('user_id', $request->user()->id)
            ->orderByDesc('created_at')
            ->get();

        $availableSlots = Slot::with('location')
            ->where('is_active', true)
            ->when($request->user()->booking_location_id, fn ($query) => $query->where('booking_location_id', $request->user()->booking_location_id))
            ->where(function ($query) {
                $query->whereDate('date', '>', now()->toDateString())
                    ->orWhere(function ($todayQuery) {
                        $todayQuery->whereDate('date', now()->toDateString())
                            ->whereTime('start_time', '>', now()->format('H:i:s'));
                    });
            })
            ->whereColumn('booked_count', '<', 'capacity')
            ->orderBy('date')
            ->orderBy('booking_location_id')
            ->orderBy('start_time')
            ->get();

        $bookingRules = BookingRule::current();
        [$weekStart, $weekEnd] = $this->monthWeekRange(now());
        [$monthStart, $monthEnd] = [now()->copy()->startOfMonth(), now()->copy()->endOfMonth()];
        $monthlyLimit = $this->effectiveMonthlyLimit($request->user(), $bookingRules);

        $weeklyUsed = Booking::where('user_id', $request->user()->id)
            ->whereIn('status', $this->weeklyQuotaStatuses)
            ->whereHas('slot', function ($query) use ($weekStart, $weekEnd) {
                $query->whereBetween('date', [
                    $weekStart->toDateString(),
                    $weekEnd->toDateString(),
                ]);
            })
            ->count();
        $monthlyUsed = Booking::where('user_id', $request->user()->id)
            ->whereIn('status', $this->monthlyQuotaStatuses)
            ->whereHas('slot', function ($query) use ($monthStart, $monthEnd) {
                $query->whereBetween('date', [
                    $monthStart->toDateString(),
                    $monthEnd->toDateString(),
                ]);
            })
            ->count();
        $remaining = [
            'weekly' => max($bookingRules->weekly_limit - $weeklyUsed, 0),
            'monthly' => max($monthlyLimit - $monthlyUsed, 0),
            'weeklyLimit' => $bookingRules->weekly_limit,
            'monthlyLimit' => $monthlyLimit,
            'rescheduleCutoffHours' => $bookingRules->reschedule_cutoff_hours,
        ];

        return view('bookings.my', compact('bookings', 'availableSlots', 'remaining'));
    }

    private function validateNewBooking(int $userId, Slot $slot): void
    {
        $user = request()->user();

        $this->validateSlotIsFuture($slot);
        $this->validateSlotBranch($slot);
        $this->validateSlotAvailability($slot);
        $this->validateDuplicateRules($userId, $slot);
        $this->validateBookingLimits($userId, $slot);
    }

    private function validateSlotIsFuture(Slot $slot): void
    {
        $startsAt = Carbon::parse($slot->date . ' ' . $slot->start_time);

        if ($startsAt->lessThanOrEqualTo(now())) {
            throw ValidationException::withMessages([
                'slot' => 'This slot has already passed.',
            ]);
        }
    }

    private function validateSlotBranch(Slot $slot): void
    {
        $user = request()->user();

        if ($user?->booking_location_id && (int) $slot->booking_location_id !== (int) $user->booking_location_id) {
            throw ValidationException::withMessages([
                'slot' => 'This slot is not available for your branch.',
            ]);
        }
    }

    private function validateSlotAvailability(Slot $slot): void
    {
        if (!$slot->is_active) {
            throw ValidationException::withMessages([
                'slot' => 'This slot is not available.',
            ]);
        }

        if ($slot->booked_count >= $slot->capacity) {
            throw ValidationException::withMessages([
                'slot' => 'Slot is fully booked.',
            ]);
        }
    }

    private function validateDuplicateRules(int $userId, Slot $slot, ?int $ignoreBookingId = null): void
    {
        $bookingRules = BookingRule::current();
        $slotDate = Carbon::parse($slot->date);

        $usedSlotQuery = Booking::where('user_id', $userId)
            ->where('slot_id', $slot->id);

        if ($ignoreBookingId) {
            $usedSlotQuery->whereKeyNot($ignoreBookingId);
        }

        if ($usedSlotQuery->exists()) {
            throw ValidationException::withMessages([
                'slot' => 'You already used this exact slot before. Please choose another time.',
            ]);
        }

        $sameSlotQuery = Booking::where('user_id', $userId)
            ->where('slot_id', $slot->id)
            ->whereIn('status', $this->activeStatuses);

        if ($ignoreBookingId) {
            $sameSlotQuery->whereKeyNot($ignoreBookingId);
        }

        if ($sameSlotQuery->exists()) {
            throw ValidationException::withMessages([
                'slot' => 'Already booked this slot.',
            ]);
        }

        if ($bookingRules->enforce_one_booking_per_day) {
            $sameDayQuery = Booking::where('user_id', $userId)
                ->whereIn('status', $this->activeStatuses)
                ->whereHas('slot', function ($query) use ($slotDate) {
                    $query->whereDate('date', $slotDate->toDateString());
                });

            if ($ignoreBookingId) {
                $sameDayQuery->whereKeyNot($ignoreBookingId);
            }

            if ($sameDayQuery->exists()) {
                throw ValidationException::withMessages([
                    'slot' => 'You can only book once per day.',
                ]);
            }
        }

        if ($bookingRules->enforce_unique_time_period) {
            $sameTimeQuery = Booking::where('user_id', $userId)
                ->whereIn('status', $this->activeStatuses)
                ->whereHas('slot', function ($query) use ($slot) {
                    $query->whereDate('date', $slot->date)
                        ->where('start_time', $slot->start_time)
                        ->where('end_time', $slot->end_time);
                });

            if ($ignoreBookingId) {
                $sameTimeQuery->whereKeyNot($ignoreBookingId);
            }

            if ($sameTimeQuery->exists()) {
                throw ValidationException::withMessages([
                    'slot' => 'You already have a booking in this time period.',
                ]);
            }
        }
    }

    private function validateBookingLimits(int $userId, Slot $slot): void
    {
        $bookingRules = BookingRule::current();
        $slotDate = Carbon::parse($slot->date);
        [$weekStart, $weekEnd] = $this->monthWeekRange($slotDate);
        $monthlyLimit = $this->effectiveMonthlyLimit(request()->user(), $bookingRules, $slotDate);

        if (!$this->slotIsInsideOpenBookingPeriod($slotDate, $bookingRules)) {
            throw ValidationException::withMessages([
                'limit' => 'Booking is not open for this weekly period yet.',
            ]);
        }

        $weeklyCount = Booking::where('user_id', $userId)
            ->whereIn('status', $this->weeklyQuotaStatuses)
            ->whereHas('slot', function ($query) use ($weekStart, $weekEnd) {
                $query->whereBetween('date', [
                    $weekStart->toDateString(),
                    $weekEnd->toDateString(),
                ]);
            })
            ->count();

        if ($weeklyCount >= $bookingRules->weekly_limit) {
            throw ValidationException::withMessages([
                'limit' => 'Weekly limit reached for this month period (' . $bookingRules->weekly_limit . ' bookings).',
            ]);
        }

        $monthlyCount = Booking::where('user_id', $userId)
            ->whereIn('status', $this->monthlyQuotaStatuses)
            ->whereHas('slot', function ($query) use ($slotDate) {
                $query->whereBetween('date', [
                    $slotDate->copy()->startOfMonth()->toDateString(),
                    $slotDate->copy()->endOfMonth()->toDateString(),
                ]);
            })
            ->count();

        if ($monthlyCount >= $monthlyLimit) {
            throw ValidationException::withMessages([
                'limit' => 'Monthly limit reached (' . $monthlyLimit . ' bookings).',
            ]);
        }
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

    private function effectiveMonthlyLimit($user, BookingRule $bookingRules, ?Carbon $targetDate = null): int
    {
        $targetDate ??= now();
        $createdAt = Carbon::parse($user->created_at);

        if (!$createdAt->isSameMonth($targetDate)) {
            return $bookingRules->monthly_limit;
        }

        $bucketIndex = match (true) {
            $createdAt->day <= 7 => 0,
            $createdAt->day <= 14 => 1,
            $createdAt->day <= 21 => 2,
            default => 3,
        };

        return min($bookingRules->monthly_limit, max(0, 4 - $bucketIndex) * $bookingRules->weekly_limit);
    }

    private function slotIsInsideOpenBookingPeriod(Carbon $slotDate, BookingRule $bookingRule): bool
    {
        [$currentStart, $currentEnd] = $this->monthWeekRange(now());

        if ($slotDate->betweenIncluded($currentStart, $currentEnd)) {
            return true;
        }

        if ((int) $bookingRule->advance_booking_days <= 0) {
            return false;
        }

        $openNextFrom = $currentEnd->copy()->subDays($bookingRule->advance_booking_days - 1)->startOfDay();

        if (now()->startOfDay()->lt($openNextFrom)) {
            return false;
        }

        $nextStart = match ($currentStart->day) {
            1 => $currentStart->copy()->day(8),
            8 => $currentStart->copy()->day(15),
            15 => $currentStart->copy()->day(22),
            default => $currentStart->copy()->addMonthNoOverflow()->startOfMonth(),
        };
        [$nextPeriodStart, $nextPeriodEnd] = $this->monthWeekRange($nextStart);

        return $slotDate->betweenIncluded($nextPeriodStart, $nextPeriodEnd);
    }

    private function refreshUserWarning($user): void
    {
        $cancelledCount = Booking::where('user_id', $user->id)
            ->where('status', 'cancelled')
            ->count();

        $noShowCount = Booking::where('user_id', $user->id)
            ->where('status', 'no_show')
            ->count();

        if ($cancelledCount >= 3 || $noShowCount >= 3) {
            $reasons = [];

            if ($cancelledCount >= 3) {
                $reasons[] = '3 cancellations';
            }

            if ($noShowCount >= 3) {
                $reasons[] = '3 no-shows';
            }

            $user->update([
                'booking_warning_count' => max($cancelledCount, $noShowCount),
                'booking_warning_reason' => implode(' and ', $reasons),
                'booking_warning_at' => now(),
            ]);
        }
    }

    private function notifyAdmins(Booking $booking, string $type, string $title, string $message): void
    {
        $booking->loadMissing('slot');

        AdminNotification::create([
            'booking_location_id' => $booking->slot?->booking_location_id,
            'user_id' => $booking->user_id,
            'booking_id' => $booking->id,
            'type' => $type,
            'title' => $title,
            'message' => $message,
        ]);
    }

    private function emailPayload(Booking $booking, string $subject, string $message): ?array
    {
        $booking->loadMissing('user');

        if (!$booking->user?->email) {
            return null;
        }

        return [
            'to' => $booking->user->email,
            'subject' => $subject,
            'message' => $message,
        ];
    }

    private function sendEmailAfterResponse(?array $email): void
    {
        if (!$email) {
            return;
        }

        app()->terminating(function () use ($email) {
            try {
                Mail::raw($email['message'], function ($mail) use ($email) {
                    $mail->to($email['to'])->subject($email['subject']);
                });
            } catch (\Throwable $exception) {
                Log::warning('Booking email failed after response.', [
                    'to' => $email['to'],
                    'subject' => $email['subject'],
                    'exception' => $exception->getMessage(),
                ]);
            }
        });
    }

    private function sendWhatsAppAfterResponse(?array $whatsapp): void
    {
        if (!$whatsapp) {
            return;
        }

        app()->terminating(function () use ($whatsapp) {
            try {
                $booking = Booking::with(['user', 'slot.location'])->find($whatsapp['booking_id']);

                if (!$booking) {
                    return;
                }

                $service = app(WhatsAppService::class);

                match ($whatsapp['type']) {
                    'booking_confirmed' => $service->sendBookingConfirmed($booking),
                    'booking_rescheduled' => $service->sendBookingRescheduled($booking),
                    'booking_cancelled' => $service->sendBookingCancelled($booking),
                    default => false,
                };
            } catch (\Throwable $exception) {
                Log::warning('Booking WhatsApp failed after response.', [
                    'type' => $whatsapp['type'] ?? null,
                    'booking_id' => $whatsapp['booking_id'] ?? null,
                    'exception' => $exception->getMessage(),
                ]);
            }
        });
    }

    private function transactionWithReconnect(callable $callback)
    {
        $callbackStarted = false;

        try {
            return DB::transaction(function () use ($callback, &$callbackStarted) {
                $callbackStarted = true;

                return $callback();
            }, 3);
        } catch (PDOException $exception) {
            if ($callbackStarted || !$this->isMysqlGoneAway($exception)) {
                throw $exception;
            }

            DB::purge();

            return DB::transaction($callback, 1);
        }
    }

    private function isMysqlGoneAway(PDOException $exception): bool
    {
        return str_contains($exception->getMessage(), '2006 MySQL server has gone away');
    }
}
