<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AdminSiteContentController;
use App\Http\Controllers\AdminSlotSettingsController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SlotController;
use App\Models\Booking;
use App\Models\BookingLocation;
use App\Models\SiteContent;
use App\Models\Slot;
use App\Models\User;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

Route::get('/', function () {
    $hasUsers = Schema::hasTable('users');
    $hasBookings = Schema::hasTable('bookings');
    $hasSlots = Schema::hasTable('slots');
    $hasLocations = Schema::hasTable('booking_locations');

    return view('welcome', [
        'content' => [
            'project_intro' => SiteContent::getValue('project_intro', 'Student weekly booking platform.'),
            'usage_instructions' => SiteContent::getValue('usage_instructions', ''),
            'contact_info' => SiteContent::getValue('contact_info', ''),
            'supporters' => SiteContent::getValue('supporters', ''),
            'social_links' => SiteContent::getValue('social_links', ''),
        ],
        'stats' => [
            'students' => $hasUsers ? User::where('role', 'student')->count() : 0,
            'bookings' => $hasBookings ? Booking::count() : 0,
            'availableSeats' => $hasSlots ? Slot::where('is_active', true)
                ->selectRaw('COALESCE(SUM(capacity - booked_count), 0) as total')
                ->value('total') : 0,
            'branches' => $hasLocations ? BookingLocation::where('is_active', true)->count() : 0,
        ],
    ]);
});

Route::view('/instructions', 'instructions')->name('instructions');

Route::get('/dashboard', function () {
    if (auth()->user()?->role === 'admin') {
        return redirect()->route('admin.dashboard');
    }

    return redirect()->route('calendar.index');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/calendar', [CalendarController::class, 'index'])->name('calendar.index');
    Route::post('/book-slot', [BookingController::class, 'store'])->name('book.slot');
    Route::get('/my-bookings', [BookingController::class, 'myBookings'])->name('bookings.my');
    Route::post('/booking/{booking}/cancel', [BookingController::class, 'cancel'])->name('bookings.cancel');
    Route::post('/booking/{booking}/reschedule', [BookingController::class, 'reschedule'])->name('bookings.reschedule');
});

Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/admin/dashboard', [AdminController::class, 'index'])->name('admin.dashboard');

    Route::get('/admin/bookings', [\App\Http\Controllers\AdminBookingController::class, 'index'])
        ->name('admin.bookings.index');

    Route::post('/admin/bookings/{id}/status', [\App\Http\Controllers\AdminBookingController::class, 'updateStatus'])
        ->name('admin.bookings.status');

    Route::get('/admin/slots', [AdminSlotSettingsController::class, 'index'])->name('admin.slots.index');
    Route::post('/admin/generate-slots', [SlotController::class, 'generate'])->name('slots.generate');
    Route::patch('/admin/locations/{location}', [AdminSlotSettingsController::class, 'updateLocation'])
        ->name('admin.locations.update');
    Route::patch('/admin/booking-rules', [AdminSlotSettingsController::class, 'updateRules'])
        ->name('admin.booking-rules.update');
    Route::post('/admin/slot-templates', [AdminSlotSettingsController::class, 'storeTemplate'])
        ->name('admin.slot-templates.store');
    Route::patch('/admin/slot-templates/{template}', [AdminSlotSettingsController::class, 'updateTemplate'])
        ->name('admin.slot-templates.update');

    Route::get('/admin/content', [AdminSiteContentController::class, 'index'])->name('admin.content.index');
    Route::patch('/admin/content', [AdminSiteContentController::class, 'update'])->name('admin.content.update');
});

require __DIR__ . '/auth.php';
