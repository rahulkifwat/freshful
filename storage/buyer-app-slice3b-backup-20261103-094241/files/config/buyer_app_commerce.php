<?php

/*
 * Customer app: which payment methods are switched on. Each is OFF until the server can really handle it.
 * Turn one on in .env (then: php artisan config:clear):
 *   BUYER_APP_PAY_ONLINE=true            UPI / cards through Razorpay (needs server-side payment checking first)
 *   BUYER_APP_PAY_WALLET=true            Freshful wallet (needs wallet debit with ledger first)
 *   BUYER_APP_PAY_CARD_ON_DELIVERY=true  only if riders can really take a card at the door
 * Cash on delivery is always available.
 */
return [
    'pay_online' => filter_var(env('BUYER_APP_PAY_ONLINE', false), FILTER_VALIDATE_BOOL),
    'pay_wallet' => filter_var(env('BUYER_APP_PAY_WALLET', false), FILTER_VALIDATE_BOOL),
    'pay_card_on_delivery' => filter_var(env('BUYER_APP_PAY_CARD_ON_DELIVERY', false), FILTER_VALIDATE_BOOL),
];
