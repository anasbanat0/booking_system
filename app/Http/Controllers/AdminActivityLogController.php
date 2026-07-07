<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\Request;

class AdminActivityLogController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->canManageAllBranches(), 403);

        $query = ActivityLog::with(['actor', 'user', 'booking.slot.location'])->latest();

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('search')) {
            $query->where(function ($builder) use ($request) {
                $builder->where('title', 'like', '%' . $request->search . '%')
                    ->orWhere('description', 'like', '%' . $request->search . '%')
                    ->orWhereHas('actor', fn ($userQuery) => $userQuery->where('name', 'like', '%' . $request->search . '%'))
                    ->orWhereHas('user', fn ($userQuery) => $userQuery->where('name', 'like', '%' . $request->search . '%'));
            });
        }

        return view('admin.activity.index', [
            'logs' => $query->paginate(20)->withQueryString(),
            'types' => ActivityLog::select('type')->distinct()->orderBy('type')->pluck('type'),
        ]);
    }
}
