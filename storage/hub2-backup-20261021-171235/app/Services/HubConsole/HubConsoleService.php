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
        $inv = ['out' => 0, 'low' => 0, 'short' => 0, 'pending_items' => 0, 'pending_qty' => 0.0, 'value' => 0.0];
        foreach ($stock as $s) {
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

        return [
            'ok' => true, 'server_time' => $now, 'hub' => $this->hub(),
            'kpis' => $kpis, 'pipeline' => $pipeline, 'days' => array_values($days),
            'hours' => ['today' => $hourToday, 'yesterday' => $hourYest, 'now' => (int) date('G')],
            'riders' => $riders, 'inventory' => $inv, 'top_products' => array_slice($rep['products'] ?? [], 0, 5),
            'insights' => $this->insights($feed, $rep, $kpis, $stock, $prep, $riders, $cmp),
            'late' => array_values(array_slice(array_filter($feed['orders'], fn ($o) => $o['late']), 0, 6)),
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
        if ($cat) $q->leftJoin('category AS c', 'c.id', '=', 'p.category_id');
        $q->select('p.id', 'p.product_id AS code', 'p.product_name', 'p.product_unit', 'p.unit_quantity', 'p.main_price', 'p.product_image', 'p.status AS pstatus', 'i.item',
            'inv.id AS inv_id', 'inv.stock', 'inv.live_stock', 'inv.fresh_stock', 'inv.variance', 'inv.updated_at AS inv_updated', $cat ? 'c.name AS category' : DB::raw('NULL AS category'));
        $rows = $q->orderBy('p.id')->get();

        $need = DB::table('orders')->where('hub_id', $this->hubId)->whereIn('order_status', OrderFlow::statusesFor(['new', 'accepted']))
            ->where('date_added', '>=', date('Y-m-d H:i:s', strtotime('-3 days')))->groupBy('product_id')->select('product_id', DB::raw('SUM(quantity) AS q'))->pluck('q', 'product_id');
        $sold = DB::table('orders')->where('hub_id', $this->hubId)->where('date_added', '>=', date('Y-m-d 00:00:00'))
            ->whereNotIn('order_status', OrderFlow::statusesFor(['cancelled']))->groupBy('product_id')->select('product_id', DB::raw('SUM(quantity) AS q'))->pluck('q', 'product_id');

        $out = [];
        foreach ($rows as $r) {
            $active = in_array(strtolower((string) $r->pstatus), ['active', '1'], true);
            if (!$active && !$r->inv_id) continue;
            $live = (float) ($r->live_stock ?? 0);
            $needQ = (float) ($need[$r->id] ?? 0);
            $status = !$r->inv_id ? 'none' : ($live <= 0 ? 'out' : ($live <= max($lowAt, $needQ) ? 'low' : 'ok'));
            $out[] = [
                'id' => (int) $r->id, 'inv_id' => $r->inv_id ? (int) $r->inv_id : null, 'code' => (string) $r->code,
                'name' => trim((string) ($r->item ?: $r->product_name)), 'category' => (string) ($r->category ?? ''),
                'pack' => trim(((float) $r->unit_quantity ? rtrim(rtrim(number_format((float) $r->unit_quantity, 3, '.', ''), '0'), '.') : '') . ' ' . $r->product_unit),
                'price' => (float) $r->main_price, 'image' => $r->product_image ? url('uploads/images/products/' . $r->product_image) : null,
                'active' => $active, 'live' => $live, 'fresh' => (float) ($r->fresh_stock ?? 0), 'old' => max(0, $live - (float) ($r->fresh_stock ?? 0)),
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
            $upd = ['live_stock' => $before + $received, 'fresh_stock' => (float) $row->fresh_stock + $received, 'stock' => 0, 'variance' => $variance];
            if (self::has('inventory', 'updated_at')) $upd['updated_at'] = now();
            DB::table('inventory')->where('id', $invId)->update($upd);
            $this->move((string) $row->product_id, 'inward', $before, $before + $received, $sent, $note, $actor);
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
            $row = DB::table('inventory')->where('product_id', $p->product_id)->where('hub_id', $this->hubId)->lockForUpdate()->first();
            $before = $row ? (float) $row->live_stock : 0.0;
            if ($row) {
                $upd = ['live_stock' => $counted, 'fresh_stock' => min((float) $row->fresh_stock, $counted)];
                if (self::has('inventory', 'updated_at')) $upd['updated_at'] = now();
                DB::table('inventory')->where('id', $row->id)->update($upd);
            } else {
                $ins = ['product_id' => $p->product_id, 'hub_id' => $this->hubId, 'stock' => 0, 'live_stock' => $counted, 'fresh_stock' => $counted, 'variance' => 0];
                if (self::has('inventory', 'created_at')) { $ins['created_at'] = now(); $ins['updated_at'] = now(); }
                DB::table('inventory')->insert($ins);
            }
            $this->move((string) $p->product_id, 'count', $before, $counted, null, $note, $actor);
            $diff = $counted - $before;
            return ['ok' => true, 'message' => 'Stock set to ' . self::q($counted) . ($diff ? ' (' . ($diff > 0 ? '+' : '') . self::q($diff) . ')' : ' (no change)') . '.'];
        });
        if ($res['ok']) Log::info('[freshful-audit] hub-count', ['hub' => $this->hubId, 'product' => $productId, 'counted' => $counted, 'by' => $actor['id'] ?? null]);
        return $res;
    }

    /* ------------------------------------------------------------------ orders */

    public function orders(string $from, string $to, string $stage, string $type, string $pay, string $q): array
    {
        $list = trim($q) !== '' ? $this->pos->search($q) : $this->pos->history($from, $to, $stage);
        $list = array_values(array_filter($list, function ($o) use ($type, $pay) {
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
