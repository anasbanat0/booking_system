<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class AdminActivityLogController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->canManageAllBranches(), 403);

        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'type' => ['nullable', 'string', 'max:255'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $query = ActivityLog::with(['actor', 'user', 'booking.slot.location']);

        $this->applyFilters($query, $filters);

        return view('admin.activity.index', [
            'logs' => $query->latest()->paginate(20)->withQueryString(),
            'types' => ActivityLog::select('type')->distinct()->orderBy('type')->pluck('type'),
        ]);
    }

    public function show(Request $request, User $user)
    {
        abort_unless($request->user()->canManageAllBranches(), 403);

        $filters = $request->validate([
            'type' => ['nullable', 'string', 'max:255'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $query = ActivityLog::with(['actor', 'user', 'booking.slot.location'])
            ->where('user_id', $user->id);

        $this->applyFilters($query, $filters, false);

        return view('admin.activity.show', [
            'selectedUser' => $user->load('managedLocation'),
            'logs' => $query->latest()->paginate(20)->withQueryString(),
            'types' => ActivityLog::where('user_id', $user->id)
                ->select('type')
                ->distinct()
                ->orderBy('type')
                ->pluck('type'),
        ]);
    }

    private function applyFilters(Builder $query, array $filters, bool $includeSearch = true): void
    {
        if (filled($filters['type'] ?? null)) {
            $query->where('type', $filters['type']);
        }

        if (filled($filters['from'] ?? null)) {
            $query->whereDate('created_at', '>=', $filters['from']);
        }

        if (filled($filters['to'] ?? null)) {
            $query->whereDate('created_at', '<=', $filters['to']);
        }

        if (! $includeSearch || blank($filters['search'] ?? null)) {
            return;
        }

        $search = $filters['search'];

        $query->where(function ($builder) use ($search) {
            $builder->where('title', 'like', '%'.$search.'%')
                ->orWhere('description', 'like', '%'.$search.'%')
                ->orWhereHas('actor', function ($userQuery) use ($search) {
                    $userQuery->where('name', 'like', '%'.$search.'%')
                        ->orWhere('email', 'like', '%'.$search.'%')
                        ->orWhere('phone', 'like', '%'.$search.'%');
                })
                ->orWhereHas('user', function ($userQuery) use ($search) {
                    $userQuery->where('name', 'like', '%'.$search.'%')
                        ->orWhere('email', 'like', '%'.$search.'%')
                        ->orWhere('phone', 'like', '%'.$search.'%');
                });
        });
    }
}
