<x-mail::message>
# Application accepted

Hello {{ $user->name }},

Your application to the {{ $hubName }} Hub has been accepted. Set your password to activate your account and start using the booking system.

<x-mail::button :url="$resetUrl">
Set your password
</x-mail::button>

Join the official {{ $hubName }} Hub WhatsApp group for updates and follow-up.

<x-mail::button :url="$whatsAppGroupUrl" color="success">
Join WhatsApp group
</x-mail::button>

For your security, do not share your password setup link with anyone.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
