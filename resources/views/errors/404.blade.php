<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Page not found</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-50 font-sans text-slate-950">
    <main class="flex min-h-screen items-center justify-center px-4 py-10">
        <section class="w-full max-w-3xl rounded-lg border border-slate-200 bg-white p-8 shadow-sm">
            <div class="flex items-center gap-3">
                <div class="flex h-14 w-14 items-center justify-center rounded-lg border border-slate-200 bg-white p-2">
                    <img src="{{ Vite::asset('resources/images/logo.png') }}" alt="Booking System" class="h-full w-full object-contain">
                </div>
                <div>
                    <p class="text-sm font-bold uppercase tracking-wide text-blue-700">404 Not Found</p>
                    <h1 class="mt-1 text-3xl font-extrabold text-slate-950">We could not find this page.</h1>
                </div>
            </div>

            <p class="mt-5 text-base leading-7 text-slate-600">
                The link may be expired, moved, or you may not have permission to open it. Students can return to the main page or log in again to view bookings.
            </p>

            <div class="mt-6 grid gap-3 sm:grid-cols-3">
                <div class="rounded-lg bg-slate-50 p-4">
                    <p class="text-sm font-bold text-slate-950">Students</p>
                    <p class="mt-1 text-sm text-slate-500">Open the calendar or My Bookings after login.</p>
                </div>
                <div class="rounded-lg bg-slate-50 p-4">
                    <p class="text-sm font-bold text-slate-950">Staff</p>
                    <p class="mt-1 text-sm text-slate-500">Use the staff panel for branch bookings and students.</p>
                </div>
                <div class="rounded-lg bg-slate-50 p-4">
                    <p class="text-sm font-bold text-slate-950">Need help?</p>
                    <p class="mt-1 text-sm text-slate-500">Go back and check the link or contact the administrator.</p>
                </div>
            </div>

            <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                <a href="{{ url('/') }}" class="inline-flex items-center justify-center rounded-md bg-slate-950 px-5 py-3 text-sm font-extrabold text-white hover:bg-slate-800">
                    Go to homepage
                </a>
                @auth
                    <a href="{{ route('dashboard') }}" class="inline-flex items-center justify-center rounded-md border border-slate-300 px-5 py-3 text-sm font-bold text-slate-700 hover:bg-slate-100">
                        Open my panel
                    </a>
                @else
                    <a href="{{ route('login') }}" class="inline-flex items-center justify-center rounded-md border border-slate-300 px-5 py-3 text-sm font-bold text-slate-700 hover:bg-slate-100">
                        Login
                    </a>
                @endauth
            </div>
        </section>
    </main>
</body>
</html>
