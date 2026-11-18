<?php

namespace App\Services\BuyerApp\Checkout;

use App\Services\BuyerApp\PaymentOptions;
use App\Services\BuyerApp\ServiceArea;
use App\Services\Stock\StockGuard;
use Illuminate\Support\Facades\DB;

/**
 * The server works out every number of an app order: prices from products, the coupon, the delivery charge from the
 * city price list, the hub from the saved address, stock from StockGuard. Nothing money-related is taken from the phone;
 * the phone only says what it expects to pay (expected_total) so a price that changed since the screen was shown is
 * refused instead of silently charged.
 *
 * Rows are written exactly like the website checkout writes them (one row per product, shared order_id,
 * total_amount = items after discount before delivery, status Order Pending) so POS, hub, riders and invoices read
 * app orders with no change. Stock is NOT decremented here: StockGuard holds it from Order Pending and the POS takes
 * it off the shelf when the hub accepts the order.
 */
final class Checkout
{
    private const PAY_TYPE = ['cash' => 'Cash', 'card_on_delivery' => 'Card'];

    private static function fail(string $code, string $message, array $extra = []): array
    {
        return ['ok' => false, 'code' => $code, 'message' => $message, 'extra' => $extra];
    }

    private static function n($v): string
    {
        return (string) round((float) $v, 2);
    }

    /** Cart lines with server prices. @return array{lines: array, problems: array, subtotal: float, mrp_total: float} */
    public static function cartLines(int $buyerId, bool $lock = false): array
    {
        $q = DB::table('cart as c')->join('products as p', 'p.id', '=', 'c.product_id')->join('items as i', 'i.id', '=', 'p.item_id')
            ->where('c.buyer_id', $buyerId)
            ->select('c.product_id', 'c.quantity', 'p.product_name', 'p.product_unit', 'p.unit_quantity', 'p.MRP', 'p.main_price', 'p.status', 'i.item as title', 'i.image as item_image');
        if ($lock) {
            // lock only this customer's own cart rows (not products or items), so a double tap waits and then finds it empty
            DB::table('cart')->where('buyer_id', $buyerId)->lockForUpdate()->get(['id']);
        }
        $lines = [];
        $problems = [];
        $subtotal = 0.0;
        $mrpTotal = 0.0;
        foreach ($q->get() as $r) {
            $name = trim((string) $r->product_name) !== '' ? trim((string) $r->product_name) : trim((string) $r->title);
            $qty = is_numeric($r->quantity) ? (float) $r->quantity : 0.0;
            $price = (float) $r->main_price > 0 ? (float) $r->main_price : (float) $r->MRP;
            $mrp = max((float) $r->MRP, $price);
            $bad = null;
            if ((string) $r->status !== 'active') {
                $bad = ['unavailable', $name . ' is no longer available. Please remove it from your cart.'];
            } elseif ($price <= 0) {
                $bad = ['unavailable', $name . ' is not available right now. Please remove it from your cart.'];
            } elseif ($qty < 1 || $qty > 99 || floor($qty) != $qty) {
                $bad = ['bad_quantity', 'Please check the quantity of ' . $name . '.'];
            }
            if ($bad) {
                $problems[] = ['product_id' => (int) $r->product_id, 'name' => $name, 'code' => $bad[0], 'message' => $bad[1]];
            }
            $amount = round($price * $qty, 2);
            $lines[] = [
                'product_id' => (int) $r->product_id, 'name' => $name, 'pack' => trim($r->unit_quantity . ' ' . $r->product_unit), 'image' => (string) $r->item_image,
                'quantity' => (int) $qty, 'mrp' => $mrp, 'price' => $price, 'amount' => $amount, 'available' => $bad === null,
            ];
            if ($bad === null) {
                $subtotal += $amount;
                $mrpTotal += $mrp * $qty;
            }
        }

        return ['lines' => $lines, 'problems' => $problems, 'subtotal' => round($subtotal, 2), 'mrp_total' => round($mrpTotal, 2)];
    }

    /** Address, hub and delivery charge for a saved address of this customer. */
    private static function destination(int $buyerId, $addressId): array
    {
        $a = DB::table('buyer_address')->where('id', (int) $addressId)->where('buyer_id', $buyerId)->whereNull('app_deleted_at')->first();
        if (! $a) {
            return self::fail('address_not_found', 'Please choose one of your saved addresses.');
        }
        $lat = $lng = null;
        foreach ([[$a->latitude ?? null, $a->longitude ?? null], [$a->user_lat ?? null, $a->user_lng ?? null]] as [$x, $y]) {
            if (is_numeric($x) && is_numeric($y) && (float) $x >= 6.0 && (float) $x <= 38.0 && (float) $y >= 68.0 && (float) $y <= 98.9) {
                [$lat, $lng] = [(float) $x, (float) $y];
                break;
            }
        }
        if ($lat === null) {
            return self::fail('address_needs_location', 'Please set this address on the map first.');
        }
        $cov = ServiceArea::check($lat, $lng);
        if (! $cov['covered']) {
            return self::fail('not_deliverable', "We'll be coming to your area soon.");
        }

        return ['ok' => true, 'address_id' => (int) $a->id, 'hub' => $cov['hub'], 'city' => $cov['city'],
            'delivery_charge' => (int) round((float) ($cov['delivery_charge'] ?? 0)), 'delivery_charge_set' => (bool) $cov['delivery_charge_set']];
    }

    /**
     * The price breakdown of the cart for an address. Never writes. $couponCode '' = none.
     * @return array{ok: bool, code?: string, message?: string, quote?: array}
     */
    public static function quote(int $buyerId, $addressId, string $couponCode, bool $lock = false, ?string $today = null): array
    {
        $dest = self::destination($buyerId, $addressId);
        if (! $dest['ok']) {
            return $dest;
        }
        $cart = self::cartLines($buyerId, $lock);
        if (! $cart['lines']) {
            return self::fail('cart_empty', 'Your cart is empty.');
        }
        $problems = $cart['problems'];
        if (! $problems) {
            $need = [];
            foreach ($cart['lines'] as $l) {
                $need[$l['product_id']] = ($need[$l['product_id']] ?? 0) + $l['quantity'];
            }
            if ($msg = StockGuard::shortage((int) $dest['hub']['id'], $need)) {
                $problems[] = ['product_id' => null, 'name' => '', 'code' => 'out_of_stock', 'message' => $msg];
            }
        }

        $discount = 0.0;
        $coupon = null;
        $couponError = null;
        if (trim($couponCode) !== '') {
            $row = Coupons::find($couponCode);
            if (! $row) {
                $couponError = ['code' => 'coupon_invalid', 'message' => 'This coupon code is not valid.'];
            } else {
                $r = Coupons::evaluate($row, $buyerId, $cart['subtotal'], $today ?? date('Y-m-d'));
                if ($r['ok']) {
                    $discount = $r['discount'];
                    $coupon = ['id' => (int) $row->id, 'code' => (string) $row->title, 'discount' => $discount, 'description' => Coupons::describe($row)];
                } else {
                    $couponError = ['code' => $r['code'], 'message' => $r['message'], 'short_by' => $r['short_by']];
                }
            }
        }

        $itemsTotal = round($cart['subtotal'] - $discount, 2);
        $quote = [
            'items' => $cart['lines'],
            'subtotal' => $cart['subtotal'],
            'coupon' => $coupon,
            'coupon_error' => $couponError,
            'discount' => $discount,
            'items_total' => $itemsTotal,
            'delivery_charge' => $dest['delivery_charge'],
            'delivery_charge_set' => $dest['delivery_charge_set'],
            'grand_total' => round($itemsTotal + $dest['delivery_charge'], 2),
            'you_save' => round(max(0, $cart['mrp_total'] - $cart['subtotal']) + $discount, 2),
            'hub' => $dest['hub'],
            'address_id' => $dest['address_id'],
            'problems' => $problems,
            'can_order' => ! $problems,
        ];

        return ['ok' => true, 'quote' => $quote];
    }

    /** Legacy order number step: ORD200009 -> ORD200010 (keeps zero padding). */
    private static function nextOrderId(?string $last): string
    {
        return $last ? preg_replace_callback('/\d+/', fn ($m) => sprintf('%0' . strlen($m[0]) . 'd', $m[0] + 1), $last) : 'ORD200000';
    }

    /**
     * @param array $in address_id, payment_type (key), delivery_type, schedule_date, schedule_time, coupon_code, expected_total
     * @return array{ok: bool, code?: string, message?: string, extra?: array, order?: array}
     */
    public static function place(int $buyerId, array $in, ?int $ts = null): array
    {
        $key = strtolower(trim((string) ($in['payment_type'] ?? 'cash')));
        if (! in_array($key, PaymentOptions::keys($buyerId), true)) {
            return self::fail('payment_not_available', 'That payment method is not available right now.');
        }
        if (! isset(self::PAY_TYPE[$key])) {
            return self::fail('payment_not_supported', 'Online and wallet payments are coming soon. Please choose cash on delivery.');
        }
        if (! isset($in['expected_total']) || ! is_numeric($in['expected_total'])) {
            return self::fail('expected_total_required', 'Please refresh your cart and try again.');
        }
        [$slot, $err] = Slots::resolve((string) ($in['delivery_type'] ?? ''), (string) ($in['schedule_date'] ?? ''), (string) ($in['schedule_time'] ?? ''), $ts);
        if ($err) {
            return self::fail($err[0], $err[1]);
        }
        $couponCode = trim((string) ($in['coupon_code'] ?? ''));
        $today = date('Y-m-d', $ts ?? time());

        return DB::transaction(function () use ($buyerId, $in, $key, $slot, $couponCode, $today) {
            // Lock the newest order row (one checkout at a time gets the next number) and this customer's cart
            // (a second tap on Place order finds the cart already empty).
            $last = DB::table('orders')->orderByDesc('id')->lockForUpdate()->value('order_id');
            $q = self::quote($buyerId, $in['address_id'] ?? 0, $couponCode, true, $today);
            if (! $q['ok']) {
                return $q;
            }
            $quote = $q['quote'];
            if ($quote['problems']) {
                return self::fail($quote['problems'][0]['code'], $quote['problems'][0]['message'], ['problems' => $quote['problems']]);
            }
            if ($couponCode !== '' && $quote['coupon_error']) {
                return self::fail($quote['coupon_error']['code'], $quote['coupon_error']['message'], ['coupon_error' => $quote['coupon_error']]);
            }
            if (abs((float) $in['expected_total'] - $quote['grand_total']) > 0.009) {
                return self::fail('price_changed', 'Prices or charges changed. Please review your order.', ['quote' => $quote]);
            }

            $orderId = self::nextOrderId($last ? (string) $last : null);
            $now = date('Y-m-d H:i:s');
            foreach ($quote['items'] as $l) {
                DB::table('orders')->insert([
                    'order_id' => $orderId, 'buyer_id' => $buyerId, 'product_id' => $l['product_id'], 'quantity' => (string) $l['quantity'],
                    'address_id' => $quote['address_id'], 'hub_id' => (int) $quote['hub']['id'],
                    'payment_type' => self::PAY_TYPE[$key], 'delivery_type' => $slot['type'], 'delivery_charge' => $quote['delivery_charge'],
                    'schedule_date' => $slot['date'], 'schedule_time' => $slot['time'],
                    'total_amount' => self::n($quote['items_total']), 'order_status' => 'Order Pending', 'reason_for_order_cancel' => '',
                    'date_added' => $now, 'updated_at' => $now,
                ]);
            }
            if ($quote['coupon']) {
                DB::table('buyer_coupon_relation')->insert([
                    'promo_id' => (string) $quote['coupon']['id'], 'user_id' => (string) $buyerId, 'order_id' => $orderId,
                    'discount' => self::n($quote['discount']), 'subtotal' => self::n($quote['subtotal']), 'total' => self::n($quote['items_total']),
                ]);
            }
            DB::table('cart')->where('buyer_id', $buyerId)->delete();
            DB::table('buyers')->where('id', $buyerId)->update(['cart_count' => 0]);

            return ['ok' => true, 'order' => [
                'order_id' => $orderId, 'status' => 'Order Pending', 'items_count' => count($quote['items']),
                'subtotal' => $quote['subtotal'], 'discount' => $quote['discount'], 'coupon' => $quote['coupon'] ? $quote['coupon']['code'] : null,
                'delivery_charge' => $quote['delivery_charge'], 'grand_total' => $quote['grand_total'],
                'payment_type' => self::PAY_TYPE[$key], 'delivery_type' => $slot['type'], 'schedule_date' => $slot['date'], 'schedule_time' => $slot['time'],
                'hub' => $quote['hub'],
            ]];
        });
    }
}
