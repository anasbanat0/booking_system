<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\ActivityLog;
use App\Models\BookingLocation;
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

    public function createHub(BookingLocation $location): View
    {
        abort_unless($location->is_active, 404);

        return view('auth.login', [
            'portal' => 'student',
            'location' => $location,
        ]);
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

        if ($redirect = $this->rejectWrongLoginPage($request)) {
            return $redirect;
        }

        ActivityLog::record('login', 'User login', $request->user()->name . ' signed in.', [
            'user_id' => $request->user()->id,
            'properties' => ['role' => $request->user()->role],
        ]);

        if ($request->user()?->role === 'admin') {
            return redirect()->intended(route('admin.dashboard', absolute: false));
        }

        if ($request->user()?->role === 'staff') {
            return redirect()->intended(route('admin.users-calendar.index', absolute: false));
        }

        return redirect()->intended(route('calendar.index', absolute: false));
    }

    private function rejectWrongLoginPage(LoginRequest $request): ?RedirectResponse
    {
        $user = $request->user();
        $portal = $request->input('portal', 'student');
        $location = $request->filled('booking_location_id')
            ? BookingLocation::find($request->integer('booking_location_id'))
            : null;
        $message = null;

        if ($portal === 'admin' && $user?->role !== 'admin') {
            $message = 'This login page is only for admin accounts.';
        } elseif ($portal === 'staff' && $user?->role !== 'staff') {
            $message = 'This login page is only for staff accounts.';
        } elseif ($portal === 'student') {
            if ($user?->role !== 'student') {
                $message = 'This login page is only for student accounts.';
            } elseif ($location && (int) $user->booking_location_id !== (int) $location->id) {
                $message = 'This student account is not registered for ' . $location->name . ' Hub.';
            }
        }

        if (!$message) {
            return null;
        }

        Auth::guard('web')->logout();
        $request->session()->regenerateToken();

        return back()
            ->withErrors(['email' => $message])
            ->onlyInput('email');
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
