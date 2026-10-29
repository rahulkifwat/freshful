<?php

namespace App\Services\HubConsole;

use App\Services\PosConsole\OrderFlow;
use App\Services\PosConsole\PosConsoleService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Freshful hub console (22 Oct 2026) - command centre for ONE hub.
 * Order work (accept, weigh, dispatch, settle) stays in the POS console; this service adds the manager's view:
 * overview with smart insights, prep list, inventory with logged stock moves, order search.
 *
 * Stock model (same as the old hub panel): inventory.stock = sent to the hub, not yet received ("pending inward");
 * accepting moves it into live_stock / fresh_stock and records the difference as variance.
 * inventory.product_id holds the product CODE (products.product_id), orders.product_id holds products.id.
 */
class HubConsoleService
{
    private static array $cols = [];
    private static array $tables = [];
    private PosConsoleService $pos;

    public function __construct(private int $hubId)
    {
        $this->pos = new PosConsoleService($hubId);
    }

    private static function has(string $t, string $c): bool
    {
        $k = "$t.$c";
        if (!array_key_exists($k, self::$cols)) { try { self::$cols[$k] = Schema::hasColumn($t, $c); } catch (\Throwable $e) { self::$cols[$k] = false; } }
        return self::$cols[$k];
    }

    private static function table(string $t): bool
    {
        if (!array_key_exists($t, self::$tables)) { try { self::$tables[$t] = Schema::hasTable($t); } catch (\Throwable $e) { self::$tables[$t] = false; } }
        return self::$tables[$t];
    }

    private static function ts($v): ?int
    {
        if (!$v) return null;
        $t = strtotime((string) $v);
        return ($t !== false && $t > 946684800) ? $t : null;
    }

    public function hub(): ?object
    {
        return $this->pos->hub();
    }

    /* ------------------------------------------------------------------ overview */

    public function overview(): array
    {
        $now = time();
        $today0 = strtotime('today');
        $feed = $this->pos->feed();
        $rep = $this->pos->dayReport(date('Y-m-d'));

        // Orders of the last 8 days (one row per order).
        $rows = DB::table('orders')->where('hub_id', $this->hubId)->where('date_added', '>=', date('Y-m-d 00:00:00', strtotime('-7 days', $today0)))
            ->groupBy('order_id')
            ->select('order_id', DB::raw('MAX(order_status) AS st'), DB::raw('MAX(date_added) AS at'), DB::raw('MAX(total_amount) AS t'),
                DB::raw('MAX(delivery_charge) AS d'), DB::raw('MAX(delivery_type) AS type'), DB::raw('MAX(buyer_id) AS buyer'),
                self::has('orders', 'amount_to_collect') ? DB::raw('MAX(amount_to_collect) AS c') : DB::raw('0 AS c'),
                self::has('orders', 'amount_to_return') ? DB::raw('MAX(amount_to_return) AS r') : DB::raw('0 AS r'))
            ->get();

        $days = [];
        for ($i = 7; $i >= 0; $i--) { $d = date('Y-m-d', strtotime("-$i days", $today0)); $days[$d] = ['date' => $d, 'orders' => 0, 'sales' => 0.0, 'cancelled' => 0]; }
        $hourToday = array_fill(0, 24, 0); $hourYest = array_fill(0, 24, 0);
        $cmp = ['today_orders' => 0, 'today_sales' => 0.0, 'yest_orders' => 0, 'yest_sales' => 0.0, 'today_cancel' => 0, 'today_all' => 0];
        $express = 0; $scheduled = 0; $buyersToday = [];
        $clock = $now - $today0;
        foreach ($rows as $r) {
            $at = self::ts($r->at); if (!$at) continue;
            $d = date('Y-m-d', $at); if (!isset($days[$d])) continue;
            $cancel = OrderFlow::stage($r->st) === 'cancelled';
            $value = (float) $r->t + (float) $r->d + (float) $r->c - (float) $r->r;
            if ($cancel) $days[$d]['cancelled']++; else { $days[$d]['orders']++; $days[$d]['sales'] += $value; }
            $h = (int) date('G', $at);
            if ($at >= $today0) {
                $cmp['today_all']++;
                if ($cancel) { $cmp['today_cancel']++; continue; }
                $hourToday[$h]++; $cmp['today_orders']++; $cmp['today_sales'] += $value;
                strtolower((string) $r->type) === 'scheduled' ? $scheduled++ : $express++;
                if ($r->buyer) $buyersToday[(int) $r->buyer] = true;
            } elseif ($at >= $today0 - 86400 && !$cancel) {
                $hourYest[$h]++;
                if ($at - ($today0 - 86400) <= $clock) { $cmp['yest_orders']++; $cmp['yest_sales'] += $value; }
            }
        }
        foreach ($days as &$x) $x['sales'] = round($x['sales'], 2);
        unset($x);

        $newBuyers = 0;
        if ($buyersToday) {
            $returning = DB::table('orders')->whereIn('buyer_id', array_keys($buyersToday))->where('date_added', '<', date('Y-m-d 00:00:00', $today0))->distinct()->pluck('buyer_id')->map(fn ($v) => (int) $v)->all();
            $newBuyers = count(array_diff(array_keys($buyersToday), $returning));
        }

        $stock = $this->stockRows();
        $prep = $this->prep();
        $inv = ['out' => 0, 'low' => 0, 'short' => 0, 'pending_items' => 0, 'pending_qty' => 0.0, 'value' => 0.0, 'danger_items' => 0, 'expired_items' => 0];
        foreach ($stock as $s) {
            if (($s['danger'] ?? 0) > 0) $inv['danger_items']++;
            if (($s['expired'] ?? 0) > 0) $inv['expired_items']++;
            if ($s['status'] === 'out') $inv['out']++;
            if ($s['status'] === 'low') $inv['low']++;
            if ($s['short'] > 0) $inv['short']++;
            if ($s['pending'] > 0) { $inv['pending_items']++; $inv['pending_qty'] += $s['pending']; }
            $inv['value'] += max(0, $s['live']) * $s['price'];
        }
        $inv['value'] = round($inv['value'], 2);

        $c = $feed['counts']; $k = $feed['kpis'];
        $aov = $cmp['today_orders'] ? round($cmp['today_sales'] / $cmp['today_orders'], 2) : 0;
        $kpis = [
            'orders' => $cmp['today_orders'], 'orders_vs' => self::pct($cmp['today_orders'], $cmp['yest_orders']),
            'sales' => round($cmp['today_sales'], 2), 'sales_vs' => self::pct($cmp['today_sales'], $cmp['yest_sales']),
            'aov' => $aov, 'express' => $express, 'scheduled' => $scheduled,
            'new_customers' => $newBuyers, 'customers' => count($buyersToday),
            'on_time' => $rep['sla']['pct'] ?? null, 'avg_delivery' => $rep['sla']['avg_min'] ?? null, 'delivered' => $rep['sla']['delivered'] ?? 0,
            'cancel_rate' => $cmp['today_all'] ? (int) round($cmp['today_cancel'] * 100 / $cmp['today_all']) : 0, 'cancelled' => $cmp['today_cancel'],
            'with_riders' => $k['with_riders'] ?? 0, 'received' => $k['settled_today']['received'] ?? 0, 'cash_to_handover' => $rep['cash_to_handover'] ?? 0,
            'day_submitted' => !empty($rep['closes']),
        ];
        $pipeline = ['new' => 0, 'accepted' => 0, 'ready' => 0, 'out' => 0, 'unsettled' => 0, 'late' => $c['late'] ?? 0, 'later' => $c['scheduled_later'] ?? 0, 'stale' => $c['stale'] ?? 0];
        foreach ($feed['orders'] as $o) {   // live orders only, same as the POS board
            if (!$o['stale'] && isset($pipeline[$o['board']]) && !in_array($o['board'], ['late', 'later', 'stale'], true)) $pipeline[$o['board']]++;
        }
        $riders = array_values(array_filter($feed['riders'], fn ($r) => $r['id']));

        // Today's orders by stage, Express vs Scheduled (old dashboard "Consolidated numbers" + "Express / Scheduled stats").
        $stages = ['new', 'accepted', 'out', 'unsettled', 'delivered', 'cancelled'];
        $grid = ['Express' => array_fill_keys($stages, 0), 'Scheduled' => array_fill_keys($stages, 0)];
        foreach ($rows as $r) {
            $at = self::ts($r->at); if (!$at || $at < $today0) continue;
            $g = strtolower((string) $r->type) === 'scheduled' ? 'Scheduled' : 'Express';
            $st = OrderFlow::stage($r->st); if (isset($grid[$g][$st])) $grid[$g][$st]++;
        }
        $raw = ['Order Pending' => 0, 'Order Placed' => 0, 'Order Processed' => 0, 'Order Shipped' => 0, 'Order Dispatched' => 0, 'Order Delivered' => 0, 'Order Cancel' => 0];
        foreach ($rows as $r) {
            $at = self::ts($r->at); if (!$at || $at < $today0) continue;
            $k = (string) $r->st; if ($k === 'Order Accepted') $k = 'Order Placed';
            if (array_key_exists($k, $raw)) $raw[$k]++;
        }
        $sched = DB::table('orders')->where('hub_id', $this->hubId)->where('delivery_type', 'Scheduled')->whereNotIn('order_status', OrderFlow::statusesFor(['cancelled']))
            ->where('date_added', '>=', date('Y-m-d H:i:s', strtotime('-30 days')))->groupBy('order_id')
            ->select('order_id', DB::raw('MAX(schedule_date) AS sd'), DB::raw('MAX(order_status) AS st'))->get();
        $schedCount = ['today' => 0, 'tomorrow' => 0, 'today_open' => 0, 'later' => 0];
        $tom = date('Y-m-d', strtotime('+1 day', $today0));
        foreach ($sched as $o) {
            $d = OrderFlow::normDate($o->sd); if (!$d) continue;
            if ($d === date('Y-m-d', $today0)) { $schedCount['today']++; if (in_array(OrderFlow::stage($o->st), ['new', 'accepted', 'out'], true)) $schedCount['today_open']++; }
            elseif ($d === $tom) $schedCount['tomorrow']++;
            elseif ($d > $tom) $schedCount['later']++;
        }
        $life = DB::table('orders')->where('hub_id', $this->hubId)->select(DB::raw('COUNT(DISTINCT order_id) AS a'),
            DB::raw("COUNT(DISTINCT CASE WHEN delivery_type = 'Scheduled' THEN order_id END) AS s"))->first();
        $extra = $this->extraSignals();

        return [
            'ok' => true, 'server_time' => $now, 'hub' => $this->hub(),
            'kpis' => $kpis, 'pipeline' => $pipeline, 'days' => array_values($days),
            'hours' => ['today' => $hourToday, 'yesterday' => $hourYest, 'now' => (int) date('G')],
            'riders' => $riders, 'inventory' => $inv, 'top_products' => array_slice($rep['products'] ?? [], 0, 5),
            'insights' => array_merge($this->insights($feed, $rep, $kpis, $stock, $prep, $riders, $cmp), []),
            'late' => array_values(array_slice(array_filter($feed['orders'], fn ($o) => $o['late']), 0, 6)),
            'status_grid' => $grid, 'raw_status' => $raw, 'grievances' => $this->grievanceSummary(), 'scheduled' => $schedCount, 'lifetime' => ['orders' => (int) ($life->a ?? 0), 'scheduled' => (int) ($life->s ?? 0), 'express' => (int) ($life->a ?? 0) - (int) ($life->s ?? 0)],
            'transfers' => $extra['transfers'], 'cash_in_hand' => $extra['cash'], 'cash_limit' => (float) config('hub_console.cash_limit', 20000),
        ];
    }

    private static function pct(float $a, float $b): ?int
    {
        if ($b <= 0) return $a > 0 ? null : 0;
        return (int) round(($a - $b) * 100 / $b);
    }

    /** Plain-language alerts worked out from the hub's own data (rule based, explainable). */
    private function insights(array $feed, array $rep, array $kpis, array $stock, array $prep, array $riders, array $cmp): array
    {
        $out = [];
        $now = time();
        $free = array_values(array_filter($riders, fn ($r) => $r['state'] === 'available'));
        $late = array_values(array_filter($feed['orders'], fn ($o) => $o['late']));
        $waiting = array_values(array_filter($feed['orders'], fn ($o) => $o['stage'] === 'new' && !$o['stale'] && $o['placed_at'] && $now - $o['placed_at'] > 300));
        $ready = array_values(array_filter($feed['orders'], fn ($o) => $o['board'] === 'ready'));

        if ($late) $out[] = ['level' => 'bad', 'title' => count($late) . ' order(s) past the promised time', 'text' => 'Oldest: ' . $late[0]['order_id'] . ' (' . $late[0]['customer'] . '). ' . ($free ? count($free) . ' rider(s) free at the hub.' : 'No rider is free right now.'), 'tab' => 'pos'];
        if ($waiting) $out[] = ['level' => 'bad', 'title' => count($waiting) . ' new order(s) waiting more than 5 minutes', 'text' => 'Customers are waiting for acceptance. Open the POS and accept or move them.', 'tab' => 'pos'];
        if ($ready && $free) $out[] = ['level' => 'warn', 'title' => count($ready) . ' order(s) ready and ' . count($free) . ' rider(s) free', 'text' => 'Send them out now: ' . $free[0]['name'] . ' is next in line.', 'tab' => 'pos'];

        $shortages = array_values(array_filter($prep['today'], fn ($p) => $p['short'] > 0));
        if ($shortages) $out[] = ['level' => 'bad', 'title' => count($shortages) . ' product(s) short for today\'s orders', 'text' => implode(', ', array_map(fn ($p) => $p['name'] . ' (need ' . self::q($p['qty']) . ', have ' . self::q($p['live']) . ')', array_slice($shortages, 0, 3))), 'tab' => 'prep'];

        // Run-out forecast: today's selling pace until 9 PM against live stock.
        $hoursGone = max(1.0, (time() - strtotime('today 07:00')) / 3600);
        $hoursLeft = max(0.0, (strtotime('today 21:00') - time()) / 3600);
        if ($hoursLeft > 0.5) {
            $risk = [];
            foreach ($stock as $s) {
                if ($s['sold_today'] <= 0 || $s['live'] <= 0) continue;
                $rate = $s['sold_today'] / $hoursGone;
                $hrs = $s['live'] / $rate;
                if ($hrs < $hoursLeft) $risk[] = $s + ['hours' => $hrs];
            }
            usort($risk, fn ($a, $b) => $a['hours'] <=> $b['hours']);
            if ($risk) $out[] = ['level' => 'warn', 'title' => 'Likely to run out before closing', 'text' => implode(', ', array_map(fn ($s) => $s['name'] . ' (about ' . max(1, (int) round($s['hours'] * 60)) . ' min left at today\'s pace)', array_slice($risk, 0, 3))), 'tab' => 'inventory'];
        }
        $outStock = array_values(array_filter($stock, fn ($s) => $s['status'] === 'out' && $s['active']));
        if ($outStock) $out[] = ['level' => 'warn', 'title' => count($outStock) . ' active product(s) out of stock', 'text' => 'Customers may not be able to order: ' . implode(', ', array_map(fn ($s) => $s['name'], array_slice($outStock, 0, 4))) . (count($outStock) > 4 ? ' and more' : '') . '.', 'tab' => 'inventory'];
        $pend = array_values(array_filter($stock, fn ($s) => $s['pending'] > 0));
        if ($pend) $out[] = ['level' => 'info', 'title' => count($pend) . ' item(s) sent to the hub, not yet received', 'text' => 'Check the delivery and accept it so stock is correct.', 'tab' => 'inward'];

        if ($kpis['with_riders'] > 0) {
            $oldest = null;
            foreach ($riders as $r) if ($r['unsettled'] && $r['oldest'] && $r['oldest'] < PHP_INT_MAX) $oldest = min($oldest ?? PHP_INT_MAX, $r['oldest']);
            $hrs = $oldest ? ($now - $oldest) / 3600 : 0;
            $out[] = ['level' => $hrs >= 2 ? 'warn' : 'info', 'title' => '₹' . number_format($kpis['with_riders'], 0) . ' still with riders', 'text' => $hrs >= 2 ? 'Some money has been with riders for over ' . (int) floor($hrs) . ' hours. Collect it in POS > Riders & cash.' : 'Collect it when riders return (POS > Riders & cash).', 'tab' => 'pos'];
        }
        if ($kpis['cancel_rate'] >= 10 && $kpis['cancelled'] >= 2) $out[] = ['level' => 'warn', 'title' => 'Cancellations at ' . $kpis['cancel_rate'] . '% today', 'text' => 'Check the reasons in Orders (filter: Cancelled).', 'tab' => 'orders'];
        if ($kpis['on_time'] !== null && $kpis['delivered'] >= 3 && $kpis['on_time'] < 80) $out[] = ['level' => 'warn', 'title' => 'On-time delivery at ' . $kpis['on_time'] . '%', 'text' => 'Below the 90% target. Average Express delivery took ' . ($kpis['avg_delivery'] ?? '-') . ' min.', 'tab' => 'pos'];
        if ($kpis['sales_vs'] !== null && $cmp['yest_sales'] > 0 && abs($kpis['sales_vs']) >= 20) $out[] = ['level' => $kpis['sales_vs'] > 0 ? 'ok' : 'info', 'title' => 'Sales ' . ($kpis['sales_vs'] > 0 ? 'up ' : 'down ') . abs($kpis['sales_vs']) . '% vs yesterday', 'text' => 'Compared at the same time of day (₹' . number_format($kpis['sales'], 0) . ' vs ₹' . number_format($cmp['yest_sales'], 0) . ').', 'tab' => 'overview'];
        if (!empty($rep['products'][0])) $out[] = ['level' => 'ok', 'title' => 'Best seller today: ' . $rep['products'][0]['name'], 'text' => self::q($rep['products'][0]['qty']) . ' sold in ' . $rep['products'][0]['orders'] . ' order(s), ₹' . number_format($rep['products'][0]['value'], 0) . '.', 'tab' => 'overview'];
        if ((int) date('G') >= 21 && !$kpis['day_submitted']) $out[] = ['level' => 'warn', 'title' => 'Day-end report not submitted yet', 'text' => 'Count the cash and submit it to head office.', 'tab' => 'report'];
        foreach ($this->extraInsights($stock) as $x) $out[] = $x;
        if (!$out) $out[] = ['level' => 'ok', 'title' => 'All clear', 'text' => 'No late orders, no stock problems and no money waiting. Good work.', 'tab' => 'overview'];
        $rank = ['bad' => 0, 'warn' => 1, 'info' => 2, 'ok' => 3];
        usort($out, fn ($a, $b) => $rank[$a['level']] <=> $rank[$b['level']]);
        return $out;
    }

    private static function q($n): string
    {
        $n = (float) $n;
        return rtrim(rtrim(number_format($n, 2, '.', ''), '0'), '.');
    }

    private ?array $extra = null;

    /** Transfers waiting and cash in hand (phase 2 modules; quiet if they are not installed). */
    private function extraSignals(): array
    {
        if ($this->extra !== null) return $this->extra;
        $t = ['incoming' => 0, 'requests_to_me' => 0, 'my_requests' => 0, 'outgoing' => 0]; $cash = null;
        try { if (class_exists(HubTransferService::class)) $t = (new HubTransferService($this->hubId))->counts(); } catch (\Throwable $e) {}
        try { if (class_exists(HubCashService::class) && HubStock::has('hub_cash_entries')) $cash = (new HubCashService($this->hubId))->inHand(); } catch (\Throwable $e) {}
        return $this->extra = ['transfers' => $t, 'cash' => $cash];
    }

    private function extraInsights(array $stock): array
    {
        $x = $this->extraSignals(); $out = [];
        if ($x['transfers']['incoming']) $out[] = ['level' => 'warn', 'title' => $x['transfers']['incoming'] . ' transfer(s) from other hubs on the way', 'text' => 'When the stock arrives, count it and press Receive in Transfers.', 'tab' => 'transfers'];
        if ($x['transfers']['requests_to_me']) $out[] = ['level' => 'warn', 'title' => $x['transfers']['requests_to_me'] . ' stock request(s) from other hubs', 'text' => 'Send the stock or decline with a reason in Transfers.', 'tab' => 'transfers'];
        $limit = (float) config('hub_console.cash_limit', 20000);
        if ($x['cash'] !== null && $x['cash'] > $limit) $out[] = ['level' => 'warn', 'title' => '₹' . number_format($x['cash'], 0) . ' cash held at the hub', 'text' => 'Above the ₹' . number_format($limit, 0) . ' limit. Deposit it and upload the receipt in Cash book.', 'tab' => 'cash'];
        // Stock age: danger = received yesterday (must sell today), expired = 2+ days old.
        $exp = array_values(array_filter($stock, fn ($s) => ($s['expired'] ?? 0) > 0));
        if ($exp) $out[] = ['level' => 'bad', 'title' => count($exp) . ' product(s) with expired stock (2+ days old)', 'text' => implode(', ', array_map(fn ($s) => $s['name'] . ' (' . self::q($s['expired']) . ')', array_slice($exp, 0, 3))) . '. Move it today or write it off.', 'tab' => 'danger'];
        $dng = array_values(array_filter($stock, fn ($s) => ($s['danger'] ?? 0) > 0));
        usort($dng, fn ($a, $b) => $b['danger'] <=> $a['danger']);
        if ($dng) $out[] = ['level' => 'warn', 'title' => count($dng) . ' product(s) with danger stock to sell today', 'text' => implode(', ', array_map(fn ($s) => $s['name'] . ' (' . self::q($s['danger']) . ')', array_slice($dng, 0, 3))) . '. Sell these first.', 'tab' => 'danger'];
        if (!empty($x['transfers']['planned'])) $out[] = ['level' => 'warn', 'title' => $x['transfers']['planned'] . ' danger-stock transfer(s) planned', 'text' => 'Today\'s ' . config('hub_console.danger_plan_time', '11:00') . ' plan is ready. Assign a rider and dispatch in Transfer danger stock.', 'tab' => 'dplan'];
        // Product data check: pack sizes that cannot be right (e.g. "5 Gram" chicken). They break weighing and prep weights.
        $odd = array_values(array_filter($stock, fn ($s) => $s['active'] && preg_match('/^\s*(\d+(?:\.\d+)?)\s*(g|gm|gms|gram|grams)\s*$/i', $s['pack'], $m) && (float) $m[1] < 50));
        if ($odd) $out[] = ['level' => 'info', 'title' => count($odd) . ' product(s) with a pack size that looks wrong', 'text' => implode(', ', array_map(fn ($s) => $s['name'] . ' (' . $s['pack'] . ')', array_slice($odd, 0, 3))) . '. Ask admin to correct them; weighing uses this size.', 'tab' => 'inventory'];
        return $out;
    }

    /* ------------------------------------------------------------------ locked stock */

    /**
     * Stock tied up in open orders (old "Locked Inventory").
     * New orders reserve stock that is still on the shelf; accepted orders have already taken it (see HubStock).
     */
    public function locked(): array
    {
        $lines = DB::table('orders AS o')->join('products AS p', 'p.id', '=', 'o.product_id')->leftJoin('items AS i', 'i.id', '=', 'p.item_id')
            ->leftJoin('inventory AS inv', function ($j) { $j->on('inv.product_id', '=', 'p.product_id')->where('inv.hub_id', '=', $this->hubId); })
            ->where('o.hub_id', $this->hubId)->whereIn('o.order_status', OrderFlow::statusesFor(['new', 'accepted', 'out']))
            ->where('o.date_added', '>=', date('Y-m-d H:i:s', time() - OrderFlow::STALE_AFTER))
            ->select('o.order_id', 'o.quantity', 'o.order_status', 'o.date_added', 'o.delivery_type', 'o.schedule_date', 'o.schedule_time',
                'p.id AS pid', 'p.product_id AS code', 'p.product_name', 'p.unit_quantity', 'p.product_unit', 'i.item', 'inv.live_stock')
            ->orderBy('o.date_added')->get();
        $by = [];
        foreach ($lines as $l) {
            $k = (int) $l->pid; $st = OrderFlow::stage($l->order_status);
            if (!isset($by[$k])) {
                $uq = (float) $l->unit_quantity;
                $by[$k] = ['id' => $k, 'code' => (string) $l->code, 'name' => trim((string) ($l->item ?: $l->product_name)), 'pack' => trim(($uq ? self::q($uq) : '') . ' ' . $l->product_unit),
                    'live' => (float) ($l->live_stock ?? 0), 'reserved' => 0.0, 'packed' => 0.0, 'out' => 0.0, 'orders' => []];
            }
            $q = (float) $l->quantity;
            if ($st === 'new') $by[$k]['reserved'] += $q; elseif ($st === 'accepted') $by[$k]['packed'] += $q; else $by[$k]['out'] += $q;
            $by[$k]['orders'][] = ['order_id' => $l->order_id, 'qty' => $q, 'stage' => $st, 'status' => $l->order_status, 'at' => self::ts($l->date_added),
                'slot' => strtolower((string) $l->delivery_type) === 'scheduled' ? trim((OrderFlow::normDate($l->schedule_date) ?? '') . ' ' . $l->schedule_time) : 'Express'];
        }
        $rows = array_values($by);
        foreach ($rows as &$r) { $r['available'] = round($r['live'] - $r['reserved'], 3); $r['short'] = max(0, round($r['reserved'] - max(0, $r['live']), 3)); }
        unset($r);
        usort($rows, fn ($a, $b) => [$b['short'] > 0, $b['reserved'] + $b['packed']] <=> [$a['short'] > 0, $a['reserved'] + $a['packed']]);
        return ['ok' => true, 'rows' => $rows, 'deducts' => HubStock::enabled()];
    }

    /* ------------------------------------------------------------------ stock ledger */

    /** Every stock change of this hub (old "Hub Inward/Outward Transactions"). */
    public function ledger(string $from, string $to, string $type, string $q): array
    {
        if (!self::table('hub_stock_moves')) return ['ok' => true, 'moves' => [], 'summary' => []];
        $m = DB::table('hub_stock_moves AS m')->where('m.hub_id', $this->hubId)->whereBetween('m.created_at', [$from . ' 00:00:00', $to . ' 23:59:59']);
        $groups = ['in' => ['inward', 'transfer_in', 'return', 'transfer_back'], 'out' => ['sale', 'transfer_out'], 'count' => ['count']];
        if (isset($groups[$type])) $m->whereIn('m.type', $groups[$type]); elseif ($type !== '') $m->where('m.type', $type);
        $rows = $m->orderByDesc('m.id')->limit(2000)->get();
        $codes = $rows->pluck('product_code')->unique()->all();
        $names = [];
        if ($codes) {
            $pq = DB::table('products AS p')->leftJoin('items AS i', 'i.id', '=', 'p.item_id')->whereIn('p.product_id', $codes)->get(['p.product_id', 'p.product_name', 'i.item']);
            foreach ($pq as $p) $names[(string) $p->product_id] = trim((string) ($p->item ?: $p->product_name));
        }
        $term = mb_strtolower(trim($q)); $out = []; $sum = [];
        foreach ($rows as $r) {
            $name = $names[(string) $r->product_code] ?? (string) $r->product_code;
            if ($term !== '' && !str_contains(mb_strtolower($name . ' ' . $r->product_code . ' ' . ($r->ref ?? '') . ' ' . $r->note), $term)) continue;
            $out[] = ['id' => (int) $r->id, 'at' => self::ts($r->created_at), 'code' => (string) $r->product_code, 'name' => $name, 'type' => (string) $r->type,
                'before' => (float) $r->before_qty, 'change' => (float) $r->change_qty, 'after' => (float) $r->after_qty, 'expected' => $r->expected_qty !== null ? (float) $r->expected_qty : null,
                'ref' => $r->ref ?? null, 'note' => $r->note, 'by' => $r->by_name];
            $sum[$r->type] = round(($sum[$r->type] ?? 0) + (float) $r->change_qty, 3);
        }
        return ['ok' => true, 'moves' => array_slice($out, 0, 1000), 'summary' => $sum, 'count' => count($out)];
    }

    /** Receive many inward lines at once (old Excel upload). Each line is checked like a single receive; all or nothing. */
    public function acceptMany(array $lines, array $actor): array
    {
        $lines = array_slice($lines, 0, 300);
        if (!$lines) return ['ok' => false, 'message' => 'Nothing to receive.'];
        $done = 0; $errors = [];
        foreach ($lines as $l) {
            $r = $this->acceptInward((int) ($l['inv_id'] ?? 0), (float) ($l['received'] ?? -1), (string) ($l['note'] ?? ''), $actor);
            if ($r['ok']) $done++; else $errors[] = $r['message'];
        }
        return ['ok' => $done > 0, 'done' => $done, 'errors' => array_slice($errors, 0, 10),
            'message' => $done . ' product(s) received.' . ($errors ? ' ' . count($errors) . ' not received: ' . $errors[0] : '')];
    }

    /* ------------------------------------------------------------------ prep list */

    /** What to cut and pack: open orders (new / accepted) for today and tomorrow, per product. */
    public function prep(): array
    {
        $today = date('Y-m-d'); $tomorrow = date('Y-m-d', strtotime('+1 day'));
        $q = DB::table('orders AS o')->join('products AS p', 'p.id', '=', 'o.product_id')->leftJoin('items AS i', 'i.id', '=', 'p.item_id')
            ->leftJoin('inventory AS inv', function ($j) { $j->on('inv.product_id', '=', 'p.product_id')->where('inv.hub_id', '=', $this->hubId); })
            ->where('o.hub_id', $this->hubId)->whereIn('o.order_status', OrderFlow::statusesFor(['new', 'accepted']))
            ->where('o.date_added', '>=', date('Y-m-d H:i:s', strtotime('-3 days')))
            ->select('o.order_id', 'o.quantity', 'o.delivery_type', 'o.schedule_date', 'o.schedule_time', 'o.order_status',
                'p.id AS pid', 'p.product_id AS code', 'p.product_name', 'p.product_unit', 'p.unit_quantity', 'p.product_image', 'i.item', 'inv.live_stock');
        $groups = ['today' => [], 'tomorrow' => []];
        foreach ($q->get() as $l) {
            $sched = strtolower((string) $l->delivery_type) === 'scheduled';
            $d = $sched ? (OrderFlow::normDate($l->schedule_date) ?? $today) : $today;
            $bucket = $d <= $today ? 'today' : ($d === $tomorrow ? 'tomorrow' : null);
            if (!$bucket) continue;
            $key = (int) $l->pid;
            $packG = OrderFlow::packGrams($l->unit_quantity, $l->product_unit);
            if (!isset($groups[$bucket][$key])) {
                $groups[$bucket][$key] = ['code' => (string) $l->code, 'name' => trim((string) ($l->item ?: $l->product_name)),
                    'pack' => trim(((float) $l->unit_quantity ? rtrim(rtrim(number_format((float) $l->unit_quantity, 3, '.', ''), '0'), '.') : '') . ' ' . $l->product_unit),
                    'pack_g' => $packG, 'qty' => 0.0, 'grams' => 0.0, 'orders' => [], 'slots' => [], 'live' => (float) ($l->live_stock ?? 0),
                    'image' => $l->product_image ? url('uploads/images/products/' . $l->product_image) : null];
            }
            $g = &$groups[$bucket][$key];
            $g['qty'] += (float) $l->quantity;
            if ($packG) $g['grams'] += $packG * (float) $l->quantity;
            $g['orders'][$l->order_id] = true;
            $slot = $sched ? (trim((string) $l->schedule_time) ?: 'Scheduled') : 'Express';
            $g['slots'][$slot] = ($g['slots'][$slot] ?? 0) + (float) $l->quantity;
            unset($g);
        }
        $fin = function (array $list) {
            $out = [];
            foreach ($list as $g) {
                $g['orders'] = count($g['orders']);
                $g['short'] = max(0, $g['qty'] - max(0, $g['live']));
                $g['qty'] = round($g['qty'], 3); $g['grams'] = round($g['grams']);
                ksort($g['slots']);
                $out[] = $g;
            }
            usort($out, fn ($a, $b) => [$b['short'] > 0, $b['qty']] <=> [$a['short'] > 0, $a['qty']]);
            return $out;
        };
        return ['ok' => true, 'today' => $fin($groups['today']), 'tomorrow' => $fin($groups['tomorrow']), 'generated_at' => date('Y-m-d H:i:s')];
    }

    /* ------------------------------------------------------------------ inventory */

    /** Every product with this hub's stock, what open orders need, what sold today and a status. */
    public function stockRows(): array
    {
        $lowAt = (float) config('hub_console.low_stock', 5);
        $cat = self::table('category') ? 'category' : null;
        $q = DB::table('products AS p')->leftJoin('items AS i', 'i.id', '=', 'p.item_id')
            ->leftJoin('inventory AS inv', function ($j) { $j->on('inv.product_id', '=', 'p.product_id')->where('inv.hub_id', '=', $this->hubId); });
        // Category: the old panel reads it through items.cat_id; products.category_id is the fallback.
        $catSql = DB::raw('NULL AS category');
        if ($cat) {
            $viaItem = self::has('items', 'cat_id'); $viaProduct = self::has('products', 'category_id');
            if ($viaItem) $q->leftJoin('category AS c', 'c.id', '=', 'i.cat_id');
            if ($viaProduct) $q->leftJoin('category AS c2', 'c2.id', '=', 'p.category_id');
            if ($viaItem && $viaProduct) $catSql = DB::raw('COALESCE(c.name, c2.name) AS category');
            elseif ($viaItem) $catSql = 'c.name AS category';
            elseif ($viaProduct) $catSql = 'c2.name AS category';
        }
        $q->select('p.id', 'p.product_id AS code', 'p.product_name', 'p.product_unit', 'p.unit_quantity', 'p.main_price', 'p.product_image', 'p.status AS pstatus', 'i.item',
            'inv.id AS inv_id', 'inv.stock', 'inv.live_stock', 'inv.fresh_stock', 'inv.variance', 'inv.updated_at AS inv_updated', $catSql);
        $rows = $q->orderBy('p.id')->get();

        $need = DB::table('orders')->where('hub_id', $this->hubId)->whereIn('order_status', OrderFlow::statusesFor(['new', 'accepted']))
            ->where('date_added', '>=', date('Y-m-d H:i:s', strtotime('-3 days')))->groupBy('product_id')->select('product_id', DB::raw('SUM(quantity) AS q'))->pluck('q', 'product_id');
        $sold = DB::table('orders')->where('hub_id', $this->hubId)->where('date_added', '>=', date('Y-m-d 00:00:00'))
            ->whereNotIn('order_status', OrderFlow::statusesFor(['cancelled']))->groupBy('product_id')->select('product_id', DB::raw('SUM(quantity) AS q'))->pluck('q', 'product_id');

        $ages = HubStock::ages($this->hubId);
        $out = [];
        foreach ($rows as $r) {
            $active = in_array(strtolower((string) $r->pstatus), ['active', '1'], true);
            $ag = $ages[(string) $r->code] ?? null;
            if (!$active && !$r->inv_id) continue;
            $live = (float) ($r->live_stock ?? 0);
            $needQ = (float) ($need[$r->id] ?? 0);
            $status = !$r->inv_id ? 'none' : ($live <= 0 ? 'out' : ($live <= max($lowAt, $needQ) ? 'low' : 'ok'));
            $out[] = [
                'id' => (int) $r->id, 'inv_id' => $r->inv_id ? (int) $r->inv_id : null, 'code' => (string) $r->code,
                'name' => trim((string) ($r->item ?: $r->product_name)), 'category' => (string) ($r->category ?? ''),
                'pack' => trim(((float) $r->unit_quantity ? rtrim(rtrim(number_format((float) $r->unit_quantity, 3, '.', ''), '0'), '.') : '') . ' ' . $r->product_unit),
                'price' => (float) $r->main_price, 'image' => $r->product_image ? url('uploads/images/products/' . $r->product_image) : null,
                'active' => $active, 'live' => $live,
                'fresh' => $ag ? $ag['fresh'] : ($ages ? 0.0 : (float) ($r->fresh_stock ?? 0)),
                'danger' => $ag ? $ag['danger'] : ($ages ? 0.0 : max(0, $live - (float) ($r->fresh_stock ?? 0))),
                'expired' => $ag ? $ag['expired'] : 0.0, 'oldest' => $ag['oldest'] ?? null,
                'old' => $ag ? round($ag['danger'] + $ag['expired'], 3) : ($ages ? 0.0 : max(0, $live - (float) ($r->fresh_stock ?? 0))),
                'pending' => (float) ($r->stock ?? 0), 'variance' => (float) ($r->variance ?? 0), 'needed' => $needQ, 'short' => max(0, $needQ - max(0, $live)),
                'sold_today' => (float) ($sold[$r->id] ?? 0), 'status' => $status, 'updated' => self::ts($r->inv_updated),
            ];
        }
        return $out;
    }

    public function inventory(): array
    {
        $rows = $this->stockRows();
        $moves = self::table('hub_stock_moves') ? DB::table('hub_stock_moves')->where('hub_id', $this->hubId)->orderByDesc('id')->limit(60)->get()->map(fn ($m) => (array) $m)->all() : [];
        return ['ok' => true, 'rows' => $rows, 'moves' => $moves, 'low_at' => (float) config('hub_console.low_stock', 5)];
    }

    private function move(string $code, string $type, float $before, float $after, ?float $expected, string $note, array $actor): void
    {
        if (!self::table('hub_stock_moves')) return;
        DB::table('hub_stock_moves')->insert([
            'hub_id' => $this->hubId, 'product_code' => mb_substr($code, 0, 40), 'type' => $type, 'before_qty' => $before, 'change_qty' => round($after - $before, 3),
            'after_qty' => $after, 'expected_qty' => $expected, 'note' => $note !== '' ? mb_substr($note, 0, 250) : null,
            'by_id' => $actor['id'] ?? null, 'by_name' => mb_substr((string) ($actor['name'] ?? ''), 0, 120), 'created_at' => now(),
        ]);
    }

    /** Accept stock sent to the hub. $received may differ from what was sent; the difference is recorded as variance. */
    public function acceptInward(int $invId, float $received, string $note, array $actor): array
    {
        $res = DB::transaction(function () use ($invId, $received, $note, $actor) {
            $row = DB::table('inventory')->where('id', $invId)->where('hub_id', $this->hubId)->lockForUpdate()->first();
            if (!$row) return ['ok' => false, 'message' => 'Stock line not found in this hub.'];
            $sent = (float) $row->stock;
            if ($sent <= 0) return ['ok' => false, 'message' => 'Nothing is waiting to be received for this product.'];
            if ($received < 0 || $received > $sent * 3 + 50) return ['ok' => false, 'message' => 'The received quantity looks wrong. Please check.'];
            $variance = round($received - $sent, 3);
            if (abs($variance) > 0.0001 && trim($note) === '') return ['ok' => false, 'message' => 'Received ' . self::q($received) . ' but ' . self::q($sent) . ' was sent. Write a note about the difference.'];
            $before = (float) $row->live_stock;
            // Clear the pending quantity and record the difference, then add what really arrived as today's batch (fresh).
            $upd = ['stock' => 0, 'variance' => $variance];
            if (self::has('inventory', 'updated_at')) $upd['updated_at'] = now();
            DB::table('inventory')->where('id', $invId)->update($upd);
            HubStock::adjust($this->hubId, (string) $row->product_id, $received, 'inward', null, $note, $actor, $sent);
            return ['ok' => true, 'message' => 'Received ' . self::q($received) . ($variance ? ' (' . ($variance > 0 ? '+' : '') . self::q($variance) . ' vs sent)' : '') . '. Live stock is now ' . self::q($before + $received) . '.'];
        });
        if ($res['ok']) Log::info('[freshful-audit] hub-inward', ['hub' => $this->hubId, 'inv' => $invId, 'received' => $received, 'by' => $actor['id'] ?? null]);
        return $res;
    }

    /** Physical stock count: set live stock to what is really on the shelf; the change is logged. */
    public function stockCount(int $productId, float $counted, string $note, array $actor): array
    {
        if ($counted < 0 || $counted > 100000) return ['ok' => false, 'message' => 'Enter a valid count.'];
        $p = DB::table('products')->where('id', $productId)->first(['id', 'product_id']);
        if (!$p || !$p->product_id) return ['ok' => false, 'message' => 'Product not found.'];
        $res = DB::transaction(function () use ($p, $counted, $note, $actor) {
            $before = (float) (DB::table('inventory')->where('product_id', $p->product_id)->where('hub_id', $this->hubId)->lockForUpdate()->value('live_stock') ?? 0);
            // Shortage comes off the oldest batches; extra found on the shelf is recorded as arriving today.
            HubStock::adjust($this->hubId, (string) $p->product_id, round($counted - $before, 3), 'count', null, $note, $actor);
            $diff = $counted - $before;
            return ['ok' => true, 'message' => 'Stock set to ' . self::q($counted) . ($diff ? ' (' . ($diff > 0 ? '+' : '') . self::q($diff) . ')' : ' (no change)') . '.'];
        });
        if ($res['ok']) Log::info('[freshful-audit] hub-count', ['hub' => $this->hubId, 'product' => $productId, 'counted' => $counted, 'by' => $actor['id'] ?? null]);
        return $res;
    }

    /* ------------------------------------------------------------------ grievances */

    /**
     * Customer grievances of this hub, read from the existing grievance table used by the role panels.
     * The table layout is not documented, so it is detected: it must link to this hub (hub_id) or to an order of this hub
     * (order_id). Anything else returns available=false with the reason, never data from other hubs.
     */
    private ?array $gv = null;

    private function grievanceSource(): array
    {
        if ($this->gv !== null) return $this->gv;
        $t = null;
        foreach (['grievance', 'grievances', 'order_grievance', 'order_grievances', 'customer_grievance'] as $c) if (self::table($c)) { $t = $c; break; }
        if (!$t) return $this->gv = ['ok' => false, 'reason' => 'No grievance table was found in the database.'];
        $pick = function (array $cands) use ($t) { foreach ($cands as $c) if (self::has($t, $c)) return $c; return null; };
        $src = ['ok' => true, 'table' => $t,
            'hub' => $pick(['hub_id']), 'order' => $pick(['order_id', 'orderid']), 'date' => $pick(['created_at', 'date_added', 'date', 'added_on']),
            'status' => $pick(['status', 'grievance_status', 'state']), 'text' => $pick(['complaint_details', 'description', 'message', 'comment', 'comments', 'remarks', 'grievance', 'details', 'issue', 'reason']),
            'cat' => $pick(['grievance_category_id', 'category_id', 'grievance_category', 'category', 'type']), 'buyer' => $pick(['buyer_id', 'user_id', 'customer_id'])];
        if (!$src['hub'] && !$src['order']) return $this->gv = ['ok' => false, 'reason' => 'The grievance table (' . $t . ') has no hub or order column, so it cannot be limited to this hub.'];
        if (!$src['date']) return $this->gv = ['ok' => false, 'reason' => 'The grievance table (' . $t . ') has no date column.'];
        $src['cat_table'] = null;
        if ($src['cat'] && str_ends_with($src['cat'], '_id')) foreach (['grievance_category', 'grievance_categories'] as $c) if (self::table($c)) { $src['cat_table'] = $c; break; }
        return $this->gv = $src;
    }

    private function grievanceQuery(array $g)
    {
        $q = DB::table($g['table'] . ' AS g');
        if ($g['hub']) $q->where('g.' . $g['hub'], $this->hubId);
        else $q->whereIn('g.' . $g['order'], DB::table('orders')->where('hub_id', $this->hubId)->select('order_id'));
        return $q;
    }

    private static function isOpenStatus($v): bool
    {
        $v = strtolower(trim((string) $v));
        return !in_array($v, ['closed', 'resolved', 'solved', 'done', 'completed', 'inactive', 'rejected'], true);
    }

    public function grievanceSummary(): array
    {
        $g = $this->grievanceSource();
        if (!$g['ok']) return ['available' => false, 'reason' => $g['reason']];
        try {
            $rows = $this->grievanceQuery($g)->where('g.' . $g['date'], '>=', date('Y-m-d 00:00:00', strtotime('-29 days')))
                ->get(array_filter(['g.' . $g['date'] . ' AS d', $g['status'] ? 'g.' . $g['status'] . ' AS s' : null]));
        } catch (\Throwable $e) { return ['available' => false, 'reason' => 'Could not read grievances.']; }
        $today = 0; $open = 0; $t0 = strtotime('today');
        foreach ($rows as $r) { if (self::ts($r->d) >= $t0) $today++; if (!$g['status'] || self::isOpenStatus($r->s ?? '')) $open++; }
        return ['available' => true, 'today' => $today, 'last30' => count($rows), 'open' => $g['status'] ? $open : null];
    }

    public function grievances(string $from, string $to): array
    {
        $g = $this->grievanceSource();
        if (!$g['ok']) return ['ok' => true, 'available' => false, 'reason' => $g['reason'], 'rows' => []];
        $q = $this->grievanceQuery($g)->whereBetween('g.' . $g['date'], [$from . ' 00:00:00', $to . ' 23:59:59'])->orderByDesc('g.' . $g['date'])->limit(500);
        $cols = ['g.id', 'g.' . $g['date'] . ' AS d'];
        foreach (['order' => 'o', 'status' => 's', 'text' => 'x', 'buyer' => 'b', 'cat' => 'c'] as $k => $a) if ($g[$k]) $cols[] = 'g.' . $g[$k] . ' AS ' . $a;
        if ($g['cat_table']) {
            $nameCol = self::has($g['cat_table'], 'name') ? 'name' : (self::has($g['cat_table'], 'title') ? 'title' : (self::has($g['cat_table'], 'category') ? 'category' : null));
            if ($nameCol) { $q->leftJoin($g['cat_table'] . ' AS gc', 'gc.id', '=', 'g.' . $g['cat']); $cols[] = 'gc.' . $nameCol . ' AS cn'; }
        }
        try { $rows = $q->get($cols); } catch (\Throwable $e) { return ['ok' => true, 'available' => false, 'reason' => 'Could not read grievances.', 'rows' => []]; }
        $buyers = [];
        if ($g['buyer']) { $ids = $rows->pluck('b')->filter()->unique()->all(); if ($ids) $buyers = DB::table('buyers')->whereIn('id', $ids)->pluck('name', 'id')->all(); }
        $out = [];
        foreach ($rows as $r) {
            $out[] = ['id' => (int) $r->id, 'at' => self::ts($r->d), 'order_id' => isset($r->o) ? (string) $r->o : '', 'status' => isset($r->s) ? (string) $r->s : '',
                'open' => isset($r->s) ? self::isOpenStatus($r->s) : null, 'text' => isset($r->x) ? mb_substr(strip_tags((string) $r->x), 0, 400) : '',
                'category' => isset($r->cn) ? (string) $r->cn : (isset($r->c) && !is_numeric($r->c) ? (string) $r->c : ''), 'customer' => isset($r->b) ? (string) ($buyers[$r->b] ?? '') : ''];
        }
        return ['ok' => true, 'available' => true, 'rows' => $out, 'from' => $from, 'to' => $to];
    }

    /* ------------------------------------------------------------------ orders */

    public function orders(string $from, string $to, string $stage, string $type, string $pay, string $q, string $sched = '', string $status = ''): array
    {
        $okStatus = ['Order Pending', 'Order Placed', 'Order Accepted', 'Order Processed', 'Order Shipped', 'Order Dispatched', 'Order Delivered', 'Order Cancel'];
        if (!in_array($status, $okStatus, true)) $status = '';
        if (in_array($sched, ['today', 'tomorrow', 'upcoming'], true) && trim($q) === '') {
            // Scheduled FOR a day (old "Scheduled orders" page): look back 30 days of placed orders, filter by delivery date.
            $day = $sched === 'tomorrow' ? date('Y-m-d', strtotime('+1 day')) : date('Y-m-d');
            $list = array_values(array_filter($this->pos->history(date('Y-m-d', strtotime('-30 days')), date('Y-m-d'), $stage),
                fn ($o) => strcasecmp($o['delivery_type'], 'Scheduled') === 0 && $o['schedule_date'] !== '' && ($sched === 'upcoming' ? $o['schedule_date'] >= date('Y-m-d') : $o['schedule_date'] === $day)));
            usort($list, fn ($a, $b) => [$a['schedule_date'], $a['slot']] <=> [$b['schedule_date'], $b['slot']]);
            $type = '';
        } else {
            // 'done' = delivered (cash to settle) + settled, the old dashboard's "Delivered" box.
            $list = trim($q) !== '' ? $this->pos->search($q) : $this->pos->history($from, $to, $stage === 'done' ? '' : $stage);
            if ($stage === 'done') $list = array_values(array_filter($list, fn ($o) => in_array($o['stage'], ['unsettled', 'delivered'], true)));
        }
        $list = array_values(array_filter($list, function ($o) use ($type, $pay, $status) {
            if ($status !== '' && !($o['status'] === $status || ($status === 'Order Placed' && $o['status'] === 'Order Accepted'))) return false;
            if ($type !== '' && strcasecmp($o['delivery_type'], $type) !== 0) return false;
            if ($pay !== '' && strcasecmp($o['payment_type'], $pay) !== 0) return false;
            return true;
        }));
        $sum = ['orders' => count($list), 'value' => 0.0, 'cancelled' => 0];
        foreach ($list as $o) { if ($o['stage'] === 'cancelled') $sum['cancelled']++; else $sum['value'] += $o['amount']; }
        $sum['value'] = round($sum['value'], 2);
        return ['ok' => true, 'orders' => $list, 'summary' => $sum];
    }
}
