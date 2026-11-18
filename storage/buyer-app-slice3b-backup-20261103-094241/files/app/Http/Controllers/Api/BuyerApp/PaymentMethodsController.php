<?php

namespace App\Http\Controllers\Api\BuyerApp;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Only payment methods that are really available are returned, so the app never shows a button that cannot work.
 * Cash on delivery is always there. The others need their switch in config/buyer_app_commerce.php (.env) AND the
 * backend pieces: online needs Razorpay keys on the server, wallet needs a balance.
 */
class PaymentMethodsController extends AppController
{
    public function index(Request $request): JsonResponse
    {
        $buyerId = $this->ownBuyerId($request);
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

        return $this->ok(['methods' => $methods, 'default' => 'cash']);
    }
}
