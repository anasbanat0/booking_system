<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('slots', function (Blueprint $table) {
            $table->foreignId('booking_location_id')
                ->nullable()
                ->after('id')
                ->constrained()
                ->nullOnDelete();
        });

        $defaultLocationId = DB::table('booking_locations')->where('name', 'غزة')->value('id');

        if ($defaultLocationId) {
            DB::table('slots')
                ->whereNull('booking_location_id')
                ->update(['booking_location_id' => $defaultLocationId]);
        }
    }

    public function down(): void
    {
        Schema::table('slots', function (Blueprint $table) {
            $table->dropConstrainedForeignId('booking_location_id');
        });
    }
};
