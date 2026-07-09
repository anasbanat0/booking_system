<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $content['page_title'] }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-[#eef2f0] font-sans text-slate-950">
    @php
        $supporters = collect(explode(',', $content['supporters'] ?? ''))->map(fn ($item) => trim($item))->filter();
        $contactLines = collect(preg_split('/\r\n|\r|\n/', $content['contact_info'] ?? ''))->filter();
        $socialLines = collect(preg_split('/\r\n|\r|\n/', $content['social_links'] ?? ''))->filter();
        $logosImage = file_exists(public_path('images/supporters-logos.png')) ? asset('images/supporters-logos.png') : null;
        $footerCtaUrl = trim($content['footer_cta_url'] ?? '');
    @endphp

    <main class="min-h-screen">
        <section class="relative overflow-hidden bg-[#071817] text-white">
            <div class="absolute inset-x-0 top-0 h-px bg-white/20"></div>

            <div class="relative mx-auto grid max-w-7xl gap-10 px-4 py-10 sm:px-6 lg:grid-cols-[1fr_390px] lg:px-8 lg:py-14">
                <div class="flex min-h-[620px] flex-col justify-between">
                    <div class="flex flex-col gap-5 sm:flex-row sm:items-center sm:justify-between">
                        <div class="flex flex-col gap-5 sm:flex-row sm:items-center">
                            <div class="inline-flex w-fit items-center justify-center rounded-[1.4rem] border border-white/75 bg-white px-4 py-3 shadow-2xl shadow-black/25">
                                <img src="{{ Vite::asset('resources/images/logo.png') }}" alt="Samir Foundation Medical Hub" class="h-14 w-auto object-contain sm:h-16">
                            </div>
                            <div>
                                <p class="text-3xl font-black tracking-tight">{{ $content['brand_title'] }}</p>
                                <p class="text-base font-semibold text-teal-100">{{ $content['brand_subtitle'] }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="max-w-4xl py-12">
                        <p class="text-sm font-extrabold uppercase tracking-[0.28em] text-teal-200">{{ $content['hero_eyebrow'] }}</p>
                        <h1 class="mt-5 text-5xl font-black leading-[0.95] tracking-tight lg:text-7xl">
                            {{ $content['hero_title'] }}
                        </h1>
                        <p class="mt-6 max-w-2xl text-lg leading-8 text-white/75">
                            {{ $content['project_intro'] }}
                        </p>

                        <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                            @auth
                                <a href="{{ route('dashboard') }}" class="inline-flex items-center justify-center rounded-full bg-white px-6 py-3 text-sm font-extrabold text-stone-950 shadow-lg shadow-black/20 hover:bg-teal-50">
                                    {{ $content['primary_cta_auth'] }}
                                </a>
                            @else
                                <a href="{{ route('login') }}" class="inline-flex items-center justify-center rounded-full bg-white px-6 py-3 text-sm font-extrabold text-stone-950 shadow-lg shadow-black/20 hover:bg-teal-50">
                                    {{ $content['primary_cta_guest'] }}
                                </a>
                            @endauth
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3 pb-4 sm:grid-cols-4">
                        <div class="rounded-lg border border-white/10 bg-white/[0.08] p-4">
                            <p class="text-2xl font-extrabold">{{ number_format($stats['students']) }}</p>
                            <p class="mt-1 text-xs font-semibold text-white/58">{{ $content['stat_students_label'] }}</p>
                        </div>
                        <div class="rounded-lg border border-white/10 bg-white/[0.08] p-4">
                            <p class="text-2xl font-extrabold">{{ number_format($stats['bookings']) }}</p>
                            <p class="mt-1 text-xs font-semibold text-white/58">{{ $content['stat_bookings_label'] }}</p>
                        </div>
                        <div class="rounded-lg border border-white/10 bg-white/[0.08] p-4">
                            <p class="text-2xl font-extrabold">{{ number_format($stats['availableSeats']) }}</p>
                            <p class="mt-1 text-xs font-semibold text-white/58">{{ $content['stat_seats_label'] }}</p>
                        </div>
                        <div class="rounded-lg border border-white/10 bg-white/[0.08] p-4">
                            <p class="text-2xl font-extrabold">{{ number_format($stats['branches']) }}</p>
                            <p class="mt-1 text-xs font-semibold text-white/58">{{ $content['stat_branches_label'] }}</p>
                        </div>
                    </div>
                </div>

                <aside class="self-center rounded-xl border border-white/70 bg-white/95 p-5 text-slate-950 shadow-2xl shadow-black/30">
                    <div class="rounded-lg border border-slate-200 p-5">
                        <p class="text-sm font-bold uppercase tracking-wide text-teal-700">{{ $content['student_card_eyebrow'] }}</p>
                        <h2 class="mt-3 text-2xl font-black">{{ $content['student_card_title'] }}</h2>
                        <p class="mt-2 text-sm leading-6 text-stone-600">
                            {{ $content['student_card_description'] }}
                        </p>

                        @guest
                            <a href="{{ route('login') }}" class="mt-5 flex w-full items-center justify-center rounded-md bg-stone-950 px-4 py-3 text-sm font-extrabold text-white hover:bg-stone-800">
                                {{ $content['student_card_guest_button'] }}
                            </a>
                            @if(Route::has('password.request'))
                                <a href="{{ route('password.request') }}" class="mt-3 flex w-full items-center justify-center rounded-md border border-stone-300 px-4 py-3 text-sm font-bold text-stone-700 hover:bg-stone-100">
                                    {{ $content['student_card_help_button'] }}
                                </a>
                            @endif
                        @else
                            <a href="{{ route('calendar.index') }}" class="mt-5 flex w-full items-center justify-center rounded-md bg-stone-950 px-4 py-3 text-sm font-extrabold text-white hover:bg-stone-800">
                                {{ $content['student_card_auth_button'] }}
                            </a>
                        @endguest
                    </div>

                    <div class="mt-5 rounded-lg border border-stone-200 p-5">
                        <p class="text-sm font-bold uppercase tracking-wide text-stone-500">{{ $content['team_card_eyebrow'] }}</p>
                        <p class="mt-2 text-sm leading-6 text-stone-600">
                            {{ $content['team_card_description'] }}
                        </p>
                        <div class="mt-4 grid grid-cols-2 gap-2">
                            <a href="{{ route('staff.login') }}" class="flex items-center justify-center rounded-md border border-stone-300 px-3 py-2 text-sm font-extrabold text-stone-700 hover:bg-stone-100">
                                {{ $content['team_staff_button'] }}
                            </a>
                            <a href="{{ route('admin.login') }}" class="flex items-center justify-center rounded-md border border-stone-300 px-3 py-2 text-sm font-extrabold text-stone-700 hover:bg-stone-100">
                                {{ $content['team_admin_button'] }}
                            </a>
                        </div>
                    </div>

                    <div class="mt-5 rounded-lg bg-[#eef2f0] p-4">
                        <p class="text-sm font-bold text-slate-900">{{ $content['partners_heading'] }}</p>
                        @if($logosImage)
                            <div class="mt-4 rounded-lg border border-white bg-white px-3 py-2">
                                <img src="{{ $logosImage }}" alt="Supporting partners logos" class="mx-auto h-16 w-auto object-contain">
                            </div>
                        @else
                            <div class="mt-4 grid grid-cols-3 gap-2">
                                @forelse($supporters->take(3) as $supporter)
                                    <div class="flex h-20 items-center justify-center rounded-md border border-stone-200 bg-white px-2 text-center text-xs font-extrabold text-stone-600">
                                        {{ $supporter }}
                                    </div>
                                @empty
                                    <div class="col-span-3 rounded-md border border-stone-200 bg-white p-4 text-center text-sm text-stone-500">
                                        {{ $content['partners_empty_text'] }}
                                    </div>
                                @endforelse
                            </div>
                        @endif
                    </div>
                </aside>
            </div>
        </section>

        <section class="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8">
            <div class="grid overflow-hidden rounded-xl border border-white bg-white shadow-sm lg:grid-cols-3">
                <div class="border-b border-slate-200 p-7 lg:border-b-0 lg:border-r">
                    <p class="text-sm font-bold uppercase tracking-wide text-teal-700">{{ $content['step_1_label'] }}</p>
                    <h2 class="mt-3 text-xl font-extrabold">{{ $content['step_1_title'] }}</h2>
                    <p class="mt-2 text-sm leading-6 text-stone-600">{{ $content['step_1_description'] }}</p>
                </div>
                <div class="border-b border-slate-200 p-7 lg:border-b-0 lg:border-r">
                    <p class="text-sm font-bold uppercase tracking-wide text-blue-700">{{ $content['step_2_label'] }}</p>
                    <h2 class="mt-3 text-xl font-extrabold">{{ $content['step_2_title'] }}</h2>
                    <p class="mt-2 text-sm leading-6 text-stone-600">{{ $content['step_2_description'] }}</p>
                </div>
                <div class="p-7">
                    <p class="text-sm font-bold uppercase tracking-wide text-amber-700">{{ $content['step_3_label'] }}</p>
                    <h2 class="mt-3 text-xl font-extrabold">{{ $content['step_3_title'] }}</h2>
                    <p class="mt-2 text-sm leading-6 text-stone-600">{{ $content['step_3_description'] }}</p>
                </div>
            </div>
        </section>

        <footer class="border-t border-slate-200 bg-[#071817] text-white">
            <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
                <div class="grid gap-10 lg:grid-cols-[1.25fr_0.9fr_0.9fr]">
                    <div>
                        <div class="inline-flex rounded-[1.1rem] bg-white px-3 py-2">
                            <img src="{{ Vite::asset('resources/images/logo.png') }}" alt="Samir Foundation Medical Hub" class="h-12 w-auto object-contain">
                        </div>
                        <h2 class="mt-6 max-w-md text-3xl font-black tracking-tight">{{ $content['footer_title'] }}</h2>
                        <p class="mt-4 max-w-xl text-sm leading-7 text-white/68">{{ $content['footer_description'] }}</p>

                        @if($content['footer_cta_text'])
                            <div class="mt-6 rounded-lg border border-white/10 bg-white/[0.06] p-4">
                                <p class="text-sm font-semibold leading-6 text-white/78">{{ $content['footer_cta_text'] }}</p>
                                @if($footerCtaUrl)
                                    <a href="{{ $footerCtaUrl }}" target="_blank" rel="noreferrer" class="mt-3 inline-flex rounded-md bg-white px-4 py-2 text-sm font-extrabold text-slate-950 hover:bg-teal-50">
                                        {{ $content['footer_cta_button'] }}
                                    </a>
                                @endif
                            </div>
                        @endif
                    </div>

                    <div>
                        <h3 class="text-sm font-extrabold uppercase tracking-[0.22em] text-teal-200">{{ $content['footer_contact_heading'] }}</h3>
                        <div class="mt-5 space-y-3">
                            @forelse($contactLines as $line)
                                <p class="border-b border-white/10 pb-3 text-sm font-semibold text-white/76">{{ $line }}</p>
                            @empty
                                <p class="text-sm text-white/55">Contact information can be edited from the admin dashboard.</p>
                            @endforelse
                        </div>
                    </div>

                    <div>
                        <h3 class="text-sm font-extrabold uppercase tracking-[0.22em] text-teal-200">{{ $content['footer_support_heading'] }}</h3>
                        <p class="mt-5 text-sm leading-7 text-white/68">{{ $content['supporters_note'] }}</p>

                        @if($supporters->isNotEmpty())
                            <div class="mt-5 flex flex-wrap gap-2">
                                @foreach($supporters as $supporter)
                                    <span class="rounded-full border border-white/12 px-3 py-1.5 text-xs font-bold text-white/75">{{ $supporter }}</span>
                                @endforeach
                            </div>
                        @endif

                        <div class="mt-6 flex flex-wrap gap-2">
                            @foreach($socialLines as $line)
                                @php
                                    [$label, $url] = array_pad(explode(': ', $line, 2), 2, '#');
                                @endphp
                                <a href="{{ $url }}" target="_blank" rel="noreferrer" class="rounded-full bg-white px-4 py-2 text-sm font-extrabold text-slate-950 hover:bg-teal-50">
                                    {{ $label }}
                                </a>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="mt-10 border-t border-white/10 pt-5 text-xs font-semibold text-white/45">
                    {{ $content['footer_bottom_text'] }}
                </div>
            </div>
        </footer>
    </main>
</body>
</html>
