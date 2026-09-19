<?php

namespace App\Jobs;

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

        if ($status !== Password::RESET_LINK_SENT) {
            throw new RuntimeException('Password setup link failed with status: '.$status);
        }
    }

    public function failed(?\Throwable $exception): void
    {
        Log::error('Password setup link job failed.', [
            'user_id' => $this->userId,
            'exception' => $exception?->getMessage(),
        ]);
    }
}
