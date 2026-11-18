<?php

namespace App\Http\Controllers\Api\BuyerApp;

use App\Services\BuyerApp\Checkout\Checkout;
use App\Services\BuyerApp\Orders\OrderHistory;
use App\Services\Stock\StockGuard;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * The customer's own orders for the app. Another customer's order id answers "order_not_found".
 *   GET  orders?page=            newest first, 15 per page
 *   GET  orders/{id}             items (names and quantities), bill (subtotal, coupon, delivery, total), address; live status comes from orders/{id}/tracking
 *   POST orders/{id}/reorder     puts the available items of an earlier order into the cart ({hub_id?} adds a stock check)
 */
class OrderHistoryController extends AppController
{
    public function index(Request $request): JsonResponse
    {
        $buyerId = $this->ownBuyerId($request);
        $r = OrderHistory::listFor($buyerId, (int) $request->input('page', 1));
        foreach ($r['orders'] as &$o) {
            foreach ($o['thumbs'] as &$t) {
                $t['image'] = $t['image'] !== '' ? $this->uploadsUrl('images/products', $t['image']) : null;
            }
            unset($t);
        }
        unset($o);

        return $this->ok($r);
    }

    public function show(Request $request, string $orderId): JsonResponse
    {
        $buyerId = $this->ownBuyerId($request);
        $rows = OrderHistory::rows($buyerId, $orderId);
        if (! $rows) {
            return $this->fail('Order not found', 'order_not_found');
        }
        $d = OrderHistory::detail($buyerId, $rows);
        foreach ($d['lines'] as &$l) {
            $l['image'] = $l['image'] !== '' ? $this->uploadsUrl('images/products', $l['image']) : null;
        }
        unset($l);
        foreach ($d['thumbs'] as &$t) {
            $t['image'] = $t['image'] !== '' ? $this->uploadsUrl('images/products', $t['image']) : null;
        }
        unset($t);

        return $this->ok(['order' => $d]);
    }

    public function reorder(Request $request, string $orderId): JsonResponse
    {
        $buyerId = $this->ownBuyerId($request);
        $rows = OrderHistory::rows($buyerId, $orderId);
        if (! $rows) {
            return $this->fail('Order not found', 'order_not_found');
        }
        $hub = (int) $request->input('hub_id', 0);

        $added = [];
        $skipped = [];
        DB::transaction(function () use ($buyerId, $rows, $hub, &$added, &$skipped) {
            foreach ($rows as $r) {
                $pid = (int) $r['product_id'];
                $name = trim((string) ($r['product_name'] ?? '')) !== '' ? trim((string) $r['product_name']) : trim((string) ($r['title'] ?? 'Item'));
                $price = (float) $r['main_price'] > 0 ? (float) $r['main_price'] : (float) $r['MRP'];
                if (! $r['p_status'] || (string) $r['p_status'] !== 'active' || $price <= 0) {
                    $skipped[] = ['product_id' => $pid, 'name' => $name, 'reason' => 'No longer available'];
                    continue;
                }
                $have = (int) DB::table('cart')->where('buyer_id', $buyerId)->where('product_id', (string) $pid)->value('quantity');
                $want = min(99, $have + max(1, (int) $r['quantity']));
                if ($hub > 0 && StockGuard::shortage($hub, [$pid => $want])) {
                    $skipped[] = ['product_id' => $pid, 'name' => $name, 'reason' => 'Not enough stock right now'];
                    continue;
                }
                $line = DB::table('cart')->where('buyer_id', $buyerId)->where('product_id', (string) $pid);
                if ($have > 0) {
                    DB::table('cart')->where('buyer_id', $buyerId)->where('product_id', (string) $pid)->update(['quantity' => (string) $want, 'total_price' => (string) round($price * $want, 2)]);
                } else {
                    DB::table('cart')->insert(['product_id' => (string) $pid, 'buyer_id' => $buyerId, 'quantity' => (string) $want, 'total_price' => (string) round($price * $want, 2)]);
                }
                $added[] = ['product_id' => $pid, 'name' => $name, 'quantity' => $want];
            }
            DB::table('buyers')->where('id', $buyerId)->update(['cart_count' => DB::table('cart')->where('buyer_id', $buyerId)->count()]);
        });

        $c = Checkout::cartLines($buyerId);
        $msg = $added ? (count($skipped) ? 'Some items were added. A few are not available right now.' : 'Items added to your cart') : 'None of these items are available right now.';

        return $this->ok(['added' => $added, 'skipped' => $skipped, 'cart' => ['line_count' => count($c['lines']), 'subtotal' => $c['subtotal']]], $msg);
    }
}
