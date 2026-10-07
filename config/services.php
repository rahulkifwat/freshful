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

    // Driver app JWT — must match JWT_SECRET in the legacy functions/functions.php
    // while both APIs are live, so tokens work on either side.
    'driver_jwt' => [
        'secret' => env('DRIVER_JWT_SECRET'),
        'ttl' => (int) env('DRIVER_JWT_TTL', 7 * 24 * 60 * 60),
    ],

    // OTP SMS gateway used by /api/login.
    'bhashsms' => [
        'url' => env('BHASHSMS_URL', 'http://bhashsms.com/api/sendmsg.php'),
        'user' => env('BHASHSMS_USER'),
        'pass' => env('BHASHSMS_PASS'),
        'sender' => env('BHASHSMS_SENDER', 'Frfull'),
    ],

];
