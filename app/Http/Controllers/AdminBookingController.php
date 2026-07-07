<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\AdminNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AdminBookingController extends Controller
{
    public function index(Request $request)
    {
        $query = Booking::with(['user', 'slot.location']);
        $statuses = ['booked', 'completed', 'no_show', 'cancelled', 'rescheduled'];

        if (!$request->user()->canManageAllBranches()) {
            $query->whereHas('slot', function ($slotQuery) use ($request) {
                $slotQuery->where('booking_location_id', $request->user()->booking_location_id);
            });
        }

        if ($request->filled('status') && in_array($request->status, $statuses, true)) {
            $query->where('status', $request->status);
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        $bookings = $query->latest()->paginate(10)->withQueryString();
        $statusCounts = Booking::selectRaw('status, count(*) as total')
            ->when(!$request->user()->canManageAllBranches(), function ($countQuery) use ($request) {
                $countQuery->whereHas('slot', fn ($slotQuery) => $slotQuery->where('booking_location_id', $request->user()->booking_location_id));
            })
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('admin.bookings.index', compact('bookings', 'statuses', 'statusCounts'));
    }

    public function updateStatus(Request $request, $id)
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(['booked', 'completed', 'no_show', 'cancelled', 'rescheduled'])],
        ]);

        $booking = DB::transaction(function () use ($id, $validated) {
            $booking = Booking::with(['slot', 'user'])->lockForUpdate()->findOrFail($id);
            $slot = $booking->slot()->lockForUpdate()->firstOrFail();

            if (!request()->user()->canManageAllBranches() && (int) $slot->booking_location_id !== (int) request()->user()->booking_location_id) {
                abort(403);
            }
            $activeStatuses = ['booked', 'rescheduled'];
            $wasActive = in_array($booking->status, $activeStatuses, true);
            $willBeActive = in_array($validated['status'], $activeStatuses, true);

            if (!$wasActive && $willBeActive && $slot->booked_count >= $slot->capacity) {
                throw ValidationException::withMessages([
                    'status' => 'Cannot reactivate this booking because the slot is full.',
                ]);
            }

            if ($wasActive && !$willBeActive && $slot->booked_count > 0) {
                $slot->decrement('booked_count');
            }

            if (!$wasActive && $willBeActive) {
                $slot->increment('booked_count');
            }

            $booking->status = $validated['status'];
            $booking->save();

            if (in_array($booking->status, ['cancelled', 'no_show'], true)) {
                $this->refreshUserWarning($booking);
            }

            AdminNotification::create([
                'booking_location_id' => $booking->slot?->booking_location_id,
                'user_id' => $booking->user_id,
                'booking_id' => $booking->id,
                'type' => 'booking_status_updated',
                'title' => 'Booking status updated',
                'message' => $booking->user?->name . ' status changed to ' . str_replace('_', ' ', $booking->status) . '.',
            ]);

            return $booking;
        });

        return response()->json([
            'success' => true,
            'status' => $booking->status,
        ]);
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
