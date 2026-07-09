<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AdminSiteContentController;
use App\Http\Controllers\AdminSlotSettingsController;
use App\Http\Controllers\AdminUserCalendarController;
use App\Http\Controllers\AdminManageUserController;
use App\Http\Controllers\AdminNotificationController;
use App\Http\Controllers\AdminActivityLogController;
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
            'page_title' => SiteContent::getValue('page_title', 'Medical Hub - Samir Foundation'),
            'brand_title' => SiteContent::getValue('brand_title', 'Samir Foundation'),
            'brand_subtitle' => SiteContent::getValue('brand_subtitle', 'Medical Hub'),
            'hero_eyebrow' => SiteContent::getValue('hero_eyebrow', 'Quiet power for serious study'),
            'hero_title' => SiteContent::getValue('hero_title', 'Reserve a calm, connected seat for exams and study.'),
            'project_intro' => SiteContent::getValue('project_intro', 'A quiet, reliable Medical Hub by Samir Foundation, prepared for students who need stable electricity, internet, and a focused place to study or take online exams.'),
            'primary_cta_guest' => SiteContent::getValue('primary_cta_guest', 'Login to book'),
            'primary_cta_auth' => SiteContent::getValue('primary_cta_auth', 'Open dashboard'),
            'stat_students_label' => SiteContent::getValue('stat_students_label', 'Students'),
            'stat_bookings_label' => SiteContent::getValue('stat_bookings_label', 'Bookings'),
            'stat_seats_label' => SiteContent::getValue('stat_seats_label', 'Seats left'),
            'stat_branches_label' => SiteContent::getValue('stat_branches_label', 'Branches'),
            'student_card_eyebrow' => SiteContent::getValue('student_card_eyebrow', 'Start here'),
            'student_card_title' => SiteContent::getValue('student_card_title', 'Student access'),
            'student_card_description' => SiteContent::getValue('student_card_description', 'Sign in to reserve a prepared seat with power, internet, and a quiet environment.'),
            'student_card_guest_button' => SiteContent::getValue('student_card_guest_button', 'Continue to login'),
            'student_card_help_button' => SiteContent::getValue('student_card_help_button', 'Need help accessing your account?'),
            'student_card_auth_button' => SiteContent::getValue('student_card_auth_button', 'View weekly calendar'),
            'team_card_eyebrow' => SiteContent::getValue('team_card_eyebrow', 'Team access'),
            'team_card_description' => SiteContent::getValue('team_card_description', 'Staff manage only their assigned branch. Admins manage all branches, users, settings, imports, and exports.'),
            'team_staff_button' => SiteContent::getValue('team_staff_button', 'Staff login'),
            'team_admin_button' => SiteContent::getValue('team_admin_button', 'Admin login'),
            'partners_heading' => SiteContent::getValue('partners_heading', 'Supporting partners'),
            'partners_empty_text' => SiteContent::getValue('partners_empty_text', 'Partner logos image can be added from the public images folder.'),
            'step_1_label' => SiteContent::getValue('step_1_label', '01'),
            'step_1_title' => SiteContent::getValue('step_1_title', 'Book ahead'),
            'step_1_description' => SiteContent::getValue('step_1_description', 'Choose your branch and weekly time before coming to the hub.'),
            'step_2_label' => SiteContent::getValue('step_2_label', '02'),
            'step_2_title' => SiteContent::getValue('step_2_title', 'Arrive prepared'),
            'step_2_description' => SiteContent::getValue('step_2_description', 'Use a calm seat with electricity and internet for studying or online exams.'),
            'step_3_label' => SiteContent::getValue('step_3_label', '03'),
            'step_3_title' => SiteContent::getValue('step_3_title', 'Manage responsibly'),
            'step_3_description' => SiteContent::getValue('step_3_description', 'Review, cancel, or reschedule from your account when changes are allowed.'),
            'contact_info' => SiteContent::getValue('contact_info', ''),
            'supporters' => SiteContent::getValue('supporters', ''),
            'footer_title' => SiteContent::getValue('footer_title', 'Samir Foundation Medical Hub'),
            'footer_description' => SiteContent::getValue('footer_description', 'A student-focused hub prepared for calm study, online exams, stable electricity, and reliable internet access.'),
            'footer_cta_text' => SiteContent::getValue('footer_cta_text', 'Need to update information or add a supporting partner announcement?'),
            'footer_cta_url' => SiteContent::getValue('footer_cta_url', ''),
            'footer_cta_button' => SiteContent::getValue('footer_cta_button', 'Open link'),
            'footer_contact_heading' => SiteContent::getValue('footer_contact_heading', 'Contact'),
            'footer_support_heading' => SiteContent::getValue('footer_support_heading', 'Support and links'),
            'supporters_note' => SiteContent::getValue('supporters_note', 'Supporting education access through prepared study spaces in Gaza and Khan Younis.'),
            'footer_bottom_text' => SiteContent::getValue('footer_bottom_text', 'Samir Foundation Medical Hub. Built for focused learning access.'),
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

Route::get('/dashboard', function () {
    if (auth()->user()?->role === 'admin') {
        return redirect()->route('admin.dashboard');
    }

    if (auth()->user()?->role === 'staff') {
        return redirect()->route('admin.users-calendar.index');
    }

    return redirect()->route('calendar.index');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::get('/profile-photo/{user}', [ProfileController::class, 'photo'])->name('profile.photo');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::middleware('student')->group(function () {
        Route::get('/calendar', [CalendarController::class, 'index'])->name('calendar.index');
        Route::get('/instructions', function () {
            $defaultInstructions = <<<'HTML'
<h2>Operating days</h2>
<p>The Medical Hub is open from Saturday to Thursday.</p>

<h2>Daily time periods</h2>
<ul>
    <li>First period: 8:30 AM to 11:30 AM.</li>
    <li>Second period: 11:30 AM to 2:30 PM.</li>
    <li>Third period: 2:30 PM to 5:30 PM.</li>
</ul>

<h2>Booking rules</h2>
<ul>
    <li>Each student may book up to 12 times per month.</li>
    <li>Each student may book one period per day, with a maximum of 3 bookings per week.</li>
    <li>Bookings are weekly only. Monthly booking is not available.</li>
</ul>

<h2>Cancellation and rescheduling</h2>
<ul>
    <li>A booking may be cancelled at any time, but the student cannot replace it with another booking after cancellation.</li>
    <li>A booking may be rescheduled to another available time up to 12 hours before the original booking starts.</li>
    <li>For emergency cancellations, the student may request compensation by contacting the on-site administrator and explaining the reason for cancellation.</li>
    <li>If a student cancels or misses more than 3 bookings in one month, one available booking day will be deducted from the student.</li>
</ul>

<h2>Booking or cancellation support</h2>
<p>If you face an issue with booking or cancellation through the website, contact the following number by direct call or WhatsApp during official working hours only: <strong>00972597231717</strong>.</p>

<h2>Conduct inside the hub</h2>
<ul>
    <li>Please keep the space quiet.</li>
    <li>Eating inside the hub is not allowed.</li>
    <li>Please leave the space clean and organized after use out of respect for other students.</li>
</ul>

<h2>Time commitment</h2>
<p>Please arrive on time for your booking to make full use of the reserved period.</p>

<h2>Use of resources</h2>
<ul>
    <li>Hub resources are for study purposes only.</li>
    <li>Tools or equipment may not be moved outside the hub without permission.</li>
    <li>Only the device being used may be charged inside the hub, to help preserve electricity and allow all students to benefit.</li>
</ul>

<h2>Personal responsibility</h2>
<ul>
    <li>Students are responsible for any damage to furniture or equipment caused by misuse.</li>
    <li>Protecting the hub and its property is a shared responsibility that helps keep the service available at a high standard.</li>
</ul>

<h2>Booking priority</h2>
<p>Priority for same-day booking and use of the hub is given to students who have exams.</p>

<h2>Feedback and evaluation</h2>
<ul>
    <li>The hub administration periodically reviews student usage to improve the service.</li>
    <li>Please cooperate in completing any requested surveys.</li>
</ul>
HTML;

            return view('instructions', [
                'instructionsEn' => SiteContent::getValue('instructions_en', $defaultInstructions),
            ]);
        })->name('instructions');
        Route::post('/book-slot', [BookingController::class, 'store'])->name('book.slot');
        Route::get('/my-bookings', [BookingController::class, 'myBookings'])->name('bookings.my');
        Route::post('/booking/{booking}/cancel', [BookingController::class, 'cancel'])->name('bookings.cancel');
        Route::post('/booking/{booking}/reschedule', [BookingController::class, 'reschedule'])->name('bookings.reschedule');
    });
});

Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/admin/dashboard', [AdminController::class, 'index'])->name('admin.dashboard');

    Route::get('/admin/bookings', [\App\Http\Controllers\AdminBookingController::class, 'index'])
        ->name('admin.bookings.index');

    Route::post('/admin/bookings/manual', [\App\Http\Controllers\AdminBookingController::class, 'storeManual'])
        ->name('admin.bookings.manual');

    Route::post('/admin/bookings/{id}/status', [\App\Http\Controllers\AdminBookingController::class, 'updateStatus'])
        ->name('admin.bookings.status');

    Route::get('/admin/users-calendar', [AdminUserCalendarController::class, 'index'])
        ->name('admin.users-calendar.index');

    Route::patch('/admin/users-calendar/bookings/{booking}', [AdminUserCalendarController::class, 'updateBooking'])
        ->name('admin.users-calendar.bookings.update');

    Route::get('/admin/manage/users', [AdminManageUserController::class, 'index'])->name('admin.manage.users.index');
    Route::post('/admin/manage/users', [AdminManageUserController::class, 'store'])->name('admin.manage.users.store');
    Route::patch('/admin/manage/users/{user}', [AdminManageUserController::class, 'update'])->name('admin.manage.users.update');
    Route::delete('/admin/manage/users/bulk', [AdminManageUserController::class, 'bulkDestroy'])->name('admin.manage.users.bulk-destroy');
    Route::delete('/admin/manage/users/{user}', [AdminManageUserController::class, 'destroy'])->name('admin.manage.users.destroy');
    Route::post('/admin/manage/users/{user}/restore', [AdminManageUserController::class, 'restore'])->name('admin.manage.users.restore');
    Route::post('/admin/manage/users/{user}/password-link', [AdminManageUserController::class, 'resendPasswordLink'])->name('admin.manage.users.password-link');
    Route::get('/admin/manage/users/export', [AdminManageUserController::class, 'export'])->name('admin.manage.users.export');
    Route::post('/admin/manage/users/import', [AdminManageUserController::class, 'import'])->name('admin.manage.users.import');

    Route::get('/admin/notifications', [AdminNotificationController::class, 'index'])->name('admin.notifications.index');
    Route::post('/admin/notifications/read', [AdminNotificationController::class, 'markRead'])->name('admin.notifications.read');

    Route::get('/admin/activity', [AdminActivityLogController::class, 'index'])->name('admin.activity.index');

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
    Route::delete('/admin/slot-templates/{template}', [AdminSlotSettingsController::class, 'destroyTemplate'])
        ->name('admin.slot-templates.destroy');
    Route::post('/admin/holidays', [AdminSlotSettingsController::class, 'storeHoliday'])
        ->name('admin.holidays.store');
    Route::patch('/admin/holidays/{holiday}', [AdminSlotSettingsController::class, 'updateHoliday'])
        ->name('admin.holidays.update');
    Route::delete('/admin/holidays/{holiday}', [AdminSlotSettingsController::class, 'destroyHoliday'])
        ->name('admin.holidays.destroy');

    Route::get('/admin/content', [AdminSiteContentController::class, 'index'])->name('admin.content.index');
    Route::patch('/admin/content', [AdminSiteContentController::class, 'update'])->name('admin.content.update');
});

require __DIR__ . '/auth.php';
