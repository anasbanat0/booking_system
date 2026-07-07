<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Holiday extends Model
{
    protected $fillable = [
        'booking_location_id',
        'date',
        'reason',
    ];

    public function location()
    {
        return $this->belongsTo(BookingLocation::class, 'booking_location_id');
    }
}
