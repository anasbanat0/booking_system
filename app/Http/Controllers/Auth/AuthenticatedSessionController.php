<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login', ['portal' => 'student']);
    }

    public function createAdmin(): View
    {
        return view('auth.login', ['portal' => 'admin']);
    }

    public function createStaff(): View
    {
        return view('auth.login', ['portal' => 'staff']);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        if ($request->user()?->role === 'admin') {
            return redirect()->intended(route('admin.dashboard', absolute: false));
        }

        if ($request->user()?->role === 'staff') {
            return redirect()->intended(route('admin.users-calendar.index', absolute: false));
        }

        return redirect()->intended(route('calendar.index', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
