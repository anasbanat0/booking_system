<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Booking Instructions</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-[#f6f7f4] font-sans text-stone-950">
    <main class="mx-auto max-w-5xl px-4 py-8 sm:px-6 lg:px-8">
        <div class="mb-8 flex items-center justify-between">
            <a href="{{ url('/') }}" class="text-sm font-bold text-stone-600 hover:text-stone-950">Sameer Foundation</a>
            <a href="{{ route('login') }}" class="rounded-full bg-stone-950 px-4 py-2 text-sm font-extrabold text-white hover:bg-stone-800">Login</a>
        </div>

        <section class="rounded-lg bg-stone-950 p-8 text-white shadow-xl shadow-stone-300/40">
            <p class="text-sm font-bold uppercase tracking-wide text-teal-200">Instructions</p>
            <h1 class="mt-4 text-4xl font-extrabold lg:text-5xl">Before you book</h1>
            <p class="mt-4 max-w-3xl text-base leading-8 text-white/70">
                Read the booking rules carefully before choosing a weekly slot. The system checks limits, capacity, and rescheduling rules automatically.
            </p>
        </section>

        <div class="mt-8 grid gap-6 lg:grid-cols-2">
            <section class="rounded-lg border border-stone-200 bg-white p-6 shadow-sm">
                <h2 class="text-2xl font-extrabold">English</h2>
                <div class="mt-5 space-y-4">
                    <div class="rounded-lg bg-stone-100 p-4">
                        <h3 class="font-bold">1. Login</h3>
                        <p class="mt-1 text-sm leading-6 text-stone-600">Use your student account to access the weekly booking calendar.</p>
                    </div>
                    <div class="rounded-lg bg-stone-100 p-4">
                        <h3 class="font-bold">2. Choose a branch and time</h3>
                        <p class="mt-1 text-sm leading-6 text-stone-600">Select an available slot from Gaza or Khan Younis based on remaining seats.</p>
                    </div>
                    <div class="rounded-lg bg-stone-100 p-4">
                        <h3 class="font-bold">3. Respect booking limits</h3>
                        <p class="mt-1 text-sm leading-6 text-stone-600">The monthly and weekly limits are applied automatically by the system.</p>
                    </div>
                    <div class="rounded-lg bg-stone-100 p-4">
                        <h3 class="font-bold">4. Cancel or reschedule</h3>
                        <p class="mt-1 text-sm leading-6 text-stone-600">Cancellation is available anytime. Rescheduling is available at least 12 hours before your booking.</p>
                    </div>
                </div>
            </section>

            <section class="rounded-lg border border-stone-200 bg-white p-6 text-right shadow-sm" dir="rtl">
                <h2 class="text-2xl font-extrabold">العربية</h2>
                <div class="mt-5 space-y-4">
                    <div class="rounded-lg bg-stone-100 p-4">
                        <h3 class="font-bold">1. تسجيل الدخول</h3>
                        <p class="mt-1 text-sm leading-6 text-stone-600">استخدم حساب الطالب للدخول إلى التقويم الأسبوعي للحجز.</p>
                    </div>
                    <div class="rounded-lg bg-stone-100 p-4">
                        <h3 class="font-bold">2. اختيار الفرع والوقت</h3>
                        <p class="mt-1 text-sm leading-6 text-stone-600">اختر فترة متاحة من غزة أو خانيونس حسب عدد المقاعد المتبقية.</p>
                    </div>
                    <div class="rounded-lg bg-stone-100 p-4">
                        <h3 class="font-bold">3. الالتزام بحدود الحجز</h3>
                        <p class="mt-1 text-sm leading-6 text-stone-600">يطبق النظام الحد الأسبوعي والشهري تلقائياً عند محاولة الحجز.</p>
                    </div>
                    <div class="rounded-lg bg-stone-100 p-4">
                        <h3 class="font-bold">4. الإلغاء أو إعادة الجدولة</h3>
                        <p class="mt-1 text-sm leading-6 text-stone-600">الإلغاء متاح في أي وقت. إعادة الجدولة متاحة قبل الحجز بـ 12 ساعة على الأقل.</p>
                    </div>
                </div>
            </section>
        </div>
    </main>
</body>
</html>
