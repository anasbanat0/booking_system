<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Slot extends Model
{
    protected $fillable = [
        'booking_location_id',
        'date',
        'start_time',
        'end_time',
        'capacity',
        'booked_count',
        'is_active',
    ];
    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    public function location()
    {
        return $this->belongsTo(BookingLocation::class, 'booking_location_id');
    }
}
