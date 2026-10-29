<?php

/* Freshful country console - pan-India command centre for the country manager (22 Oct 2026). */
return [
    // Login guard of each panel. Empty = detected automatically (old dashboard route, then the guard named like the table).
    'guards' => ['country' => env('COUNTRY_CONSOLE_GUARD', ''), 'area' => env('AREA_CONSOLE_GUARD', ''), 'operation' => env('OPERATION_CONSOLE_GUARD', '')],

    // The old <panel>/dashboard address opens the new console (the old page stays at <panel>/console/classic).
    'replace_dashboard' => [
        'country' => filter_var(env('COUNTRY_CONSOLE_HOME', true), FILTER_VALIDATE_BOOLEAN),
        'area' => filter_var(env('AREA_CONSOLE_HOME', true), FILTER_VALIDATE_BOOLEAN),
        'operation' => filter_var(env('OPERATION_CONSOLE_HOME', true), FILTER_VALIDATE_BOOLEAN),
    ],

    // Operation managers: the operation_managers table has no city column. Empty = all hubs. "4" or "4,5" = only those city ids.
    'operation_cities' => env('OPERATION_CONSOLE_CITIES', ''),

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
