<?php

namespace App\Services\Stock;

use App\Services\HubConsole\HubStock;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * One sellable number per hub and product (30 Oct 2026).
 *
 *   sellable = inventory.live_stock - packs held by open online orders that the POS has not accepted yet.
 *
 * An open online order is "held" from the moment it is created until the POS accepts it (HubStock::takeForOrder then
 * takes the packs off the shelf and the hold disappears, because a sale move now exists for that order) or it is
 * cancelled. Nothing is stored twice, so nothing can drift. Outlet sales deduct at payment through HubStock and check
 * the same number.
 *
 * Safe by design: any internal error lets the order through (and is logged); STOCK_GUARD=false switches it off.
 */
class StockGuard
{
    /** open, not yet accepted by the POS, and young enough to be a real order */
    private const OPEN = ['Order Pending', 'Order Placed'];

    public static function enabled(): bool
    {
        return filter_var(env('STOCK_GUARD', true), FILTER_VALIDATE_BOOL) && class_exists(HubStock::class) && HubStock::enabled();
    }

    private static function days(): int
    {
        return max(1, (int) env('STOCK_GUARD_DAYS', 2));
    }

    /**
     * Packs held by open online orders in one hub: [product code => packs]. $lock = take row locks (inside a checkout transaction).
     */
    public static function held(int $hubId, bool $lock = false): array
    {
        $q = DB::table('orders AS o')->join('products AS p', 'p.id', '=', 'o.product_id')
            ->where('o.hub_id', $hubId)->whereIn('o.order_status', self::OPEN)
            ->where('o.date_added', '>=', date('Y-m-d H:i:s', time() - self::days() * 86400))
            ->select('o.order_id', 'p.product_id AS code', 'o.quantity');
        if ($lock) $q->lockForUpdate();
        $rows = $q->get();
        if ($rows->isEmpty()) return [];
        $taken = [];
        foreach (array_chunk($rows->pluck('order_id')->unique()->values()->all(), 500) as $chunk) {
            foreach (DB::table('hub_stock_moves')->where('hub_id', $hubId)->where('type', 'sale')->whereIn('ref', $chunk)->pluck('ref') as $ref) $taken[(string) $ref] = true;
        }
        $out = [];
        foreach ($rows as $r) {
            if (isset($taken[(string) $r->order_id]) || !$r->code) continue;
            $out[(string) $r->code] = ($out[(string) $r->code] ?? 0.0) + (float) $r->quantity;
        }
        return $out;
    }

    /** What customers may buy: live minus held, never below zero. $live = [code => live_stock]. */
    public static function net(int $hubId, $live)
    {
        try {
            if (!self::enabled()) return $live;
            $held = self::held($hubId);
            if (!$held) return $live;
            $isCol = $live instanceof \Illuminate\Support\Collection;
            $arr = $isCol ? $live->all() : (array) $live;
            foreach ($held as $code => $q) {
                if (array_key_exists($code, $arr)) $arr[$code] = max(0, (float) $arr[$code] - $q);
            }
            return $isCol ? collect($arr) : $arr;
        } catch (\Throwable $e) {
            Log::warning('StockGuard::net ' . $e->getMessage());
            return $live;
        }
    }

    /** Sellable packs of one product in one hub (outlet scan uses this). */
    public static function sellable(int $hubId, string $code, bool $lock = false): float
    {
        $row = DB::table('inventory')->where('hub_id', $hubId)->where('product_id', $code);
        if ($lock) $row->lockForUpdate();
        $live = (float) ($row->value('live_stock') ?? 0);
        if (!self::enabled()) return max(0.0, $live);
        return max(0.0, $live - (self::held($hubId, $lock)[$code] ?? 0.0));
    }

    /**
     * Checkout check. $lines: [products.id => quantity]. Call INSIDE the order transaction, before the order rows are inserted:
     * the inventory lines are locked first (sorted, so two checkouts cannot deadlock), then the held packs are read.
     * Returns null when everything can be sold, else a message for the customer.
     */
    public static function shortage(int $hubId, array $lines): ?string
    {
        try {
            if (!self::enabled() || $hubId <= 0 || !$lines) return null;
            $prods = DB::table('products')->whereIn('id', array_keys($lines))->get(['id', 'product_id', 'product_name', 'unit_quantity', 'product_unit'])->keyBy('id');
            $byCode = [];
            foreach ($lines as $pid => $qty) {
                $p = $prods[$pid] ?? null;
                if (!$p || !$p->product_id) continue;
                $byCode[(string) $p->product_id] = ['need' => ($byCode[(string) $p->product_id]['need'] ?? 0.0) + (float) $qty, 'name' => trim((string) $p->product_name), 'pack' => trim($p->unit_quantity . ' ' . $p->product_unit)];
            }
            if (!$byCode) return null;
            ksort($byCode);
            $live = [];
            foreach ($byCode as $code => $_) $live[$code] = (float) (DB::table('inventory')->where('hub_id', $hubId)->where('product_id', $code)->lockForUpdate()->value('live_stock') ?? 0);
            $held = self::held($hubId, true);
            foreach ($byCode as $code => $x) {
                $free = max(0.0, $live[$code] - ($held[$code] ?? 0.0));
                if ($x['need'] > $free + 0.0005) {
                    $n = $x['name'] !== '' ? $x['name'] : $code;
                    return $free <= 0
                        ? $n . ' is out of stock right now. Please remove it from your cart.'
                        : 'Only ' . rtrim(rtrim(number_format($free, 3, '.', ''), '0'), '.') . ' left of ' . $n . '. Please reduce the quantity.';
                }
            }
            return null;
        } catch (\Throwable $e) {
            Log::warning('StockGuard::shortage ' . $e->getMessage());
            return null;
        }
    }
}
