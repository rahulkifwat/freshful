<?php

namespace App\Services\Orders;

use App\Exceptions\ApiException;
use App\Services\BuyerApp\Checkout\Slots;
use App\Services\BuyerApp\ServiceArea;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Old app order endpoint (/api/add_order), 15 Nov 2026. The server works out the money, the hub and the dates.
 * The phone only says WHAT it wants (products, quantities, address, payment type, delivery slot). Everything else
 * it sends is ignored, and only an approved list of columns is ever saved.
 *
 *   price           products.main_price (else MRP), the same rule as the new app
 *   total_amount    sum of price x quantity (items, before delivery), as every other checkout saves it
 *   hub             from the saved address (ServiceArea); an old address with no map point keeps the hub the phone
 *                   sent, but only if it is a real active hub
 *   delivery_charge city price list (delivery_price) of that hub's city; a city with no price refuses the order
 *   amount_to_collect  Cash: items + delivery. Other types: 0
 *   order_status    always Order Pending; dates are the server's clock
 */
final class LegacyOrderGuard
{
    private static function note(string $msg, array $ctx = []): void
    {
        try { Log::notice('[order-guard] ' . $msg, $ctx); } catch (\Throwable $e) { /* never block an order for a log line */ }
    }

    private static function money($v): string
    {
        return (string) round((float) $v, 2);
    }

    /**
     * @param array $productIds comma-split product ids as the old code read them
     * @param array $quantities comma-split quantities
     * @return array{base: array, product_ids: array, quantities: array}
     * @throws ApiException
     */
    public static function prepare(int $buyerId, array $in, array $productIds, array $quantities, ?int $ts = null): array
    {
        $ts = $ts ?? time();

        // 1. Lines
        $ids = [];
        foreach ($productIds as $p) {
            $p = trim((string) $p);
            if ($p === '' ) continue;
            if (!ctype_digit($p) || (int) $p < 1) throw new ApiException('Please check the products in your order.');
            $ids[] = (int) $p;
        }
        $qtys = [];
        foreach ($quantities as $q) {
            $q = trim((string) $q);
            if ($q === '' ) continue;
            if (!ctype_digit($q) || (int) $q < 1 || (int) $q > 99) throw new ApiException('Please check the quantity of each product (1 to 99).');
            $qtys[] = (string) (int) $q;
        }
        if (!$ids || count($ids) !== count($qtys)) throw new ApiException('Please check the products and quantities in your order.');
        if (count($ids) > 60) throw new ApiException('Too many items in one order. Please split it into two orders.');

        // 2. Server prices
        $prod = DB::table('products')->whereIn('id', array_values(array_unique($ids)))->get()->keyBy('id');
        $items = 0.0;
        foreach ($ids as $i => $pid) {
            $p = $prod->get($pid);
            if (!$p || (string) $p->status !== 'active') throw new ApiException('One of the products is no longer available. Please refresh your cart.');
            $price = (float) $p->main_price > 0 ? (float) $p->main_price : (float) $p->MRP;
            if ($price <= 0) throw new ApiException('One of the products is not available right now. Please refresh your cart.');
            $items += round($price * (int) $qtys[$i], 2);
        }
        $items = round($items, 2);

        // 3. Hub from the saved address; the phone's hub_id only as a checked fallback
        $addr = DB::table('buyer_address')->where('id', (int) ($in['address_id'] ?? 0))->where('buyer_id', $buyerId)->first();
        if (!$addr) throw new ApiException('Address not found');
        $hub = null; $how = null;
        foreach ([[$addr->latitude ?? null, $addr->longitude ?? null], [$addr->user_lat ?? null, $addr->user_lng ?? null]] as [$x, $y]) {
            if (is_numeric($x) && is_numeric($y) && (float) $x >= 6.0 && (float) $x <= 38.0 && (float) $y >= 68.0 && (float) $y <= 98.9) {
                $cov = ServiceArea::check((float) $x, (float) $y);
                if (!$cov['covered']) throw new ApiException("We'll be coming to your area soon.");
                $hub = DB::table('hubs')->where('id', (int) $cov['hub']['id'])->first();
                $how = 'address';
                break;
            }
        }
        if (!$hub) {
            $h = DB::table('hubs')->where('id', (int) ($in['hub_id'] ?? 0))->whereRaw("LOWER(CAST(status AS CHAR)) IN ('active', '1')")->first();
            if (!$h) throw new ApiException('We could not find a delivery hub for this address. Please set the address on the map.');
            $hub = $h; $how = 'phone';
        }
        if ($how === 'phone' || (int) ($in['hub_id'] ?? 0) !== (int) $hub->id) {
            self::note('hub decided by server', ['how' => $how, 'phone_hub' => (int) ($in['hub_id'] ?? 0), 'hub' => (int) $hub->id]);
        }

        // 4. Delivery charge from the city price list. No price set = the order is refused, never charged as 0.
        $price = DB::table('delivery_price')->where('city', (string) $hub->city_id)->first();
        if (!$price || !is_numeric($price->price)) {
            self::note('order refused: no delivery price set for the hub city', ['hub' => (int) $hub->id, 'city' => (string) $hub->city_id]);
            throw new ApiException('Delivery to this area is not set up yet. Please try again later.');
        }
        $delivery = (int) round((float) $price->price);

        // 5. Delivery slot: same rules as the website and the new app
        [$slot, $err] = Slots::resolve(trim((string) ($in['delivery_type'] ?? '')), trim((string) ($in['schedule_date'] ?? '')), trim((string) ($in['schedule_time'] ?? '')), $ts);
        if ($err) throw new ApiException($err[1]);

        // 6. Payment type is normalised here; PaymentGuard refuses the unpaid kinds right after
        $ptype = class_exists(PaymentGuard::class) ? PaymentGuard::normalise($in['payment_type'] ?? null) : (trim((string) ($in['payment_type'] ?? '')) ?: 'Cash');
        $grand = round($items + $delivery, 2);

        // 7. The same basket from the same customer in the last 30 seconds is a double tap
        $recent = DB::table('orders')->where('buyer_id', $buyerId)->where('date_added', '>=', date('Y-m-d H:i:s', $ts - 30))->orderBy('id')->get(['order_id', 'product_id', 'quantity']);
        if ($recent->isNotEmpty()) {
            $want = []; foreach ($ids as $i => $pid) $want[] = $pid . 'x' . $qtys[$i]; sort($want);
            foreach ($recent->groupBy('order_id') as $lines) {
                $have = []; foreach ($lines as $l) $have[] = (int) $l->product_id . 'x' . (int) $l->quantity; sort($have);
                if ($have === $want) throw new ApiException('This order was just placed. Please check My Orders.');
            }
        }

        if (isset($in['total_amount']) && is_numeric($in['total_amount']) && abs((float) $in['total_amount'] - $items) > 0.5 && abs((float) $in['total_amount'] - $grand) > 0.5) {
            self::note('phone total differs from server total', ['phone' => (string) $in['total_amount'], 'server_items' => $items, 'server_grand' => $grand]);
        }

        $now = date('Y-m-d H:i:s', $ts);
        $base = [
            'buyer_id' => $buyerId, 'address_id' => (int) $addr->id, 'hub_id' => (int) $hub->id,
            'payment_type' => $ptype, 'delivery_type' => $slot['type'], 'delivery_charge' => $delivery,
            'schedule_date' => $slot['date'], 'schedule_time' => $slot['time'],
            'total_amount' => self::money($items), 'amount_to_collect' => $ptype === 'Cash' ? self::money($grand) : '0',
            'order_status' => 'Order Pending', 'reason_for_order_cancel' => '', 'date_added' => $now, 'updated_at' => $now,
        ];

        return ['base' => $base, 'product_ids' => $ids, 'quantities' => $qtys];
    }
}
