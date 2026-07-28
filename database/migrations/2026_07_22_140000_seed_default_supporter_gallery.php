<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $gallery = [
            [
                'name' => 'Samir Foundation',
                'url' => '/images/supporters/default/samir.png',
            ],
            [
                'name' => 'Wear The Peace',
                'url' => '/images/supporters/default/wear.png',
            ],
            [
                'name' => 'Smile Givers International',
                'url' => '/images/supporters/default/smile.png',
            ],
            [
                'name' => 'Human Smile',
                'url' => '/images/supporters/default/human.png',
            ],
        ];

        DB::table('site_contents')->updateOrInsert(
            ['key' => 'supporter_gallery'],
            [
                'value' => json_encode($gallery, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        DB::table('site_contents')->updateOrInsert(
            ['key' => 'partners_heading'],
            [
                'value' => 'Supporters Gallery',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }

    public function down(): void
    {
        DB::table('site_contents')
            ->where('key', 'supporter_gallery')
            ->delete();
    }
};
