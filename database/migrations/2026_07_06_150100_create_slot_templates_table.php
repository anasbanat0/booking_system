<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('slot_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_location_id')->constrained()->cascadeOnDelete();
            $table->time('start_time');
            $table->time('end_time');
            $table->unsignedInteger('capacity')->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['booking_location_id', 'start_time', 'end_time']);
        });

        $gazaId = DB::table('booking_locations')->where('name', 'غزة')->value('id');
        $khanYounisId = DB::table('booking_locations')->where('name', 'خانيونس')->value('id');

        $templates = [];

        foreach ([['08:00', '11:00'], ['11:00', '14:00'], ['14:00', '17:00']] as $time) {
            $templates[] = [
                'booking_location_id' => $gazaId,
                'start_time' => $time[0],
                'end_time' => $time[1],
                'capacity' => 35,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        foreach ([['08:30', '11:30'], ['11:30', '14:30'], ['14:30', '17:30']] as $time) {
            $templates[] = [
                'booking_location_id' => $khanYounisId,
                'start_time' => $time[0],
                'end_time' => $time[1],
                'capacity' => 30,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        DB::table('slot_templates')->insert($templates);
    }

    public function down(): void
    {
        Schema::dropIfExists('slot_templates');
    }
};
