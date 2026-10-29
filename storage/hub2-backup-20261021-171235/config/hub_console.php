<?php

/* Freshful hub console settings (22 Oct 2026). */
return [
    // A product with this many units or fewer (or fewer than open orders need) is shown as "low".
    'low_stock' => (float) env('HUB_LOW_STOCK', 5),
];
