<?php

/*
| Freshful production console (production update 1, 28 Oct 2026). /production/console
| The production manager's command centre: what to produce, sending it to the hubs, stock in every hub,
| wastage and products. Built on the same engine as the country / area / operation / admin consoles.
*/
return [
    // Login guard of the production panel. Empty = detected (the guard whose table is "production", then "productions").
    'guard' => env('PRODUCTION_CONSOLE_GUARD', ''),

    // production/dashboard opens the new console. The old page stays at production/console/classic. false = keep the old page.
    'replace_dashboard' => filter_var(env('PRODUCTION_CONSOLE_HOME', true), FILTER_VALIDATE_BOOLEAN),

    // Which hubs the production manager covers. Empty = all of India (the old panel showed every hub). "4" or "4,5" = only those city ids.
    'cities' => env('PRODUCTION_CONSOLE_CITIES', ''),

    // Menu and access follow the old role_user permissions of the "Production" role (same as the old sidebar).
    // false = show every production screen to every production user.
    'use_role_permissions' => filter_var(env('PRODUCTION_USE_ROLE_PERMISSIONS', true), FILTER_VALIDATE_BOOLEAN),

    // Demand forecast for the production plan.
    'forecast' => [
        'history_days' => (int) env('PRODUCTION_HISTORY_DAYS', 14),      // average of the last N full days
        'weekday_weight' => (float) env('PRODUCTION_WEEKDAY_WEIGHT', 0.4), // share given to the same weekday of the last 4 weeks
        'safety_pct' => (float) env('PRODUCTION_SAFETY_PCT', 10),         // extra over the express forecast
        'min_pack_share' => (float) env('PRODUCTION_MIN_PACK_SHARE', 0.35),  // forecast-only need below this share of a pack is not made (booked orders always are)
        'pending_max_days' => (int) env('PRODUCTION_PENDING_MAX_DAYS', 2), // stock sent but not received longer than this is treated as stale (not counted as coming)
        'days_ahead' => 3,                                                 // plan today, tomorrow and up to N days ahead
    ],

    // Sending stock to hubs.
    'dispatch' => [
        'max_packs_per_line' => (float) env('PRODUCTION_MAX_PACKS_LINE', 5000),
        'max_lines' => 600,
    ],
];
