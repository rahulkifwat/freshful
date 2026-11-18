<?php

namespace App\Http\Controllers\Api\BuyerApp;

use App\Http\Controllers\InvoiceController;
use App\Services\BuyerApp\Orders\OrderHistory;
use App\Services\BuyerApp\Shop\Latin1;
use App\Services\BuyerApp\Shop\OrderStage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * The website's order extras, for the app. An order of another customer answers "order_not_found".
 *   POST orders/{id}/invoice          link to the website's own invoice page (signed, no login needed, valid 7 days); the phone opens it to print or save as PDF
 *   GET  orders/{id}/ratings          the items of a delivered order with this customer's rating of each
 *   POST orders/{id}/ratings          {product_id, rating 1-5, feedback?} rate one item (a new rating replaces the old one, like the website)
 */
class OrderExtrasController extends AppController
{
    public const INVOICE_DAYS = 7;

    public function invoice(Request $request, string $orderId): JsonResponse
    {
        $buyerId = $this->ownBuyerId($request);
        $rows = OrderHistory::rows($buyerId, $orderId);
        if (! $rows) {
            return $this->fail('Order not found', 'order_not_found');
        }
        if (OrderStage::stage($rows[0]['order_status']) === 'cancelled') {
            return $this->fail('A cancelled order has no invoice.', 'invoice_not_available');
        }
        $until = new \DateTimeImmutable('+' . self::INVOICE_DAYS . ' days');

        return $this->ok(['order_id' => $orderId, 'url' => InvoiceController::signedUrl('order', $orderId, 'a4', false, $until), 'expires_at' => $until->format('Y-m-d H:i:s')]);
    }

    private function canRate(array $rows): bool
    {
        return in_array(OrderStage::stage($rows[0]['order_status']), ['delivered', 'unsettled'], true);
    }

    public function ratings(Request $request, string $orderId): JsonResponse
    {
        $buyerId = $this->ownBuyerId($request);
        $rows = OrderHistory::rows($buyerId, $orderId);
        if (! $rows) {
            return $this->fail('Order not found', 'order_not_found');
        }
        $ids = array_values(array_unique(array_map(fn ($r) => (int) $r['product_id'], $rows)));
        $mine = [];
        foreach (DB::table('reviews')->where('buyer_id', $buyerId)->whereIn('product_id', $ids)->orderBy('id')->get() as $v) {
            $mine[(int) $v->product_id] = $v;   // the newest row wins
        }
        $items = [];
        $seen = [];
        foreach ($rows as $r) {
            $pid = (int) $r['product_id'];
            if (isset($seen[$pid])) {
                continue;
            }
            $seen[$pid] = true;
            $name = trim((string) ($r['product_name'] ?? '')) !== '' ? trim((string) $r['product_name']) : trim((string) ($r['title'] ?? 'Item'));
            $v = $mine[$pid] ?? null;
            $items[] = [
                'product_id' => $pid, 'name' => $name, 'pack' => trim(($r['unit_quantity'] ?? '') . ' ' . ($r['product_unit'] ?? '')),
                'image' => ($r['item_image'] ?? '') !== '' ? $this->uploadsUrl('images/products', $r['item_image']) : null,
                'rating' => $v && is_numeric($v->rating) ? (int) $v->rating : null, 'feedback' => $v ? (string) $v->feedback : '',
            ];
        }

        return $this->ok(['can_rate' => $this->canRate($rows), 'items' => $items]);
    }

    public function rate(Request $request, string $orderId): JsonResponse
    {
        $buyerId = $this->ownBuyerId($request);
        $rows = OrderHistory::rows($buyerId, $orderId);
        if (! $rows) {
            return $this->fail('Order not found', 'order_not_found');
        }
        if (! $this->canRate($rows)) {
            return $this->fail('You can rate the items once the order is delivered.', 'not_delivered');
        }
        $pid = (int) $request->input('product_id', 0);
        if (! in_array($pid, array_map(fn ($r) => (int) $r['product_id'], $rows), true)) {
            return $this->fail('This item is not part of the order.', 'item_not_in_order');
        }
        $rating = $request->input('rating');
        if (! is_numeric($rating) || floor((float) $rating) != (float) $rating || $rating < 1 || $rating > 5) {
            return $this->fail('Please give 1 to 5 stars.', 'invalid_rating');
        }
        $text = trim((string) $request->input('feedback', ''));
        $text = function_exists('mb_substr') ? mb_substr($text, 0, 200) : substr($text, 0, 200);
        if (! Latin1::canStore($text)) {
            return $this->fail('Please remove emojis or special characters from your comment', 'text_unsupported');
        }

        $set = ['rating' => (string) (int) $rating, 'feedback' => $text];
        $q = DB::table('reviews')->where('buyer_id', $buyerId)->where('product_id', $pid);
        if ($q->exists()) {
            DB::table('reviews')->where('buyer_id', $buyerId)->where('product_id', $pid)->update($set);
        } else {
            DB::table('reviews')->insert($set + ['buyer_id' => $buyerId, 'product_id' => $pid, 'date_added' => date('Y-m-d H:i:s')]);
        }

        return $this->ok(['product_id' => $pid, 'rating' => (int) $rating, 'feedback' => $text], 'Thank you for your rating');
    }
}
