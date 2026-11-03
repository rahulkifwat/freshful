<?php

/*
|--------------------------------------------------------------------------
| Freshful customer app (Expo) settings
|--------------------------------------------------------------------------
| Raise min_version (via .env) to make older app builds show "please update".
| Versions look like 2.0.0.
*/
return [
    'min_version' => [
        'android' => env('BUYER_APP_MIN_ANDROID', '1.0.0'),
        'ios'     => env('BUYER_APP_MIN_IOS', '1.0.0'),
    ],

    'latest_version' => [
        'android' => env('BUYER_APP_LATEST_ANDROID', '1.0.0'),
        'ios'     => env('BUYER_APP_LATEST_IOS', '1.0.0'),
    ],

    'store_url' => [
        'android' => env('BUYER_APP_ANDROID_URL', 'https://play.google.com/store/apps/details?id=com.freshful'),
        'ios'     => env('BUYER_APP_IOS_URL', ''),
    ],

    'maintenance' => [
        'on'      => (bool) env('BUYER_APP_MAINTENANCE', false),
        'message' => env('BUYER_APP_MAINTENANCE_MESSAGE', 'Freshful is being updated. Please try again in a few minutes.'),
    ],
];
