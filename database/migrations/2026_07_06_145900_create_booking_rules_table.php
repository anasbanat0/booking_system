<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_rules', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('weekly_limit')->default(4);
            $table->unsignedInteger('monthly_limit')->default(16);
            $table->boolean('enforce_one_booking_per_day')->default(true);
            $table->boolean('enforce_unique_time_period')->default(true);
            $table->timestamps();
        });

        DB::table('booking_rules')->insert([
            'weekly_limit' => 4,
            'monthly_limit' => 16,
            'enforce_one_booking_per_day' => true,
            'enforce_unique_time_period' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_rules');
    }
};
