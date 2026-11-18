<?php

namespace App\Http\Controllers\Api\BuyerApp;

use App\Services\BuyerApp\Payments\OnlinePayments;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Online payment for an app order (Razorpay). Wallet payment needs no extra step: POST orders with payment_type "wallet".
 *   POST payments/razorpay/create   same fields as POST orders (payment_type is ignored): starts the payment
 *   POST payments/razorpay/confirm  {razorpay_order_id, razorpay_payment_id, razorpay_signature}: verifies and places the order
 *   GET  payments/razorpay/status   ?razorpay_order_id=  recover a payment after the app was closed
 */
class PaymentsController extends AppController
{
    private function answer(array $r, string $message): JsonResponse
    {
        if (! $r['ok']) {
            return $this->fail($r['message'], $r['code'], $r['extra'] ?? []);
        }

        return $this->ok(array_diff_key($r, ['ok' => 1]), $message);
    }

    public function create(Request $request): JsonResponse
    {
        $buyerId = $this->ownBuyerId($request);
        $r = OnlinePayments::create($buyerId, [
            'address_id' => $request->input('address_id'), 'delivery_type' => $request->input('delivery_type', ''),
            'schedule_date' => $request->input('schedule_date', ''), 'schedule_time' => $request->input('schedule_time', ''),
            'coupon_code' => $request->input('coupon_code', ''), 'expected_total' => $request->input('expected_total'),
        ]);

        return $this->answer($r['ok'] ? ['ok' => true, 'payment' => $r['data']] : $r, 'Payment started');
    }

    public function confirm(Request $request): JsonResponse
    {
        $buyerId = $this->ownBuyerId($request);
        foreach (['razorpay_order_id', 'razorpay_payment_id', 'razorpay_signature'] as $k) {
            $v = (string) $request->input($k, '');
            if ($v === '' || strlen($v) > 100) {
                return $this->fail('Payment details are missing. Please contact support if money was charged.', 'payment_details_missing');
            }
        }
        $r = OnlinePayments::confirm($buyerId, (string) $request->input('razorpay_order_id'), (string) $request->input('razorpay_payment_id'), (string) $request->input('razorpay_signature'));

        return $this->answer($r, 'Payment received and order placed');
    }

    public function status(Request $request): JsonResponse
    {
        $buyerId = $this->ownBuyerId($request);
        $id = (string) $request->input('razorpay_order_id', '');
        if ($id === '' || strlen($id) > 100) {
            return $this->fail('razorpay_order_id is required', 'payment_details_missing');
        }

        return $this->answer(OnlinePayments::status($buyerId, $id), 'Order placed');
    }
}
