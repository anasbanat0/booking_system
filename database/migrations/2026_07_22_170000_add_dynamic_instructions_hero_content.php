<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $items = [
            'instructions_hero_eyebrow' => 'Samir Foundation Medical Hub',
            'instructions_hero_title' => 'تعليمات الحجز واستخدام المكان',
            'instructions_hero_description' => 'مساحة هادئة ومجهزة للطلبة الذين يحتاجون إلى كهرباء مستقرة، اتصال إنترنت، وبيئة مناسبة للدراسة أو تقديم الامتحانات الإلكترونية.',
        ];

        foreach ($items as $key => $value) {
            DB::table('site_contents')->updateOrInsert(
                ['key' => $key],
                [
                    'value' => $value,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }

    public function down(): void
    {
        DB::table('site_contents')
            ->whereIn('key', [
                'instructions_hero_eyebrow',
                'instructions_hero_title',
                'instructions_hero_description',
            ])
            ->delete();
    }
};
