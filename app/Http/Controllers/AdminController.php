<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Booking;
use App\Models\Slot;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    public function index()
    {
        $totalUsers = User::count();

        $totalBookings = Booking::count();

        $todayBookings = Booking::whereDate('created_at', Carbon::today())->count();

        $cancelledBookings = Booking::where('status', 'cancelled')->count();

        $activeSlots = Slot::where('is_active', true)->count();

        $upcomingBookings = Booking::whereHas('slot', function ($query) {
            $query->whereDate('date', '>=', Carbon::today());
        })->where('status', 'booked')->count();

        $latestBookings = Booking::with(['user', 'slot.location'])
            ->latest()
            ->take(10)
            ->get();

        $bookingsPerDay = Booking::select(
            DB::raw('DATE(created_at) as date'),
            DB::raw('count(*) as total')
        )
        ->where('created_at', '>=', Carbon::now()->subDays(7))
        ->groupBy('date')
        ->orderBy('date')
        ->get();

        $statusCounts = Booking::select('status', DB::raw('count(*) as total'))
        ->groupBy('status')
        ->get();

        $peakHours = Booking::select(
            DB::raw('HOUR(created_at) as hour'),
            DB::raw('count(*) as total')
        )
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
