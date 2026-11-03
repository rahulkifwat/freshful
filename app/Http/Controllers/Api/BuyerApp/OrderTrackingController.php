<?php

namespace App\Http\Controllers\Api\BuyerApp;

use App\Services\BuyerApp\Shop\Latin1;
use App\Services\BuyerApp\Shop\OrderStage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Order tracking and customer cancel. Read-only on rider data; the driver section is not touched.
 * An order belongs to the token's buyer only: another buyer's order id answers "order_not_found".
 */
class OrderTrackingController extends AppController
{
    /** GET /api/app/orders/{orderId}/tracking */
    public function show(Request $request, string $orderId): JsonResponse
    {
        $buyerId = $this->ownBuyerId($request);

        $rows = DB::table('orders')->where('order_id', $orderId)->where('buyer_id', $buyerId)->orderBy('id')->get();
        if ($rows->isEmpty()) {
            return $this->fail('Order not found', 'order_not_found');
        }

        $o = (array) $rows->first();
        $stage = OrderStage::stage($o['order_status']);

        $stockTaken = false;
        $decision = ['ok' => false, 'code' => '', 'message' => ''];
        if ($stage === 'new') {
            $stockTaken = DB::table('hub_stock_moves')->where('type', 'sale')->where('ref', $orderId)->exists();
        }
        $decision = OrderStage::cancelDecision($o['order_status'], $o['payment_type'], $stockTaken);

        // The rider is shown only while the order is out for delivery.
        $rider = null;
        if ($stage === 'out' && (int) $o['rider_id'] > 0) {
            $b = DB::table('delivery_boy')->where('id', (int) $o['rider_id'])->first(['name', 'phone']);
            if ($b) {
                $rider = ['name' => (string) $b->name, 'phone' => (string) $b->phone];
            }
        }

        return $this->ok([
            'order' => [
                'order_id'          => (string) $o['order_id'],
                'placed_at'         => OrderStage::cleanTime($o['date_added']),
                'status'            => (string) $o['order_status'],
                'stage'             => $stage,
                'stage_label'       => OrderStage::LABEL[$stage] ?? OrderStage::LABEL['other'],
                'delivery_type'     => (string) $o['delivery_type'],
                'schedule_date'     => (string) $o['schedule_date'],
                'schedule_time'     => (string) $o['schedule_time'],
                'expected_by'       => OrderStage::expectedBy($o['delivery_type'], $o['date_added']),
                'payment_type'      => (string) $o['payment_type'],
                'payment_status'    => (string) $o['payment_status'],
                'total_amount'      => (float) $o['total_amount'],
                'delivery_charge'   => (float) $o['delivery_charge'],
                'amount_to_collect' => (float) $o['amount_to_collect'],
                'amount_to_return'  => (float) $o['amount_to_return'],
                'item_count'        => $rows->count(),
            ],
            'timeline'       => OrderStage::timeline($o),
            'rider'          => $rider,
            'can_cancel'     => $decision['ok'],
            'cancel_message' => $decision['ok'] ? '' : $decision['message'],
        ]);
    }

    /** POST /api/app/orders/{orderId}/cancel {reason_id, note?} - reason ids from GET content/cancel-reasons */
    public function cancel(Request $request, string $orderId): JsonResponse
    {
        $buyerId = $this->ownBuyerId($request);

        $reasonId = (int) $request->input('reason_id', 0);
        if (! isset(OrderStage::CANCEL_REASONS[$reasonId])) {
            return $this->fail('Please select a reason', 'reason_required');
        }
        $note = trim((string) $request->input('note', ''));
        $note = function_exists('mb_substr') ? mb_substr($note, 0, 120) : substr($note, 0, 120);
        if (! Latin1::canStore($note)) {
            return $this->fail('Please remove emojis or special characters from your note', 'text_unsupported');
        }
        $text = 'Customer (app): ' . OrderStage::CANCEL_REASONS[$reasonId] . ($note !== '' ? ' - ' . $note : '');

        $result = DB::transaction(function () use ($orderId, $buyerId, $text) {
            $rows = DB::table('orders')->where('order_id', $orderId)->where('buyer_id', $buyerId)->lockForUpdate()->get();
            if ($rows->isEmpty()) {
                return ['Order not found', 'order_not_found'];
            }
            $first = $rows->first();
            $stockTaken = DB::table('hub_stock_moves')->where('type', 'sale')->where('ref', $orderId)->exists();
            $d = OrderStage::cancelDecision($first->order_status, $first->payment_type, $stockTaken);
            if (! $d['ok']) {
                return [$d['message'], $d['code']];
            }

            // Still new at this very moment? (The hub may have accepted it a second ago.)
            $n = DB::table('orders')->where('order_id', $orderId)->where('buyer_id', $buyerId)
                ->where('order_status', 'Order Pending')
                ->update(['order_status' => 'Order Cancel', 'reason_for_order_cancel' => substr($text, 0, 255), 'updated_at' => date('Y-m-d H:i:s')]);

            return $n > 0 ? null : ['The store has just accepted your order, so it cannot be cancelled here. Please contact support.', 'order_accepted'];
        });

        if ($result !== null) {
            return $this->fail($result[0], $result[1]);
        }

        // Cancelling releases the packs held for this order automatically (the stock hold is worked out from the status).
        return $this->ok(['order_id' => $orderId, 'status' => 'Order Cancel'], 'Order cancelled successfully');
    }
}
