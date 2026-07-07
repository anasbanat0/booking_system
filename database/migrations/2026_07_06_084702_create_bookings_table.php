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
    Schema::create('bookings', function (Blueprint $table) {
        $table->id();

        $table->foreignId('user_id')->constrained()->cascadeOnDelete();
        $table->foreignId('slot_id')->constrained()->cascadeOnDelete();

        $table->enum('status', [
            'booked',
            'cancelled',
            'rescheduled',
            'completed',
            'no_show'
        ])->default('booked');

        $table->timestamp('cancelled_at')->nullable();
        $table->timestamp('rescheduled_at')->nullable();

        $table->timestamps();

        $table->unique(['user_id', 'slot_id']);
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
