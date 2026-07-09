<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Booking;
use App\Models\BookingLocation;
use App\Models\Slot;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    public function index(Request $request)
    {
        $locations = BookingLocation::orderBy('name')->get();
        $selectedLocationId = $request->user()->canManageAllBranches()
            ? $request->integer('location_id') ?: null
            : $request->user()->booking_location_id;
        $period = in_array($request->input('period'), ['today', 'week', 'month', 'custom'], true)
            ? $request->input('period')
            : 'week';

        $startDate = $request->filled('start_date')
            ? Carbon::parse($request->start_date)->startOfDay()
            : now()->copy()->startOfWeek(Carbon::SATURDAY)->startOfDay();
        $endDate = $request->filled('end_date')
            ? Carbon::parse($request->end_date)->endOfDay()
            : now()->copy()->endOfWeek(Carbon::FRIDAY)->endOfDay();

        if ($period !== 'custom') {
            [$startDate, $endDate] = match ($period) {
                'today' => [now()->startOfDay(), now()->endOfDay()],
                'month' => [now()->startOfMonth()->startOfDay(), now()->endOfMonth()->endOfDay()],
                default => [now()->copy()->startOfWeek(Carbon::SATURDAY)->startOfDay(), now()->copy()->endOfWeek(Carbon::FRIDAY)->endOfDay()],
            };
        }

        if ($startDate->gt($endDate)) {
            [$startDate, $endDate] = [$endDate->copy()->startOfDay(), $startDate->copy()->endOfDay()];
        }

        $totalUsers = User::where('role', 'student')
            ->when($selectedLocationId, fn ($query) => $query->where('booking_location_id', $selectedLocationId))
            ->count();

        $bookingScope = function ($query) use ($selectedLocationId, $startDate, $endDate) {
            $query->whereHas('slot', function ($slotQuery) use ($selectedLocationId, $startDate, $endDate) {
                $slotQuery->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
                    ->when($selectedLocationId, fn ($branchQuery) => $branchQuery->where('booking_location_id', $selectedLocationId));
            });
        };

        $totalBookings = Booking::where($bookingScope)->count();

        $todayBookings = Booking::where($bookingScope)
            ->whereHas('slot', fn ($slotQuery) => $slotQuery->whereDate('date', Carbon::today()))
            ->count();

        $cancelledBookings = Booking::where('status', 'cancelled')
            ->where($bookingScope)
            ->count();

        $activeSlots = Slot::where('is_active', true)
            ->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
            ->when($selectedLocationId, fn ($query) => $query->where('booking_location_id', $selectedLocationId))
            ->count();

        $upcomingBookings = Booking::whereIn('status', ['booked', 'rescheduled'])
            ->whereHas('slot', function ($query) use ($selectedLocationId) {
                $query->whereDate('date', '>=', Carbon::today())
                    ->when($selectedLocationId, fn ($branchQuery) => $branchQuery->where('booking_location_id', $selectedLocationId));
            })
            ->count();

        $latestBookings = Booking::with(['user', 'slot.location'])
            ->where($bookingScope)
            ->latest()
            ->take(10)
            ->get();

        $bookingsPerDay = Booking::select(
            DB::raw('slots.date as date'),
            DB::raw('count(*) as total')
        )
        ->join('slots', 'slots.id', '=', 'bookings.slot_id')
        ->whereBetween('slots.date', [$startDate->toDateString(), $endDate->toDateString()])
        ->when($selectedLocationId, function ($query) use ($selectedLocationId) {
            $query->where('slots.booking_location_id', $selectedLocationId);
        })
        ->groupBy('slots.date')
        ->orderBy('slots.date')
        ->get();

        $statusCounts = Booking::select('status', DB::raw('count(*) as total'))
        ->where($bookingScope)
        ->groupBy('status')
        ->get();

        $peakHours = Booking::select(
            DB::raw('HOUR(slots.start_time) as hour'),
            DB::raw('count(*) as total')
        )
        ->join('slots', 'slots.id', '=', 'bookings.slot_id')
        ->whereBetween('slots.date', [$startDate->toDateString(), $endDate->toDateString()])
        ->when($selectedLocationId, function ($query) use ($selectedLocationId) {
            $query->where('slots.booking_location_id', $selectedLocationId);
        })
        ->groupBy('hour')
        ->orderBy('hour')
        ->get();

        return view('admin.dashboard', compact(
            'totalUsers',
            'totalBookings',
            'todayBookings',
            'cancelledBookings',
            'activeSlots',
            'upcomingBookings',
            'latestBookings',
            'bookingsPerDay',
            'statusCounts',
            'peakHours',
            'locations',
            'selectedLocationId',
            'startDate',
            'endDate',
            'period'
        ));
    }
}
