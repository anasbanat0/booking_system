<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Booking;
use App\Models\Slot;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    public function index(Request $request)
    {
        $locationId = $request->user()->canManageAllBranches() ? null : $request->user()->booking_location_id;

        $totalUsers = User::count();

        $totalBookings = Booking::when($locationId, function ($query) use ($locationId) {
            $query->whereHas('slot', fn ($slotQuery) => $slotQuery->where('booking_location_id', $locationId));
        })->count();

        $todayBookings = Booking::whereDate('created_at', Carbon::today())
            ->when($locationId, function ($query) use ($locationId) {
                $query->whereHas('slot', fn ($slotQuery) => $slotQuery->where('booking_location_id', $locationId));
            })
            ->count();

        $cancelledBookings = Booking::where('status', 'cancelled')
            ->when($locationId, function ($query) use ($locationId) {
                $query->whereHas('slot', fn ($slotQuery) => $slotQuery->where('booking_location_id', $locationId));
            })
            ->count();

        $activeSlots = Slot::where('is_active', true)
            ->when($locationId, fn ($query) => $query->where('booking_location_id', $locationId))
            ->count();

        $upcomingBookings = Booking::whereHas('slot', function ($query) {
            $query->whereDate('date', '>=', Carbon::today());
        })->where('status', 'booked')
            ->when($locationId, function ($query) use ($locationId) {
                $query->whereHas('slot', fn ($slotQuery) => $slotQuery->where('booking_location_id', $locationId));
            })
            ->count();

        $latestBookings = Booking::with(['user', 'slot.location'])
            ->when($locationId, function ($query) use ($locationId) {
                $query->whereHas('slot', fn ($slotQuery) => $slotQuery->where('booking_location_id', $locationId));
            })
            ->latest()
            ->take(10)
            ->get();

        $bookingsPerDay = Booking::select(
            DB::raw('DATE(created_at) as date'),
            DB::raw('count(*) as total')
        )
        ->when($locationId, function ($query) use ($locationId) {
            $query->whereHas('slot', fn ($slotQuery) => $slotQuery->where('booking_location_id', $locationId));
        })
        ->where('created_at', '>=', Carbon::now()->subDays(7))
        ->groupBy('date')
        ->orderBy('date')
        ->get();

        $statusCounts = Booking::select('status', DB::raw('count(*) as total'))
        ->when($locationId, function ($query) use ($locationId) {
            $query->whereHas('slot', fn ($slotQuery) => $slotQuery->where('booking_location_id', $locationId));
        })
        ->groupBy('status')
        ->get();

        $peakHours = Booking::select(
            DB::raw('HOUR(created_at) as hour'),
            DB::raw('count(*) as total')
        )
        ->when($locationId, function ($query) use ($locationId) {
            $query->whereHas('slot', fn ($slotQuery) => $slotQuery->where('booking_location_id', $locationId));
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
            'peakHours'
        ));
    }
}
