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
        'token' => env('POSTMARK_TOKEN'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
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

    'recaptcha' => [
        'secret_key' => env('RECAPTCHA_SECRET_KEY'),
        'enabled' => env('RECAPTCHA_ENABLED', false),
    ],

    'sunat' => [
        'persona_id' => env('SUNAT_PERSONA_ID', '685effa1250d3a0015f27672'),
        'persona_token' => env('SUNAT_PERSONA_TOKEN', 'DEV_4tkV0zZAS3p0BTrSiEMDCx3URjeFtwbgu0VHQ2OIvVbEiHeNbDfU313BZdSEeCnL'),
        'base_url' => env('SUNAT_BASE_URL', 'https://apisunat.com/api/v1'),
        'produccion' => env('SUNAT_PRODUCCION', false), // false = modo desarrollo/simulado
    ],

];
