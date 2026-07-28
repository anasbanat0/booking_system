<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['site_logo_url', 'site_logo_path'] as $key) {
            DB::table('site_contents')->updateOrInsert(
                ['key' => $key],
                [
                    'value' => '',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }

    public function down(): void
    {
        DB::table('site_contents')
            ->whereIn('key', ['site_logo_url', 'site_logo_path'])
            ->delete();
    }
};
