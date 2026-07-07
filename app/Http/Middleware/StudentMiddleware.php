<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class StudentMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->user()?->role === 'student') {
            return $next($request);
        }

        if ($request->user()?->isAdminPanelUser()) {
            return redirect()->route('admin.users-calendar.index');
        }

        abort(403);
    }
}
