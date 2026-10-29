<?php

namespace App\Services\HubConsole;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Freshful hub stock engine (22 Oct 2026). Every change to a hub's live stock goes through here and is written to
 * hub_stock_moves, so each number on the console can be traced to who changed it and why.
 *
 * Root cause fixed here: website orders try to reduce inventory with products.id, but inventory.product_id holds the
 * product CODE, so the update never matched and live stock never went down when something sold (app orders never
 * reduced it either). Stock now leaves the shelf at ONE point: when the hub accepts the order in the POS (it is cut
 * and packed). If an accepted order is cancelled or moved to another hub, the same quantity goes back. Only what was
 * actually taken is ever returned, so old orders accepted before this change are never "returned" by mistake.
 *
 * live_stock  = what is physically in the hub.  fresh_stock = the part received most recently.
 * older stock = live - fresh (the old panel's "danger stock"); sales and transfers use older stock first (FIFO).
 * Only a delivery received through Receive stock counts as fresh (same as the old Excel upload: fresh = what arrived).
 */
class HubStock
{
    private static array $c = [];

    public static function has(string $t, ?string $col = null): bool
    {
        $k = $t . '.' . $col;
        if (!array_key_exists($k, self::$c)) {
            try { self::$c[$k] = $col === null ? Schema::hasTable($t) : Schema::hasColumn($t, $col); } catch (\Throwable $e) { self::$c[$k] = false; }
        }
        return self::$c[$k];
    }

    public static function enabled(): bool
    {
        return (bool) config('hub_console.deduct_on_accept', true) && self::has('hub_stock_moves') && self::has('hub_stock_moves', 'ref');
    }

    /**
     * Change one product's live stock in one hub by $delta (negative = out). Must run inside a DB transaction.
     * Returns [before, after].
     */
    public static function adjust(int $hubId, string $code, float $delta, string $type, ?string $ref, string $note, array $actor, ?float $expected = null): array
    {
        $row = DB::table('inventory')->where('product_id', $code)->where('hub_id', $hubId)->lockForUpdate()->first();
        $before = $row ? (float) $row->live_stock : 0.0;
        $after = round($before + $delta, 3);
        if ($row) {
            $fresh = (float) $row->fresh_stock;
            // Going out: older stock is used first, so "fresh" only drops when live falls below it.
            // Coming in from a transfer / return / count: that stock is not a new delivery, so it does not count as fresh.
            $fresh = min($fresh, max(0, $after));
            $upd = ['live_stock' => $after, 'fresh_stock' => round($fresh, 3)];
            if (self::has('inventory', 'updated_at')) $upd['updated_at'] = now();
            DB::table('inventory')->where('id', $row->id)->update($upd);
        } else {
            $ins = ['product_id' => $code, 'hub_id' => $hubId, 'stock' => 0, 'live_stock' => $after, 'fresh_stock' => max(0, $after), 'variance' => 0];
            if (self::has('inventory', 'created_at')) { $ins['created_at'] = now(); $ins['updated_at'] = now(); }
            DB::table('inventory')->insert($ins);
        }
        self::log($hubId, $code, $type, $before, $after, $expected, $note, $actor, $ref);
        return [$before, $after];
    }

    public static function log(int $hubId, string $code, string $type, float $before, float $after, ?float $expected, string $note, array $actor, ?string $ref = null): void
    {
        if (!self::has('hub_stock_moves')) return;
        $row = [
            'hub_id' => $hubId, 'product_code' => mb_substr($code, 0, 40), 'type' => mb_substr($type, 0, 20), 'before_qty' => $before,
            'change_qty' => round($after - $before, 3), 'after_qty' => $after, 'expected_qty' => $expected,
            'note' => trim($note) !== '' ? mb_substr(trim($note), 0, 250) : null,
            'by_id' => $actor['id'] ?? null, 'by_name' => mb_substr((string) ($actor['name'] ?? ''), 0, 120), 'created_at' => now(),
        ];
        if (self::has('hub_stock_moves', 'ref')) $row['ref'] = $ref !== null ? mb_substr($ref, 0, 40) : null;
        DB::table('hub_stock_moves')->insert($row);
    }

    /** product codes and quantities of one order in one hub: [code => qty] */
    private static function orderLines(int $hubId, string $orderId): array
    {
        $out = [];
        $rows = DB::table('orders AS o')->join('products AS p', 'p.id', '=', 'o.product_id')
            ->where('o.order_id', $orderId)->where('o.hub_id', $hubId)->get(['p.product_id AS code', 'o.quantity']);
        foreach ($rows as $r) {
            if (!$r->code) continue;
            $out[(string) $r->code] = ($out[(string) $r->code] ?? 0) + (float) $r->quantity;
        }
        return $out;
    }

    /** What is still taken out of a hub for one order: [code => qty taken] */
    private static function taken(int $hubId, string $orderId): array
    {
        $out = [];
        $rows = DB::table('hub_stock_moves')->where('hub_id', $hubId)->where('ref', $orderId)->whereIn('type', ['sale', 'return'])
            ->groupBy('product_code')->select('product_code', DB::raw('SUM(change_qty) AS net'))->get();
        foreach ($rows as $r) if ((float) $r->net < -0.0001) $out[(string) $r->product_code] = -(float) $r->net;
        return $out;
    }

    /** Order accepted in the POS: take its items off the shelf (once). */
    public static function takeForOrder(int $hubId, string $orderId, array $actor): void
    {
        if (!self::enabled()) return;
        if (self::taken($hubId, $orderId)) return;
        foreach (self::orderLines($hubId, $orderId) as $code => $qty) {
            if ($qty > 0) self::adjust($hubId, $code, -$qty, 'sale', $orderId, 'Order ' . $orderId . ' accepted', $actor);
        }
    }

    /** Accepted order cancelled or moved away: put back exactly what was taken. */
    public static function returnForOrder(int $hubId, string $orderId, array $actor, string $why): void
    {
        if (!self::enabled()) return;
        foreach (self::taken($hubId, $orderId) as $code => $qty) {
            self::adjust($hubId, $code, $qty, 'return', $orderId, mb_substr('Order ' . $orderId . ' ' . $why, 0, 250), $actor);
        }
    }
}
