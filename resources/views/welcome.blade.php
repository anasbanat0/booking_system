<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Medical Hub - Samir Foundation</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-[#f6f7f4] font-sans text-stone-950">
    @php
        $supporters = collect(explode(',', $content['supporters'] ?? ''))->map(fn ($item) => trim($item))->filter();
        $contactLines = collect(preg_split('/\r\n|\r|\n/', $content['contact_info'] ?? ''))->filter();
        $socialLines = collect(preg_split('/\r\n|\r|\n/', $content['social_links'] ?? ''))->filter();
        $logosImage = file_exists(public_path('images/supporters-logos.png')) ? asset('images/supporters-logos.png') : null;
    @endphp

    <main class="min-h-screen">
        <section class="relative overflow-hidden bg-stone-950 text-white">
            <div class="absolute inset-0 bg-[linear-gradient(135deg,rgba(15,118,110,.32),rgba(37,99,235,.22),rgba(0,0,0,0))]"></div>

            <div class="relative mx-auto grid max-w-7xl gap-10 px-4 py-8 sm:px-6 lg:grid-cols-[1fr_420px] lg:px-8 lg:py-12">
                <div class="flex min-h-[620px] flex-col justify-between">
                    <div class="flex flex-col items-start gap-5 sm:flex-row sm:items-center sm:justify-between">
                        <div class="flex flex-col gap-5 sm:flex-row sm:items-center">
                            <div class="flex h-12 w-auto max-w-80 items-center justify-center rounded-2xl border border-white/70 bg-white p-5 shadow-2xl shadow-black/25 sm:h-32 sm:w-80">
                                <img src="{{ Vite::asset('resources/images/logo.png') }}" alt="Samir Foundation" class="max-h-14 w-auto object-contain">
                            </div>
                            <div>
                                <p class="text-3xl font-extrabold">Samir Foundation</p>
                                <p class="text-base text-white/60">Medical Hub</p>
                            </div>
                        </div>
                    </div>

                    <div class="max-w-3xl py-12">
                        <p class="text-sm font-bold uppercase tracking-wide text-teal-200">Book your seat with clarity</p>
                        <h1 class="mt-5 text-5xl font-extrabold leading-tight lg:text-7xl">
                            A cleaner way to reserve your weekly appointment.
                        </h1>
                        <p class="mt-6 max-w-2xl text-lg leading-8 text-white/72">
                            {{ $content['project_intro'] }}
                        </p>

                        <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                            @auth
                                <a href="{{ route('dashboard') }}" class="inline-flex items-center justify-center rounded-full bg-white px-6 py-3 text-sm font-extrabold text-stone-950 shadow-lg shadow-black/20 hover:bg-teal-50">
                                    Open dashboard
                                </a>
                            @else
                                <a href="{{ route('login') }}" class="inline-flex items-center justify-center rounded-full bg-white px-6 py-3 text-sm font-extrabold text-stone-950 shadow-lg shadow-black/20 hover:bg-teal-50">
                                    Login to book
                                </a>
                            @endauth
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3 pb-4 sm:grid-cols-4">
                        <div class="rounded-lg border border-white/10 bg-white/10 p-4">
                            <p class="text-2xl font-extrabold">{{ number_format($stats['students']) }}</p>
                            <p class="mt-1 text-xs font-semibold text-white/58">Students</p>
                        </div>
                        <div class="rounded-lg border border-white/10 bg-white/10 p-4">
                            <p class="text-2xl font-extrabold">{{ number_format($stats['bookings']) }}</p>
                            <p class="mt-1 text-xs font-semibold text-white/58">Bookings</p>
                        </div>
                        <div class="rounded-lg border border-white/10 bg-white/10 p-4">
                            <p class="text-2xl font-extrabold">{{ number_format($stats['availableSeats']) }}</p>
                            <p class="mt-1 text-xs font-semibold text-white/58">Seats left</p>
                        </div>
                        <div class="rounded-lg border border-white/10 bg-white/10 p-4">
                            <p class="text-2xl font-extrabold">{{ number_format($stats['branches']) }}</p>
                            <p class="mt-1 text-xs font-semibold text-white/58">Branches</p>
                        </div>
                    </div>
                </div>

                <aside class="self-center rounded-lg bg-white p-5 text-stone-950 shadow-2xl shadow-black/30">
                    <div class="rounded-lg border border-stone-200 p-5">
                        <p class="text-sm font-bold uppercase tracking-wide text-teal-700">Start here</p>
                        <h2 class="mt-3 text-2xl font-extrabold">Student access</h2>
                        <p class="mt-2 text-sm leading-6 text-stone-600">
                            Sign in to view the weekly calendar, manage upcoming bookings, cancel, or reschedule when allowed.
                        </p>

                        @guest
                            <a href="{{ route('login') }}" class="mt-5 flex w-full items-center justify-center rounded-md bg-stone-950 px-4 py-3 text-sm font-extrabold text-white hover:bg-stone-800">
                                Continue to login
                            </a>
                            @if(Route::has('password.request'))
                                <a href="{{ route('password.request') }}" class="mt-3 flex w-full items-center justify-center rounded-md border border-stone-300 px-4 py-3 text-sm font-bold text-stone-700 hover:bg-stone-100">
                                    Need help accessing your account?
                                </a>
                            @endif
                        @else
                            <a href="{{ route('calendar.index') }}" class="mt-5 flex w-full items-center justify-center rounded-md bg-stone-950 px-4 py-3 text-sm font-extrabold text-white hover:bg-stone-800">
                                View weekly calendar
                            </a>
                        @endguest
                    </div>

                    <div class="mt-5 rounded-lg border border-stone-200 p-5">
                        <p class="text-sm font-bold uppercase tracking-wide text-stone-500">Team access</p>
                        <p class="mt-2 text-sm leading-6 text-stone-600">
                            Staff manage only their assigned branch. Admins manage all branches, users, settings, imports, and exports.
                        </p>
                        <div class="mt-4 grid grid-cols-2 gap-2">
                            <a href="{{ route('staff.login') }}" class="flex items-center justify-center rounded-md border border-stone-300 px-3 py-2 text-sm font-extrabold text-stone-700 hover:bg-stone-100">
                                Staff login
                            </a>
                            <a href="{{ route('admin.login') }}" class="flex items-center justify-center rounded-md border border-stone-300 px-3 py-2 text-sm font-extrabold text-stone-700 hover:bg-stone-100">
                                Admin login
                            </a>
                        </div>
                    </div>

                    <div class="mt-5 rounded-lg bg-stone-100 p-4">
                        <p class="text-sm font-bold text-stone-900">Supporting partners</p>
                        @if($logosImage)
                            <img src="{{ $logosImage }}" alt="Supporting partners logos" class="mt-4 w-full rounded-md border border-stone-200 bg-white object-contain">
                        @else
                            <div class="mt-4 grid grid-cols-3 gap-2">
                                @forelse($supporters->take(3) as $supporter)
                                    <div class="flex h-20 items-center justify-center rounded-md border border-stone-200 bg-white px-2 text-center text-xs font-extrabold text-stone-600">
                                        {{ $supporter }}
                                    </div>
                                @empty
                                    <div class="col-span-3 rounded-md border border-stone-200 bg-white p-4 text-center text-sm text-stone-500">
                                        Partner logos image can be added from the public images folder.
                                    </div>
                                @endforelse
                            </div>
                        @endif
                    </div>
                </aside>
            </div>
        </section>

        <section class="mx-auto grid max-w-7xl gap-6 px-4 py-12 sm:px-6 lg:grid-cols-3 lg:px-8">
            <div class="rounded-lg border border-stone-200 bg-white p-6 shadow-sm">
                <p class="text-sm font-bold uppercase tracking-wide text-teal-700">01</p>
                <h2 class="mt-3 text-xl font-extrabold">Log in</h2>
                <p class="mt-2 text-sm leading-6 text-stone-600">Access your student account securely before viewing available weekly slots.</p>
            </div>
            <div class="rounded-lg border border-stone-200 bg-white p-6 shadow-sm">
                <p class="text-sm font-bold uppercase tracking-wide text-blue-700">02</p>
                <h2 class="mt-3 text-xl font-extrabold">Choose a slot</h2>
                <p class="mt-2 text-sm leading-6 text-stone-600">Pick the branch and time period that still has available seats.</p>
            </div>
            <div class="rounded-lg border border-stone-200 bg-white p-6 shadow-sm">
                <p class="text-sm font-bold uppercase tracking-wide text-amber-700">03</p>
                <h2 class="mt-3 text-xl font-extrabold">Manage bookings</h2>
                <p class="mt-2 text-sm leading-6 text-stone-600">Open My Bookings to review upcoming visits, cancel, or reschedule.</p>
            </div>
        </section>

        <section class="border-y border-stone-200 bg-white">
            <div class="mx-auto grid max-w-7xl gap-8 px-4 py-10 sm:px-6 lg:grid-cols-2 lg:px-8">
                <div>
                    <h2 class="text-2xl font-extrabold">Contact</h2>
                    <div class="mt-4 space-y-2">
                        @foreach($contactLines as $line)
                            <p class="rounded-md bg-stone-100 px-4 py-3 text-sm font-semibold text-stone-700">{{ $line }}</p>
                        @endforeach
                    </div>
                </div>
                <div>
                    <h2 class="text-2xl font-extrabold">Social links</h2>
                    <div class="mt-4 flex flex-wrap gap-2">
                        @foreach($socialLines as $line)
                            @php
                                [$label, $url] = array_pad(explode(': ', $line, 2), 2, '#');
                            @endphp
                            <a href="{{ $url }}" target="_blank" rel="noreferrer" class="rounded-full border border-stone-300 px-4 py-2 text-sm font-bold text-stone-700 hover:bg-stone-100">
                                {{ $label }}
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>
    </main>
</body>
</html>
