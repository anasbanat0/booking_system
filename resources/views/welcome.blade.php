<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $content['page_title'] }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="overflow-x-hidden bg-[#eef2f0] font-sans text-slate-950">
    @php
        $contactBlocks = collect(preg_split('/\R{2,}/', trim($content['contact_info'] ?? '')))
            ->map(fn ($block) => collect(preg_split('/\r\n|\r|\n/', trim($block)))->filter()->values())
            ->filter(fn ($block) => $block->isNotEmpty())
            ->values();
        $footerLegalLines = collect(preg_split('/\r\n|\r|\n/', $content['footer_legal_text'] ?? ''))->filter();
        $socialLines = collect(preg_split('/\r\n|\r|\n/', $content['social_links'] ?? ''))->filter();
        $socialLinks = $socialLines
            ->map(function ($line) {
                [$label, $url] = array_pad(explode(': ', $line, 2), 2, '#');
                $label = trim($label);

                return [
                    'label' => $label,
                    'url' => trim($url) ?: '#',
                    'icon' => match (strtolower($label)) {
                        'facebook' => 'fa-brands fa-facebook-f',
                        'instagram' => 'fa-brands fa-instagram',
                        'linkedin' => 'fa-brands fa-linkedin-in',
                        'youtube' => 'fa-brands fa-youtube',
                        default => 'fa-solid fa-link',
                    },
                ];
            })
            ->reject(fn ($item) => str_contains(strtolower($item['label']), 'whatsapp'))
            ->values();

        foreach (['LinkedIn' => 'fa-brands fa-linkedin-in', 'YouTube' => 'fa-brands fa-youtube'] as $label => $icon) {
            if (!$socialLinks->contains(fn ($item) => strtolower($item['label']) === strtolower($label))) {
                $socialLinks->push(['label' => $label, 'url' => '#', 'icon' => $icon]);
            }
        }
        $locations = $locations ?? (\Illuminate\Support\Facades\Schema::hasTable('booking_locations')
            ? \App\Models\BookingLocation::where('is_active', true)->orderBy('name')->get()
            : collect());
        $selectedLocation = $selectedLocation ?? null;
        $uploadedGallery = json_decode($content['supporter_gallery'] ?? '[]', true);
        $supporterCarousel = collect(is_array($uploadedGallery) ? $uploadedGallery : [])
            ->map(fn ($item) => [
                'name' => $item['name'] ?? '',
                'image' => $item['url'] ?? '',
                'url' => '',
            ])
            ->filter(fn ($item) => $item['name'] || $item['image']);

        if ($supporterCarousel->isEmpty()) {
            $supporterCarousel = collect(preg_split('/\r\n|\r|\n/', $content['supporter_carousel'] ?? ''))
                ->map(function ($line) {
                    [$name, $image, $url] = array_pad(array_map('trim', explode('|', $line, 3)), 3, '');

                    return compact('name', 'image', 'url');
                })
                ->filter(fn ($item) => $item['name'] || $item['image']);
        }
        $supporterCarouselLoop = $supporterCarousel->count() > 1
            ? $supporterCarousel->concat($supporterCarousel)->concat($supporterCarousel)->values()
            : $supporterCarousel->values();
        $siteLogoUrl = $content['site_logo_url'] ?: Vite::asset('resources/images/logo.png');
        $logosImage = file_exists(public_path('images/supporters-logos.png')) ? asset('images/supporters-logos.png') : null;
        $footerCtaUrl = trim($content['footer_cta_url'] ?? '');
        $uploadedHeroBackgrounds = json_decode($content['hero_background_gallery'] ?? '[]', true);
        $heroBackgrounds = collect(is_array($uploadedHeroBackgrounds) ? $uploadedHeroBackgrounds : [])
            ->map(fn ($item) => [
                'name' => $item['name'] ?? 'Medical Hub',
                'image' => $item['url'] ?? '',
            ])
            ->filter(fn ($item) => $item['image'])
            ->values();

        if ($heroBackgrounds->isEmpty()) {
            $heroBackgrounds = collect([
                ['name' => 'Medical Hub study space', 'image' => Vite::asset('resources/images/IMG_0491.webp')],
                ['name' => 'Medical Hub study space', 'image' => Vite::asset('resources/images/IMG_0605.webp')],
                ['name' => 'Medical Hub study space', 'image' => Vite::asset('resources/images/IMG_0612.webp')],
                ['name' => 'Medical Hub study space', 'image' => Vite::asset('resources/images/IMG_0623.webp')],
                ['name' => 'Medical Hub study space', 'image' => Vite::asset('resources/images/IMG_0642.webp')],
            ]);
        }

        $uploadedEventGallery = json_decode($content['event_gallery'] ?? '[]', true);
        $eventGallery = collect(is_array($uploadedEventGallery) ? $uploadedEventGallery : [])
            ->map(fn ($item) => [
                'name' => trim($item['name'] ?? ''),
                'image' => $item['url'] ?? '',
            ])
            ->filter(fn ($item) => $item['image'])
            ->values();
        $eventGalleryLoop = $eventGallery->count() > 1
            ? $eventGallery->concat($eventGallery)->concat($eventGallery)->values()
            : $eventGallery->values();
    @endphp

    <main class="public-home-page min-h-screen">
        <section class="relative w-full max-w-full bg-[#071817] text-white">
            <div class="hero-background-carousel" style="--hero-bg-duration: {{ max($heroBackgrounds->count(), 1) * 6 }}s;" aria-hidden="true">
                @foreach($heroBackgrounds as $index => $background)
                    <img
                        src="{{ $background['image'] }}"
                        alt=""
                        class="hero-background-carousel__image"
                        style="animation-delay: {{ $index * 6 }}s;"
                    >
                @endforeach
            </div>
            <div class="absolute inset-0 bg-black/58"></div>
            <div class="absolute inset-x-0 top-0 h-px bg-white/20"></div>

            <div class="relative mx-auto grid w-full max-w-7xl gap-8 overflow-hidden px-4 py-8 sm:px-6 lg:px-8 lg:py-10 xl:grid-cols-[minmax(0,1fr)_380px] xl:gap-10 xl:py-12">
                <div class="flex min-w-0 flex-col justify-between xl:min-h-[590px]">
                    <div class="flex flex-col gap-5 sm:flex-row sm:items-center sm:justify-between">
                        <div class="flex min-w-0 flex-col gap-4 sm:flex-row sm:items-center">
                            <a href="{{ url('/') }}" aria-label="Go to homepage" class="public-home-hero-logo inline-flex w-full max-w-[18rem] items-center justify-center overflow-hidden rounded-[1.15rem] border border-white/80 bg-white/90 px-3 py-2 shadow-2xl shadow-black/30 transition hover:bg-white/95 hover:shadow-black/35 focus:outline-none focus:ring-2 focus:ring-[#9fc6e4] sm:w-fit sm:max-w-[24rem] md:max-w-[28rem] lg:max-w-[30rem]">
                                <img src="{{ $siteLogoUrl }}" alt="Samir Foundation Medical Hub" class="h-9 w-full object-contain sm:h-10 md:h-11 lg:h-12">
                            </a>
                            <div class="min-w-0">
                                <p class="public-home-hero-brand text-2xl font-black tracking-tight sm:text-3xl">{{ $content['brand_title'] }}</p>
                                <p class="public-home-hero-brand text-base font-semibold text-[#d5ecff]">{{ $content['brand_subtitle'] }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="min-w-0 max-w-full py-10 xl:max-w-4xl xl:py-12">
                        <p class="public-home-hero-kicker max-w-full break-words text-xs font-extrabold uppercase tracking-[0.18em] text-[#b8dfff] sm:text-sm sm:tracking-[0.24em]">{{ $content['hero_eyebrow'] }}</p>
                        <h1 class="public-home-hero-title mt-5 max-w-full break-words text-4xl font-black leading-[1.06] tracking-tight sm:text-5xl md:text-5xl lg:text-6xl xl:text-6xl">
                            {{ $content['hero_title'] }}
                        </h1>
                        <p class="public-home-hero-text mt-6 max-w-full break-words text-base leading-8 text-white/75 sm:text-lg lg:max-w-2xl">
                            {{ $content['project_intro'] }}
                        </p>

                        <div class="mt-8 flex max-w-full flex-col items-start gap-3 sm:flex-row">
                            @auth
                                <a href="{{ route('dashboard') }}" class="inline-flex w-auto max-w-full items-center justify-center rounded-full bg-white px-6 py-3 text-sm font-extrabold text-stone-950 shadow-lg shadow-black/20 hover:bg-[#edf6fd]">
                                    {{ $content['primary_cta_auth'] }}
                                </a>
                            @else
                                <a href="{{ route('login') }}" class="inline-flex w-auto max-w-full items-center justify-center rounded-full bg-white px-6 py-3 text-sm font-extrabold text-stone-950 shadow-lg shadow-black/20 hover:bg-[#edf6fd]">
                                    {{ $content['primary_cta_guest'] }}
                                </a>
                            @endauth
                        </div>

                    </div>

                    <div class="grid grid-cols-1 gap-3 pb-4 sm:grid-cols-2 md:grid-cols-4">
                        <div class="rounded-lg border border-white/10 bg-white/[0.08] p-4">
                            <p class="text-2xl font-extrabold" data-count-up="{{ $stats['students'] }}">0</p>
                            <p class="mt-1 text-xs font-semibold text-white/58">{{ $content['stat_students_label'] }}</p>
                        </div>
                        <div class="rounded-lg border border-white/10 bg-white/[0.08] p-4">
                            <p class="text-2xl font-extrabold" data-count-up="{{ $stats['bookings'] }}">0</p>
                            <p class="mt-1 text-xs font-semibold text-white/58">{{ $content['stat_bookings_label'] }}</p>
                        </div>
                        <div class="rounded-lg border border-white/10 bg-white/[0.08] p-4">
                            <p class="text-2xl font-extrabold" data-count-up="{{ $stats['studyHours'] }}">0</p>
                            <p class="mt-1 text-xs font-semibold text-white/58">{{ $content['stat_seats_label'] }}</p>
                        </div>
                        <div class="rounded-lg border border-white/10 bg-white/[0.08] p-4">
                            <p class="text-2xl font-extrabold" data-count-up="{{ $stats['branches'] }}">0</p>
                            <p class="mt-1 text-xs font-semibold text-white/58">{{ $content['stat_branches_label'] }}</p>
                        </div>
                    </div>
                </div>

                <aside class="w-full self-center rounded-xl border border-white/70 bg-white/95 p-4 text-slate-950 shadow-2xl shadow-black/30 sm:p-5 xl:max-w-[380px]">
                    <div class="rounded-lg border border-slate-200 p-5">
                        <p class="text-sm font-bold uppercase tracking-wide text-[#2f6fa3]">{{ $content['student_card_eyebrow'] }}</p>
                        <h2 class="mt-3 text-2xl font-black">{{ $content['student_card_title'] }}</h2>
                        <p class="mt-2 text-sm leading-6 text-stone-600">
                            {{ $content['student_card_description'] }}
                        </p>

                        @if(!$selectedLocation && $locations->count() > 1)
                            <div class="mt-5 grid grid-cols-1 gap-2 sm:grid-cols-2">
                                @foreach($locations as $location)
                                    <a href="{{ route('login.hub', $location->slug) }}"
                                       class="flex items-center justify-center rounded-md border border-[#2f6fa3] bg-[#2f6fa3] px-4 py-3 text-sm font-extrabold text-white transition hover:border-[#255a84] hover:bg-[#255a84]">
                                        {{ $location->name }} Hub
                                    </a>
                                @endforeach
                            </div>
                        @else
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
                        @endif
                    </div>

                    <div class="mt-5 rounded-lg border border-stone-200 p-5">
                        <p class="text-2xl font-black text-slate-950">{{ $content['team_card_eyebrow'] }}</p>
                        <p class="mt-2 text-sm leading-6 text-stone-600">
                            {{ $content['team_card_description'] }}
                        </p>
                        <div class="mt-4 grid grid-cols-1 gap-2 sm:grid-cols-2">
                            <a href="{{ route('staff.login') }}" class="flex items-center justify-center rounded-md border border-stone-300 px-3 py-2 text-sm font-extrabold text-stone-700 hover:bg-stone-100">
                                {{ $content['team_staff_button'] }}
                            </a>
                            <a href="{{ route('admin.login') }}" class="flex items-center justify-center rounded-md border border-stone-300 px-3 py-2 text-sm font-extrabold text-stone-700 hover:bg-stone-100">
                                {{ $content['team_admin_button'] }}
                            </a>
                        </div>
                    </div>

                </aside>
            </div>
        </section>

        <section class="border-y border-slate-200 bg-white/70 py-10">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="mb-5 flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <p class="text-sm font-extrabold uppercase tracking-[0.22em] text-[#2f6fa3]">{{ $content['inside_eyebrow'] ?? 'Inside the Medical Hub' }}</p>
                        <h2 class="mt-2 text-3xl font-black text-slate-950">{{ $content['inside_heading'] ?? 'Inside the Medical Hub' }}</h2>
                    </div>
                </div>

                @if($eventGallery->isNotEmpty())
                    <div class="inside-hub-carousel" style="--inside-count: {{ max($eventGallery->count(), 1) }};" aria-label="Inside the Medical Hub carousel">
                        <div class="inside-hub-carousel__track {{ $eventGallery->count() > 1 ? 'is-looping' : '' }}">
                            @foreach($eventGalleryLoop as $photo)
                                <button type="button"
                                        class="inside-hub-carousel__item"
                                        data-gallery-image="{{ $photo['image'] }}"
                                        data-gallery-title="{{ $photo['name'] }}"
                                        aria-label="Open {{ $photo['name'] ?: 'Medical Hub photo' }}">
                                    <img src="{{ $photo['image'] }}" alt="{{ $photo['name'] ?: 'Medical Hub photo' }}" class="inside-hub-carousel__image">
                                    @if($photo['name'])
                                        <span class="inside-hub-carousel__caption">{{ $photo['name'] }}</span>
                                    @endif
                                </button>
                            @endforeach
                        </div>
                    </div>
                @else
                    <div class="rounded-xl border border-slate-200 bg-white p-8 text-center text-sm font-semibold text-slate-500">
                        {{ $content['inside_empty_text'] ?? 'Medical Hub photos can be added from the admin dashboard.' }}
                    </div>
                @endif
            </div>
        </section>

        <div id="inside-hub-lightbox" class="inside-hub-lightbox hidden" aria-hidden="true">
            <button type="button" class="inside-hub-lightbox__backdrop" data-gallery-close aria-label="Close gallery preview"></button>
            <div class="inside-hub-lightbox__panel" role="dialog" aria-modal="true" aria-label="Medical Hub photo preview">
                <button type="button" class="inside-hub-lightbox__close" data-gallery-close aria-label="Close gallery preview">Close</button>
                <img src="" alt="" class="inside-hub-lightbox__image" data-gallery-preview-image>
                <p class="inside-hub-lightbox__title" data-gallery-preview-title></p>
            </div>
        </div>

        <section class="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8">
            <div class="grid overflow-hidden rounded-xl border border-white bg-white shadow-sm lg:grid-cols-3">
                <div class="border-b border-slate-200 p-7 lg:border-b-0 lg:border-r">
                    <p class="text-sm font-bold uppercase tracking-wide text-[#2f6fa3]">{{ $content['step_1_label'] }}</p>
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

        <footer class="border-t border-white/20 bg-[#255a84] text-white">
            <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
                <div class="grid gap-10 lg:grid-cols-[1.25fr_0.9fr_0.9fr]">
                    <div>
                        <a href="{{ url('/') }}" aria-label="Go to homepage" class="inline-flex rounded-[1.1rem] bg-white px-3 py-2 transition hover:bg-[#edf6fd] focus:outline-none focus:ring-2 focus:ring-[#9fc6e4]">
                            <img src="{{ $siteLogoUrl }}" alt="Samir Foundation Medical Hub" class="h-12 w-auto object-contain">
                        </a>
                        <h2 class="mt-6 max-w-md text-3xl font-black tracking-tight text-white">{{ $content['footer_title'] }}</h2>
                        <p class="mt-4 max-w-xl text-sm font-medium leading-7 text-white/95">{{ $content['footer_description'] }}</p>

                        @if($content['footer_cta_text'])
                            <div class="mt-6 rounded-lg border border-white/55 bg-white/12 p-4">
                                <p class="text-sm font-bold leading-6 text-white/95">{{ $content['footer_cta_text'] }}</p>
                                @if($footerCtaUrl)
                                    <a href="{{ $footerCtaUrl }}" target="_blank" rel="noreferrer" class="mt-3 inline-flex rounded-md bg-[#2f6fa3] px-4 py-2 text-sm font-extrabold text-white hover:bg-[#255a84]">
                                        {{ $content['footer_cta_button'] }}
                                    </a>
                                @endif
                            </div>
                        @endif
                    </div>

                    <div>
                        <h3 class="text-sm font-extrabold uppercase tracking-[0.22em] text-white">{{ $content['footer_contact_heading'] }}</h3>
                        <div class="mt-5 space-y-4">
                            @forelse($contactBlocks as $block)
                                <div class="border-b border-white/70 pb-4">
                                    @foreach($block as $index => $line)
                                        <p class="{{ $index === 0 ? 'text-sm font-extrabold text-white' : 'mt-1 text-sm font-semibold leading-6 text-white/92' }}">{{ $line }}</p>
                                    @endforeach
                                </div>
                            @empty
                                <p class="text-sm text-white/68">Contact information can be edited from the admin dashboard.</p>
                            @endforelse
                        </div>
                    </div>

                    <div>
                        <h3 class="text-sm font-extrabold uppercase tracking-[0.22em] text-white">{{ $content['footer_legal_heading'] }}</h3>
                        <div class="mt-5 space-y-1 text-sm leading-6 text-white/95">
                            @foreach($footerLegalLines as $index => $line)
                                <p class="{{ $index === 1 ? 'font-extrabold' : 'font-medium' }}">{{ $line }}</p>
                            @endforeach
                        </div>

                        <div class="mt-6 flex flex-wrap gap-3">
                            @foreach($socialLinks as $social)
                                <a href="{{ $social['url'] }}" target="_blank" rel="noreferrer" class="inline-flex h-11 w-11 items-center justify-center rounded-full bg-white text-lg text-[#255a84] shadow-sm transition hover:-translate-y-0.5 hover:bg-[#edf6fd]" aria-label="{{ $social['label'] }}">
                                    <i class="{{ $social['icon'] }}"></i>
                                </a>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="mt-10 border-t border-white/70 pt-5 text-xs font-bold text-white/80">
                    {{ $content['footer_bottom_text'] }}
                </div>
            </div>
        </footer>
    </main>
    <script>
        const countUpElements = document.querySelectorAll('[data-count-up]');
        const countFormatter = new Intl.NumberFormat('en-US');

        function animateCountUp(element) {
            const target = Number.parseInt(element.dataset.countUp || '0', 10);
            const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

            if (!Number.isFinite(target) || target <= 0 || prefersReducedMotion) {
                element.textContent = countFormatter.format(Math.max(target, 0));
                return;
            }

            const duration = Math.min(1800, Math.max(850, target / 18));
            const startTime = performance.now();

            function tick(now) {
                const progress = Math.min((now - startTime) / duration, 1);
                const eased = 1 - Math.pow(1 - progress, 3);
                element.textContent = countFormatter.format(Math.round(target * eased));

                if (progress < 1) {
                    requestAnimationFrame(tick);
                } else {
                    element.textContent = countFormatter.format(target);
                }
            }

            requestAnimationFrame(tick);
        }

        if ('IntersectionObserver' in window) {
            const countObserver = new IntersectionObserver((entries, observer) => {
                entries.forEach(entry => {
                    if (!entry.isIntersecting) {
                        return;
                    }

                    animateCountUp(entry.target);
                    observer.unobserve(entry.target);
                });
            }, { threshold: 0.45 });

            countUpElements.forEach(element => countObserver.observe(element));
        } else {
            countUpElements.forEach(animateCountUp);
        }

        const insideHubLightbox = document.getElementById('inside-hub-lightbox');
        const insideHubPreviewImage = document.querySelector('[data-gallery-preview-image]');
        const insideHubPreviewTitle = document.querySelector('[data-gallery-preview-title]');

        document.querySelectorAll('[data-gallery-image]').forEach(button => {
            button.addEventListener('click', () => {
                if (!insideHubLightbox || !insideHubPreviewImage || !insideHubPreviewTitle) {
                    return;
                }

                const title = button.dataset.galleryTitle || '';
                insideHubPreviewImage.src = button.dataset.galleryImage || '';
                insideHubPreviewImage.alt = title || 'Medical Hub photo';
                insideHubPreviewTitle.textContent = title;
                insideHubLightbox.classList.remove('hidden');
                insideHubLightbox.setAttribute('aria-hidden', 'false');
            });
        });

        document.querySelectorAll('[data-gallery-close]').forEach(button => {
            button.addEventListener('click', () => {
                if (!insideHubLightbox || !insideHubPreviewImage) {
                    return;
                }

                insideHubLightbox.classList.add('hidden');
                insideHubLightbox.setAttribute('aria-hidden', 'true');
                insideHubPreviewImage.src = '';
            });
        });

        document.addEventListener('keydown', event => {
            if (event.key === 'Escape' && insideHubLightbox && !insideHubLightbox.classList.contains('hidden')) {
                insideHubLightbox.classList.add('hidden');
                insideHubLightbox.setAttribute('aria-hidden', 'true');
                if (insideHubPreviewImage) {
                    insideHubPreviewImage.src = '';
                }
            }
        });
    </script>
</body>
</html>
