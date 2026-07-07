<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\ActivityLog;
use App\Models\AdminNotification;
use App\Models\Slot;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
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

        if ($request->filled('date')) {
            $query->whereHas('slot', fn ($slotQuery) => $slotQuery->whereDate('date', $request->date));
        }

        if ($request->filled('period')) {
            $query->whereHas('slot', fn ($slotQuery) => $slotQuery->where('start_time', $request->period));
        }

        if ($request->filled('search')) {
            $query->whereHas('user', function ($userQuery) use ($request) {
                $userQuery->where('name', 'like', '%' . $request->search . '%')
                    ->orWhere('email', 'like', '%' . $request->search . '%')
                    ->orWhere('phone', 'like', '%' . $request->search . '%');
            });
        }

        $bookings = $query->latest()->paginate(10)->withQueryString();
        $statusCounts = Booking::selectRaw('status, count(*) as total')
            ->when(!$request->user()->canManageAllBranches(), function ($countQuery) use ($request) {
                $countQuery->whereHas('slot', fn ($slotQuery) => $slotQuery->where('booking_location_id', $request->user()->booking_location_id));
            })
            ->groupBy('status')
            ->pluck('total', 'status');
        $users = User::where('role', 'student')
            ->when(!$request->user()->canManageAllBranches(), function ($userQuery) use ($request) {
                $userQuery->where('booking_location_id', $request->user()->booking_location_id);
            })
            ->orderBy('name')
            ->get();
        $slots = Slot::with('location')
            ->where('is_active', true)
            ->whereDate('date', '>=', now()->toDateString())
            ->when(!$request->user()->canManageAllBranches(), function ($slotQuery) use ($request) {
                $slotQuery->where('booking_location_id', $request->user()->booking_location_id);
            })
            ->orderBy('date')
            ->orderBy('start_time')
            ->get();
        $periods = Slot::query()
            ->select('start_time', 'end_time')
            ->distinct()
            ->when(!$request->user()->canManageAllBranches(), function ($slotQuery) use ($request) {
                $slotQuery->where('booking_location_id', $request->user()->booking_location_id);
            })
            ->orderBy('start_time')
            ->get();

        return view('admin.bookings.index', compact('bookings', 'statuses', 'statusCounts', 'users', 'slots', 'periods'));
    }

    public function storeManual(Request $request)
    {
        $validated = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'slot_id' => ['required', 'exists:slots,id'],
        ]);

        $booking = DB::transaction(function () use ($request, $validated) {
            $slot = Slot::lockForUpdate()->findOrFail($validated['slot_id']);
            $user = User::findOrFail($validated['user_id']);

            if (! $request->user()->canManageAllBranches() && (int) $slot->booking_location_id !== (int) $request->user()->booking_location_id) {
                abort(403);
            }

            if ($slot->booked_count >= $slot->capacity || ! $slot->is_active) {
                throw ValidationException::withMessages([
                    'slot_id' => 'This slot is not available.',
                ]);
            }

            $exists = Booking::where('user_id', $user->id)
                ->where('slot_id', $slot->id)
                ->whereIn('status', ['booked', 'rescheduled'])
                ->exists();

            if ($exists) {
                throw ValidationException::withMessages([
                    'user_id' => 'This student already has an active booking in this slot.',
                ]);
            }

            $booking = Booking::create([
                'user_id' => $user->id,
                'slot_id' => $slot->id,
                'status' => 'booked',
            ]);

            $slot->increment('booked_count');

            AdminNotification::create([
                'booking_location_id' => $slot->booking_location_id,
                'user_id' => $user->id,
                'booking_id' => $booking->id,
                'type' => 'manual_booking_created',
                'title' => 'Manual booking created',
                'message' => $request->user()->name . ' created a booking for ' . $user->name . '.',
            ]);

            ActivityLog::record('manual_booking_created', 'Manual booking created', $request->user()->name . ' created a booking for ' . $user->name . '.', [
                'user_id' => $user->id,
                'booking_id' => $booking->id,
                'properties' => ['slot_id' => $slot->id],
            ]);

            Mail::raw('A booking was created for you on ' . $slot->date . ' from ' . $slot->start_time . ' to ' . $slot->end_time . '.', function ($mail) use ($user) {
                $mail->to($user->email)->subject('Booking created');
            });

            return $booking;
        });

        return back()->with('success', 'Manual booking #' . $booking->id . ' created successfully.');
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
            ActivityLog::record('booking_status_updated', 'Booking status updated', $booking->user?->name . ' status changed to ' . str_replace('_', ' ', $booking->status) . '.', [
                'user_id' => $booking->user_id,
                'booking_id' => $booking->id,
                'properties' => ['status' => $booking->status],
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
