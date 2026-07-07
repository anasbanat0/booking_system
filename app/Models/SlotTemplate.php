<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SlotTemplate extends Model
{
    protected $fillable = [
        'booking_location_id',
        'start_time',
        'end_time',
        'capacity',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function location()
    {
        return $this->belongsTo(BookingLocation::class, 'booking_location_id');
    }
}
