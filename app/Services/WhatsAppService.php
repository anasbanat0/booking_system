<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class WhatsAppService
{
    public function enabled(): bool
    {
        return (bool) config('services.whatsapp.enabled')
            && filled(config('services.whatsapp.phone_number_id'))
            && filled(config('services.whatsapp.access_token'));
    }

    public function sendAccountCreated(User $user): bool
    {
        return $this->sendTemplateToUser($user, 'account_created', [
            $user->name,
            config('app.name', 'Medical Hub'),
        ]);
    }

    public function sendPasswordSetupLink(User $user, string $url): bool
    {
        return $this->sendTemplateToUser($user, 'password_setup', [
            $user->name,
            $url,
        ]);
    }

    public function sendBookingConfirmed(Booking $booking): bool
    {
        $booking->loadMissing('user', 'slot.location');

        return $this->sendBookingTemplate($booking, 'booking_confirmed');
    }

    public function sendBookingReminder(Booking $booking): bool
    {
        $booking->loadMissing('user', 'slot.location');

        return $this->sendBookingTemplate($booking, 'booking_reminder');
    }

    public function sendBookingCancelled(Booking $booking): bool
    {
        $booking->loadMissing('user', 'slot.location');

        return $this->sendBookingTemplate($booking, 'booking_cancelled');
    }

    public function sendBookingRescheduled(Booking $booking): bool
    {
        $booking->loadMissing('user', 'slot.location');

        return $this->sendBookingTemplate($booking, 'booking_rescheduled');
    }

    private function sendBookingTemplate(Booking $booking, string $templateKey): bool
    {
        if (!$booking->user || !$booking->slot) {
            return false;
        }

        return $this->sendTemplateToUser($booking->user, $templateKey, [
            $booking->user->name,
            $booking->slot->location?->name ?? 'Medical Hub',
            Carbon::parse($booking->slot->date)->format('Y-m-d'),
            Carbon::parse($booking->slot->start_time)->format('H:i'),
            Carbon::parse($booking->slot->end_time)->format('H:i'),
        ]);
    }

    public function sendTemplateToUser(User $user, string $templateKey, array $parameters = []): bool
    {
        $phone = $this->normalizePhone($user->phone);

        if (!$phone) {
            return false;
        }

        return $this->sendTemplate($phone, $templateKey, $parameters);
    }

    public function sendTemplate(string $phone, string $templateKey, array $parameters = []): bool
    {
        if (!$this->enabled()) {
            return false;
        }

        $template = config('services.whatsapp.templates.' . $templateKey);

        if (blank($template)) {
            Log::warning('WhatsApp template is missing.', ['template_key' => $templateKey]);

            return false;
        }

        $phone = $this->normalizePhone($phone);

        if (!$phone) {
            return false;
        }

        $payload = [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $phone,
            'type' => 'template',
            'template' => [
                'name' => $template,
                'language' => [
                    'code' => config('services.whatsapp.default_language', 'ar'),
                ],
            ],
        ];

        if ($parameters !== []) {
            $payload['template']['components'] = [[
                'type' => 'body',
                'parameters' => collect($parameters)
                    ->map(fn ($value) => ['type' => 'text', 'text' => (string) $value])
                    ->values()
                    ->all(),
            ]];
        }

        try {
            $response = Http::withToken(config('services.whatsapp.access_token'))
                ->acceptJson()
                ->asJson()
                ->timeout(10)
                ->post($this->messagesEndpoint(), $payload);

            if ($response->successful()) {
                return true;
            }

            Log::warning('WhatsApp message failed.', [
                'template_key' => $templateKey,
                'phone' => Str::mask($phone, '*', 3, -2),
                'status' => $response->status(),
                'body' => $response->json() ?: $response->body(),
            ]);
        } catch (\Throwable $exception) {
            Log::warning('WhatsApp message exception.', [
                'template_key' => $templateKey,
                'phone' => Str::mask($phone, '*', 3, -2),
                'exception' => $exception->getMessage(),
            ]);
        }

        return false;
    }

    private function messagesEndpoint(): string
    {
        return 'https://graph.facebook.com/'
            . config('services.whatsapp.graph_version', 'v20.0')
            . '/'
            . config('services.whatsapp.phone_number_id')
            . '/messages';
    }

    private function normalizePhone(?string $phone): ?string
    {
        if (blank($phone)) {
            return null;
        }

        $phone = preg_replace('/[^\d+]/', '', $phone);

        if (str_starts_with($phone, '+')) {
            return ltrim($phone, '+');
        }

        if (str_starts_with($phone, '00')) {
            return substr($phone, 2);
        }

        if (str_starts_with($phone, '0')) {
            return '970' . substr($phone, 1);
        }

        return $phone;
    }
}
