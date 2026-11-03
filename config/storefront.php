<?php

// Freshful storefront (new home page) settings.
// Every value can be changed in .env, then: php artisan config:clear

return [
    // false = the old home page stays at "/"; the new one is only at /home-v2 for review.
    // true  = the new home page is served at "/" (the old one stays at /home-classic).
    'home_v2' => env('STOREFRONT_HOME_V2', false),

    // Show per-store stock signals on product cards (Cut today / Only X left / Out of stock).
    'stock_badges' => env('STOREFRONT_STOCK_BADGES', true),

    // "Only X left" shows when the store has this many packs or fewer.
    'low_stock_at' => (int) env('STOREFRONT_LOW_STOCK_AT', 5),

    // Store used for stock signals when the visitor has not chosen a location yet.
    'default_hub_id' => (int) env('STOREFRONT_DEFAULT_HUB', 3),

    // Products in the big hero at the top come from this product tag (up to 4 slides).
    'hero_tag' => env('STOREFRONT_HERO_TAG', 'Best Sellers'),

    // Products shown in each tag section (4 per row on desktop).
    'section_limit' => (int) env('STOREFRONT_SECTION_LIMIT', 8),

    // Category words that mean "Cut today" instead of "Fresh today".
    'cut_words' => ['chicken', 'mutton', 'goat', 'lamb', 'beef', 'buff', 'meat', 'fish', 'seafood', 'prawn', 'crab', 'squid', 'kabab', 'kebab', 'offal', 'tandoor'],

    // Colours for coupons, one per coupon in turn (no theme red here on purpose).
    'coupon_colors' => ['#0b6f78', '#b85a00', '#8c1d47', '#2f6b2f', '#1f3a6e', '#5b2a86'],
];
