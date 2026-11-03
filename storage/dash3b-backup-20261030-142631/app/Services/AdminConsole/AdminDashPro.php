<?php

namespace App\Services\AdminConsole;

use App\Services\CountryConsole\CountryAnalytics;
use App\Services\CountryConsole\CountryScope;
use App\Services\PosConsole\OrderFlow;
use Illuminate\Support\Facades\DB;

/**
 * Freshful admin console (dashboard 2, 30 Oct 2026): the full business dashboard (sales, growth, cash with riders,
 * delivery performance, hubs, payments, hours, customers, products) plus the hub / delivery boy detail used by the live map.
 * Read only. Order rows are one per product line, so everything is grouped by order_id first.
 */
class AdminDashPro
{
    private static function h(string $t, ?string $c = null): bool { return CountryScope::has($t, $c); }

    private static function mx(string $col, string $alias, bool $num = false)
    {
        return self::h('orders', $col) ? DB::raw("MAX($col) AS $alias") : DB::raw(($num ? '0' : 'NULL') . " AS $alias");
    }

    /** One row per order for these hubs from $fromTs on. */
    private static function orderRows(array $ids, int $fromTs, string $toStr): array
    {
        $out = [];
        $sel = [
            'order_id', DB::raw('MAX(hub_id) AS hub'), DB::raw('MAX(order_status) AS st'), DB::raw('MAX(date_added) AS at'),
            DB::raw('MAX(total_amount) AS t'), DB::raw('MAX(delivery_charge) AS d'),
            self::mx('amount_to_collect', 'c', true), self::mx('amount_to_return', 'rt', true),
            self::mx('payment_type', 'pt'), self::mx('delivery_type', 'dty'), self::mx('buyer_id', 'buyer'), self::mx('rider_id', 'rider', true),
            self::mx('delivery_complete_time', 'dc'), self::mx('schedule_date', 'sd'), self::mx('schedule_time', 'stime'), self::mx('reason_for_order_cancel', 'rsn'),
        ];
        foreach (array_chunk($ids ?: [0], 500) as $chunk) {
            foreach (DB::table('orders')->whereIn('hub_id', $chunk)->where('date_added', '>=', date('Y-m-d H:i:s', $fromTs))->where('date_added', '<=', $toStr)->groupBy('order_id')->select($sel)->get() as $o) {
                $out[] = $o;
            }
        }
        return $out;
    }

    public static function data(CountryScope $scope, string $from, string $to): array
    {
        $ids = $scope->ids(); $hubs = CountryScope::hubs();
        $f = strtotime($from . ' 00:00:00'); $t = strtotime($to . ' 23:59:59');
        $len = max(86400, $t - $f + 1);
        $pf = $f - $len; $pt = $f - 1;
        $y0 = strtotime(date('Y-m-01', strtotime('-11 months')));
        $d30 = strtotime(date('Y-m-d', strtotime('-29 days')));
        $minTs = min($f, $pf, $y0, $d30);
        $now = time(); $todayStart = strtotime(date('Y-m-d'));
        $rows = self::orderRows($ids, $minTs, date('Y-m-d H:i:s', max($t, $now)));

        $blank = fn () => ['orders' => 0, 'sales' => 0.0, 'delivered' => 0, 'cancelled' => 0, 'settled' => 0.0, 'unsettled' => 0.0, 'fees' => 0.0, 'gross' => 0.0, 'new' => 0, 'ongoing' => 0];
        $R = $blank(); $P = $blank();
        $months = []; for ($i = 11; $i >= 0; $i--) { $k = date('Y-m', strtotime(date('Y-m-01') . " -$i months")); $months[$k] = ['m' => date('M y', strtotime($k . '-01')), 'orders' => 0, 'sales' => 0.0, 'settled' => 0.0, 'cancelled' => 0]; }
        $days = []; for ($i = 29; $i >= 0; $i--) { $k = date('Y-m-d', strtotime("-$i days")); $days[$k] = ['d' => $k, 'orders' => 0, 'sales' => 0.0]; }
        $byHub = []; $pay = []; $types = []; $hours = array_fill(0, 24, 0); $reasons = []; $status = ['new' => 0, 'accepted' => 0, 'out' => 0, 'unsettled' => 0, 'delivered' => 0, 'cancelled' => 0];
        $onTime = 0; $timed = 0; $mins = 0.0; $buyers = []; $open = ['new' => 0, 'accepted' => 0, 'out' => 0, 'late' => 0, 'delivered_today' => 0];
        $riderCash = []; $riderToday = [];
        $mStart = strtotime(date('Y-m-01')); $mon = ['orders' => 0, 'sales' => 0.0, 'delivered' => 0, 'express' => 0, 'scheduled' => 0, 'cancelled' => 0]; $topR = [];

        $acc = function (array &$X, string $stage, float $bill, float $value, bool $done, float $fee) {
            if ($stage === 'cancelled') { $X['cancelled']++; return; }
            $X['orders']++; $X['gross'] += $bill;
            if ($stage === 'new') $X['new']++; elseif (in_array($stage, ['accepted', 'out'], true)) $X['ongoing']++;
            if ($done) { $X['delivered']++; $X['sales'] += $value; $X['fees'] += $fee; }
            if ($stage === 'delivered') $X['settled'] += $value;
            if ($stage === 'unsettled') $X['unsettled'] += $value;
        };
        foreach ($rows as $o) {
            $at = strtotime((string) $o->at); if (!$at) continue;
            $stage = OrderFlow::stage($o->st);
            $bill = (float) $o->t + (float) $o->d; $adj = (float) $o->c - (float) $o->rt;
            $done = in_array($stage, ['delivered', 'unsettled'], true);
            $value = $done ? $bill + $adj : 0.0;
            $due = OrderFlow::dueAt((string) $o->dty, $o->at, $o->sd, $o->stime);
            $dc = CountryScope::ts($o->dc);

            // open right now (any date in the window)
            if (in_array($stage, ['new', 'accepted', 'out'], true) && !OrderFlow::isStale($stage, $due, $at, $now)) {
                $open[$stage]++;
                if ($due && $due < $now) $open['late']++;
            }
            if ($stage === 'unsettled') {
                $rid = (int) $o->rider;
                $riderCash[$rid] = $riderCash[$rid] ?? ['n' => 0, 'amt' => 0.0, 'oldest' => $at];
                $riderCash[$rid]['n']++; $riderCash[$rid]['amt'] += OrderFlow::expectedCollection($o->pt, $bill, $adj); $riderCash[$rid]['oldest'] = min($riderCash[$rid]['oldest'], $at);
            }
            if ($done && $dc && $dc >= $todayStart) {
                $open['delivered_today']++;
                $rid = (int) $o->rider; if ($rid) $riderToday[$rid] = ($riderToday[$rid] ?? 0) + 1;
            }

            $mk = date('Y-m', $at);
            if (isset($months[$mk])) {
                if ($stage === 'cancelled') $months[$mk]['cancelled']++;
                else { $months[$mk]['orders']++; $months[$mk]['sales'] += $value; if ($stage === 'delivered') $months[$mk]['settled'] += $value; }
            }
            $dk = date('Y-m-d', $at);
            if (isset($days[$dk]) && $stage !== 'cancelled') { $days[$dk]['orders']++; $days[$dk]['sales'] += $value; }

            if ($at >= $mStart) {
                if ($stage === 'cancelled') $mon['cancelled']++;
                else {
                    $mon['orders']++; if (strtolower(trim((string) $o->dty)) === 'scheduled') $mon['scheduled']++; else $mon['express']++;
                    if ($done) {
                        $mon['delivered']++; $mon['sales'] += $value;
                        $rr = (int) $o->rider;
                        if ($rr) {
                            $topR[$rr] = $topR[$rr] ?? ['n' => 0, 'timed' => 0, 'ontime' => 0, 'mins' => 0.0, 'value' => 0.0];
                            $topR[$rr]['n']++; $topR[$rr]['value'] += $value;
                            if ($due && $dc) { $topR[$rr]['timed']++; $topR[$rr]['mins'] += max(0, ($dc - $at) / 60); if ($dc <= $due) $topR[$rr]['ontime']++; }
                        }
                    }
                }
            }
            if ($at >= $f && $at <= $t) $acc($R, $stage, $bill, $value, $done, (float) $o->d);
            elseif ($at >= $pf && $at <= $pt) $acc($P, $stage, $bill, $value, $done, (float) $o->d);
            if ($at < $f || $at > $t) continue;

            // current range only
            $status[$stage] = ($status[$stage] ?? 0) + 1;
            if ($stage === 'cancelled') {
                $r = trim((string) $o->rsn); if ($r === '') $r = 'No reason given';
                $reasons[$r] = ($reasons[$r] ?? 0) + 1; continue;
            }
            $hub = (int) $o->hub;
            $byHub[$hub] = $byHub[$hub] ?? ['orders' => 0, 'sales' => 0.0, 'delivered' => 0, 'late' => 0, 'timed' => 0, 'ontime' => 0];
            $byHub[$hub]['orders']++; $byHub[$hub]['sales'] += $value; if ($done) $byHub[$hub]['delivered']++;
            if ($done && $due && $dc) { $timed++; $byHub[$hub]['timed']++; $mins += max(0, ($dc - $at) / 60); if ($dc <= $due) { $onTime++; $byHub[$hub]['ontime']++; } }
            $pk = strtolower(trim((string) $o->pt)) ?: 'cash';
            $pay[$pk] = $pay[$pk] ?? ['n' => 0, 'v' => 0.0]; $pay[$pk]['n']++; $pay[$pk]['v'] += $bill;
            $tk = trim((string) $o->dty) ?: 'Express'; $types[$tk] = ($types[$tk] ?? 0) + 1;
            $hours[(int) date('G', $at)]++;
            $b = (int) $o->buyer; if ($b) { $buyers[$b] = $buyers[$b] ?? ['n' => 0, 'v' => 0.0]; $buyers[$b]['n']++; $buyers[$b]['v'] += $value; }
        }

        // customers
        $custTotal = (int) DB::table('buyers')->count();
        $newC = self::h('buyers', 'date_added') ? (int) DB::table('buyers')->whereBetween('date_added', [$from . ' 00:00:00', $to . ' 23:59:59'])->count() : null;
        $newP = self::h('buyers', 'date_added') ? (int) DB::table('buyers')->whereBetween('date_added', [date('Y-m-d H:i:s', $pf), date('Y-m-d H:i:s', $pt)])->count() : null;
        $repeat = count(array_filter($buyers, fn ($x) => $x['n'] > 1));
        uasort($buyers, fn ($a, $b) => $b['v'] <=> $a['v']);
        $topIds = array_slice(array_keys($buyers), 0, 8);
        $names = $topIds ? DB::table('buyers')->whereIn('id', $topIds)->get(['id', 'name', 'phone'])->keyBy('id') : collect();
        $topCust = []; foreach ($topIds as $id) $topCust[] = ['id' => $id, 'name' => trim((string) ($names[$id]->name ?? '')) ?: ('Customer #' . $id), 'phone' => (string) ($names[$id]->phone ?? ''), 'orders' => $buyers[$id]['n'], 'value' => round($buyers[$id]['v'], 2)];

        // hubs
        $hubRows = [];
        foreach ($byHub as $hid => $v) $hubRows[] = ['id' => $hid, 'name' => $hubs[$hid]['name'] ?? ('Hub ' . $hid), 'orders' => $v['orders'], 'sales' => round($v['sales'], 2), 'delivered' => $v['delivered'], 'ontime' => $v['timed'] ? round($v['ontime'] * 100 / $v['timed']) : null];
        usort($hubRows, fn ($a, $b) => $b['sales'] <=> $a['sales']);

        // rider cash right now
        $rn = $riderCash ? DB::table('delivery_boy')->whereIn('id', array_keys($riderCash))->get(['id', 'name', 'phone', 'hub_id'])->keyBy('id') : collect();
        $cash = [];
        foreach ($riderCash as $rid => $v) $cash[] = ['id' => $rid, 'name' => $rid ? ($rn[$rid]->name ?? ('Rider #' . $rid)) : 'No rider', 'phone' => (string) ($rn[$rid]->phone ?? ''), 'hub' => $hubs[(int) ($rn[$rid]->hub_id ?? 0)]['name'] ?? '', 'orders' => $v['n'], 'amount' => round($v['amt'], 2), 'since' => $v['oldest']];
        usort($cash, fn ($a, $b) => $b['amount'] <=> $a['amount']);
        $cashTotal = round(array_sum(array_column($cash, 'amount')), 2);

        // products
        $items = array_values(CountryAnalytics::productLines($ids, $from . ' 00:00:00', $to . ' 23:59:59'));
        usort($items, fn ($a, $b) => $b['value'] <=> $a['value']);
        $top = array_map(fn ($i) => ['name' => $i['name'], 'pack' => $i['pack'] ?? '', 'value' => round($i['value'], 2), 'orders' => $i['orders'], 'qty' => $i['qty'] ?? 0], array_slice($items, 0, 8));

        // refunds and tickets (optional tables)
        $refunds = null;
        try { if (self::h('admin_refunds', 'amount') && self::h('admin_refunds', 'created_at')) $refunds = round((float) DB::table('admin_refunds')->whereBetween('created_at', [date('Y-m-d H:i:s', $f), date('Y-m-d H:i:s', $t)])->sum('amount'), 2); } catch (\Throwable $e) {}
        $tickets = null;
        try { if (self::h('support_tickets', 'status')) $tickets = (int) DB::table('support_tickets')->whereNotIn('status', ['closed', 'resolved', 'Closed', 'Resolved'])->count(); } catch (\Throwable $e) {}

        $recent = [];
        foreach (DB::table('orders AS o')->leftJoin('buyers AS b', 'b.id', '=', 'o.buyer_id')->whereIn('o.hub_id', $ids ?: [0])->groupBy('o.order_id')
            ->select('o.order_id', DB::raw('MAX(o.date_added) AS at'), DB::raw('MAX(o.order_status) AS st'), DB::raw('MAX(o.total_amount) + MAX(o.delivery_charge) AS amt'), DB::raw('MAX(o.hub_id) AS hub'), DB::raw('MAX(b.name) AS name'))
            ->orderByDesc('at')->limit(10)->get() as $o) {
            $recent[] = ['id' => (string) $o->order_id, 'at' => CountryScope::ts($o->at), 'stage' => OrderFlow::stage($o->st), 'amount' => round((float) $o->amt, 2), 'hub' => $hubs[(int) $o->hub]['name'] ?? '', 'customer' => (string) $o->name];
        }

        uasort($topR, fn ($a, $b) => [$b['n'], $b['ontime']] <=> [$a['n'], $a['ontime']]);
        $topR = array_slice($topR, 0, 10, true);
        $rnm = $topR ? DB::table('delivery_boy')->whereIn('id', array_keys($topR))->get(['id', 'name', 'hub_id'])->keyBy('id') : collect();
        $topRiders = []; $rk = 0;
        foreach ($topR as $rid => $x) {
            $rk++;
            $topRiders[] = ['id' => $rid, 'rank' => $rk, 'name' => trim((string) ($rnm[$rid]->name ?? '')) ?: ('Delivery boy #' . $rid), 'hub' => $hubs[(int) ($rnm[$rid]->hub_id ?? 0)]['name'] ?? '',
                'deliveries' => $x['n'], 'value' => round($x['value'], 2), 'ontime' => $x['timed'] ? round($x['ontime'] * 100 / $x['timed']) : null, 'avg_min' => $x['timed'] ? round($x['mins'] / $x['timed']) : null, 'cash' => round((float) ($riderCash[$rid]['amt'] ?? 0), 2)];
        }
        $mon['sales'] = round($mon['sales'], 2); $mon['label'] = date('F Y');
        $round = function (array $x) { foreach (['sales', 'settled', 'unsettled', 'fees', 'gross'] as $k) $x[$k] = round($x[$k], 2); return $x; };
        $R = $round($R); $P = $round($P);
        $growth = fn ($a, $b) => $b > 0 ? round(($a - $b) * 100 / $b, 1) : null;
        arsort($reasons); arsort($pay);
        return ['ok' => true,
            'range' => $R + ['aov' => $R['delivered'] ? round($R['sales'] / $R['delivered'], 2) : 0, 'cancel_rate' => ($R['orders'] + $R['cancelled']) ? round($R['cancelled'] * 100 / ($R['orders'] + $R['cancelled']), 1) : 0,
                'ontime' => $timed ? round($onTime * 100 / $timed) : null, 'avg_min' => $timed ? round($mins / $timed) : null, 'new_customers' => $newC, 'repeat' => $repeat, 'buyers' => count($buyers), 'refunds' => $refunds],
            'growth' => ['sales' => $growth($R['sales'], $P['sales']), 'orders' => $growth($R['orders'], $P['orders']), 'customers' => ($newC !== null && $newP !== null) ? $growth($newC, $newP) : null],
            'open' => $open + ['tickets' => $tickets], 'cash' => ['total' => $cashTotal, 'riders' => $cash],
            'months' => array_values(array_map(fn ($m) => $m + [], array_map(function ($m) { $m['sales'] = round($m['sales'], 2); $m['settled'] = round($m['settled'], 2); return $m; }, $months))),
            'days' => array_values(array_map(function ($d) { $d['sales'] = round($d['sales'], 2); return $d; }, $days)),
            'status' => $status, 'payments' => array_map(fn ($k, $v) => ['label' => ucfirst($k), 'orders' => $v['n'], 'value' => round($v['v'], 2)], array_keys($pay), array_values($pay)),
            'month' => $mon, 'top_riders' => $topRiders,
            'types' => array_map(fn ($k, $v) => ['label' => $k, 'orders' => $v], array_keys($types), array_values($types)), 'hours' => array_values($hours),
            'reasons' => array_map(fn ($k, $v) => ['label' => $k, 'value' => $v], array_keys(array_slice($reasons, 0, 6, true)), array_values(array_slice($reasons, 0, 6, true))),
            'by_hub' => array_slice($hubRows, 0, 12), 'top_customers' => $topCust, 'top_items' => $top, 'recent' => $recent,
            'totals' => ['customers' => $custTotal, 'riders_today' => count($riderToday)], 'attention' => AdminDashboard::attention(), 'range_label' => $from . ' to ' . $to];
    }

    /* ------------------------------------------------------------------ hub and delivery boy detail (live map drawer) */

    public static function hub(int $hubId): array
    {
        $hubs = CountryScope::hubs();
        if (!isset($hubs[$hubId])) return ['ok' => false, 'message' => 'Hub not found.'];
        $info = null;
        try { foreach (AdminHubs::list()['rows'] as $r) if ((int) $r['id'] === $hubId) { $info = $r; break; } } catch (\Throwable $e) {}
        $now = time(); $todayStart = strtotime(date('Y-m-d'));
        $rows = self::orderRows([$hubId], $now - 3 * 86400, date('Y-m-d H:i:s', $now + 86400));
        $st = ['new' => 0, 'accepted' => 0, 'out' => 0, 'late' => 0, 'delivered_today' => 0, 'sales_today' => 0.0, 'orders_today' => 0];
        $per = [];
        foreach ($rows as $o) {
            $at = strtotime((string) $o->at); $stage = OrderFlow::stage($o->st);
            $bill = (float) $o->t + (float) $o->d; $adj = (float) $o->c - (float) $o->rt;
            $due = OrderFlow::dueAt((string) $o->dty, $o->at, $o->sd, $o->stime); $dc = CountryScope::ts($o->dc);
            if ($at >= $todayStart && $stage !== 'cancelled') { $st['orders_today']++; if (in_array($stage, ['delivered', 'unsettled'], true)) $st['sales_today'] += $bill + $adj; }
            if (in_array($stage, ['new', 'accepted', 'out'], true) && !OrderFlow::isStale($stage, $due, $at, $now)) { $st[$stage]++; if ($due && $due < $now) $st['late']++; }
            $rid = (int) $o->rider; if (!$rid) continue;
            $p = &$per[$rid]; $p = $p ?? ['assigned' => [], 'out' => [], 'unsettled' => 0, 'cash' => 0.0, 'today' => 0, 'last' => null];
            if ($stage === 'accepted') $p['assigned'][] = (string) $o->order_id;
            elseif ($stage === 'out') $p['out'][] = (string) $o->order_id;
            elseif ($stage === 'unsettled') { $p['unsettled']++; $p['cash'] += OrderFlow::expectedCollection($o->pt, $bill, $adj); }
            if (in_array($stage, ['delivered', 'unsettled'], true) && $dc) { if ($dc >= $todayStart) $p['today']++; $p['last'] = max($p['last'] ?? 0, $dc); }
            unset($p);
        }
        $st['sales_today'] = round($st['sales_today'], 2);
        $boys = DB::table('delivery_boy')->where('hub_id', $hubId)->orderBy('name')->get();
        $regIds = []; foreach ($boys as $b) if (self::h('delivery_boy', 'regid') && is_numeric($b->regid ?? null) && (int) $b->regid) $regIds[] = (int) $b->regid;
        $regs = ($regIds && self::h('driver_registration')) ? DB::table('driver_registration')->whereIn('id', $regIds)->get()->keyBy('id') : collect();
        $duty = self::h('pos_rider_status') ? DB::table('pos_rider_status')->whereIn('rider_id', $boys->pluck('id')->all() ?: [0])->get()->keyBy('rider_id') : collect();
        $riders = [];
        foreach ($boys as $b) {
            $id = (int) $b->id; $p = $per[$id] ?? ['assigned' => [], 'out' => [], 'unsettled' => 0, 'cash' => 0.0, 'today' => 0, 'last' => null];
            $reg = (array) ($regs[(int) ($b->regid ?? 0)] ?? []);
            $pick = []; foreach (['vehicle_number' => 'Vehicle number', 'vehicle_no' => 'Vehicle number', 'vehicle_type' => 'Vehicle', 'vehicle_name' => 'Vehicle', 'email' => 'Email', 'city' => 'City'] as $k => $lab) if (!empty($reg[$k]) && !isset($pick[$lab])) $pick[$lab] = (string) $reg[$k];
            if (!isset($pick['Email']) && !empty($b->email)) $pick['Email'] = (string) $b->email;
            $d = $duty[$id] ?? null;
            $riders[] = ['id' => $id, 'regid' => (int) ($b->regid ?? 0) ?: null, 'name' => (string) $b->name, 'phone' => (string) ($b->phone ?? ''), 'code' => (string) ($b->employee_id ?? ''),
                'active' => CountryScope::isActive($b->status), 'on_duty' => $d ? (bool) $d->on_duty : true, 'assigned' => $p['assigned'], 'out' => $p['out'], 'unsettled' => $p['unsettled'], 'cash' => round($p['cash'], 2),
                'today' => $p['today'], 'last' => $p['last'], 'extra' => $pick, 'hub' => $hubs[$hubId]['name']];
        }
        return ['ok' => true, 'hub' => ['id' => $hubId, 'name' => $hubs[$hubId]['name'], 'city' => $hubs[$hubId]['city'], 'active' => $hubs[$hubId]['active'], 'kind' => $info['kind'] ?? null, 'radius_m' => $info['radius_m'] ?? null], 'stats' => $st, 'riders' => $riders, 'server_time' => $now];
    }

    public static function rider(int $boyId): array
    {
        $hub = (int) DB::table('delivery_boy')->where('id', $boyId)->value('hub_id');
        if (!$hub) return ['ok' => false, 'message' => 'Delivery boy not found.'];
        $d = self::hub($hub);
        if (empty($d['ok'])) return $d;
        foreach ($d['riders'] as $r) if ($r['id'] === $boyId) return ['ok' => true, 'rider' => $r, 'hub' => $d['hub'], 'server_time' => $d['server_time']];
        return ['ok' => false, 'message' => 'Delivery boy not found.'];
    }
}
