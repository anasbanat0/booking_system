<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $contents = [
            'footer_title' => 'Samir Foundation Medical Hub',
            'footer_description' => 'A student-focused hub prepared for calm study, online exams, stable electricity, and reliable internet access.',
            'footer_cta_text' => 'Need to update information or add a supporting partner announcement?',
            'footer_cta_url' => '',
            'supporters_note' => 'Supporting education access through prepared study spaces in Gaza and Khan Younis.',
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
        DB::table('site_contents')
            ->whereIn('key', [
                'footer_title',
                'footer_description',
                'footer_cta_text',
                'footer_cta_url',
                'supporters_note',
            ])
            ->delete();
    }
};
