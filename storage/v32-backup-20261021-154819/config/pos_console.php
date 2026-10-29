<?php

/*
| Freshful POS console settings (21 Oct 2026).
| Set POS_HEAD_OFFICE_EMAIL in .env (comma separated for more than one) to email day-end reports.
*/
return [
    'head_office_email' => env('POS_HEAD_OFFICE_EMAIL', ''),
    // Orders still open this many days after they were due go to the "Old open" tab (no alarm).
    'stale_days' => (int) env('POS_STALE_DAYS', 3),
    // Express orders are due this many minutes after they are placed.
    'express_minutes' => (int) env('POS_EXPRESS_MINUTES', 90),
];
