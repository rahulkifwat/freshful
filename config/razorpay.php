<?php

return [
    // Reuse the existing Freshful checkout public Key ID unless deployment config overrides it.
    'key_id' => env('RAZORPAY_KEY_ID', env('RAZORPAY_KEY', 'rzp_live_wHEJMOTYVkTyZn')),
    // Never put the secret in Blade/JavaScript. Support common existing .env variable names.
    'key_secret' => env('RAZORPAY_KEY_SECRET', env('RAZORPAY_SECRET', env('RAZORPAY_SECRET_KEY'))),
];
