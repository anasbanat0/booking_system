<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (! in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            return;
        }

        foreach ([
            'hub_registration_forms',
            'hub_registration_fields',
            'hub_registrations',
            'hub_registration_files',
        ] as $table) {
            DB::statement("ALTER TABLE `{$table}` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        }
    }

    public function down(): void
    {
        // Converting back to three-byte UTF-8 could destroy saved Unicode characters.
    }
};
