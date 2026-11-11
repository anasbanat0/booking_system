<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HubRegistration extends Model
{
    protected $fillable = [
        'booking_location_id',
        'full_name_ar',
        'full_name_en',
        'email',
        'answers',
        'submitted_at',
    ];

    protected $casts = [
        'answers' => 'array',
        'submitted_at' => 'datetime',
    ];

    public function location()
    {
        return $this->belongsTo(BookingLocation::class, 'booking_location_id');
    }

    public function files()
    {
        return $this->hasMany(HubRegistrationFile::class);
    }
}
