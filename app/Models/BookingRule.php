<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BookingRule extends Model
{
    protected $fillable = [
        'weekly_limit',
        'monthly_limit',
        'enforce_one_booking_per_day',
        'enforce_unique_time_period',
    ];

    protected $casts = [
        'enforce_one_booking_per_day' => 'boolean',
        'enforce_unique_time_period' => 'boolean',
    ];

    public static function current(): self
    {
        return self::query()->firstOrCreate([], [
            'weekly_limit' => 4,
            'monthly_limit' => 16,
            'enforce_one_booking_per_day' => true,
            'enforce_unique_time_period' => true,
        ]);
    }
}
