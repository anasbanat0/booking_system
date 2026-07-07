<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['name', 'email', 'phone', 'password', 'role', 'booking_location_id', 'profile_photo_path', 'booking_warning_count', 'booking_warning_reason', 'booking_warning_at'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    public function managedLocation()
    {
        return $this->belongsTo(BookingLocation::class, 'booking_location_id');
    }

    public function canManageAllBranches(): bool
    {
        return $this->role === 'admin';
    }

    public function isAdminPanelUser(): bool
    {
        return in_array($this->role, ['admin', 'staff'], true);
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
