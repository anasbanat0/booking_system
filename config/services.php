<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'whatsapp' => [
        'enabled' => env('WHATSAPP_ENABLED', false),
        'graph_version' => env('WHATSAPP_GRAPH_VERSION', 'v20.0'),
        'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID'),
        'access_token' => env('WHATSAPP_ACCESS_TOKEN'),
        'default_language' => env('WHATSAPP_DEFAULT_LANGUAGE', 'ar'),
        'templates' => [
            'account_created' => env('WHATSAPP_TEMPLATE_ACCOUNT_CREATED', 'account_created'),
            'password_setup' => env('WHATSAPP_TEMPLATE_PASSWORD_SETUP', 'password_setup'),
            'booking_confirmed' => env('WHATSAPP_TEMPLATE_BOOKING_CONFIRMED', 'booking_confirmed'),
            'booking_reminder' => env('WHATSAPP_TEMPLATE_BOOKING_REMINDER', 'booking_reminder'),
            'booking_cancelled' => env('WHATSAPP_TEMPLATE_BOOKING_CANCELLED', 'booking_cancelled'),
            'booking_rescheduled' => env('WHATSAPP_TEMPLATE_BOOKING_RESCHEDULED', 'booking_rescheduled'),
        ],
    ],

];
