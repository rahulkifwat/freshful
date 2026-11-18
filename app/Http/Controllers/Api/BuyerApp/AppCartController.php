<?php

namespace App\Http\Controllers\Api\BuyerApp;

use App\Services\BuyerApp\Checkout\Checkout;
use App\Services\Stock\StockGuard;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * The customer's cart for the app. It is the same server cart the website and the old app use (table cart), so a cart
 * started on one is seen on the other. Prices are always the server's; the old /api/cart is not used because it returns
 * raw product rows (including cost_price).
 *   GET  cart         lines with server prices, subtotal, problems
 *   POST cart/set     {product_id, quantity, hub_id?}  absolute quantity 0..99 (0 removes). hub_id adds a stock check.
 *   POST cart/clear
 */
class AppCartController extends AppController
{
    private function summary(int $buyerId): array
    {
        $c = Checkout::cartLines($buyerId);
        $lines = [];
        foreach ($c['lines'] as $l) {
            $l['image'] = $l['image'] !== '' ? $this->uploadsUrl('images/products', $l['image']) : null;
            $lines[] = $l;
        }

        return [
            'lines' => $lines, 'line_count' => count($lines), 'quantity' => array_sum(array_column(array_filter($c['lines'], fn ($l) => $l['available']), 'quantity')),
            'subtotal' => $c['subtotal'], 'you_save' => round(max(0, $c['mrp_total'] - $c['subtotal']), 2), 'problems' => $c['problems'],
        ];
    }

    private function recount(int $buyerId): void
    {
        DB::table('buyers')->where('id', $buyerId)->update(['cart_count' => DB::table('cart')->where('buyer_id', $buyerId)->count()]);
    }

    public function show(Request $request): JsonResponse
    {
        return $this->ok(['cart' => $this->summary($this->ownBuyerId($request))]);
    }

    public function set(Request $request): JsonResponse
    {
        $buyerId = $this->ownBuyerId($request);
        $pid = (int) $request->input('product_id', 0);
        $qty = $request->input('quantity');
        if ($pid <= 0) {
            return $this->fail('Please choose a product', 'product_required');
        }
        if (! is_numeric($qty) || floor((float) $qty) != (float) $qty || $qty < 0 || $qty > 99) {
            return $this->fail('Quantity must be a whole number from 0 to 99', 'invalid_quantity');
        }
        $qty = (int) $qty;
        $price = 0.0;

        if ($qty > 0) {
            $p = DB::table('products')->where('id', $pid)->first(['id', 'status', 'main_price', 'MRP']);
            $price = $p ? ((float) $p->main_price > 0 ? (float) $p->main_price : (float) $p->MRP) : 0.0;
            if (! $p || (string) $p->status !== 'active' || $price <= 0) {
                return $this->fail('This item is not available right now.', 'unavailable');
            }
            $hub = (int) $request->input('hub_id', 0);
            if ($hub > 0 && ($msg = StockGuard::shortage($hub, [$pid => $qty]))) {
                return $this->fail($msg, 'out_of_stock');
            }
        }

        DB::transaction(function () use ($buyerId, $pid, $qty, $price) {
            $line = DB::table('cart')->where('buyer_id', $buyerId)->where('product_id', (string) $pid);
            if ($qty === 0) {
                $line->delete();
            } elseif ($line->exists()) {
                DB::table('cart')->where('buyer_id', $buyerId)->where('product_id', (string) $pid)->update(['quantity' => (string) $qty, 'total_price' => (string) round($price * $qty, 2)]);
            } else {
                DB::table('cart')->insert(['product_id' => (string) $pid, 'buyer_id' => $buyerId, 'quantity' => (string) $qty, 'total_price' => (string) round($price * $qty, 2)]);
            }
            $this->recount($buyerId);
        });

        return $this->ok(['cart' => $this->summary($buyerId)], $qty === 0 ? 'Removed from cart' : 'Cart updated');
    }

    public function clear(Request $request): JsonResponse
    {
        $buyerId = $this->ownBuyerId($request);
        DB::transaction(function () use ($buyerId) {
            DB::table('cart')->where('buyer_id', $buyerId)->delete();
            $this->recount($buyerId);
        });

        return $this->ok(['cart' => $this->summary($buyerId)], 'Cart emptied');
    }
}
