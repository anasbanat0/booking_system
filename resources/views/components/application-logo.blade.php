@php
    $siteLogoUrl = \App\Models\SiteContent::getValue('site_logo_url', '');
    $user = auth()->user();
    $hubLogoUrl = '';

    if ($user && in_array($user->role, ['student', 'staff'], true) && $user->booking_location_id) {
        $hubLogoUrl = \App\Models\SiteContent::getValue('hub_' . $user->booking_location_id . '_logo_url', '');
    }

    $logoUrl = $hubLogoUrl ?: ($siteLogoUrl ?: Vite::asset('resources/images/logo.png'));
@endphp

<img src="{{ $logoUrl }}" alt="Samir Foundation Medical Hub" {{ $attributes->merge(['class' => 'block h-10 w-auto object-contain']) }}>
