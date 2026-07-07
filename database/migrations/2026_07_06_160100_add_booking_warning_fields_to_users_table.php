<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedInteger('booking_warning_count')->default(0)->after('role');
            $table->string('booking_warning_reason')->nullable()->after('booking_warning_count');
            $table->timestamp('booking_warning_at')->nullable()->after('booking_warning_reason');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'booking_warning_count',
                'booking_warning_reason',
                'booking_warning_at',
            ]);
        });
    }
};
