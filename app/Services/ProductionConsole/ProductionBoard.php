<?php

namespace App\Services\ProductionConsole;

use App\Services\CountryConsole\CountryScope;
use App\Services\CountryConsole\CountryStock;
use App\Services\CountryConsole\CountryUnits;
use App\Services\PosConsole\OrderFlow;
use Illuminate\Support\Facades\DB;

/**
 * Freshful production console (production update 1, 28 Oct 2026): the production dashboard, the production stock board
 * (the old production.php page: every product in every hub with live, fresh and danger stock, now with expired,
 * waiting inward and sales) and the products list.
 */
class ProductionBoard
{
    /** Pack units the unit rule understands (CountryUnits). Anything else (e.g. "0") is read as pieces and is flagged. */
    public const UNITS = ['g', 'gm', 'gms', 'gram', 'grams', 'grm', 'gr', 'kg', 'kgs', 'kilo', 'kilos', 'kilogram', 'kilograms', 'ml', 'mls', 'millilitre', 'milliliter', 'l', 'lt', 'ltr', 'ltrs', 'litre', 'litres', 'liter', 'liters',
        'dozen', 'dozens', 'dz', 'tray', 'trays', 'pcs', 'pc', 'piece', 'pieces', 'nos', 'no', 'pack', 'packs', 'pkt', 'packet', 'unit', 'units', 'bunch', 'box', 'qty', 'quantity'];

    private static function h(string $t, ?string $c = null): bool { return CountryScope::has($t, $c); }

    /** Packs and value sold per day (delivery day not needed here: day the order was placed), cancelled left out. */
    private static function salesByDay(array $ids, string $from, string $to): array
    {
        $out = [];
        if (!$ids) return $out;
        $prods = CountryStock::products();
        $rows = DB::table('orders AS o')->join('products AS p', 'p.id', '=', 'o.product_id')->whereIn('o.hub_id', $ids)
            ->whereBetween('o.date_added', [$from . ' 00:00:00', $to . ' 23:59:59'])->whereNotIn('o.order_status', OrderFlow::statusesFor(['cancelled']))
            ->groupBy(DB::raw('DATE(o.date_added)'), 'p.product_id')->select(DB::raw('DATE(o.date_added) AS d'), 'p.product_id AS code', DB::raw('SUM(o.quantity) AS q'))->get();
        foreach ($rows as $r) {
            $code = CountryStock::canon((string) $r->code); $d = (string) $r->d;
            $out[$d]['packs'] = ($out[$d]['packs'] ?? 0) + (float) $r->q;
            $out[$d]['value'] = round(($out[$d]['value'] ?? 0) + (float) $r->q * ($prods[$code]['price'] ?? 0), 2);
        }
        return $out;
    }

    public static function home(CountryScope $scope): array
    {
        $ids = $scope->ids(); $hubs = CountryScope::hubs(); $prods = CountryStock::products();
        $today = date('Y-m-d'); $tomorrow = date('Y-m-d', strtotime('+1 day')); $from14 = date('Y-m-d', strtotime('-13 days')); $from7 = date('Y-m-d', strtotime('-6 days'));
        ProductionDispatch::sync($ids);
        $snap = CountryStock::snapshot($ids);
        $plan = ProductionPlan::build($scope, $tomorrow);
        $planToday = ProductionPlan::build($scope, $today);
        $stock = ProductionPlan::stock($ids);

        // what is waiting at hubs (sent, not received) and stale pending
        $waitLines = 0; $waitPacks = 0.0; $waitValue = 0.0; $stale = 0.0; $staleValue = 0.0; $staleLines = 0; $oldest = null; $overLines = 0; $overValue = 0.0;
        foreach ($stock as $h => $list) foreach ($list as $code => $s) {
            if ($s['pending'] <= 0) continue;
            $price = $prods[$code]['price'] ?? 0;
            $waitLines++; $waitPacks += $s['pending']; $waitValue += $s['pending'] * $price;
            if ($s['untracked'] > 0) { $stale += $s['untracked']; $staleValue += $s['untracked'] * $price; $staleLines++; }
            if ($s['overdue'] > 0) { $overLines++; $overValue += $s['overdue'] * $price; }
            if ($s['since'] && ($oldest === null || $s['since'] < $oldest)) $oldest = $s['since'];
        }
        // stockouts: active products sold in a hub in the last 14 days that have nothing usable there now
        $hist = ProductionPlan::history($ids, 14);
        $outRows = [];
        foreach ($hist as $h => $codes) {
            if (!($hubs[$h]['active'] ?? false)) continue;
            foreach ($codes as $code => $days) {
                $p = $prods[$code] ?? null; if (!$p || !$p['active']) continue;
                $sold = 0.0; foreach ($days as $d => $q) if ($d >= date('Y-m-d', strtotime('-14 days')) && $d < $today) $sold += $q;
                if ($sold <= 0) continue;
                $s = $stock[$h][$code] ?? null;
                $usable = $s ? $s['fresh'] + $s['danger'] + $s['pending_ok'] : 0;
                if ($usable <= 0) $outRows[] = ['hub_id' => $h, 'hub' => $hubs[$h]['name'] ?? '', 'code' => $code, 'name' => $p['name'], 'pack' => $p['pack'], 'per_day' => round($sold / 14, 2)];
            }
        }
        usort($outRows, fn ($a, $b) => $b['per_day'] <=> $a['per_day']);

        // dispatched today and in 14 days
        $sentToday = ['batches' => 0, 'packs' => 0.0, 'value' => 0.0]; $sentDays = [];
        if (ProductionDispatch::on()) {
            $r = DB::table('production_batch_lines AS l')->join('production_batches AS b', 'b.id', '=', 'l.batch_id')->whereIn('l.hub_id', $ids ?: [0])->where('l.status', '!=', 'cancelled')
                ->where('l.created_at', '>=', $from14 . ' 00:00:00')->groupBy(DB::raw('DATE(l.created_at)'))
                ->select(DB::raw('DATE(l.created_at) AS d'), DB::raw('SUM(l.qty) AS q'), DB::raw('SUM(l.qty * l.price) AS v'), DB::raw('COUNT(DISTINCT l.batch_id) AS n'))->get();
            foreach ($r as $x) $sentDays[(string) $x->d] = ['packs' => (float) $x->q, 'value' => round((float) $x->v, 2), 'batches' => (int) $x->n];
            if (isset($sentDays[$today])) $sentToday = $sentDays[$today];
        }
        // received (hub receipts) per day
        $recvDays = []; $short7 = ['lines' => 0, 'value' => 0.0];
        if (self::h('hub_stock_moves') && $ids) {
            foreach (DB::table('hub_stock_moves')->whereIn('hub_id', $ids)->where('type', 'inward')->where('created_at', '>=', $from14 . ' 00:00:00')->get(['product_code', 'change_qty', 'expected_qty', 'created_at']) as $m) {
                $d = substr((string) $m->created_at, 0, 10); $price = $prods[CountryStock::canon((string) $m->product_code)]['price'] ?? 0;
                $recvDays[$d] = round(($recvDays[$d] ?? 0) + (float) $m->change_qty * $price, 2);
                if ($d >= $from7 && $m->expected_qty !== null && (float) $m->change_qty < (float) $m->expected_qty) { $short7['lines']++; $short7['value'] += ((float) $m->expected_qty - (float) $m->change_qty) * $price; }
            }
        }
        $sales = self::salesByDay($ids, $from14, $today);
        $waste = []; $w7 = CountryStock::wastageTotals($ids, $from7, $today);
        if (self::h('hub_stock_moves') && $ids) {
            foreach (DB::table('hub_stock_moves')->whereIn('hub_id', $ids)->where('type', 'wastage')->where('created_at', '>=', $from14 . ' 00:00:00')->get(['product_code', 'change_qty', 'created_at']) as $m) {
                $d = substr((string) $m->created_at, 0, 10);
                $waste[$d] = round(($waste[$d] ?? 0) - (float) $m->change_qty * ($prods[CountryStock::canon((string) $m->product_code)]['price'] ?? 0), 2);
            }
        }
        $days = [];
        for ($i = 13; $i >= 0; $i--) {
            $d = date('Y-m-d', strtotime('-' . $i . ' days'));
            $days[] = ['date' => $d, 'sent' => $sentDays[$d]['value'] ?? 0, 'received' => $recvDays[$d] ?? 0, 'sold' => $sales[$d]['value'] ?? 0, 'sold_packs' => $sales[$d]['packs'] ?? 0, 'wasted' => $waste[$d] ?? 0];
        }
        $sold7 = 0.0; foreach ($days as $d) if ($d['date'] >= $from7) $sold7 += $d['sold'];

        // tomorrow: products to make, top lines
        $top = array_slice(array_values(array_filter($plan['rows'], fn ($r) => $r['to_send'] > 0)), 0, 10);
        $top = array_map(fn ($r) => ['code' => $r['code'], 'name' => $r['name'], 'pack' => $r['pack'], 'category' => $r['category'], 'kind' => $r['kind'], 'per' => $r['per'], 'uom' => $r['uom'],
            'planned' => $r['planned'], 'sent' => $r['sent'], 'to_send' => $r['to_send'], 'suggested' => $r['suggested'], 'demand' => $r['demand'], 'usable' => $r['usable'], 'hubs' => count(array_filter($r['hubs'], fn ($c) => $c['planned'] > 0))], $top);
        $byCat = [];
        foreach ($plan['rows'] as $r) {
            if ($r['planned'] <= 0) continue;
            $k = $r['kind'] === 'count' ? 'pcs' : ($r['kind'] === 'volume' ? 'L' : 'kg');
            $byCat[$r['category']] = $byCat[$r['category']] ?? ['name' => $r['category'], 'kg' => 0.0, 'pcs' => 0.0, 'L' => 0.0, 'value' => 0.0];
            $byCat[$r['category']][$k] = round($byCat[$r['category']][$k] + CountryUnits::amount($r['planned'], $r), 3);
            $byCat[$r['category']]['value'] = round($byCat[$r['category']]['value'] + $r['planned'] * $r['price'], 2);
        }
        $byCat = array_values($byCat); usort($byCat, fn ($a, $b) => $b['value'] <=> $a['value']);

        // hubs: danger / expired / waiting
        $hubRows = [];
        foreach ($ids as $h) {
            $b = $snap['by_hub'][$h] ?? ['value' => 0, 'danger_value' => 0, 'expired_value' => 0, 'out' => 0];
            $pend = 0.0; foreach ($stock[$h] ?? [] as $code => $s) $pend += $s['pending'] * ($prods[$code]['price'] ?? 0);
            $hubRows[] = ['hub_id' => $h, 'hub' => $hubs[$h]['name'] ?? '', 'city' => $hubs[$h]['city'] ?? '', 'active' => $hubs[$h]['active'] ?? false, 'value' => $b['value'], 'danger' => $b['danger_value'], 'expired' => $b['expired_value'], 'waiting' => round($pend, 2), 'out' => $b['out']];
        }
        usort($hubRows, fn ($a, $b) => [$b['expired'] + $b['danger'], $b['waiting']] <=> [$a['expired'] + $a['danger'], $a['waiting']]);

        $recent = ProductionDispatch::on() ? array_slice(ProductionDispatch::list($scope, $from14, $today)['rows'], 0, 6) : [];
        $attn = [];
        if ($snap['expired_value'] > 0) $attn[] = ['tone' => 'bad', 'go' => 'danger', 'text' => 'Expired stock in the hubs worth ₹' . number_format($snap['expired_value']) . ' (' . $snap['expired_items'] . ' lines). Write it off or move it.'];
        if ($overLines) $attn[] = ['tone' => 'bad', 'go' => 'pr-batches', 'text' => $overLines . ' dispatched products have not been received after ' . (int) config('production_console.forecast.pending_max_days', 2) . ' days (₹' . number_format($overValue) . '). Ask the hubs to receive them, or cancel the lines that never left.'];
        if ($staleLines) $attn[] = ['tone' => 'warn', 'go' => 'pr-batches', 'text' => $staleLines . ' lines are waiting at hubs that no production dispatch explains (₹' . number_format($staleValue) . ', old panels or test data). They are not counted as stock; review and clear them in Dispatches to hubs.'];
        if ($outRows) $attn[] = ['tone' => 'bad', 'go' => 'pr-plan', 'text' => count($outRows) . ' regular sellers have no usable stock in their hub right now (e.g. ' . $outRows[0]['name'] . ' at ' . $outRows[0]['hub'] . ').'];
        if ($short7['lines']) $attn[] = ['tone' => 'warn', 'go' => 'pr-receipts', 'text' => $short7['lines'] . ' receipts in 7 days came in short of what was sent (₹' . number_format($short7['value']) . ').'];
        if ($plan['total']['to_send'] > 0) $attn[] = ['tone' => 'info', 'go' => 'pr-plan', 'text' => 'Tomorrow: ' . CountryScope::q($plan['total']['to_send']) . ' packs still to send across ' . $plan['total']['products'] . ' products.'];
        $badUnit = [];
        foreach (DB::table('products')->where('status', 'active')->get(['product_name', 'product_unit', 'unit_quantity']) as $bu) {
            if (!in_array(strtolower(preg_replace('/[^a-z]/i', '', (string) $bu->product_unit)), self::UNITS, true)) $badUnit[] = trim($bu->product_name) . ' (' . trim($bu->unit_quantity . ' ' . $bu->product_unit) . ')';
        }
        if ($badUnit) $attn[] = ['tone' => 'warn', 'go' => 'pr-products', 'text' => count($badUnit) . ' product' . (count($badUnit) === 1 ? ' has' : 's have') . ' a pack unit the system does not recognise, e.g. ' . $badUnit[0] . '. It is counted as pieces until the admin corrects it.'];
        foreach ($prods as $p) if ($p['active'] && $p['suspect']) { $attn[] = ['tone' => 'warn', 'go' => 'pr-products', 'text' => 'Pack size looks wrong for ' . $p['name'] . ' (' . $p['pack'] . '). Kg figures for it will be off until it is corrected.']; break; }

        return ['ok' => true, 'kpi' => [
            'to_make_tomorrow' => $plan['total']['planned'], 'to_send_tomorrow' => $plan['total']['to_send'], 'products_tomorrow' => $plan['total']['products'], 'value_tomorrow' => $plan['total']['value'],
            'to_send_today' => $planToday['total']['to_send'], 'demand_today' => $planToday['total']['demand'],
            'sent_today' => $sentToday, 'waiting_lines' => $waitLines, 'waiting_packs' => round($waitPacks, 2), 'waiting_value' => round($waitValue, 2), 'oldest_waiting' => $oldest,
            'stale_lines' => $staleLines, 'stale_value' => round($staleValue, 2), 'overdue_lines' => $overLines, 'overdue_value' => round($overValue, 2),
            'stock_value' => $snap['value'], 'fresh_value' => $snap['fresh_value'], 'danger_value' => $snap['danger_value'], 'expired_value' => $snap['expired_value'],
            'stockouts' => count($outRows), 'short7' => ['lines' => $short7['lines'], 'value' => round($short7['value'], 2)],
            'waste7' => $w7['value'], 'waste7_pct' => $sold7 > 0 ? round($w7['value'] * 100 / $sold7, 2) : null, 'sold7' => round($sold7, 2)],
            'days' => $days, 'top' => $top, 'by_category' => $byCat, 'hubs' => $hubRows, 'stockouts' => array_slice($outRows, 0, 30), 'recent' => $recent, 'attention' => $attn,
            'tomorrow' => $tomorrow, 'wastage_limit' => (float) config('country_console.wastage_limit_pct', 3), 'generated_at' => time()];
    }

    /** The old production page: product x hub with live, fresh, danger (+ expired, waiting, sales). */
    public static function board(CountryScope $scope, string $q = '', string $category = '', string $show = ''): array
    {
        $ids = $scope->ids(); $hubs = CountryScope::hubs(); $prods = CountryStock::products();
        $stock = ProductionPlan::stock($ids);
        $hist = ProductionPlan::history($ids, 7);
        $qq = mb_strtolower(trim($q));
        $rows = []; $cats = [];
        $d7 = []; for ($i = 1; $i <= 7; $i++) $d7[] = date('Y-m-d', strtotime('-' . $i . ' days'));
        $today = date('Y-m-d');
        foreach ($ids as $h) {
            foreach ($stock[$h] ?? [] as $code => $s) {
                $p = $prods[$code] ?? null;
                $name = $p['name'] ?? 'Unknown product'; $cat = $p['category'] ?? 'Unknown';
                $cats[$cat] = true;
                if ($category !== '' && strcasecmp($cat, $category) !== 0) continue;
                if ($qq !== '' && !str_contains(mb_strtolower($name . ' ' . $code . ' ' . ($hubs[$h]['name'] ?? '')), $qq)) continue;
                $sold7 = 0.0; foreach ($d7 as $d) $sold7 += $hist[$h][$code][$d] ?? 0;
                $soldToday = $hist[$h][$code][$today] ?? 0;
                $perDay = round($sold7 / 7, 2);
                $usable = $s['fresh'] + $s['danger'];
                $cover = $perDay > 0 ? round($usable / $perDay, 1) : null;
                if ($show === 'stock' && $s['live'] <= 0 && $s['pending'] <= 0) continue;
                if ($show === 'danger' && $s['danger'] <= 0 && $s['expired'] <= 0) continue;
                if ($show === 'out' && !($usable <= 0 && $perDay > 0)) continue;
                if ($show === 'waiting' && $s['pending'] <= 0) continue;
                $rows[] = ['hub_id' => $h, 'hub' => $hubs[$h]['name'] ?? '', 'city' => $hubs[$h]['city'] ?? '', 'hub_active' => $hubs[$h]['active'] ?? false,
                    'id' => $p['id'] ?? 0, 'code' => $code, 'name' => $name, 'category' => $cat, 'pack' => $p['pack'] ?? '', 'known' => (bool) $p, 'active' => $p['active'] ?? false,
                    'kind' => $p['kind'] ?? 'count', 'per' => $p['per'] ?? 1, 'uom' => $p['uom'] ?? 'pcs', 'price' => $p['price'] ?? 0,
                    'live' => $s['live'], 'fresh' => $s['fresh'], 'danger' => $s['danger'], 'expired' => $s['expired'], 'pending' => $s['pending'], 'stale' => $s['stale'],
                    'sold_today' => $soldToday, 'per_day' => $perDay, 'cover' => $cover, 'value' => round($s['live'] * ($p['price'] ?? 0), 2)];
            }
        }
        usort($rows, fn ($a, $b) => [$a['name'], $a['hub']] <=> [$b['name'], $b['hub']]);
        $cl = array_keys($cats); sort($cl, SORT_NATURAL | SORT_FLAG_CASE);
        $t = ['rows' => count($rows), 'value' => round(array_sum(array_column($rows, 'value')), 2)];
        return ['ok' => true, 'rows' => array_slice($rows, 0, 6000), 'total' => $t, 'categories' => $cl, 'truncated' => count($rows) > 6000];
    }

    /** Products for production: pack, unit, price, cost, margin, stock in all hubs, sales, wastage, on / off. */
    public static function products(CountryScope $scope): array
    {
        $ids = $scope->ids(); $prods = CountryStock::products();
        $stock = ProductionPlan::stock($ids);
        $hist = ProductionPlan::history($ids, 30);
        $from30 = date('Y-m-d', strtotime('-30 days'));
        $waste = [];
        if (self::h('hub_stock_moves') && $ids) {
            foreach (DB::table('hub_stock_moves')->whereIn('hub_id', $ids)->where('type', 'wastage')->where('created_at', '>=', $from30 . ' 00:00:00')->groupBy('product_code')->select('product_code', DB::raw('SUM(change_qty) AS q'))->get() as $r)
                $waste[CountryStock::canon((string) $r->product_code)] = -(float) $r->q;
        }
        $extra = [];
        $cols = ['id', 'product_id', 'MRP', 'cost_price', 'gross_quantity', 'product_image', 'product_unit', 'date_added'];
        $sel = array_values(array_filter($cols, fn ($c) => self::h('products', $c)));
        foreach (DB::table('products')->get($sel) as $r) $extra[(string) $r->product_id] = $r;
        $rows = [];
        foreach ($prods as $code => $p) {
            $live = 0.0; $danger = 0.0; $expired = 0.0; $pend = 0.0; $hubsWith = 0;
            foreach ($ids as $h) { $s = $stock[$h][$code] ?? null; if (!$s) continue; $live += $s['live']; $danger += $s['danger']; $expired += $s['expired']; $pend += $s['pending']; if ($s['live'] > 0) $hubsWith++; }
            $sold = 0.0; foreach ($ids as $h) foreach ($hist[$h][$code] ?? [] as $d => $q) if ($d >= $from30) $sold += $q;
            $x = $extra[$code] ?? null;
            $cost = $x && isset($x->cost_price) && is_numeric($x->cost_price) ? (float) $x->cost_price : null;
            $mrp = $x && isset($x->MRP) && is_numeric($x->MRP) ? (float) $x->MRP : null;
            $w = $waste[$code] ?? 0.0;
            $uRaw = strtolower(preg_replace('/[^a-z]/i', '', (string) ($x->product_unit ?? '')));
            $unitBad = !in_array($uRaw, self::UNITS, true);
            $rows[] = ['id' => $p['id'], 'code' => $code, 'name' => $p['name'], 'category' => $p['category'], 'pack' => $p['pack'], 'kind' => $p['kind'], 'per' => $p['per'], 'uom' => $p['uom'], 'suspect' => $p['suspect'] || $unitBad, 'unit_bad' => $unitBad,
                'price' => $p['price'], 'mrp' => $mrp, 'cost' => $cost, 'margin' => $cost !== null && $cost > 0 && $p['price'] > 0 ? round(($p['price'] - $cost) * 100 / $p['price'], 1) : null,
                'gross' => $x->gross_quantity ?? '', 'image' => $x && !empty($x->product_image) ? url('uploads/images/products/' . $x->product_image) : null,
                'active' => $p['active'], 'live' => round($live, 2), 'danger' => round($danger, 2), 'expired' => round($expired, 2), 'pending' => round($pend, 2), 'hubs_with' => $hubsWith,
                'sold30' => round($sold, 2), 'per_day' => round($sold / 30, 2), 'waste30' => round($w, 2), 'waste_pct' => ($sold + $w) > 0 ? round($w * 100 / ($sold + $w), 1) : null];
        }
        usort($rows, fn ($a, $b) => [$b['active'], $b['sold30'], $a['name']] <=> [$a['active'], $a['sold30'], $b['name']]);
        $t = ['products' => count($rows), 'on' => count(array_filter($rows, fn ($r) => $r['active'])), 'suspect' => count(array_filter($rows, fn ($r) => $r['suspect'])), 'no_cost' => count(array_filter($rows, fn ($r) => $r['active'] && $r['cost'] === null))];
        $cats = array_values(array_unique(array_column($rows, 'category'))); sort($cats, SORT_NATURAL | SORT_FLAG_CASE);
        return ['ok' => true, 'rows' => $rows, 'total' => $t, 'categories' => $cats];
    }

    public static function toggle(int $id, array $actor): array
    {
        if (class_exists(\App\Services\AdminConsole\AdminCatalog::class)) return \App\Services\AdminConsole\AdminCatalog::toggle($id, $actor);
        $old = DB::table('products')->where('id', $id)->first();
        if (!$old) return ['ok' => false, 'message' => 'Product not found.'];
        $on = CountryScope::isActive($old->status);
        DB::table('products')->where('id', $id)->update(['status' => $on ? 'disabled' : 'active']);
        ProductionLog::write($actor, $on ? 'switch off' : 'switch on', 'products', $id, ($on ? 'Switched off ' : 'Switched on ') . $old->product_name);
        return ['ok' => true, 'on' => !$on, 'message' => $old->product_name . ' is now ' . ($on ? 'off.' : 'on sale.')];
    }
}
