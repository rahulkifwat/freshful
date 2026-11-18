<?php

namespace App\Services\BuyerApp\Orders;

use Illuminate\Support\Facades\DB;

/**
 * "Exact weight": what the hub's scale found for the weighed items of an order, from the POS table pos_order_weights
 * (written when the cashier types the real weight; read only here).
 * The POS rule: adjustment = (actual g - ordered g) x pack price / pack g. Positive = the customer pays a little more, negative = a little less.
 * The order's stored total is NOT changed by weighing (the POS keeps the difference in amount_to_collect / amount_to_return),
 * so the bill after weighing = total (items after coupon + delivery) + net adjustment.
 */
final class OrderWeights
{
    /** @return array{weighed: bool, items: array, net_adjustment: float, bill_before: float, bill_after: float} */
    public static function forOrder(int $buyerId, string $orderId): array
    {
        $rows = OrderHistory::rows($buyerId, $orderId);
        if (! $rows) {
            return ['weighed' => false, 'items' => [], 'net_adjustment' => 0.0, 'bill_before' => 0.0, 'bill_after' => 0.0];
        }
        $before = OrderHistory::total($rows[0]);

        $w = DB::table('pos_order_weights as w')->leftJoin('products as p', 'p.id', '=', 'w.product_id')
            ->where('w.order_id', $orderId)->orderBy('w.id')
            ->get(['w.id', 'w.product_id', 'w.expected_g', 'w.actual_g', 'w.pack_g', 'w.pack_price', 'w.amount_diff', 'p.product_name', 'p.product_unit', 'p.unit_quantity']);

        // the newest weighing of a line wins when the cashier corrected it
        $byProduct = [];
        foreach ($w as $r) {
            $byProduct[(int) $r->product_id] = $r;
        }
        $items = [];
        $net = 0.0;
        foreach ($byProduct as $pid => $r) {
            $exp = (float) $r->expected_g;
            $act = (float) $r->actual_g;
            if ($exp <= 0 || $act <= 0) {
                continue;
            }
            $diff = round((float) $r->amount_diff, 2);
            $net += $diff;
            $items[] = [
                'product_id' => $pid, 'name' => trim((string) $r->product_name) !== '' ? trim((string) $r->product_name) : 'Item',
                'pack' => trim(($r->unit_quantity ?? '') . ' ' . ($r->product_unit ?? '')),
                'ordered_g' => round($exp, 1), 'packed_g' => round($act, 1), 'difference_g' => round($act - $exp, 1), 'adjustment' => $diff,
            ];
        }
        $net = round($net, 2);

        return ['weighed' => (bool) $items, 'items' => $items, 'net_adjustment' => $net, 'bill_before' => $before, 'bill_after' => round($before + $net, 2)];
    }
}
