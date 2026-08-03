<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        @php
            use App\Models\SiteContent;

            $siteTitle = \App\Models\SiteContent::getValue('page_title', 'Samir Medical Hub') ?: 'Samir Medical Hub';
            $routeTitles = [
                'login' => 'Student Login',
                'login.hub' => 'Hub Login',
                'staff.login' => 'Staff Login',
                'admin.login' => 'Admin Login',
                'register' => 'Create Account',
                'password.request' => 'Reset Password',
                'password.reset' => 'Set Password',
            ];
            $pageTitle = $routeTitles[request()->route()?->getName()] ?? null;
            $prefix = $location ? 'hub_' . $location->id . '_' : '';
            $guestLogoUrl = SiteContent::getValue('site_logo_url', '');
            $guestLogoUrl = $guestLogoUrl ?: Vite::asset('resources/images/logo.png');
            $guestEyebrow = $location
                ? SiteContent::getValue($prefix . 'login_hero_eyebrow', $location->name . ' Hub')
                : 'Samir Foundation';
            $guestTitle = $location
                ? SiteContent::getValue($prefix . 'login_hero_title', $location->name . ' Medical Hub')
                : 'Medical Hub for focused study and online exams.';
            $guestDescription = $location
                ? SiteContent::getValue($prefix . 'login_hero_description', 'A calm student space prepared for reliable study, online exams, and weekly booking at ' . $location->name . ' Hub.')
                : 'A calm student space prepared with dependable electricity, internet access, and a clear booking flow for weekly seats.';
            $guestCards = [
                [
                    'label' => $location ? SiteContent::getValue($prefix . 'login_card_1_label', 'Power') : 'Power',
                    'title' => $location ? SiteContent::getValue($prefix . 'login_card_1_title', 'Reliable setup') : 'Reliable setup',
                ],
                [
                    'label' => $location ? SiteContent::getValue($prefix . 'login_card_2_label', 'Internet') : 'Internet',
                    'title' => $location ? SiteContent::getValue($prefix . 'login_card_2_title', 'Study ready') : 'Study ready',
                ],
                [
                    'label' => $location ? SiteContent::getValue($prefix . 'login_card_3_label', 'Hub') : 'Branches',
                    'title' => $location ? SiteContent::getValue($prefix . 'login_card_3_title', $location->name) : 'Gaza and Khan Younis',
                ],
            ];
            $gallery = $location ? json_decode(SiteContent::getValue($prefix . 'supporter_gallery', '[]'), true) : [];
            $gallery = collect(is_array($gallery) ? $gallery : [])
                ->map(fn ($item) => ['name' => $item['name'] ?? '', 'image' => $item['url'] ?? ''])
                ->filter(fn ($item) => $item['name'] || $item['image']);
            $galleryLoop = $gallery->count() > 1 ? $gallery->concat($gallery)->concat($gallery)->values() : $gallery->values();
        @endphp
        <title>{{ $pageTitle ? $pageTitle . ' - ' . $siteTitle : $siteTitle }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-gray-900 antialiased">
        <x-toast-notifications />

        <div class="min-h-screen bg-[#eef2f0] px-4 py-8">
            <div class="mx-auto flex min-h-[calc(100vh-4rem)] max-w-6xl items-center justify-center">
                <div class="grid w-full gap-8 lg:grid-cols-[1fr_520px] lg:items-center">
                    <section class="hidden lg:block">
                        @unless($location)
                            <a href="/" class="inline-flex items-center rounded-[1.4rem] border border-white/80 bg-white/85 px-4 py-3 shadow-xl shadow-slate-900/10">
                                <img src="{{ $guestLogoUrl }}" alt="Samir Foundation Medical Hub" class="h-14 w-auto object-contain" />
                            </a>
                        @endunless
                        <p class="mt-10 text-sm font-extrabold uppercase tracking-[0.28em] text-teal-700">{{ $guestEyebrow }}</p>
                        <h1 class="mt-4 max-w-2xl text-6xl font-black leading-[0.95] text-slate-950">
                            {{ $guestTitle }}
                        </h1>
                        <p class="mt-6 max-w-xl text-lg leading-8 text-slate-600">
                            {{ $guestDescription }}
                        </p>

                        @if($gallery->isNotEmpty())
                            <div class="mt-8 max-w-xl rounded-xl border border-white bg-white/60 p-4 shadow-sm">
                                <p class="text-sm font-extrabold text-slate-950">Supporters Gallery</p>
                                <div class="supporter-carousel supporter-carousel--login mt-4" style="--supporter-count: {{ max($gallery->count(), 1) }};" aria-label="Hub supporters carousel">
                                    <div class="supporter-carousel__track supporter-carousel__track--compact">
                                        @foreach($galleryLoop as $supporter)
                                            <div class="supporter-carousel__item supporter-carousel__item--login">
                                                @if($supporter['image'])
                                                    <img src="{{ $supporter['image'] }}" alt="{{ $supporter['name'] }}" class="max-h-20 w-auto object-contain">
                                                @else
                                                    <span class="text-center text-xs font-extrabold text-stone-600">{{ $supporter['name'] }}</span>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        @endif

                        <div class="mt-8 grid max-w-xl grid-cols-3 gap-3">
                            @foreach($guestCards as $card)
                                <div class="rounded-lg border border-white bg-white/70 p-4 shadow-sm">
                                    <p class="text-xs font-bold uppercase tracking-wide text-slate-500">{{ $card['label'] }}</p>
                                    <p class="mt-2 text-sm font-extrabold text-slate-950">{{ $card['title'] }}</p>
                                </div>
                            @endforeach
                        </div>
                    </section>

                    <div>
                        @unless($location)
                        <div class="mb-5 flex justify-center lg:hidden">
                            <a href="/" class="rounded-[1.2rem] border border-white/80 bg-white/85 px-4 py-3 shadow-lg shadow-slate-900/10">
                                <img src="{{ $guestLogoUrl }}" alt="Samir Foundation Medical Hub" class="h-12 w-auto object-contain" />
                            </a>
                        </div>
                        @endunless

            <div class="w-full overflow-hidden rounded-xl border border-white/80 bg-white/95 px-6 py-6 shadow-2xl shadow-slate-900/10 sm:px-8">
                {{ $slot }}
            </div>
                    </div>
                </div>
            </div>
        </div>
    </body>
</html>
