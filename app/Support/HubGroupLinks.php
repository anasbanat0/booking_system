<?php

namespace App\Support;

use App\Models\BookingLocation;

class HubGroupLinks
{
    private static array $slugs = [];

    public static function forLocation(BookingLocation $location): ?string
    {
        $url = config('services.hub_groups.'.$location->slug);

        return filled($url) ? (string) $url : null;
    }

    public static function forLocationId(?int $locationId): ?string
    {
        if (! $locationId) {
            return null;
        }

        self::$slugs[$locationId] ??= BookingLocation::whereKey($locationId)->value('slug');
        $slug = self::$slugs[$locationId];
        $url = $slug ? config('services.hub_groups.'.$slug) : null;

        return filled($url) ? (string) $url : null;
    }
}
