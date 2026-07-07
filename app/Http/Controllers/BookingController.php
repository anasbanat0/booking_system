<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\BookingRule;
use App\Models\Slot;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BookingController extends Controller
{
    private array $activeStatuses = ['booked', 'rescheduled'];

    public function store(Request $request)
    {
        $request->validate([
            'slot_id' => ['required', 'exists:slots,id'],
        ]);

        $user = $request->user();

        return DB::transaction(function () use ($request, $user) {
            $slot = Slot::lockForUpdate()->findOrFail($request->slot_id);

            $this->validateNewBooking($user->id, $slot);

            Booking::create([
                'user_id' => $user->id,
                'slot_id' => $slot->id,
                'status' => 'booked',
            ]);

            $slot->increment('booked_count');

            return back()->with('success', 'Booking confirmed.');
        });
    }

    public function reschedule(Request $request, Booking $booking)
    {
        $request->validate([
            'slot_id' => ['required', 'exists:slots,id'],
        ]);

        abort_unless($booking->user_id === $request->user()->id, 403);

        return DB::transaction(function () use ($request, $booking) {
            $booking = Booking::with('slot')->lockForUpdate()->findOrFail($booking->id);

            if (!in_array($booking->status, $this->activeStatuses, true)) {
                return back()->with('error', 'This booking cannot be rescheduled.');
            }

            $currentSlotDateTime = Carbon::parse($booking->slot->date . ' ' . $booking->slot->start_time);

            if (now()->diffInHours($currentSlotDateTime, false) < 12) {
                return back()->with('error', 'Rescheduling is allowed at least 12 hours before the booking.');
            }

            $newSlot = Slot::lockForUpdate()->findOrFail($request->slot_id);

            if ($booking->slot_id === $newSlot->id) {
                return back()->with('error', 'Please choose a different slot.');
            }

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
            ]);

            return back()->with('success', 'Booking rescheduled successfully.');
        });
    }

    public function cancel(Request $request, Booking $booking)
    {
        abort_unless($booking->user_id === $request->user()->id, 403);

        return DB::transaction(function () use ($booking) {
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

            return back()->with('success', 'Booking cancelled.');
        });
    }

    public function myBookings(Request $request)
    {
        $bookings = Booking::with('slot.location')
            ->where('user_id', $request->user()->id)
            ->orderByDesc('created_at')
            ->get();

        $availableSlots = Slot::with('location')
            ->where('is_active', true)
            ->whereDate('date', '>=', now()->toDateString())
            ->whereColumn('booked_count', '<', 'capacity')
            ->orderBy('date')
            ->orderBy('booking_location_id')
            ->orderBy('start_time')
            ->get();

        return view('bookings.my', compact('bookings', 'availableSlots'));
    }

    private function validateNewBooking(int $userId, Slot $slot): void
    {
        $this->validateSlotAvailability($slot);
        $this->validateDuplicateRules($userId, $slot);
        $this->validateBookingLimits($userId, $slot);
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

        $weeklyCount = Booking::where('user_id', $userId)
            ->whereIn('status', $this->activeStatuses)
            ->whereHas('slot', function ($query) use ($slotDate) {
                $query->whereBetween('date', [
                    $slotDate->copy()->startOfWeek()->toDateString(),
                    $slotDate->copy()->endOfWeek()->toDateString(),
                ]);
            })
            ->count();

        if ($weeklyCount >= $bookingRules->weekly_limit) {
            throw ValidationException::withMessages([
                'limit' => 'Weekly limit reached (' . $bookingRules->weekly_limit . ' bookings).',
            ]);
        }

        $monthlyCount = Booking::where('user_id', $userId)
            ->whereIn('status', $this->activeStatuses)
            ->whereHas('slot', function ($query) use ($slotDate) {
                $query->whereBetween('date', [
                    $slotDate->copy()->startOfMonth()->toDateString(),
                    $slotDate->copy()->endOfMonth()->toDateString(),
                ]);
            })
            ->count();

        if ($monthlyCount >= $bookingRules->monthly_limit) {
            throw ValidationException::withMessages([
                'limit' => 'Monthly limit reached (' . $bookingRules->monthly_limit . ' bookings).',
            ]);
        }
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
}
