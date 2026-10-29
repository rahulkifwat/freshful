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
];
