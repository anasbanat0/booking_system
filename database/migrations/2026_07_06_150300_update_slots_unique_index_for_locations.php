<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('slots', function (Blueprint $table) {
            $table->dropUnique(['date', 'start_time']);
            $table->unique(['booking_location_id', 'date', 'start_time', 'end_time'], 'slots_location_date_time_unique');
        });
    }

    public function down(): void
    {
        Schema::table('slots', function (Blueprint $table) {
            $table->dropUnique('slots_location_date_time_unique');
            $table->unique(['date', 'start_time']);
        });
    }
};
