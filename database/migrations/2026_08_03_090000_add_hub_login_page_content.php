<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('booking_locations') || !Schema::hasTable('site_contents')) {
            return;
        }

        DB::table('booking_locations')->get()->each(function ($location) {
            $prefix = 'hub_' . $location->id . '_';
            $values = [
                'login_eyebrow' => $location->name . ' Hub',
                'login_form_title' => 'Student login',
                'login_form_description' => 'Sign in to book and manage your appointments for ' . $location->name . ' Hub.',
                'login_hero_eyebrow' => $location->name . ' Hub',
                'login_hero_title' => $location->name . ' Medical Hub',
                'login_hero_description' => 'A calm student space prepared for reliable study, online exams, and weekly booking at ' . $location->name . ' Hub.',
                'login_card_1_label' => 'Power',
                'login_card_1_title' => 'Reliable setup',
                'login_card_2_label' => 'Internet',
                'login_card_2_title' => 'Study ready',
                'login_card_3_label' => 'Hub',
                'login_card_3_title' => $location->name,
            ];

            foreach ($values as $key => $value) {
                DB::table('site_contents')->updateOrInsert(
                    ['key' => $prefix . $key],
                    ['value' => $value, 'updated_at' => now(), 'created_at' => now()]
                );
            }
        });
    }

    public function down(): void
    {
        DB::table('site_contents')
            ->where('key', 'like', 'hub_%_login_%')
            ->delete();
    }
};
