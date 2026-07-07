<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $contents = [
            'project_intro' => 'A simple weekly booking experience for students, designed to make seat availability, branch selection, and appointment management clear from the first click.',
            'usage_instructions' => "1. Log in with your student account.\n2. Open the weekly calendar.\n3. Choose a branch and an available time slot.\n4. Manage cancellations and rescheduling from My Bookings.",
            'contact_info' => "Sameer Foundation\nGaza and Khan Younis branches\ninfo@example.org\n+970 599 000 000",
            'supporters' => 'Sameer Foundation, Education Partner, Community Supporter',
            'social_links' => "Facebook: https://facebook.com\nInstagram: https://instagram.com\nWhatsApp: https://wa.me/970599000000",
        ];

        foreach ($contents as $key => $value) {
            DB::table('site_contents')->updateOrInsert(
                ['key' => $key],
                ['value' => $value, 'updated_at' => now(), 'created_at' => now()]
            );
        }
    }

    public function down(): void
    {
        //
    }
};
