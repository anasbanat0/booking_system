<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('site_contents')->updateOrInsert(
            ['key' => 'stat_seats_label'],
            ['value' => 'Study hours', 'updated_at' => now(), 'created_at' => now()]
        );

        if (!Schema::hasTable('booking_locations')) {
            return;
        }

        DB::table('booking_locations')->get()->each(function ($location) {
            $slug = strtolower($location->slug ?? '');
            $name = mb_strtolower($location->name ?? '');
            $legacyBookings = match (true) {
                $slug === 'gaza' || str_contains($name, 'gaza') || str_contains($name, 'غزة') => 15000,
                str_contains($slug, 'khan') || str_contains($name, 'khan') || str_contains($name, 'خانيونس') => 900,
                default => 0,
            };

            if ($legacyBookings > 0) {
                DB::table('site_contents')->updateOrInsert(
                    ['key' => 'hub_' . $location->id . '_legacy_bookings'],
                    ['value' => (string) $legacyBookings, 'updated_at' => now(), 'created_at' => now()]
                );
            }
        });
    }

    public function down(): void
    {
        DB::table('site_contents')
            ->where('key', 'stat_seats_label')
            ->update(['value' => 'Seats left', 'updated_at' => now()]);

        DB::table('site_contents')
            ->where('key', 'like', 'hub_%_legacy_bookings')
            ->delete();
    }
};
