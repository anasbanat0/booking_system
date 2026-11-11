<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HubRegistrationForm extends Model
{
    protected $fillable = [
        'booking_location_id',
        'eyebrow',
        'title',
        'description',
        'instructions',
        'submit_button',
        'success_title',
        'success_message',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function location()
    {
        return $this->belongsTo(BookingLocation::class, 'booking_location_id');
    }

    public function fields()
    {
        return $this->hasMany(HubRegistrationField::class)->orderBy('sort_order')->orderBy('id');
    }
}
