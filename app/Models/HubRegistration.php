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
        'status',
        'reviewed_by',
        'reviewed_at',
        'accepted_user_id',
        'rejection_reason',
        'submitted_at',
    ];

    protected $casts = [
        'answers' => 'array',
        'reviewed_at' => 'datetime',
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

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function acceptedUser()
    {
        return $this->belongsTo(User::class, 'accepted_user_id');
    }
}
