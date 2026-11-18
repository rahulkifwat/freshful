<?php

/*
 * Manager wallet top-ups (15 Nov 2026). All values can be changed in .env, then run: php artisan config:clear
 *   WALLET_TOPUP_ROLES=marketing,operation   who may add wallet money (any of: marketing, operation, hr, planning, production)
 *   WALLET_TOPUP_MAX=2000                    most one top-up may add (Rs)
 *   WALLET_TOPUP_DAILY=5000                 most one person may add in one day (Rs)
 *   WALLET_MIN_REASON=5                     shortest reason allowed (0 = reason not required)
 */
return [
    'roles' => array_values(array_filter(array_map('trim', explode(',', strtolower((string) env('WALLET_TOPUP_ROLES', 'marketing,operation')))))),
    'max_each' => (float) env('WALLET_TOPUP_MAX', 2000),
    'max_daily' => (float) env('WALLET_TOPUP_DAILY', 5000),
    'min_reason' => (int) env('WALLET_MIN_REASON', 5),
];
