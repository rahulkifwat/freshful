<?php

namespace App\Services\AdminConsole;

use App\Services\CountryConsole\CountryAnalytics;
use App\Services\CountryConsole\CountryScope;
use App\Services\CountryConsole\CountryStock;
use App\Services\CountryConsole\CountryUnits;
use App\Services\CountryConsole\SimpleXlsx;
use Illuminate\Support\Facades\DB;

/**
 * Freshful admin console (admin update 1, 22 Oct 2026): "Inventory report detail" of the old admin, rebuilt.
 * The old page called stock functions that are not in the old code, so it could not run. Same columns, from real data:
 *   per product, for the hubs and dates chosen: cost, MRP, sold amount, live stock now, stock received (inward + from
 *   other hubs), sold, wastage, transfer in, transfer out, plus closing value.
 * Stock moves are only recorded since the hub console went live; earlier days show sales only.
 */
class AdminReports
{
    private static function h(string $t, ?string $c = null): bool { return CountryScope::has($t, $c); }

    public static function inventory(CountryScope $scope, string $from, string $to): array
    {
        $ids = $scope->ids();
        $prods = CountryStock::products();
        $costs = self::h('products', 'cost_price') ? DB::table('products')->pluck('cost_price', 'product_id')->all() : [];
        $mrp = DB::table('products')->pluck('MRP', 'product_id')->all();
        $rows = [];
        foreach ($prods as $code => $p) $rows[$code] = ['code' => $code, 'name' => $p['name'], 'category' => $p['category'], 'pack' => $p['pack'], 'kind' => $p['kind'], 'per' => $p['per'], 'uom' => $p['uom'],
            'cost' => (float) ($costs[$code] ?? 0), 'mrp' => (float) ($mrp[$code] ?? 0), 'price' => $p['price'], 'active' => $p['active'],
            'sold_amt' => 0.0, 'sold' => 0.0, 'orders' => 0, 'live' => 0.0, 'received' => 0.0, 'wastage' => 0.0, 'tin' => 0.0, 'tout' => 0.0, 'returned' => 0.0, 'counted' => 0.0];
        $byId = []; foreach ($prods as $code => $p) $byId[$p['id']] = $code;
        foreach (CountryAnalytics::productLines($ids, $from . ' 00:00:00', $to . ' 23:59:59') as $pid => $l) {
            $code = $byId[$pid] ?? null; if (!$code) continue;
            $rows[$code]['sold'] += $l['qty']; $rows[$code]['sold_amt'] += $l['value']; $rows[$code]['orders'] += $l['orders'];
        }
        if (self::h('inventory')) foreach (array_chunk($ids, 500) as $c) foreach (DB::table('inventory')->whereIn('hub_id', $c)->select('product_id', DB::raw('SUM(live_stock) AS l'))->groupBy('product_id')->get() as $r) {
            $code = CountryStock::canon((string) $r->product_id); if (isset($rows[$code])) $rows[$code]['live'] += (float) $r->l;
        }
        $movesFrom = null;
        if (self::h('hub_stock_moves')) {
            $movesFrom = DB::table('hub_stock_moves')->min('created_at');
            foreach (array_chunk($ids, 500) as $c) foreach (DB::table('hub_stock_moves')->whereIn('hub_id', $c)->whereBetween('created_at', [$from . ' 00:00:00', $to . ' 23:59:59'])->select('product_code', 'type', DB::raw('SUM(change_qty) AS q'))->groupBy('product_code', 'type')->get() as $m) {
                $code = CountryStock::canon((string) $m->product_code); if (!isset($rows[$code])) continue;
                $q = (float) $m->q;
                match ((string) $m->type) {
                    'inward' => $rows[$code]['received'] += $q, 'wastage' => $rows[$code]['wastage'] += -$q, 'transfer_in' => $rows[$code]['tin'] += $q,
                    'transfer_out' => $rows[$code]['tout'] += -$q, 'transfer_back' => $rows[$code]['tin'] += $q, 'return' => $rows[$code]['returned'] += $q, 'count' => $rows[$code]['counted'] += $q, default => null,
                };
            }
        }
        $out = [];
        foreach ($rows as $r) {
            if (!$r['active'] && !$r['live'] && !$r['sold'] && !$r['received'] && !$r['wastage'] && !$r['tin'] && !$r['tout']) continue;
            foreach (['sold_amt', 'sold', 'live', 'received', 'wastage', 'tin', 'tout', 'returned', 'counted'] as $k) $r[$k] = round($r[$k], 3);
            $r['live_value'] = round($r['live'] * $r['price'], 2);
            $r['waste_value'] = round($r['wastage'] * $r['price'], 2);
            $out[] = $r;
        }
        usort($out, fn ($a, $b) => [$a['category'], $a['name']] <=> [$b['category'], $b['name']]);
        return ['ok' => true, 'rows' => $out, 'moves_from' => $movesFrom ? substr((string) $movesFrom, 0, 10) : null, 'hubs' => count($ids)];
    }

    public static function inventoryXlsx(array $d, string $title, string $units = 'w'): string
    {
        $x = new SimpleXlsx();
        $x->row([$title], 'title'); $x->merge(0, 1, 16, 1);
        $u = $units === 'p' ? 'packs' : 'kg / pcs';
        $x->row(['#', 'Product ID', 'Product name', 'Category', 'Pack', 'Cost', 'MRP', 'Price', 'Sold amount', 'Orders', 'Sold (' . $u . ')', 'Received (' . $u . ')', 'Transfer in', 'Transfer out', 'Wastage', 'Live now', 'Live value'], 'head');
        $conv = function ($q, $r) use ($units) { if ($units === 'p' || !$r['per']) return $q; return CountryUnits::amount((float) $q, ['kind' => $r['kind'], 'per' => $r['per']]); };
        $i = 0;
        foreach ($d['rows'] as $r) $x->row([++$i, $r['code'], $r['name'], $r['category'], $r['pack'], $r['cost'], $r['mrp'], $r['price'], $r['sold_amt'], $r['orders'], $conv($r['sold'], $r), $conv($r['received'], $r), $conv($r['tin'], $r), $conv($r['tout'], $r), $conv($r['wastage'], $r), $conv($r['live'], $r), $r['live_value']], '', [15 => $r['live'] <= 0 ? 'zero' : '']);
        $x->widths([5, 12, 32, 18, 12, 8, 8, 8, 12, 8, 11, 12, 11, 11, 10, 10, 12]);
        $x->freeze(3, 2);
        return $x->build('Inventory report');
    }
}
