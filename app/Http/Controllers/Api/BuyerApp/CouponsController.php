<?php

namespace App\Http\Controllers\Api\BuyerApp;

use App\Services\BuyerApp\Checkout\Checkout;
use App\Services\BuyerApp\Checkout\Coupons;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * GET coupons: the coupons a customer can see, each with built-from-numbers text, whether it works for the current
 * cart, why not (and how much more to add), and how much it would save.
 */
class CouponsController extends AppController
{
    public function index(Request $request): JsonResponse
    {
        $buyerId = $this->ownBuyerId($request);
        $cart = Checkout::cartLines($buyerId);
        $list = Coupons::listFor($buyerId, $cart['subtotal'], date('Y-m-d'));
        foreach ($list as &$c) {
            $c['image_url'] = ($c['image'] === '' || $c['image'] === 'none.png') ? null : $this->uploadsUrl('images/promotions', $c['image']);
            unset($c['image']);
        }

        return $this->ok(['cart_subtotal' => $cart['subtotal'], 'count' => count($list), 'coupons' => $list]);
    }
}
