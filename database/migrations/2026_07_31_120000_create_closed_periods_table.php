<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('closed_periods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_location_id')->nullable()->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->time('start_time');
            $table->time('end_time');
            $table->string('reason')->nullable();
            $table->timestamps();

            $table->index(['booking_location_id', 'date', 'start_time'], 'closed_periods_lookup_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('closed_periods');
    }
};
