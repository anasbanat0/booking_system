<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\AdminNotification;
use App\Models\BookingLocation;
use App\Models\Slot;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AdminUserCalendarController extends Controller
{
    public function index(Request $request)
    {
        $view = in_array($request->input('view', 'day'), ['day', 'week', 'month'], true)
            ? $request->input('view', 'day')
            : 'day';
        $date = $request->filled('date') ? Carbon::parse($request->date) : now();
        $startOfWeek = $request->filled('week')
            ? Carbon::parse($request->week)->startOfWeek(Carbon::SATURDAY)
            : $date->copy()->startOfWeek(Carbon::SATURDAY);
        $monthStart = $date->copy()->startOfMonth();
        $monthEnd = $date->copy()->endOfMonth();

        $days = match ($view) {
            'month' => collect(range(0, $monthStart->diffInDays($monthEnd)))->map(fn ($day) => $monthStart->copy()->addDays($day)),
            'week' => collect(range(0, 6))->map(fn ($day) => $startOfWeek->copy()->addDays($day)),
            default => collect([$date->copy()]),
        };

        $locations = BookingLocation::orderBy('name')->get();
        $selectedLocationId = $request->user()->canManageAllBranches()
            ? $request->integer('location_id')
            : $request->user()->booking_location_id;

        $slots = Slot::with(['location', 'bookings.user'])
            ->whereBetween('date', [
                $days->first()->toDateString(),
                $days->last()->toDateString(),
            ])
            ->when($selectedLocationId, fn ($query) => $query->where('booking_location_id', $selectedLocationId))
            ->orderBy('date')
            ->orderBy('booking_location_id')
            ->orderBy('start_time')
            ->get();

        $slotPeriodMap = $slots
            ->groupBy('booking_location_id')
            ->flatMap(function ($locationSlots) {
                return $locationSlots
                    ->unique(fn ($slot) => $slot->start_time . '-' . $slot->end_time)
                    ->sortBy('start_time')
                    ->values()
                    ->mapWithKeys(fn ($slot, $index) => [
                        $slot->booking_location_id . '|' . $slot->start_time . '|' . $slot->end_time => $index + 1,
                    ]);
            });

        $slotsByDayPeriod = $slots->groupBy(function ($slot) use ($slotPeriodMap) {
            $period = $slotPeriodMap[$slot->booking_location_id . '|' . $slot->start_time . '|' . $slot->end_time] ?? 1;

            return $slot->date . '|' . $period;
        });
        $slotsByDay = $slots->groupBy('date');
        $periods = range(1, max(3, (int) $slotPeriodMap->max()));

        $statusCounts = Booking::selectRaw('status, count(*) as total')
            ->whereHas('slot', function ($query) use ($days) {
                $query->whereBetween('date', [
                    $days->first()->toDateString(),
                    $days->last()->toDateString(),
                ]);
            })
            ->when($selectedLocationId, function ($query) use ($selectedLocationId) {
                $query->whereHas('slot', fn ($slotQuery) => $slotQuery->where('booking_location_id', $selectedLocationId));
            })
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('admin.users.calendar', [
            'days' => $days,
            'periods' => $periods,
            'slotsByDayPeriod' => $slotsByDayPeriod,
            'slotsByDay' => $slotsByDay,
            'statusCounts' => $statusCounts,
            'weekStart' => $startOfWeek,
            'previousDate' => match ($view) {
                'month' => $date->copy()->subMonthNoOverflow()->toDateString(),
                'week' => $startOfWeek->copy()->subWeek()->toDateString(),
                default => $date->copy()->subDay()->toDateString(),
            },
            'nextDate' => match ($view) {
                'month' => $date->copy()->addMonthNoOverflow()->toDateString(),
                'week' => $startOfWeek->copy()->addWeek()->toDateString(),
                default => $date->copy()->addDay()->toDateString(),
            },
            'statuses' => ['booked', 'rescheduled', 'completed', 'cancelled', 'no_show'],
            'view' => $view,
            'selectedDate' => $date,
            'rangeEnd' => $view === 'month' ? $monthEnd : ($view === 'week' ? $startOfWeek->copy()->addDays(6) : $date),
            'locations' => $locations,
            'selectedLocationId' => $selectedLocationId,
        ]);
    }

    public function updateBooking(Request $request, Booking $booking)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($booking->user_id)],
            'phone' => ['nullable', 'string', 'max:40'],
            'status' => ['required', Rule::in(['booked', 'rescheduled', 'completed', 'cancelled', 'no_show'])],
        ]);

        DB::transaction(function () use ($booking, $validated) {
            $booking = Booking::with(['slot', 'user'])->lockForUpdate()->findOrFail($booking->id);
            $slot = $booking->slot()->lockForUpdate()->firstOrFail();

            if (!request()->user()->canManageAllBranches() && (int) $slot->booking_location_id !== (int) request()->user()->booking_location_id) {
                abort(403);
            }
            $activeStatuses = ['booked', 'rescheduled'];
            $wasActive = in_array($booking->status, $activeStatuses, true);
            $willBeActive = in_array($validated['status'], $activeStatuses, true);

            if ($wasActive && !$willBeActive && $slot->booked_count > 0) {
                $slot->decrement('booked_count');
            }

            if (!$wasActive && $willBeActive && $slot->booked_count >= $slot->capacity) {
                throw ValidationException::withMessages([
                    'status' => 'Cannot activate this booking because the slot is full.',
                ]);
            }

            if (!$wasActive && $willBeActive) {
                $slot->increment('booked_count');
            }

            $booking->user->update([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'] ?? null,
            ]);

            $booking->update([
                'status' => $validated['status'],
            ]);

            if (in_array($booking->status, ['cancelled', 'no_show'], true)) {
                $this->refreshUserWarning($booking);
            }

            AdminNotification::create([
                'booking_location_id' => $booking->slot?->booking_location_id,
                'user_id' => $booking->user_id,
                'booking_id' => $booking->id,
                'type' => 'booking_status_updated',
                'title' => 'Calendar booking updated',
                'message' => $booking->user?->name . ' was updated from Users Calendar.',
            ]);
        });

        return back()->with('success', 'Booking and student information updated.');
    }

    private function refreshUserWarning(Booking $booking): void
    {
        $cancelledCount = Booking::where('user_id', $booking->user_id)
            ->where('status', 'cancelled')
            ->count();

        $noShowCount = Booking::where('user_id', $booking->user_id)
            ->where('status', 'no_show')
            ->count();

        if ($cancelledCount < 3 && $noShowCount < 3) {
            return;
        }

        $reasons = [];

        if ($cancelledCount >= 3) {
            $reasons[] = '3 cancellations';
        }

        if ($noShowCount >= 3) {
            $reasons[] = '3 no-shows';
        }

        $booking->user->update([
            'booking_warning_count' => max($cancelledCount, $noShowCount),
            'booking_warning_reason' => implode(' and ', $reasons),
            'booking_warning_at' => now(),
        ]);
    }
}
