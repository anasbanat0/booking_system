<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
{
    Schema::create('slots', function (Blueprint $table) {
        $table->id();

        $table->date('date');

        $table->time('start_time');
        $table->time('end_time');

        $table->unsignedInteger('capacity')->default(1);
        $table->unsignedInteger('booked_count')->default(0);

        $table->boolean('is_active')->default(true);

        $table->timestamps();

        $table->unique(['date', 'start_time']);
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('slots');
    }
};
