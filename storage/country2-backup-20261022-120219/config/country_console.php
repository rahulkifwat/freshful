<?php

/* Freshful country console - pan-India command centre for the country manager (22 Oct 2026). */
return [
    // Login guard of the country manager panel. Empty = detected automatically (from the old dashboard route, then by name).
    'guard' => env('COUNTRY_CONSOLE_GUARD', ''),

    // The old /country_manager/dashboard address opens the new console (the old page stays at /country_manager/console/classic).
    'replace_dashboard' => filter_var(env('COUNTRY_CONSOLE_HOME', true), FILTER_VALIDATE_BOOLEAN),

    // Wastage above this share of a hub's sales value (in the chosen dates) is flagged.
    'wastage_limit_pct' => (float) env('COUNTRY_WASTAGE_LIMIT_PCT', 3),

    // Cancellation rate that raises an alert (needs at least 5 orders).
    'cancel_alert_pct' => (float) env('COUNTRY_CANCEL_ALERT_PCT', 15),

    // Write-off reasons (key => label). The label is saved with the stock move, so reports can group by it.
    'writeoff_reasons' => [
        'expired' => 'Expired (2+ days old)',
        'spoiled' => 'Spoiled / rotten',
        'damaged' => 'Damaged in handling or transit',
        'quality' => 'Quality rejected',
        'return' => 'Customer return, not resaleable',
        'missing' => 'Missing / theft',
        'other' => 'Other',
    ],

    // Longest date range one screen can ask for (days).
    'max_days' => 400,
];
