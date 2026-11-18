<?php

namespace App\Http\Controllers\Api\BuyerApp;

use App\Services\BuyerApp\Orders\OrderHistory;
use App\Services\BuyerApp\Orders\OrderWeights;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** GET orders/{id}/weights: the exact weights found when the store packed the order (empty until it is weighed). Read only. */
class OrderWeightsController extends AppController
{
    public function show(Request $request, string $orderId): JsonResponse
    {
        $buyerId = $this->ownBuyerId($request);
        if (! OrderHistory::rows($buyerId, $orderId)) {
            return $this->fail('Order not found', 'order_not_found');
        }

        return $this->ok(OrderWeights::forOrder($buyerId, $orderId) + ['order_id' => $orderId]);
    }
}
