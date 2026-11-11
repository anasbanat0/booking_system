<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HubRegistrationField extends Model
{
    protected $fillable = [
        'hub_registration_form_id',
        'key',
        'label',
        'type',
        'placeholder',
        'help_text',
        'options',
        'is_required',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'options' => 'array',
        'is_required' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function form()
    {
        return $this->belongsTo(HubRegistrationForm::class, 'hub_registration_form_id');
    }
}
