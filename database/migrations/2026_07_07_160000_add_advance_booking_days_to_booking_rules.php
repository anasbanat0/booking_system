<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('booking_rules', function (Blueprint $table) {
            $table->unsignedInteger('advance_booking_days')->default(1)->after('reminder_hours_before');
        });
    }

    public function down(): void
    {
        Schema::table('booking_rules', function (Blueprint $table) {
            $table->dropColumn('advance_booking_days');
        });
    }
};
