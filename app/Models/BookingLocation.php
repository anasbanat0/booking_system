<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BookingLocation extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'default_capacity',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function slotTemplates()
    {
        return $this->hasMany(SlotTemplate::class);
    }

    public function slots()
    {
        return $this->hasMany(Slot::class);
    }

    public function registrationForm()
    {
        return $this->hasOne(HubRegistrationForm::class);
    }

    public function hubRegistrations()
    {
        return $this->hasMany(HubRegistration::class);
    }
}
