<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('booking_locations', function (Blueprint $table) {
            $table->string('slug')->nullable()->unique()->after('name');
        });

        DB::table('booking_locations')->orderBy('id')->get()->each(function ($location) {
            $name = trim($location->name);
            $lower = mb_strtolower($name);

            $slug = match (true) {
                str_contains($lower, 'gaza') || str_contains($name, 'غزة') || (int) $location->id === 1 => 'gaza',
                str_contains($lower, 'khan') || str_contains($name, 'خانيونس') || str_contains($name, 'خان يونس') || (int) $location->id === 2 => 'khan-younis',
                default => Str::slug($name) ?: 'hub-' . $location->id,
            };

            DB::table('booking_locations')
                ->where('id', $location->id)
                ->update(['slug' => $slug]);
        });
    }

    public function down(): void
    {
        Schema::table('booking_locations', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->dropColumn('slug');
        });
    }
};
