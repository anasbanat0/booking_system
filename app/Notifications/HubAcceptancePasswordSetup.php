<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class HubAcceptancePasswordSetup extends Notification
{
    use Queueable;

    public function __construct(
        public string $token,
        public string $whatsAppGroupUrl,
        public ?string $hubName = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $resetUrl = route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ]);

        return (new MailMessage)
            ->subject('Your Medical Hub application has been accepted')
            ->markdown('mail.hub-acceptance-password-setup', [
                'user' => $notifiable,
                'hubName' => $this->hubName ?: 'Medical',
                'resetUrl' => $resetUrl,
                'whatsAppGroupUrl' => $this->whatsAppGroupUrl,
            ]);
    }
}
