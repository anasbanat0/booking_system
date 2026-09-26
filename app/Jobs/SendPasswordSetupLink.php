<?php

namespace App\Jobs;

use App\Models\ActivityLog;
use App\Models\User;
use App\Services\WhatsAppService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use RuntimeException;

class SendPasswordSetupLink implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public array $backoff = [60, 300, 900];

    public function __construct(public int $userId, public bool $sendAccountCreatedMessage = false) {}

    public function handle(): void
    {
        $user = User::find($this->userId);

        if (! $user) {
            return;
        }

        if ($this->sendAccountCreatedMessage) {
            app(WhatsAppService::class)->sendAccountCreated($user);
        }

        $status = Password::sendResetLink(['email' => $user->email]);

        if ($status === Password::RESET_THROTTLED) {
            Log::notice('Password setup link skipped because a recent link is still valid.', [
                'user_id' => $user->id,
                'email' => $user->email,
            ]);

            return;
        }

        if ($status !== Password::RESET_LINK_SENT) {
            throw new RuntimeException('Password setup link failed with status: '.$status);
        }

        ActivityLog::record(
            'password_setup_link_accepted',
            'Password setup link accepted',
            'The mail provider accepted the password setup message for delivery.',
            [
                'user_id' => $user->id,
                'properties' => ['email' => $user->email],
            ],
        );

        Log::info('Password setup link accepted by mail provider.', [
            'user_id' => $user->id,
            'email' => $user->email,
        ]);
    }

    public function failed(?\Throwable $exception): void
    {
        $user = User::find($this->userId);

        ActivityLog::record(
            'password_setup_link_failed',
            'Password setup link failed',
            $exception?->getMessage(),
            [
                'user_id' => $user?->id,
                'properties' => ['email' => $user?->email],
            ],
        );

        Log::error('Password setup link job failed.', [
            'user_id' => $this->userId,
            'email' => $user?->email,
            'exception' => $exception?->getMessage(),
        ]);
    }
}
