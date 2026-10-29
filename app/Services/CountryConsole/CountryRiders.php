<?php

namespace App\Services\CountryConsole;

use App\Services\HubConsole\HubStock;
use App\Services\PosConsole\OrderFlow;
use Illuminate\Support\Facades\DB;

/**
 * Delivery boy performance and SLA (update 3, 22 Oct 2026) - for incentives.
 * Per rider, for orders placed in the dates and assigned to the rider (orders.rider_id):
 *   delivered, on time (delivered by the promised time: Express = placed + the POS promise, Scheduled = slot end),
 *   late, average minutes dispatch -> delivered and order -> delivered (Express), cancelled while assigned,
 *   value delivered, days worked (days with a delivery), deliveries per working day,
 *   cash handed over in the POS (expected vs received; short = received less than expected).
 * Incentive amounts are worked out on the screen from the rates the manager types (nothing is paid or saved).
 */
class CountryRiders
{
    private static function rows(array $riderIds, string $from, string $to): array
    {
        if (!$riderIds || !CountryScope::has('orders', 'rider_id')) return [];
        $sel = ['order_id', DB::raw('MAX(rider_id) AS rider'), DB::raw('MAX(hub_id) AS hub'), DB::raw('MAX(order_status) AS st'), DB::raw('MAX(date_added) AS at'),
            DB::raw('MAX(delivery_type) AS type'), DB::raw('MAX(schedule_date) AS sd'), DB::raw('MAX(schedule_time) AS stime'), DB::raw('MAX(total_amount) AS t'), DB::raw('MAX(delivery_charge) AS d'), DB::raw('MAX(payment_type) AS pay')];
        foreach (['delivery_complete_time' => 'dt', 'shipping_complete_time' => 'sh', 'amount_to_collect' => 'c', 'amount_to_return' => 'r'] as $col => $a) $sel[] = CountryScope::has('orders', $col) ? DB::raw("MAX($col) AS $a") : DB::raw("NULL AS $a");
        $out = [];
        foreach (array_chunk($riderIds, 500) as $chunk) {
            foreach (DB::table('orders')->whereIn('rider_id', $chunk)->whereBetween('date_added', [$from . ' 00:00:00', $to . ' 23:59:59'])->groupBy('order_id')->select($sel)->get() as $o) {
                $at = CountryScope::ts($o->at); $dt = CountryScope::ts($o->dt); $sh = CountryScope::ts($o->sh);
                $due = OrderFlow::dueAt($o->type, $o->at, $o->sd, $o->stime);
                $stage = OrderFlow::stage($o->st);
                $done = in_array($stage, ['delivered', 'unsettled'], true);
                $out[] = ['order_id' => (string) $o->order_id, 'rider' => (int) $o->rider, 'hub' => (int) $o->hub, 'stage' => $stage, 'status' => (string) $o->st, 'at' => $at, 'shipped' => $sh, 'delivered' => $dt,
                    'type' => strcasecmp((string) $o->type, 'Scheduled') === 0 ? 'Scheduled' : 'Express', 'due' => $due, 'pay' => (string) ($o->pay ?: 'Cash'),
                    'value' => round((float) $o->t + (float) $o->d + (float) $o->c - (float) $o->r, 2), 'done' => $done,
                    'on_time' => $done && $dt && $due ? $dt <= $due : null,
                    'ride_min' => $done && $dt && $sh && $dt > $sh ? (int) round(($dt - $sh) / 60) : null,
                    'total_min' => $done && $dt && $at && $dt > $at ? (int) round(($dt - $at) / 60) : null];
            }
        }
        return $out;
    }

    private static function cash(array $riderIds, string $from, string $to): array
    {
        $out = [];
        if (!$riderIds || !HubStock::has('pos_cash_handovers')) return $out;
        $recv = CountryScope::has('pos_cash_handovers', 'received') ? 'received' : null;
        foreach (array_chunk($riderIds, 500) as $chunk) {
            foreach (DB::table('pos_cash_handovers')->whereIn('rider_id', $chunk)->whereBetween('created_at', [$from . ' 00:00:00', $to . ' 23:59:59'])->get() as $h) {
                $rid = (int) $h->rider_id; $got = $recv ? (float) $h->received : (float) $h->cash_amount + (float) $h->upi_amount + (float) $h->card_amount;
                $diff = round($got - (float) $h->expected, 2);
                $out[$rid] = $out[$rid] ?? ['handovers' => 0, 'expected' => 0.0, 'received' => 0.0, 'short' => 0.0, 'excess' => 0.0, 'cash' => 0.0, 'upi' => 0.0];
                $out[$rid]['handovers']++; $out[$rid]['expected'] += (float) $h->expected; $out[$rid]['received'] += $got;
                $out[$rid]['cash'] += (float) $h->cash_amount; $out[$rid]['upi'] += (float) $h->upi_amount;
                if ($diff < 0) $out[$rid]['short'] += -$diff; else $out[$rid]['excess'] += $diff;
            }
        }
        foreach ($out as &$c) foreach ($c as $k => $v) if (is_float($v)) $c[$k] = round($v, 2);
        unset($c);
        return $out;
    }

    private static function summarise(array $list): array
    {
        $done = array_values(array_filter($list, fn ($o) => $o['done']));
        $timed = array_values(array_filter($done, fn ($o) => $o['on_time'] !== null));
        $on = count(array_filter($timed, fn ($o) => $o['on_time']));
        $ride = array_values(array_filter(array_column($done, 'ride_min'), fn ($v) => $v !== null && $v < 600));
        $tot = array_values(array_filter(array_map(fn ($o) => $o['type'] === 'Express' ? $o['total_min'] : null, $done), fn ($v) => $v !== null && $v < 600));
        $days = count(array_unique(array_map(fn ($o) => date('Y-m-d', $o['delivered'] ?: $o['at']), $done)));
        sort($ride);
        return ['assigned' => count($list), 'delivered' => count($done), 'on_time' => $on, 'late' => count($timed) - $on, 'timed' => count($timed),
            'sla' => count($timed) ? (int) round($on * 100 / count($timed)) : null,
            'avg_ride' => $ride ? (int) round(array_sum($ride) / count($ride)) : null, 'median_ride' => $ride ? (int) $ride[intdiv(count($ride), 2)] : null,
            'avg_total' => $tot ? (int) round(array_sum($tot) / count($tot)) : null,
            'cancelled' => count(array_filter($list, fn ($o) => $o['stage'] === 'cancelled')), 'out_now' => count(array_filter($list, fn ($o) => $o['stage'] === 'out')),
            'value' => round(array_sum(array_column($done, 'value')), 2), 'express' => count(array_filter($done, fn ($o) => $o['type'] === 'Express')), 'scheduled' => count(array_filter($done, fn ($o) => $o['type'] === 'Scheduled')),
            'cod' => count(array_filter($done, fn ($o) => strcasecmp($o['pay'], 'Cash') === 0)), 'days' => $days, 'per_day' => $days ? round(count($done) / $days, 1) : 0];
    }

    /** Every rider in the scope with performance in the dates. */
    public static function performance(CountryScope $scope, string $from, string $to): array
    {
        $ids = $scope->ids(); $hubs = CountryScope::hubs();
        if (!$ids) return ['ok' => true, 'rows' => [], 'total' => []];
        $cols = array_values(array_filter(['id', 'name', 'phone', 'hub_id', 'status', 'employee_id', 'date_added'], fn ($c) => CountryScope::has('delivery_boy', $c)));
        $riders = DB::table('delivery_boy')->whereIn('hub_id', $ids)->get($cols);
        $rids = $riders->pluck('id')->map(fn ($v) => (int) $v)->all();
        $orders = self::rows($rids, $from, $to); $cash = self::cash($rids, $from, $to);
        $by = []; foreach ($orders as $o) $by[$o['rider']][] = $o;
        $rows = [];
        foreach ($riders as $r) {
            $id = (int) $r->id; $s = self::summarise($by[$id] ?? []); $c = $cash[$id] ?? ['handovers' => 0, 'expected' => 0, 'received' => 0, 'short' => 0, 'excess' => 0, 'cash' => 0, 'upi' => 0];
            $rows[] = ['id' => $id, 'name' => (string) $r->name, 'phone' => (string) ($r->phone ?? ''), 'employee_id' => (string) ($r->employee_id ?? ''), 'hub_id' => (int) $r->hub_id,
                'hub' => $hubs[(int) $r->hub_id]['name'] ?? '', 'city' => $hubs[(int) $r->hub_id]['city'] ?? '', 'active' => CountryScope::isActive($r->status ?? 'active')] + $s + ['cash' => $c];
        }
        usort($rows, fn ($a, $b) => [$b['delivered'], $b['sla'] ?? -1, $a['name']] <=> [$a['delivered'], $a['sla'] ?? -1, $b['name']]);
        $all = self::summarise($orders);
        $all['riders'] = count($rows); $all['working'] = count(array_filter($rows, fn ($r) => $r['delivered'] > 0));
        $all['short'] = round(array_sum(array_map(fn ($r) => $r['cash']['short'], $rows)), 2);
        return ['ok' => true, 'rows' => $rows, 'total' => $all, 'from' => $from, 'to' => $to, 'rates' => (array) config('country_console.incentive', [])];
    }

    /** One rider: profile, per-day history and every order. */
    public static function rider(int $id, string $from, string $to): array
    {
        $r = DB::table('delivery_boy')->where('id', $id)->first();
        if (!$r || !CountryScope::hub((int) $r->hub_id)) return ['ok' => false, 'message' => 'Rider not found.'];
        $hubs = CountryScope::hubs();
        $orders = self::rows([$id], $from, $to); $cash = self::cash([$id], $from, $to)[$id] ?? null;
        usort($orders, fn ($a, $b) => $b['at'] <=> $a['at']);
        $days = [];
        foreach ($orders as $o) { $d = date('Y-m-d', $o['delivered'] ?: $o['at']); $days[$d][] = $o; }
        krsort($days);
        $daily = [];
        foreach ($days as $d => $list) $daily[] = ['date' => $d] + self::summarise($list);
        $list = array_map(fn ($o) => $o + ['hub_name' => $hubs[$o['hub']]['name'] ?? ''], array_slice($orders, 0, 1000));
        return ['ok' => true, 'rider' => ['id' => (int) $r->id, 'name' => (string) $r->name, 'phone' => (string) ($r->phone ?? ''), 'employee_id' => (string) ($r->employee_id ?? ''),
            'hub' => $hubs[(int) $r->hub_id]['name'] ?? '', 'city' => $hubs[(int) $r->hub_id]['city'] ?? '', 'active' => CountryScope::isActive($r->status ?? 'active'),
            'joined' => isset($r->date_added) ? CountryScope::ts($r->date_added) : null],
            'summary' => self::summarise($orders), 'cash' => $cash, 'daily' => $daily, 'orders' => $list, 'from' => $from, 'to' => $to];
    }
}
