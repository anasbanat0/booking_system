<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClosedPeriod extends Model
{
    protected $fillable = [
        'booking_location_id',
        'date',
        'start_time',
        'end_time',
        'reason',
    ];

    public function location()
    {
        return $this->belongsTo(BookingLocation::class, 'booking_location_id');
    }
}
