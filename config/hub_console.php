<?php

/* Freshful hub console settings (22 Oct 2026). */
return [
    // A product with this many units or fewer (or fewer than open orders need) is shown as "low".
    'low_stock' => (float) env('HUB_LOW_STOCK', 5),

    // Take stock off the shelf when the POS accepts an order, and put it back if that order is cancelled or moved.
    // Set HUB_STOCK_DEDUCT=false in .env to switch it off (nothing else changes).
    'deduct_on_accept' => (bool) env('HUB_STOCK_DEDUCT', true),

    // Warn the hub manager when the cash held at the hub goes above this amount (rupees).
    'cash_limit' => (float) env('HUB_CASH_LIMIT', 20000),

    // Danger stock (stock received yesterday that must sell today). The plan runs once per hub per day from this time.
    'danger_plan_time' => env('HUB_DANGER_PLAN_TIME', '11:00'),
    // Hubs within this distance (km, from hub coordinates) can take each other's danger stock. Same city if no coordinates.
    'nearby_km' => (float) env('HUB_NEARBY_KM', 25),

    // Customer grades from the last 90 days at the hub: reach EITHER the orders OR the spend (rupees) to get the grade.
    'grades' => [
        'gold' => ['orders' => 8, 'spend' => 8000],
        'silver' => ['orders' => 3, 'spend' => 2500],
    ],
];
