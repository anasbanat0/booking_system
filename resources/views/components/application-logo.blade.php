@php($siteLogoUrl = \App\Models\SiteContent::getValue('site_logo_url', ''))
<img src="{{ $siteLogoUrl ?: Vite::asset('resources/images/logo.png') }}" alt="Samir Foundation Medical Hub" {{ $attributes->merge(['class' => 'block h-10 w-auto object-contain']) }}>
