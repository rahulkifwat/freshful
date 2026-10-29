<?php

namespace App\Services\HubConsole;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Freshful hub stock engine. Every change to a hub's live stock goes through here and is written to hub_stock_moves,
 * so each number on the console can be traced to who changed it and why.
 *
 * Root cause fixed here (21 Oct 2026): website orders try to reduce inventory with products.id, but
 * inventory.product_id holds the product CODE, so the update never matched and live stock never went down when
 * something sold (app orders never reduced it either). Stock now leaves the shelf at ONE point: when the hub accepts
 * the order in the POS. A cancelled or moved order puts back exactly what was taken.
 *
 * Stock age (22 Oct 2026, owner's rule: sell within 2 days):
 *   every batch that arrives is a "lot" with the day it arrived (hub_stock_lots).
 *   day 0 = FRESH, day 1 = DANGER (must be sold today), day 2 and older = EXPIRED (move out or write off).
 *   Sales, transfers and write-offs always use the OLDEST stock first. Stock moved to another hub keeps its age.
 *   inventory.live_stock stays the total that the rest of the system reads; inventory.fresh_stock is kept equal to
 *   today's lots so the older panels (danger = live - fresh) keep showing the right numbers.
 *   If anything outside this class changes live_stock, the lots are reconciled on the next read (extra stock is
 *   recorded as arriving today, missing stock is taken from the oldest lots).
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

    public static function lotsOn(): bool
    {
        return self::has('hub_stock_lots');
    }

    private static function today(): string { return date('Y-m-d'); }

    /* ------------------------------------------------------------------ lots */

    /** Make the lots of one product add up to $live (creating them the first time from fresh / older stock). */
    private static function syncLots(int $hubId, string $code, float $live, ?float $fresh = null): void
    {
        if (!self::lotsOn()) return;
        $lots = DB::table('hub_stock_lots')->where('hub_id', $hubId)->where('product_code', $code)->where('qty_left', '>', 0)->orderBy('received_on')->orderBy('id')->lockForUpdate()->get();
        $sum = round((float) $lots->sum('qty_left'), 3);
        $live = max(0.0, round($live, 3));
        if (abs($sum - $live) < 0.0005) return;
        $first = !DB::table('hub_stock_lots')->where('hub_id', $hubId)->where('product_code', $code)->exists();
        if ($first) {
            // Go-live: the old panels treated fresh_stock as the latest delivery and live - fresh as danger stock.
            $f = $fresh === null ? $live : max(0.0, min($live, $fresh));
            if ($live - $f > 0.0005) self::addLot($hubId, $code, $live - $f, date('Y-m-d', strtotime('-1 day')), 'opening', null);
            if ($f > 0.0005) self::addLot($hubId, $code, $f, self::today(), 'opening', null);
            return;
        }
        if ($live > $sum) self::addLot($hubId, $code, $live - $sum, self::today(), 'untracked', null);
        else self::takeLots($hubId, $code, $sum - $live);
    }

    private static function addLot(int $hubId, string $code, float $qty, string $date, string $source, ?string $ref): void
    {
        if ($qty <= 0.0005) return;
        DB::table('hub_stock_lots')->insert(['hub_id' => $hubId, 'product_code' => $code, 'received_on' => $date, 'qty_in' => round($qty, 3), 'qty_left' => round($qty, 3),
            'source' => $source, 'ref' => $ref !== null ? mb_substr($ref, 0, 40) : null, 'created_at' => now(), 'updated_at' => now()]);
    }

    /** Take $qty from the oldest lots. Returns [[date, qty], ...]. */
    private static function takeLots(int $hubId, string $code, float $qty): array
    {
        $used = [];
        $left = round($qty, 3);
        foreach (DB::table('hub_stock_lots')->where('hub_id', $hubId)->where('product_code', $code)->where('qty_left', '>', 0)->orderBy('received_on')->orderBy('id')->lockForUpdate()->get() as $l) {
            if ($left <= 0.0005) break;
            $take = min($left, (float) $l->qty_left);
            DB::table('hub_stock_lots')->where('id', $l->id)->update(['qty_left' => round((float) $l->qty_left - $take, 3), 'updated_at' => now()]);
            $used[] = [(string) $l->received_on, round($take, 3)];
            $left = round($left - $take, 3);
        }
        return self::merge($used);
    }

    private static function merge(array $lots): array
    {
        $m = [];
        foreach ($lots as [$d, $q]) $m[$d] = round(($m[$d] ?? 0) + $q, 3);
        ksort($m);
        $out = [];
        foreach ($m as $d => $q) if ($q > 0.0005) $out[] = [$d, $q];
        return $out;
    }

    /** Spread $qty over the given lot dates (oldest first); anything beyond them arrives today. */
    private static function addLots(int $hubId, string $code, float $qty, ?array $lots, string $source, ?string $ref): array
    {
        $added = [];
        $left = round($qty, 3);
        foreach (self::merge($lots ?? []) as [$d, $q]) {
            if ($left <= 0.0005) break;
            $put = min($left, $q);
            self::addLot($hubId, $code, $put, $d, $source, $ref);
            $added[] = [$d, $put];
            $left = round($left - $put, 3);
        }
        if ($left > 0.0005) { self::addLot($hubId, $code, $left, self::today(), $source, $ref); $added[] = [self::today(), $left]; }
        return self::merge($added);
    }

    private static function freshFromLots(int $hubId, string $code): float
    {
        return round((float) DB::table('hub_stock_lots')->where('hub_id', $hubId)->where('product_code', $code)->where('received_on', '>=', self::today())->sum('qty_left'), 3);
    }

    /** Reconcile every product of a hub, then return [code => [fresh, danger, expired, oldest_date]]. */
    public static function ages(int $hubId): array
    {
        if (!self::lotsOn()) return [];
        DB::transaction(function () use ($hubId) {
            $inv = DB::table('inventory')->where('hub_id', $hubId)->get(['product_id', 'live_stock', 'fresh_stock']);
            $sums = DB::table('hub_stock_lots')->where('hub_id', $hubId)->groupBy('product_code')->select('product_code', DB::raw('SUM(qty_left) AS s'), DB::raw('COUNT(*) AS n'))->get()->keyBy('product_code');
            foreach ($inv as $r) {
                $s = $sums[(string) $r->product_id] ?? null;
                if ($s && abs((float) $s->s - max(0, (float) $r->live_stock)) < 0.0005) continue;
                if (!$s && (float) $r->live_stock <= 0) continue;
                self::syncLots($hubId, (string) $r->product_id, (float) $r->live_stock, (float) $r->fresh_stock);
            }
        });
        $today = self::today(); $yest = date('Y-m-d', strtotime('-1 day'));
        $out = [];
        foreach (DB::table('hub_stock_lots')->where('hub_id', $hubId)->where('qty_left', '>', 0)->get(['product_code', 'received_on', 'qty_left']) as $l) {
            $c = (string) $l->product_code; $d = (string) $l->received_on;
            $out[$c] = $out[$c] ?? ['fresh' => 0.0, 'danger' => 0.0, 'expired' => 0.0, 'oldest' => $d];
            $k = $d >= $today ? 'fresh' : ($d >= $yest ? 'danger' : 'expired');
            $out[$c][$k] = round($out[$c][$k] + (float) $l->qty_left, 3);
            if ($d < $out[$c]['oldest']) $out[$c]['oldest'] = $d;
        }
        return $out;
    }

    /* ------------------------------------------------------------------ the one way to change stock */

    /**
     * Change one product's live stock in one hub by $delta (negative = out). Must run inside a DB transaction.
     * $lotsIn: for stock coming back (transfer received, order returned) - the batches it came from, so it keeps its age.
     * Returns [before, after, lots used or added].
     */
    public static function adjust(int $hubId, string $code, float $delta, string $type, ?string $ref, string $note, array $actor, ?float $expected = null, ?array $lotsIn = null): array
    {
        $row = DB::table('inventory')->where('product_id', $code)->where('hub_id', $hubId)->lockForUpdate()->first();
        $before = $row ? (float) $row->live_stock : 0.0;
        $after = round($before + $delta, 3);
        $lots = [];
        if (self::lotsOn()) {
            self::syncLots($hubId, $code, $before, $row ? (float) $row->fresh_stock : null);
            if ($delta < 0) $lots = self::takeLots($hubId, $code, min(-$delta, max(0, $before)));
            elseif ($delta > 0) $lots = self::addLots($hubId, $code, $delta, $type === 'inward' ? null : $lotsIn, $type, $ref);
        }
        $fresh = self::lotsOn() ? min(max(0, $after), self::freshFromLots($hubId, $code)) : ($row ? min((float) $row->fresh_stock, max(0, $after)) : max(0, $after));
        if ($row) {
            $upd = ['live_stock' => $after, 'fresh_stock' => round($fresh, 3)];
            if (self::has('inventory', 'updated_at')) $upd['updated_at'] = now();
            DB::table('inventory')->where('id', $row->id)->update($upd);
        } else {
            $ins = ['product_id' => $code, 'hub_id' => $hubId, 'stock' => 0, 'live_stock' => $after, 'fresh_stock' => round($fresh, 3), 'variance' => 0];
            if (self::has('inventory', 'created_at')) { $ins['created_at'] = now(); $ins['updated_at'] = now(); }
            DB::table('inventory')->insert($ins);
        }
        self::log($hubId, $code, $type, $before, $after, $expected, $note, $actor, $ref, $lots);
        return [$before, $after, $lots];
    }

    public static function log(int $hubId, string $code, string $type, float $before, float $after, ?float $expected, string $note, array $actor, ?string $ref = null, array $lots = []): void
    {
        if (!self::has('hub_stock_moves')) return;
        $row = [
            'hub_id' => $hubId, 'product_code' => mb_substr($code, 0, 40), 'type' => mb_substr($type, 0, 20), 'before_qty' => $before,
            'change_qty' => round($after - $before, 3), 'after_qty' => $after, 'expected_qty' => $expected,
            'note' => trim($note) !== '' ? mb_substr(trim($note), 0, 250) : null,
            'by_id' => $actor['id'] ?? null, 'by_name' => mb_substr((string) ($actor['name'] ?? ''), 0, 120), 'created_at' => now(),
        ];
        if (self::has('hub_stock_moves', 'ref')) $row['ref'] = $ref !== null ? mb_substr($ref, 0, 40) : null;
        if (self::has('hub_stock_moves', 'lots')) $row['lots'] = $lots ? json_encode($lots) : null;
        DB::table('hub_stock_moves')->insert($row);
    }

    /* ------------------------------------------------------------------ orders (called from the POS) */

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

    /** The batches an order's sale used (so a return goes back with the same age). */
    private static function saleLots(int $hubId, string $orderId, string $code): array
    {
        if (!self::has('hub_stock_moves', 'lots')) return [];
        $all = [];
        foreach (DB::table('hub_stock_moves')->where('hub_id', $hubId)->where('ref', $orderId)->where('product_code', $code)->where('type', 'sale')->pluck('lots') as $j) {
            foreach ((array) json_decode((string) $j, true) as $l) if (is_array($l) && count($l) === 2) $all[] = [(string) $l[0], (float) $l[1]];
        }
        return $all;
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

    /** Accepted order cancelled or moved away: put back exactly what was taken, with its original age. */
    public static function returnForOrder(int $hubId, string $orderId, array $actor, string $why): void
    {
        if (!self::enabled()) return;
        foreach (self::taken($hubId, $orderId) as $code => $qty) {
            self::adjust($hubId, $code, $qty, 'return', $orderId, mb_substr('Order ' . $orderId . ' ' . $why, 0, 250), $actor, null, self::saleLots($hubId, $orderId, $code));
        }
    }
}
