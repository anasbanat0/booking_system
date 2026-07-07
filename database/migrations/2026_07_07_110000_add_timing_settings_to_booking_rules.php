<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('booking_rules', function (Blueprint $table) {
            $table->unsignedInteger('reschedule_cutoff_hours')->default(12)->after('monthly_limit');
            $table->unsignedInteger('reminder_hours_before')->default(24)->after('reschedule_cutoff_hours');
        });
    }

    public function down(): void
    {
        Schema::table('booking_rules', function (Blueprint $table) {
            $table->dropColumn(['reschedule_cutoff_hours', 'reminder_hours_before']);
        });
    }
};
