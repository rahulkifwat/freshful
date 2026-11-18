<?php

/*
 * Order payment guard (15 Nov 2026).
 * Until payment is verified on the server, only Cash on delivery may be placed from the website and the old app endpoint.
 * To switch Online and Wallet back on later, add this line to .env and run: php artisan config:clear
 *   ORDER_ALLOW_ONLINE_WALLET=true
 * Do this only after server-side payment verification and wallet debit are connected to these paths.
 */
return [
    'allow_online_wallet' => filter_var(env('ORDER_ALLOW_ONLINE_WALLET', false), FILTER_VALIDATE_BOOL),
];
