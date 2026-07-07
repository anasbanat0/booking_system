<?php

namespace App\Http\Controllers;

use App\Models\AdminNotification;
use Illuminate\Http\Request;

class AdminNotificationController extends Controller
{
    public function index(Request $request)
    {
        $query = AdminNotification::with(['location', 'user', 'booking.slot'])
            ->latest();

        if (!$request->user()->canManageAllBranches()) {
            $query->where('booking_location_id', $request->user()->booking_location_id);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        return view('admin.notifications.index', [
            'notifications' => $query->paginate(20)->withQueryString(),
            'types' => ['booking_created', 'booking_cancelled', 'booking_rescheduled', 'booking_status_updated'],
        ]);
    }

    public function markRead(Request $request)
    {
        $query = AdminNotification::whereNull('read_at');

        if (!$request->user()->canManageAllBranches()) {
            $query->where('booking_location_id', $request->user()->booking_location_id);
        }

        $query->update(['read_at' => now()]);

        return back()->with('success', 'Notifications marked as read.');
    }
}
