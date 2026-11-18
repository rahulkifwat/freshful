<?php

namespace App\Http\Controllers\Api\BuyerApp;

use App\Services\BuyerApp\Checkout\Checkout;
use App\Services\BuyerApp\Checkout\Slots;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Checkout for the app. The cart is the one already kept on the server (add_cart / cart / empty_cart).
 *   GET  checkout/slots   delivery times the customer can pick
 *   POST checkout/quote   {address_id, coupon_code?}  the full price breakdown, nothing is saved
 *   POST orders           {address_id, payment_type, delivery_type, schedule_date?, schedule_time?, coupon_code?, expected_total}
 */
class CheckoutController extends AppController
{
    public function slots(Request $request): JsonResponse
    {
        $this->ownBuyerId($request);

        return $this->ok(Slots::listing());
    }

    public function quote(Request $request): JsonResponse
    {
        $buyerId = $this->ownBuyerId($request);
        $r = Checkout::quote($buyerId, $request->input('address_id'), (string) $request->input('coupon_code', ''));
        if (! $r['ok']) {
            return $this->fail($r['message'], $r['code']);
        }

        return $this->ok(['quote' => $r['quote']]);
    }

    public function store(Request $request): JsonResponse
    {
        $buyerId = $this->ownBuyerId($request);
        $r = Checkout::place($buyerId, [
            'address_id' => $request->input('address_id'),
            'payment_type' => $request->input('payment_type', 'cash'),
            'delivery_type' => $request->input('delivery_type', ''),
            'schedule_date' => $request->input('schedule_date', ''),
            'schedule_time' => $request->input('schedule_time', ''),
            'coupon_code' => $request->input('coupon_code', ''),
            'expected_total' => $request->input('expected_total'),
        ]);
        if (! $r['ok']) {
            return $this->fail($r['message'], $r['code'], $r['extra'] ?? []);
        }

        return $this->ok(['order' => $r['order']], 'Order placed successfully');
    }
}
