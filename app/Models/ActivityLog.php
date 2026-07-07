<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    protected $fillable = [
        'actor_id',
        'user_id',
        'booking_id',
        'type',
        'title',
        'description',
        'properties',
    ];

    protected $casts = [
        'properties' => 'array',
    ];

    public static function record(string $type, string $title, ?string $description = null, array $context = []): self
    {
        return self::create([
            'actor_id' => $context['actor_id'] ?? auth()->id(),
            'user_id' => $context['user_id'] ?? null,
            'booking_id' => $context['booking_id'] ?? null,
            'type' => $type,
            'title' => $title,
            'description' => $description,
            'properties' => $context['properties'] ?? null,
        ]);
    }

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }
}
