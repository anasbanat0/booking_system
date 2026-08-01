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
use App\Support\HomepageContent;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

Route::get('/', function () {
    return view('welcome', HomepageContent::payload());
});

Route::redirect('/hubs/khanyounis', '/hubs/khan-younis');
Route::redirect('/hubs/khan-younes', '/hubs/khan-younis');
Route::redirect('/hubs/khanieness', '/hubs/khan-younis');

Route::get('/hubs/{location:slug}', function (BookingLocation $location) {
    abort_unless($location->is_active, 404);

    return view('welcome', HomepageContent::payload($location));
})->name('hubs.show');

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
<h2>أيام عمل المكان</h2>
<p>من السبت إلى الخميس.</p>

<h2>فترات العمل داخل المكان</h2>
<ul>
    <li>الفترة الأولى: من الساعة 8:30 صباحًا حتى 11:30 ظهرًا.</li>
    <li>الفترة الثانية: من الساعة 11:30 صباحًا حتى 2:30 مساءً.</li>
    <li>الفترة الثالثة: من الساعة 2:30 مساءً حتى 5:30 مساءً.</li>
</ul>

<h2>نظام الحجز</h2>
<ul>
    <li>يُسمح لكل طالب/ة بالحجز 12 مرة شهريًا كحد أقصى.</li>
    <li>يُسمح بالحجز لفترة واحدة في اليوم، وبحد أقصى 3 حجوزات في الأسبوع.</li>
    <li>الحجز يتم فقط على أساس أسبوعي، ولا يُتاح الحجز الشهري.</li>
</ul>

<h2>الإلغاء وإعادة الجدولة</h2>
<ul>
    <li>يمكن إلغاء الحجز في أي وقت، ولكن لا يحق للطالب استبدال حجز آخر بعد الإلغاء.</li>
    <li>يمكن إعادة جدولة الحجز لموعد آخر حتى 12 ساعة قبل بداية الحجز الأساسي.</li>
    <li>في حال وجود ظرف طارئ لإلغاء الحجز، يمكن تعويض هذا الحجز عبر الاتصال على رقم الإداري المتواجد بالمكان مع ضرورة توضيح سبب الإلغاء.</li>
    <li>في حال تم الإلغاء أو عدم الحضور لأكثر من 3 حجوزات في الشهر، سيتم خصم يوم واحد من الأيام المتاحة للطالب.</li>
</ul>

<h2>في حال وجود مشكلة في الحجز أو الإلغاء عبر الموقع</h2>
<p>يمكنك التواصل على الرقم التالي عبر الاتصال المباشر أو من خلال الواتساب خلال أوقات العمل الرسمية فقط: <strong>00972597231717</strong>.</p>

<h2>ضوابط السلوك داخل المكان</h2>
<ul>
    <li>يُرجى المحافظة على الهدوء داخل المكان.</li>
    <li>يُمنع الأكل داخل المكان.</li>
    <li>نأمل منكم ترك المساحة مرتبة بعد الاستخدام احترامًا لزملائكم والمستخدمين بعدها.</li>
</ul>

<h2>الالتزام بالوقت</h2>
<p>يُرجى الالتزام بموعد الحجز بدقة لضمان الاستفادة الكاملة من الوقت المحدد.</p>

<h2>استخدام الموارد</h2>
<ul>
    <li>الموارد المتاحة داخل المكان مخصصة لأغراض دراسية فقط.</li>
    <li>يُمنع نقل الأدوات أو المعدات خارج المكان دون إذن.</li>
    <li>يُسمح بشحن الجهاز المُستخدم فقط داخل المكان، وذلك حرصًا على توفير الكهرباء وتمكين جميع الطلبة من الاستفادة.</li>
</ul>

<h2>المسؤولية الشخصية</h2>
<ul>
    <li>الطالب يتحمّل المسؤولية عن أي تلف في الأثاث أو المعدات نتيجة سوء الاستخدام.</li>
    <li>الحفاظ على ممتلكات المكان مسؤولية جماعية تُسهم في استمرارية تقديم الخدمة بجودة عالية.</li>
</ul>

<h2>أولوية الحجز</h2>
<p>تُعطى أولوية حجز واستخدام المكان في نفس اليوم للطلبة الذين لديهم امتحانات.</p>

<h2>الملاحظات والتقييم</h2>
<ul>
    <li>تقوم إدارة المكان بتقييم استخدام الطلبة بشكل دوري لتحسين الخدمة.</li>
    <li>نرجو التعاون في تعبئة أي استبيانات عند الطلب.</li>
</ul>
HTML;

            return view('instructions', [
                'instructionsAr' => SiteContent::getValue('instructions_ar', $defaultInstructions) ?: $defaultInstructions,
                'instructionsHeroEyebrow' => SiteContent::getValue('instructions_hero_eyebrow', 'Samir Foundation Medical Hub'),
                'instructionsHeroTitle' => SiteContent::getValue('instructions_hero_title', 'تعليمات الحجز واستخدام المكان'),
                'instructionsHeroDescription' => SiteContent::getValue('instructions_hero_description', 'مساحة هادئة ومجهزة للطلبة الذين يحتاجون إلى كهرباء مستقرة، اتصال إنترنت، وبيئة مناسبة للدراسة أو تقديم الامتحانات الإلكترونية.'),
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
    Route::post('/admin/closed-periods', [AdminSlotSettingsController::class, 'storeClosedPeriod'])
        ->name('admin.closed-periods.store');
    Route::delete('/admin/closed-periods/{closedPeriod}', [AdminSlotSettingsController::class, 'destroyClosedPeriod'])
        ->name('admin.closed-periods.destroy');

    Route::get('/admin/content', [AdminSiteContentController::class, 'index'])->name('admin.content.index');
    Route::patch('/admin/content', [AdminSiteContentController::class, 'update'])->name('admin.content.update');
});

require __DIR__ . '/auth.php';
