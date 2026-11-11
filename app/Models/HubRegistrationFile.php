<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HubRegistrationFile extends Model
{
    protected $fillable = [
        'hub_registration_id',
        'hub_registration_field_id',
        'disk',
        'path',
        'original_name',
        'mime_type',
        'size',
    ];

    public function registration()
    {
        return $this->belongsTo(HubRegistration::class, 'hub_registration_id');
    }

    public function field()
    {
        return $this->belongsTo(HubRegistrationField::class, 'hub_registration_field_id');
    }
}
