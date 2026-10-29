<?php

namespace App\Services\CountryConsole;

use App\Services\HubConsole\HubCashService;
use App\Services\HubConsole\HubStock;
use App\Services\PosConsole\OrderFlow;
use Illuminate\Support\Facades\DB;

/**
 * Freshful country console (22 Oct 2026): pan-India numbers.
 * Order value = total_amount + delivery_charge + weight adjustment (amount_to_collect - amount_to_return), per order,
 * exactly as the hub console counts it, so a hub's numbers here match its own console.
 * Product and category sales are quantity x the product's list price (orders store no line price).
 */
class CountryAnalytics
{
    private static function h(string $t, ?string $c = null): bool { return CountryScope::has($t, $c); }

    /** One row per order placed between two moments, in the given hubs. */
    public static function orderRows(array $ids, string $fromTs, string $toTs): array
    {
        if (!$ids) return [];
        $sel = ['order_id', DB::raw('MAX(hub_id) AS hub'), DB::raw('MAX(order_status) AS st'), DB::raw('MAX(date_added) AS at'), DB::raw('MAX(total_amount) AS t'),
            DB::raw('MAX(delivery_charge) AS d'), DB::raw('MAX(delivery_type) AS type'), DB::raw('MAX(payment_type) AS pay'), DB::raw('MAX(buyer_id) AS buyer'),
            DB::raw('MAX(schedule_date) AS sd'), DB::raw('MAX(schedule_time) AS stime')];
        foreach (['amount_to_collect' => 'c', 'amount_to_return' => 'r', 'delivery_complete_time' => 'dt', 'reason_for_order_cancel' => 'why'] as $col => $a) {
            $sel[] = self::h('orders', $col) ? DB::raw("MAX($col) AS $a") : DB::raw("NULL AS $a");
        }
        $rows = [];
        foreach (array_chunk($ids, 500) as $chunk) {
            foreach (DB::table('orders')->whereIn('hub_id', $chunk)->where('date_added', '>=', $fromTs)->where('date_added', '<=', $toTs)->groupBy('order_id')->select($sel)->get() as $r) {
                $at = CountryScope::ts($r->at); if (!$at) continue;
                $stage = OrderFlow::stage($r->st);
                $rows[] = ['id' => (string) $r->order_id, 'hub' => (int) $r->hub, 'stage' => $stage, 'status' => (string) $r->st, 'at' => $at,
                    'value' => round((float) $r->t + (float) $r->d + (float) $r->c - (float) $r->r, 2), 'type' => strcasecmp((string) $r->type, 'Scheduled') === 0 ? 'Scheduled' : 'Express',
                    'pay' => trim((string) $r->pay) !== '' ? ucfirst(strtolower(trim((string) $r->pay))) : 'Cash', 'buyer' => (int) $r->buyer,
                    'delivered_at' => CountryScope::ts($r->dt), 'due' => OrderFlow::dueAt($r->type, $r->at, $r->sd, $r->stime), 'why' => trim((string) ($r->why ?? ''))];
            }
        }
        return $rows;
    }

    private static function pct(float $a, float $b): ?float
    {
        if ($b <= 0) return $a > 0 ? null : 0.0;
        return round(($a - $b) * 100 / $b, 1);
    }

    /** Totals of a set of order rows. */
    private static function totals(array $rows): array
    {
        $t = ['orders' => 0, 'sales' => 0.0, 'cancelled' => 0, 'all' => 0, 'delivered' => 0, 'ontime' => 0, 'timed' => 0, 'mins' => 0.0, 'mins_n' => 0, 'express' => 0, 'scheduled' => 0, 'buyers' => []];
        foreach ($rows as $r) {
            $t['all']++;
            if ($r['stage'] === 'cancelled') { $t['cancelled']++; continue; }
            $t['orders']++; $t['sales'] += $r['value'];
            $r['type'] === 'Scheduled' ? $t['scheduled']++ : $t['express']++;
            if ($r['buyer']) $t['buyers'][$r['buyer']] = true;
            if (in_array($r['stage'], ['delivered', 'unsettled'], true)) {
                $t['delivered']++;
                if ($r['delivered_at'] && $r['due']) { $t['timed']++; if ($r['delivered_at'] <= $r['due']) $t['ontime']++; }
                if ($r['type'] === 'Express' && $r['delivered_at']) {
                    $m = ($r['delivered_at'] - $r['at']) / 60;
                    if ($m > 0 && $m < 600) { $t['mins'] += $m; $t['mins_n']++; }
                }
            }
        }
        $t['sales'] = round($t['sales'], 2);
        $t['aov'] = $t['orders'] ? round($t['sales'] / $t['orders'], 2) : 0.0;
        $t['cancel_rate'] = $t['all'] ? round($t['cancelled'] * 100 / $t['all'], 1) : 0.0;
        $t['on_time'] = $t['timed'] ? (int) round($t['ontime'] * 100 / $t['timed']) : null;
        $t['avg_min'] = $t['mins_n'] ? (int) round($t['mins'] / $t['mins_n']) : null;
        $t['customers'] = count($t['buyers']);
        return $t;
    }

    /** The comparison period: same length just before; "today" compares with yesterday up to the same time. */
    public static function previous(string $from, string $to): array
    {
        $today = date('Y-m-d');
        if ($from === $today && $to === $today) {
            return [date('Y-m-d 00:00:00', strtotime('-1 day')), date('Y-m-d H:i:s', time() - 86400), 'yesterday, same time'];
        }
        $days = (int) round((strtotime($to) - strtotime($from)) / 86400) + 1;
        $pf = date('Y-m-d', strtotime($from . ' -' . $days . ' days')); $pt = date('Y-m-d', strtotime($from . ' -1 day'));
        return [$pf . ' 00:00:00', $pt . ' 23:59:59', 'previous ' . $days . ' day' . ($days > 1 ? 's' : '') . ' (' . date('d M', strtotime($pf)) . ' - ' . date('d M', strtotime($pt)) . ')'];
    }

    /** Product lines (not cancelled) in range: [products.id => [name, pack, category, qty, orders, value, code]] */
    public static function productLines(array $ids, string $fromTs, string $toTs): array
    {
        if (!$ids) return [];
        $q = DB::table('orders AS o')->join('products AS p', 'p.id', '=', 'o.product_id');
        if (self::h('items')) $q->leftJoin('items AS i', 'i.id', '=', 'p.item_id');
        $cat = DB::raw("'' AS category");
        if (self::h('category')) {
            $viaItem = self::h('items') && self::h('items', 'cat_id'); $viaProduct = self::h('products', 'category_id');
            if ($viaItem) $q->leftJoin('category AS c', 'c.id', '=', 'i.cat_id');
            if ($viaProduct) $q->leftJoin('category AS c2', 'c2.id', '=', 'p.category_id');
            if ($viaItem && $viaProduct) $cat = DB::raw('MAX(COALESCE(c.name, c2.name)) AS category');
            elseif ($viaItem) $cat = DB::raw('MAX(c.name) AS category');
            elseif ($viaProduct) $cat = DB::raw('MAX(c2.name) AS category');
        }
        $q->whereIn('o.hub_id', $ids)->where('o.date_added', '>=', $fromTs)->where('o.date_added', '<=', $toTs)
            ->whereNotIn('o.order_status', OrderFlow::statusesFor(['cancelled']))
            ->groupBy('p.id')->select('p.id', DB::raw('MAX(p.product_id) AS code'), DB::raw('MAX(p.product_name) AS pname'), DB::raw(self::h('items') ? 'MAX(i.item) AS item' : "'' AS item"),
                DB::raw('MAX(p.unit_quantity) AS uq'), DB::raw('MAX(p.product_unit) AS unit'), DB::raw('MAX(p.main_price) AS price'),
                DB::raw('SUM(o.quantity) AS qty'), DB::raw('COUNT(DISTINCT o.order_id) AS n'), $cat);
        $out = [];
        foreach ($q->get() as $r) {
            $uq = (float) $r->uq;
            $out[(int) $r->id] = ['id' => (int) $r->id, 'code' => (string) $r->code, 'name' => trim((string) ($r->item ?: $r->pname)), 'pack' => trim(($uq ? CountryScope::q($uq) : '') . ' ' . $r->unit),
                'category' => trim((string) ($r->category ?? '')) ?: 'Uncategorised', 'qty' => round((float) $r->qty, 3), 'orders' => (int) $r->n, 'value' => round((float) $r->qty * (float) $r->price, 2)];
        }
        return $out;
    }

    /* ------------------------------------------------------------------ command centre */

    public static function overview(CountryScope $scope, string $from, string $to): array
    {
        $ids = $scope->ids();
        $hubs = CountryScope::hubs();
        $now = time();
        [$pf, $pt, $plabel] = self::previous($from, $to);
        $rows = self::orderRows($ids, $from . ' 00:00:00', $to . ' 23:59:59');
        $prev = self::orderRows($ids, $pf, $pt);
        $cur = self::totals($rows); $old = self::totals($prev);

        // New customers: first ever order inside the range.
        $buyers = array_keys($cur['buyers']); $newBuyers = 0;
        foreach (array_chunk($buyers, 800) as $chunk) {
            $firsts = DB::table('orders')->whereIn('buyer_id', $chunk)->groupBy('buyer_id')->select('buyer_id', DB::raw('MIN(date_added) AS f'))->pluck('f', 'buyer_id');
            foreach ($firsts as $f) if ((string) $f >= $from . ' 00:00:00') $newBuyers++;
        }

        // Day trend and hour profile.
        $days = [];
        $span = (int) round((strtotime($to) - strtotime($from)) / 86400);
        for ($i = 0; $i <= $span; $i++) { $d = date('Y-m-d', strtotime($from . " +$i days")); $days[$d] = ['date' => $d, 'orders' => 0, 'sales' => 0.0, 'cancelled' => 0]; }
        $hours = array_fill(0, 24, 0); $hoursPrev = array_fill(0, 24, 0);
        foreach ($rows as $r) {
            $d = date('Y-m-d', $r['at']);
            if (!isset($days[$d])) continue;
            if ($r['stage'] === 'cancelled') { $days[$d]['cancelled']++; continue; }
            $days[$d]['orders']++; $days[$d]['sales'] += $r['value']; $hours[(int) date('G', $r['at'])]++;
        }
        foreach ($prev as $r) if ($r['stage'] !== 'cancelled') $hoursPrev[(int) date('G', $r['at'])]++;
        foreach ($days as &$x) $x['sales'] = round($x['sales'], 2);
        unset($x);

        // Mix.
        $pay = []; $type = ['Express' => ['orders' => 0, 'sales' => 0.0], 'Scheduled' => ['orders' => 0, 'sales' => 0.0]];
        foreach ($rows as $r) {
            if ($r['stage'] === 'cancelled') continue;
            $pay[$r['pay']] = $pay[$r['pay']] ?? ['type' => $r['pay'], 'orders' => 0, 'sales' => 0.0];
            $pay[$r['pay']]['orders']++; $pay[$r['pay']]['sales'] = round($pay[$r['pay']]['sales'] + $r['value'], 2);
            $type[$r['type']]['orders']++; $type[$r['type']]['sales'] = round($type[$r['type']]['sales'] + $r['value'], 2);
        }
        usort($pay, fn ($a, $b) => $b['sales'] <=> $a['sales']);

        // Products and categories.
        $lines = self::productLines($ids, $from . ' 00:00:00', $to . ' 23:59:59');
        $top = array_values($lines); usort($top, fn ($a, $b) => $b['value'] <=> $a['value']);
        $cats = [];
        foreach ($lines as $l) { $c = $l['category']; $cats[$c] = $cats[$c] ?? ['name' => $c, 'qty' => 0.0, 'value' => 0.0, 'products' => 0]; $cats[$c]['qty'] += $l['qty']; $cats[$c]['value'] = round($cats[$c]['value'] + $l['value'], 2); $cats[$c]['products']++; }
        $cats = array_values($cats); usort($cats, fn ($a, $b) => $b['value'] <=> $a['value']);

        $live = self::liveNow($ids);
        $stock = CountryStock::snapshot($ids);
        $waste = CountryStock::wastageTotals($ids, $from, $to);
        $cash = self::cashInHand($ids);
        $league = self::league($ids, $rows, $prev, $live['by_hub'], $waste['by_hub'], $cash['by_hub'], $stock['by_hub']);

        $kpis = [
            'orders' => $cur['orders'], 'orders_vs' => self::pct($cur['orders'], $old['orders']), 'orders_prev' => $old['orders'],
            'sales' => $cur['sales'], 'sales_vs' => self::pct($cur['sales'], $old['sales']), 'sales_prev' => $old['sales'],
            'aov' => $cur['aov'], 'aov_vs' => self::pct($cur['aov'], $old['aov']),
            'cancelled' => $cur['cancelled'], 'cancel_rate' => $cur['cancel_rate'], 'cancel_rate_prev' => $old['cancel_rate'],
            'delivered' => $cur['delivered'], 'on_time' => $cur['on_time'], 'avg_min' => $cur['avg_min'],
            'customers' => $cur['customers'], 'new_customers' => $newBuyers, 'repeat_customers' => max(0, $cur['customers'] - $newBuyers),
            'express' => $cur['express'], 'scheduled' => $cur['scheduled'],
            'wastage_value' => $waste['value'], 'wastage_qty' => $waste['qty'], 'wastage_pct' => $cur['sales'] > 0 ? round($waste['value'] * 100 / $cur['sales'], 2) : null,
            'stock_value' => $stock['value'], 'danger_value' => $stock['danger_value'], 'expired_value' => $stock['expired_value'], 'danger_items' => $stock['danger_items'], 'expired_items' => $stock['expired_items'],
            'out_items' => $stock['out_items'], 'pending_lines' => $stock['pending_lines'],
            'cash_in_hand' => $cash['total'], 'cash_over' => $cash['over'],
            'open_now' => $live['open'], 'late_now' => $live['late'],
            'hubs' => count($ids), 'hubs_active' => count(array_filter($ids, fn ($i) => $hubs[$i]['active'] ?? false)),
            'hubs_selling' => count(array_unique(array_map(fn ($r) => $r['hub'], array_filter($rows, fn ($r) => $r['stage'] !== 'cancelled')))),
        ];
        $targets = CountryTargets::progressFor($scope);
        // Order status row (update 3): same counts in every console. Rejected = cancelled before acceptance (POS "reject").
        $cancelIds = array_values(array_map(fn ($r) => $r['id'], array_filter($rows, fn ($r) => $r['stage'] === 'cancelled')));
        $rejected = 0;
        if ($cancelIds && CountryScope::has('pos_order_events')) foreach (array_chunk($cancelIds, 1000) as $c) $rejected += DB::table('pos_order_events')->whereIn('order_id', $c)->where('action', 'reject')->distinct()->count('order_id');
        $stageCount = fn ($st) => count(array_filter($rows, fn ($r) => $r['stage'] === $st));
        $status = ['express' => $cur['express'], 'scheduled' => $cur['scheduled'], 'pending' => $live['stages']['new'], 'accepted' => $stageCount('accepted'), 'out' => $stageCount('out'),
            'delivered' => $stageCount('delivered') + $stageCount('unsettled'), 'cancelled' => count($cancelIds) - $rejected, 'rejected' => $rejected, 'all' => count($rows)];
        return [
            'ok' => true, 'scope' => $scope->label(), 'from' => $from, 'to' => $to, 'compare' => $plabel, 'server_time' => $now,
            'kpis' => $kpis, 'status' => $status, 'days' => array_values($days), 'hours' => ['cur' => $hours, 'prev' => $hoursPrev],
            'pay_mix' => array_values($pay), 'type_mix' => $type, 'top_products' => array_slice($top, 0, 10), 'categories' => array_slice($cats, 0, 12),
            'pipeline' => $live['stages'], 'league' => $league, 'stock' => array_diff_key($stock, ['by_hub' => 1]),
            'targets' => $targets,
            'insights' => self::insights($scope, $kpis, $league, $live, $stock, $waste, $cash, $top, $from, $to),
        ];
    }

    /** Open orders right now (last 3 days, not stale): stage counts and late orders, overall and per hub. */
    public static function liveNow(array $ids): array
    {
        $rows = self::orderRows($ids, date('Y-m-d H:i:s', time() - OrderFlow::STALE_AFTER), date('Y-m-d 23:59:59'));
        $now = time(); $endToday = strtotime('tomorrow') - 1;
        $stages = ['new' => 0, 'accepted' => 0, 'out' => 0, 'later' => 0, 'late' => 0];
        $byHub = []; $late = [];
        foreach ($rows as $r) {
            if (!in_array($r['stage'], ['new', 'accepted', 'out'], true)) continue;
            if ($r['due'] && $r['due'] > $endToday) { $stages['later']++; continue; }
            $stages[$r['stage']]++;
            $byHub[$r['hub']] = $byHub[$r['hub']] ?? ['new' => 0, 'accepted' => 0, 'out' => 0, 'late' => 0];
            $byHub[$r['hub']][$r['stage']]++;
            if ($r['due'] && $r['due'] < $now) { $stages['late']++; $byHub[$r['hub']]['late']++; $late[] = $r; }
        }
        usort($late, fn ($a, $b) => $a['due'] <=> $b['due']);
        return ['stages' => $stages, 'open' => $stages['new'] + $stages['accepted'] + $stages['out'], 'late' => $stages['late'], 'by_hub' => $byHub, 'late_rows' => array_slice($late, 0, 50)];
    }

    /** Cash held at each hub (cash book: cash sales - deposits), and hubs above the limit. */
    public static function cashInHand(array $ids): array
    {
        $by = []; $total = 0.0; $over = 0;
        $limit = (float) config('hub_console.cash_limit', 20000);
        if (!class_exists(HubCashService::class) || !HubStock::has('hub_cash_entries')) return ['by_hub' => [], 'total' => null, 'over' => 0, 'limit' => $limit];
        foreach ($ids as $h) {
            try { $v = round((float) (new HubCashService($h))->inHand(), 2); } catch (\Throwable $e) { continue; }
            $by[$h] = $v; $total += $v; if ($v > $limit) $over++;
        }
        return ['by_hub' => $by, 'total' => round($total, 2), 'over' => $over, 'limit' => $limit];
    }

    /** Ranking by state, city and hub. */
    private static function league(array $ids, array $rows, array $prev, array $liveByHub, array $wasteByHub, array $cashByHub, array $stockByHub): array
    {
        $hubs = CountryScope::hubs();
        $agg = function (array $rs, callable $key) {
            $o = [];
            foreach ($rs as $r) {
                $k = $key($r); if ($k === null) continue;
                $o[$k] = $o[$k] ?? ['orders' => 0, 'sales' => 0.0, 'cancelled' => 0, 'all' => 0, 'buyers' => []];
                $o[$k]['all']++;
                if ($r['stage'] === 'cancelled') { $o[$k]['cancelled']++; continue; }
                $o[$k]['orders']++; $o[$k]['sales'] += $r['value']; if ($r['buyer']) $o[$k]['buyers'][$r['buyer']] = true;
            }
            return $o;
        };
        $total = 0.0; foreach ($rows as $r) if ($r['stage'] !== 'cancelled') $total += $r['value'];
        $levels = [
            'states' => fn ($r) => ($hubs[$r['hub']]['state_id'] ?? 0) ?: null,
            'cities' => fn ($r) => ($hubs[$r['hub']]['city_id'] ?? 0) ?: null,
            'hubs' => fn ($r) => $r['hub'],
        ];
        $out = [];
        foreach ($levels as $lvl => $key) {
            $c = $agg($rows, $key); $p = $agg($prev, $key);
            // Every unit in scope appears, also with no orders (a hub that sold nothing is the most important row).
            $names = [];
            foreach ($ids as $hid) {
                $h = $hubs[$hid] ?? null; if (!$h) continue;
                if ($lvl === 'states' && $h['state_id']) $names[$h['state_id']] = ['name' => $h['state'] ?: 'State ' . $h['state_id'], 'sub' => ''];
                if ($lvl === 'cities' && $h['city_id']) $names[$h['city_id']] = ['name' => $h['city'] ?: 'City ' . $h['city_id'], 'sub' => $h['state']];
                if ($lvl === 'hubs') $names[$hid] = ['name' => $h['name'], 'sub' => trim($h['city'] . ($h['type'] !== '' ? ' · ' . $h['type'] : ''), ' ·'), 'active' => $h['active']];
            }
            $list = [];
            foreach ($names as $k => $n) {
                $x = $c[$k] ?? ['orders' => 0, 'sales' => 0.0, 'cancelled' => 0, 'all' => 0, 'buyers' => []];
                $y = $p[$k] ?? ['orders' => 0, 'sales' => 0.0];
                $row = ['id' => (int) $k, 'name' => $n['name'], 'sub' => $n['sub'], 'orders' => $x['orders'], 'sales' => round($x['sales'], 2),
                    'aov' => $x['orders'] ? round($x['sales'] / $x['orders'], 2) : 0, 'cancel_rate' => $x['all'] ? round($x['cancelled'] * 100 / $x['all'], 1) : 0,
                    'cancelled' => $x['cancelled'], 'customers' => count($x['buyers']), 'share' => $total > 0 ? round($x['sales'] * 100 / $total, 1) : 0,
                    'growth' => self::pct($x['sales'], $y['sales']), 'prev_sales' => round($y['sales'], 2)];
                if ($lvl === 'hubs') {
                    $row['active'] = $n['active'];
                    $row['late'] = $liveByHub[$k]['late'] ?? 0; $row['open'] = ($liveByHub[$k]['new'] ?? 0) + ($liveByHub[$k]['accepted'] ?? 0) + ($liveByHub[$k]['out'] ?? 0);
                    $row['wastage'] = $wasteByHub[$k]['value'] ?? 0; $row['wastage_pct'] = $x['sales'] > 0 ? round(($wasteByHub[$k]['value'] ?? 0) * 100 / $x['sales'], 2) : null;
                    $row['cash'] = $cashByHub[$k] ?? null;
                    $row['danger_value'] = $stockByHub[$k]['danger_value'] ?? 0; $row['expired_value'] = $stockByHub[$k]['expired_value'] ?? 0;
                    $row['stock_value'] = $stockByHub[$k]['value'] ?? 0; $row['out_items'] = $stockByHub[$k]['out'] ?? 0;
                }
                $list[] = $row;
            }
            usort($list, fn ($a, $b) => [$b['sales'], $b['orders']] <=> [$a['sales'], $a['orders']]);
            foreach ($list as $i => &$r) $r['rank'] = $i + 1;
            unset($r);
            $out[$lvl] = $list;
        }
        return $out;
    }

    /** Plain-language alerts across all hubs in scope (rule based, explainable). */
    private static function insights(CountryScope $scope, array $k, array $league, array $live, array $stock, array $waste, array $cash, array $top, string $from, string $to): array
    {
        $out = [];
        $hubs = $league['hubs'];
        $names = fn (array $list, int $n = 3) => implode(', ', array_map(fn ($r) => $r['name'], array_slice($list, 0, $n))) . (count($list) > $n ? ' and ' . (count($list) - $n) . ' more' : '');
        $money = fn ($v) => '₹' . number_format((float) $v, 0);

        $lateHubs = array_values(array_filter($hubs, fn ($h) => $h['late'] > 0)); usort($lateHubs, fn ($a, $b) => $b['late'] <=> $a['late']);
        if ($live['late']) $out[] = ['level' => 'bad', 'title' => $live['late'] . ' order(s) late right now in ' . count($lateHubs) . ' hub(s)', 'text' => 'Most: ' . implode(', ', array_map(fn ($h) => $h['name'] . ' (' . $h['late'] . ')', array_slice($lateHubs, 0, 3))) . '.', 'view' => 'live'];
        if ($k['expired_value'] > 0) {
            $eh = array_values(array_filter($hubs, fn ($h) => $h['expired_value'] > 0)); usort($eh, fn ($a, $b) => $b['expired_value'] <=> $a['expired_value']);
            $out[] = ['level' => 'bad', 'title' => $money($k['expired_value']) . ' of expired stock (2+ days old) in ' . count($eh) . ' hub(s)', 'text' => 'Write it off or move it today: ' . implode(', ', array_map(fn ($h) => $h['name'] . ' ' . $money($h['expired_value']), array_slice($eh, 0, 3))) . '.', 'view' => 'danger'];
        }
        if ($k['danger_value'] > 0) $out[] = ['level' => 'warn', 'title' => $money($k['danger_value']) . ' of danger stock must sell today', 'text' => $k['danger_items'] . ' product line(s) received yesterday. Check the 11:00 transfer plan per hub.', 'view' => 'danger'];
        $notRun = CountryStock::plansNotRun(array_map(fn ($h) => $h['id'], array_filter($hubs, fn ($h) => $h['active'] && $h['danger_value'] > 0)));
        if ($notRun) $out[] = ['level' => 'warn', 'title' => 'Danger stock plan has not run in ' . count($notRun) . ' hub(s)', 'text' => 'Nobody opened these hub consoles after ' . config('hub_console.danger_plan_time', '11:00') . ': ' . implode(', ', array_slice($notRun, 0, 4)) . '. Run it from Danger & expired.', 'view' => 'danger'];
        $limit = (float) config('country_console.wastage_limit_pct', 3);
        $wh = array_values(array_filter($hubs, fn ($h) => $h['wastage_pct'] !== null && $h['wastage_pct'] > $limit)); usort($wh, fn ($a, $b) => $b['wastage_pct'] <=> $a['wastage_pct']);
        if ($wh) $out[] = ['level' => 'warn', 'title' => count($wh) . ' hub(s) wasting more than ' . CountryScope::q($limit) . '% of sales', 'text' => implode(', ', array_map(fn ($h) => $h['name'] . ' ' . $h['wastage_pct'] . '%', array_slice($wh, 0, 3))) . '.', 'view' => 'wastage'];
        if ($cash['over']) {
            $ch = array_values(array_filter($hubs, fn ($h) => $h['cash'] !== null && $h['cash'] > $cash['limit'])); usort($ch, fn ($a, $b) => $b['cash'] <=> $a['cash']);
            $out[] = ['level' => 'warn', 'title' => count($ch) . ' hub(s) holding more than ' . $money($cash['limit']) . ' cash', 'text' => implode(', ', array_map(fn ($h) => $h['name'] . ' ' . $money($h['cash']), array_slice($ch, 0, 3))) . '. Ask them to deposit.', 'view' => 'cash'];
        }
        $cl = (float) config('country_console.cancel_alert_pct', 15);
        $cx = array_values(array_filter($hubs, fn ($h) => $h['orders'] + $h['cancelled'] >= 5 && $h['cancel_rate'] >= $cl)); usort($cx, fn ($a, $b) => $b['cancel_rate'] <=> $a['cancel_rate']);
        if ($cx) $out[] = ['level' => 'warn', 'title' => count($cx) . ' hub(s) cancelling ' . CountryScope::q($cl) . '% or more of orders', 'text' => implode(', ', array_map(fn ($h) => $h['name'] . ' ' . $h['cancel_rate'] . '%', array_slice($cx, 0, 3))) . '. See the reasons in Cancellations.', 'view' => 'cancels'];
        if ($to === date('Y-m-d') && (int) date('G') >= 11) {
            $zero = array_values(array_filter($hubs, fn ($h) => $h['active'] && $h['orders'] === 0));
            if ($zero) $out[] = ['level' => 'warn', 'title' => count($zero) . ' active hub(s) with no orders ' . ($from === $to ? 'today' : 'in these dates'), 'text' => $names($zero, 4) . '. Check stock, riders and the delivery area.', 'view' => 'league'];
        }
        if ($k['out_items'] > 0) $out[] = ['level' => 'info', 'title' => $k['out_items'] . ' product line(s) out of stock across hubs', 'text' => 'Customers in those areas cannot order them. See Stock by hub.', 'view' => 'stock-hubs'];
        if ($k['pending_lines'] > 0) $out[] = ['level' => 'info', 'title' => $k['pending_lines'] . ' stock line(s) sent but not received by hubs', 'text' => 'Hubs must accept inward stock so their numbers are right.', 'view' => 'inward'];
        $stuck = CountryStock::stuckTransfers(array_column($hubs, 'id'));
        if ($stuck) $out[] = ['level' => 'warn', 'title' => $stuck . ' transfer(s) on the road for more than 6 hours', 'text' => 'The receiving hub has not pressed Receive. Check with both hubs.', 'view' => 'transfers'];
        if ($k['sales_vs'] !== null && $k['sales_prev'] > 0 && abs($k['sales_vs']) >= 10) $out[] = ['level' => $k['sales_vs'] > 0 ? 'ok' : 'warn', 'title' => 'Sales ' . ($k['sales_vs'] > 0 ? 'up ' : 'down ') . abs($k['sales_vs']) . '%', 'text' => $money($k['sales']) . ' vs ' . $money($k['sales_prev']) . ' (' . 'compared with the previous period).', 'view' => 'overview'];
        $grow = array_values(array_filter($league['cities'], fn ($c) => $c['growth'] !== null && $c['prev_sales'] >= 1000)); usort($grow, fn ($a, $b) => $a['growth'] <=> $b['growth']);
        if ($grow && $grow[0]['growth'] <= -20) $out[] = ['level' => 'warn', 'title' => $grow[0]['name'] . ' sales down ' . abs($grow[0]['growth']) . '%', 'text' => $money($grow[0]['sales']) . ' vs ' . $money($grow[0]['prev_sales']) . ' before.', 'view' => 'league'];
        if ($grow && end($grow)['growth'] >= 20) { $g = end($grow); $out[] = ['level' => 'ok', 'title' => $g['name'] . ' sales up ' . $g['growth'] . '%', 'text' => $money($g['sales']) . ' vs ' . $money($g['prev_sales']) . ' before.', 'view' => 'league']; }
        if (!empty($hubs[0]) && $hubs[0]['sales'] > 0) $out[] = ['level' => 'ok', 'title' => 'Top hub: ' . $hubs[0]['name'], 'text' => $money($hubs[0]['sales']) . ' from ' . $hubs[0]['orders'] . ' orders (' . $hubs[0]['share'] . '% of sales).', 'view' => 'league'];
        if (!empty($top[0])) $out[] = ['level' => 'ok', 'title' => 'Best seller: ' . $top[0]['name'], 'text' => CountryScope::q($top[0]['qty']) . ' sold in ' . $top[0]['orders'] . ' orders, ' . $money($top[0]['value']) . '.', 'view' => 'reports'];
        if ($k['on_time'] !== null && $k['delivered'] >= 10 && $k['on_time'] < 85) $out[] = ['level' => 'warn', 'title' => 'On-time delivery at ' . $k['on_time'] . '%', 'text' => 'Average Express delivery ' . ($k['avg_min'] ?? '-') . ' min. Target is 90% on time.', 'view' => 'league'];
        foreach (CountryTargets::progressFor($scope) as $t) {
            if ($t['sales_target'] > 0 && $t['pace'] !== null && $t['pace'] < 90) $out[] = ['level' => 'warn', 'title' => $t['label'] . ': behind the monthly target', 'text' => 'Projected ' . $money($t['projected']) . ' of ' . $money($t['sales_target']) . ' (' . $t['pace'] . '% of target pace).', 'view' => 'targets'];
        }
        if (!$out) $out[] = ['level' => 'ok', 'title' => 'All clear', 'text' => 'No late orders, no expired stock and no cash problems in ' . $scope->label() . '.', 'view' => 'overview'];
        $rank = ['bad' => 0, 'warn' => 1, 'info' => 2, 'ok' => 3];
        usort($out, fn ($a, $b) => $rank[$a['level']] <=> $rank[$b['level']]);
        return $out;
    }

    /* ------------------------------------------------------------------ sales report */

    public const GROUPS = ['day' => 'Day', 'state' => 'State', 'city' => 'City', 'hub' => 'Hub', 'category' => 'Category', 'product' => 'Product', 'payment' => 'Payment type', 'type' => 'Delivery type', 'hour' => 'Hour of day'];

    public static function sales(CountryScope $scope, string $from, string $to, string $group): array
    {
        $group = array_key_exists($group, self::GROUPS) ? $group : 'day';
        $ids = $scope->ids();
        $hubs = CountryScope::hubs();
        if (in_array($group, ['category', 'product'], true)) {
            $lines = self::productLines($ids, $from . ' 00:00:00', $to . ' 23:59:59');
            $rows = [];
            foreach ($lines as $l) {
                $k = $group === 'product' ? $l['id'] : $l['category'];
                $rows[$k] = $rows[$k] ?? ['key' => $group === 'product' ? $l['name'] . ($l['pack'] ? ' (' . $l['pack'] . ')' : '') : $l['category'], 'sub' => $group === 'product' ? $l['category'] : '', 'qty' => 0.0, 'orders' => 0, 'sales' => 0.0, 'products' => 0];
                $rows[$k]['qty'] = round($rows[$k]['qty'] + $l['qty'], 3); $rows[$k]['orders'] += $l['orders']; $rows[$k]['sales'] = round($rows[$k]['sales'] + $l['value'], 2); $rows[$k]['products']++;
            }
            $rows = array_values($rows);
            $tot = array_sum(array_column($rows, 'sales'));
            foreach ($rows as &$r) $r['share'] = $tot > 0 ? round($r['sales'] * 100 / $tot, 1) : 0;
            unset($r);
            usort($rows, fn ($a, $b) => $b['sales'] <=> $a['sales']);
            return ['ok' => true, 'group' => $group, 'label' => self::GROUPS[$group], 'basis' => 'list price', 'rows' => $rows, 'total' => ['sales' => round($tot, 2), 'qty' => round(array_sum(array_column($rows, 'qty')), 3)], 'scope' => $scope->label(), 'from' => $from, 'to' => $to];
        }
        $orders = self::orderRows($ids, $from . ' 00:00:00', $to . ' 23:59:59');
        $key = match ($group) {
            'day' => fn ($r) => date('Y-m-d', $r['at']), 'hour' => fn ($r) => sprintf('%02d:00', (int) date('G', $r['at'])),
            'state' => fn ($r) => $hubs[$r['hub']]['state'] ?? '-', 'city' => fn ($r) => $hubs[$r['hub']]['city'] ?? '-',
            'hub' => fn ($r) => $hubs[$r['hub']]['name'] ?? ('Hub ' . $r['hub']), 'payment' => fn ($r) => $r['pay'], 'type' => fn ($r) => $r['type'],
        };
        $rows = [];
        foreach ($orders as $o) {
            $k = $key($o) ?: '-';
            $rows[$k] = $rows[$k] ?? ['key' => $k, 'sub' => $group === 'hub' ? ($hubs[$o['hub']]['city'] ?? '') : ($group === 'city' ? ($hubs[$o['hub']]['state'] ?? '') : ''), 'orders' => 0, 'sales' => 0.0, 'cancelled' => 0, 'cancel_value' => 0.0, 'buyers' => []];
            if ($o['stage'] === 'cancelled') { $rows[$k]['cancelled']++; $rows[$k]['cancel_value'] = round($rows[$k]['cancel_value'] + $o['value'], 2); continue; }
            $rows[$k]['orders']++; $rows[$k]['sales'] = round($rows[$k]['sales'] + $o['value'], 2); if ($o['buyer']) $rows[$k]['buyers'][$o['buyer']] = true;
        }
        $tot = array_sum(array_map(fn ($r) => $r['sales'], $rows));
        foreach ($rows as &$r) {
            $r['customers'] = count($r['buyers']); unset($r['buyers']);
            $r['aov'] = $r['orders'] ? round($r['sales'] / $r['orders'], 2) : 0;
            $r['cancel_rate'] = ($r['orders'] + $r['cancelled']) ? round($r['cancelled'] * 100 / ($r['orders'] + $r['cancelled']), 1) : 0;
            $r['share'] = $tot > 0 ? round($r['sales'] * 100 / $tot, 1) : 0;
        }
        unset($r);
        $rows = array_values($rows);
        if (in_array($group, ['day', 'hour'], true)) usort($rows, fn ($a, $b) => strcmp($a['key'], $b['key'])); else usort($rows, fn ($a, $b) => $b['sales'] <=> $a['sales']);
        return ['ok' => true, 'group' => $group, 'label' => self::GROUPS[$group], 'basis' => 'order value', 'rows' => $rows,
            'total' => ['orders' => array_sum(array_column($rows, 'orders')), 'sales' => round($tot, 2), 'cancelled' => array_sum(array_column($rows, 'cancelled'))], 'scope' => $scope->label(), 'from' => $from, 'to' => $to];
    }

    /** Cancelled orders and their reasons. */
    public static function cancellations(CountryScope $scope, string $from, string $to): array
    {
        $hubs = CountryScope::hubs();
        $rows = array_values(array_filter(self::orderRows($scope->ids(), $from . ' 00:00:00', $to . ' 23:59:59'), fn ($r) => $r['stage'] === 'cancelled'));
        $why = []; $byHub = [];
        foreach ($rows as $r) {
            $w = $r['why'] !== '' ? mb_substr($r['why'], 0, 80) : 'No reason recorded';
            $why[$w] = $why[$w] ?? ['reason' => $w, 'orders' => 0, 'value' => 0.0]; $why[$w]['orders']++; $why[$w]['value'] = round($why[$w]['value'] + $r['value'], 2);
            $byHub[$r['hub']] = $byHub[$r['hub']] ?? ['hub' => $hubs[$r['hub']]['name'] ?? ('Hub ' . $r['hub']), 'city' => $hubs[$r['hub']]['city'] ?? '', 'orders' => 0, 'value' => 0.0];
            $byHub[$r['hub']]['orders']++; $byHub[$r['hub']]['value'] = round($byHub[$r['hub']]['value'] + $r['value'], 2);
        }
        usort($why, fn ($a, $b) => $b['orders'] <=> $a['orders']);
        $byHub = array_values($byHub); usort($byHub, fn ($a, $b) => $b['orders'] <=> $a['orders']);
        usort($rows, fn ($a, $b) => $b['at'] <=> $a['at']);
        $list = array_map(fn ($r) => ['order_id' => $r['id'], 'at' => $r['at'], 'hub' => $hubs[$r['hub']]['name'] ?? '', 'city' => $hubs[$r['hub']]['city'] ?? '', 'value' => $r['value'], 'type' => $r['type'], 'pay' => $r['pay'], 'reason' => $r['why']], array_slice($rows, 0, 1000));
        return ['ok' => true, 'reasons' => array_values($why), 'hubs' => $byHub, 'rows' => $list, 'total' => count($rows), 'value' => round(array_sum(array_column($rows, 'value')), 2)];
    }
}
