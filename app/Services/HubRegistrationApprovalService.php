<?php

namespace App\Services;

use App\Jobs\SendPasswordSetupLink;
use App\Models\ActivityLog;
use App\Models\HubRegistration;
use App\Models\HubRegistrationForm;
use App\Models\User;
use App\Support\HubGroupLinks;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class HubRegistrationApprovalService
{
    public function approve(HubRegistration $registration, User $actor, int $queuePosition = 0): bool
    {
        $result = DB::transaction(function () use ($registration, $actor) {
            $registration = HubRegistration::with('location')->lockForUpdate()->findOrFail($registration->id);

            if ($registration->status === 'accepted') {
                return null;
            }

            $user = User::withTrashed()->where('email', mb_strtolower($registration->email))->first();

            if ($user && $user->role !== 'student') {
                throw new RuntimeException('This email already belongs to a staff or admin account.');
            }

            $phone = $this->answerByKey($registration, 'whatsapp_phone');

            if ($phone && User::withTrashed()->where('phone', $phone)->when($user, fn ($query) => $query->whereKeyNot($user->id))->exists()) {
                $phone = $user?->phone;
            }

            if ($user) {
                $user->update([
                    'name' => $registration->full_name_en,
                    'phone' => $phone,
                    'role' => 'student',
                    'booking_location_id' => $registration->booking_location_id,
                ]);

                if ($user->trashed()) {
                    $user->restore();
                }
            } else {
                $user = User::create([
                    'name' => $registration->full_name_en,
                    'email' => mb_strtolower($registration->email),
                    'phone' => $phone,
                    'role' => 'student',
                    'booking_location_id' => $registration->booking_location_id,
                    'password' => Str::random(40),
                ]);
            }

            $registration->update([
                'status' => 'accepted',
                'reviewed_by' => $actor->id,
                'reviewed_at' => now(),
                'accepted_user_id' => $user->id,
                'rejection_reason' => null,
            ]);

            ActivityLog::record('hub_registration_accepted', 'Hub registration accepted', $registration->full_name_en.' was accepted into '.$registration->location->name.' Hub.', [
                'actor_id' => $actor->id,
                'user_id' => $user->id,
                'properties' => ['registration_id' => $registration->id, 'location_id' => $registration->booking_location_id],
            ]);

            return ['user' => $user, 'location' => $registration->location];
        });

        if (! $result) {
            return false;
        }

        SendPasswordSetupLink::dispatch(
            $result['user']->id,
            true,
            HubGroupLinks::forLocation($result['location']),
            $result['location']->name,
        )->delay(now()->addSeconds($queuePosition * 8));

        return true;
    }

    private function answerByKey(HubRegistration $registration, string $key): ?string
    {
        $fieldId = HubRegistrationForm::where('booking_location_id', $registration->booking_location_id)
            ->first()?->fields()
            ->where('key', $key)
            ->value('id');
        $value = $fieldId ? trim((string) ($registration->answers[(string) $fieldId] ?? '')) : '';

        return $value !== '' ? $value : null;
    }
}
