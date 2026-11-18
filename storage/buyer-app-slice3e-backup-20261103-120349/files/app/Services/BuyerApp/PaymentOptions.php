<?php

namespace App\Services\BuyerApp;

use Illuminate\Support\Facades\DB;

/**
 * The payment methods that are really available to a customer right now. One place, so the payment-methods list
 * and the order check can never disagree. Cash on delivery is always there; every other method needs its switch in
 * config/buyer_app_commerce.php AND its backend piece (Razorpay keys, a wallet balance).
 */
final class PaymentOptions
{
    public static function available(int $buyerId): array
    {
        $cfg = (array) config('buyer_app_commerce', []);

        $methods = [[
            'key' => 'cash', 'label' => 'Cash on delivery', 'description' => 'Pay the rider when your order arrives',
        ]];

        $razorOk = trim((string) config('razorpay.key_id', '')) !== '' && trim((string) config('razorpay.key_secret', '')) !== '';
        if (! empty($cfg['pay_online']) && $razorOk) {
            $methods[] = ['key' => 'online', 'label' => 'UPI or card', 'description' => 'Pay securely online'];
        }

        if (! empty($cfg['pay_wallet'])) {
            $balance = (float) (DB::table('buyers')->where('id', $buyerId)->value('wallet_amount') ?? 0);
            if ($balance > 0) {
                $methods[] = ['key' => 'wallet', 'label' => 'Freshful wallet', 'description' => 'Balance ₹' . rtrim(rtrim(number_format($balance, 2, '.', ''), '0'), '.'), 'balance' => $balance];
            }
        }

        if (! empty($cfg['pay_card_on_delivery'])) {
            $methods[] = ['key' => 'card_on_delivery', 'label' => 'Card on delivery', 'description' => 'Pay by card when your order arrives'];
        }

        return $methods;
    }

    public static function keys(int $buyerId): array
    {
        return array_column(self::available($buyerId), 'key');
    }
}
