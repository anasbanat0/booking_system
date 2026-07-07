<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('booking_location_id')
                ->nullable()
                ->after('role')
                ->constrained()
                ->nullOnDelete();
        });

        Schema::table('holidays', function (Blueprint $table) {
            $table->foreignId('booking_location_id')
                ->nullable()
                ->after('id')
                ->constrained()
                ->cascadeOnDelete();

            $table->dropUnique(['date']);
            $table->unique(['booking_location_id', 'date'], 'holidays_location_date_unique');
        });
    }

    public function down(): void
    {
        Schema::table('holidays', function (Blueprint $table) {
            $table->dropUnique('holidays_location_date_unique');
            $table->unique('date');
            $table->dropConstrainedForeignId('booking_location_id');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('booking_location_id');
        });
    }
};
