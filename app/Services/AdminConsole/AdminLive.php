<?php

namespace App\Services\AdminConsole;

use App\Services\CountryConsole\CountryScope;
use App\Services\PosConsole\OrderFlow;
use App\Services\PosConsole\PosConsoleService;
use Illuminate\Support\Facades\DB;

/**
 * Freshful admin console (admin update 2): live operations across every hub.
 *   god view   - one map: hub areas, every open order at the customer's address, delivery boys where the rider app last saw them;
 *   heat map   - where orders come from (customer addresses), counted in small squares (about 100 m);
 *   live dispatch - every hub's live order board in one place; accept, dispatch (smart rider pick), change rider, cancel,
 *                   all through the hub's own POS rules (PosConsoleService), recorded as done by the admin.
 * Rider locations are read from wherever the rider app saves them (found automatically; see riderSource()).
 */
class AdminLive
{
    private static function h(string $t, ?string $c = null): bool { return CountryScope::has($t, $c); }

    /* ------------------------------------------------------------------ where are rider locations kept? */
    public static function riderSource(): ?array
    {
        $pairs = [['latitude', 'longitude'], ['lat', 'lng'], ['current_lat', 'current_lng'], ['current_latitude', 'current_longitude'], ['driver_lat', 'driver_lng'], ['live_lat', 'live_lng'], ['user_lat', 'user_lng']];
        $times = ['location_updated_at', 'location_time', 'last_location_at', 'last_seen', 'updated_at', 'created_at', 'date_added'];
        // 1. columns on the delivery boy table
        if (self::h('delivery_boy')) foreach ($pairs as [$a, $b]) if (self::h('delivery_boy', $a) && self::h('delivery_boy', $b)) {
            $t = null; foreach ($times as $c) if (self::h('delivery_boy', $c)) { $t = $c; break; }
            return ['table' => 'delivery_boy', 'rider' => 'id', 'lat' => $a, 'lng' => $b, 'time' => $t, 'history' => false];
        }
        // 2. a location log table
        foreach (['delivery_boy_locations', 'delivery_boy_location', 'rider_locations', 'rider_location', 'driver_locations', 'driver_location', 'driver_tracking', 'rider_tracking', 'delivery_boy_tracking', 'locations'] as $tb) {
            if (!self::h($tb)) continue;
            $rid = null; foreach (['delivery_boy_id', 'rider_id', 'driver_id', 'boy_id', 'user_id'] as $c) if (self::h($tb, $c)) { $rid = $c; break; }
            if (!$rid) continue;
            foreach ($pairs as [$a, $b]) if (self::h($tb, $a) && self::h($tb, $b)) {
                $t = null; foreach ($times as $c) if (self::h($tb, $c)) { $t = $c; break; }
                return ['table' => $tb, 'rider' => $rid, 'lat' => $a, 'lng' => $b, 'time' => $t, 'history' => true];
            }
        }
        return null;
    }

    /** Latest known position per rider id: [id => [lat, lng, at]] */
    public static function riderPositions(): array
    {
        $s = self::riderSource(); if (!$s) return [];
        $out = [];
        try {
            if (!$s['history']) {
                foreach (DB::table($s['table'])->whereNotNull($s['lat'])->get(array_values(array_filter(['id', $s['lat'], $s['lng'], $s['time']]))) as $r) {
                    $la = (float) $r->{$s['lat']}; $ln = (float) $r->{$s['lng']}; if (!$la || !$ln) continue;
                    $out[(int) $r->id] = ['lat' => $la, 'lng' => $ln, 'at' => $s['time'] ? CountryScope::ts($r->{$s['time']}) : null];
                }
            } else {
                $q = DB::table($s['table'])->whereNotNull($s['lat']);
                if ($s['time']) $q->where($s['time'], '>=', date('Y-m-d H:i:s', time() - 86400));
                $q->orderBy($s['time'] ?: 'id');
                foreach ($q->limit(20000)->get([$s['rider'], $s['lat'], $s['lng']] + ($s['time'] ? [3 => $s['time']] : [])) as $r) {
                    $la = (float) $r->{$s['lat']}; $ln = (float) $r->{$s['lng']}; if (!$la || !$ln) continue;
                    $out[(int) $r->{$s['rider']}] = ['lat' => $la, 'lng' => $ln, 'at' => $s['time'] ? CountryScope::ts($r->{$s['time']}) : null];
                }
            }
        } catch (\Throwable $e) {}
        return $out;
    }

    private static function addrCols(): array
    {
        $la = []; $ln = [];
        foreach (['latitude', 'user_lat', 'lat'] as $c) if (self::h('buyer_address', $c)) $la[] = 'a.' . $c;
        foreach (['longitude', 'user_lng', 'lng'] as $c) if (self::h('buyer_address', $c)) $ln[] = 'a.' . $c;
        return [$la ? 'COALESCE(' . implode(',', array_map(fn ($c) => "NULLIF($c,'')", $la)) . ')' : 'NULL', $ln ? 'COALESCE(' . implode(',', array_map(fn ($c) => "NULLIF($c,'')", $ln)) . ')' : 'NULL'];
    }

    /* ------------------------------------------------------------------ god view */
    public static function god(CountryScope $scope): array
    {
        $ids = $scope->ids(); $hubs = CountryScope::hubs();
        $hubRows = [];
        foreach (AdminHubs::list()['rows'] as $h) if (in_array($h['id'], $ids, true)) $hubRows[] = ['id' => $h['id'], 'name' => $h['name'], 'city' => $h['city'], 'active' => $h['active'], 'kind' => $h['kind'], 'points' => $h['points'], 'lat' => $h['lat'], 'lng' => $h['lng'], 'radius_m' => $h['radius_m'] ?? null];
        [$la, $ln] = self::addrCols();
        $orders = [];
        if ($ids) {
            $open = OrderFlow::statusesFor(['new', 'accepted', 'out']);
            $q = DB::table('orders AS o')->leftJoin('buyer_address AS a', 'a.id', '=', 'o.address_id')->leftJoin('buyers AS b', 'b.id', '=', 'o.buyer_id')
                ->whereIn('o.hub_id', $ids)->whereIn('o.order_status', $open)->where('o.date_added', '>=', date('Y-m-d H:i:s', time() - 3 * 86400))
                ->groupBy('o.order_id')->select('o.order_id', DB::raw('MAX(o.hub_id) AS hub'), DB::raw('MAX(o.order_status) AS st'), DB::raw('MAX(o.date_added) AS at'), DB::raw('MAX(o.delivery_type) AS type'),
                    DB::raw('MAX(o.schedule_date) AS sd'), DB::raw('MAX(o.schedule_time) AS stime'), DB::raw('MAX(o.total_amount) + MAX(o.delivery_charge) AS amt'), DB::raw('MAX(o.rider_id) AS rider'),
                    DB::raw("MAX($la) AS lat"), DB::raw("MAX($ln) AS lng"), DB::raw('MAX(b.name) AS name'), DB::raw('MAX(a.locality) AS area'));
            $now = time();
            foreach ($q->limit(1500)->get() as $o) {
                $due = OrderFlow::dueAt($o->type, $o->at, $o->sd, $o->stime);
                $orders[] = ['id' => (string) $o->order_id, 'hub' => (int) $o->hub, 'hub_name' => $hubs[(int) $o->hub]['name'] ?? '', 'stage' => OrderFlow::stage($o->st), 'at' => CountryScope::ts($o->at), 'due' => $due,
                    'late' => $due && $due < $now, 'type' => (string) $o->type, 'amount' => round((float) $o->amt, 2), 'rider' => (int) $o->rider ?: null, 'customer' => (string) $o->name, 'area' => (string) $o->area,
                    'lat' => is_numeric($o->lat) ? (float) $o->lat : null, 'lng' => is_numeric($o->lng) ? (float) $o->lng : null];
            }
        }
        $pos = self::riderPositions();
        $riders = [];
        if ($ids && self::h('delivery_boy')) {
            $out = [];
            foreach ($orders as $o) if ($o['stage'] === 'out' && $o['rider']) $out[$o['rider']][] = $o['id'];
            foreach (DB::table('delivery_boy')->whereIn('hub_id', $ids)->get(['id', 'name', 'phone', 'hub_id', 'status']) as $r) {
                $p = $pos[(int) $r->id] ?? null;
                $riders[] = ['id' => (int) $r->id, 'name' => (string) $r->name, 'phone' => (string) ($r->phone ?? ''), 'hub' => (int) $r->hub_id, 'hub_name' => $hubs[(int) $r->hub_id]['name'] ?? '', 'active' => CountryScope::isActive($r->status),
                    'lat' => $p['lat'] ?? null, 'lng' => $p['lng'] ?? null, 'seen' => $p['at'] ?? null, 'orders' => $out[(int) $r->id] ?? []];
            }
        }
        $src = self::riderSource();
        return ['ok' => true, 'hubs' => $hubRows, 'orders' => $orders, 'riders' => $riders, 'rider_source' => $src ? $src['table'] . '.' . $src['lat'] : null,
            'with_location' => count(array_filter($orders, fn ($o) => $o['lat'] !== null)), 'riders_located' => count(array_filter($riders, fn ($r) => $r['lat'] !== null)), 'server_time' => time()];
    }

    /* ------------------------------------------------------------------ heat map */
    public static function heat(CountryScope $scope, string $from, string $to, string $what = 'orders'): array
    {
        $ids = $scope->ids();
        if (!$ids) return ['ok' => true, 'cells' => [], 'total' => 0, 'located' => 0];
        [$la, $ln] = self::addrCols();
        $q = DB::table('orders AS o')->leftJoin('buyer_address AS a', 'a.id', '=', 'o.address_id')->whereIn('o.hub_id', $ids)
            ->whereBetween('o.date_added', [$from . ' 00:00:00', $to . ' 23:59:59'])->whereNotIn('o.order_status', OrderFlow::statusesFor(['cancelled']))
            ->groupBy('o.order_id')->select('o.order_id', DB::raw("MAX($la) AS lat"), DB::raw("MAX($ln) AS lng"), DB::raw('MAX(o.total_amount) + MAX(o.delivery_charge) AS amt'), DB::raw('MAX(o.buyer_id) AS b'));
        $cells = []; $total = 0; $located = 0;
        foreach ($q->limit(50000)->get() as $o) {
            $total++;
            if (!is_numeric($o->lat) || !is_numeric($o->lng) || !(float) $o->lat) continue;
            $located++;
            $k = round((float) $o->lat, 3) . ',' . round((float) $o->lng, 3);
            if (!isset($cells[$k])) $cells[$k] = ['lat' => round((float) $o->lat, 3), 'lng' => round((float) $o->lng, 3), 'orders' => 0, 'value' => 0.0, 'buyers' => []];
            $cells[$k]['orders']++; $cells[$k]['value'] += (float) $o->amt; $cells[$k]['buyers'][(int) $o->b] = true;
        }
        $out = array_map(fn ($c) => ['lat' => $c['lat'], 'lng' => $c['lng'], 'orders' => $c['orders'], 'value' => round($c['value'], 2), 'customers' => count($c['buyers'])], array_values($cells));
        usort($out, fn ($a, $b) => $b['orders'] <=> $a['orders']);
        $hubs = [];
        foreach (AdminHubs::list()['rows'] as $h) if (in_array($h['id'], $ids, true)) $hubs[] = ['id' => $h['id'], 'name' => $h['name'], 'kind' => $h['kind'], 'points' => $h['points'], 'lat' => $h['lat'], 'lng' => $h['lng'], 'radius_m' => $h['radius_m'] ?? null, 'active' => $h['active']];
        return ['ok' => true, 'cells' => $out, 'total' => $total, 'located' => $located, 'hubs' => $hubs];
    }

    /* ------------------------------------------------------------------ live dispatch */
    public static function board(CountryScope $scope): array
    {
        $hubs = CountryScope::hubs(); $orders = []; $riders = []; $counts = ['new' => 0, 'accepted' => 0, 'ready' => 0, 'out' => 0, 'late' => 0, 'no_rider' => 0];
        foreach ($scope->ids() as $h) {
            if (empty($hubs[$h]['active'])) continue;
            try { $f = (new PosConsoleService($h))->feed(); } catch (\Throwable $e) { continue; }
            foreach ($f['orders'] as $o) {
                if (!in_array($o['stage'], ['new', 'accepted', 'out'], true) || !empty($o['stale'])) continue;
                $o['hub_id'] = $h; $o['hub'] = $hubs[$h]['name'] ?? ''; $o['city'] = $hubs[$h]['city'] ?? '';
                $orders[] = $o;
                $counts[$o['board']] = ($counts[$o['board']] ?? 0) + 1;
                if ($o['late']) $counts['late']++;
                if ($o['stage'] === 'out' && !$o['rider_id']) $counts['no_rider']++;
            }
            foreach ($f['riders'] as $r) { if (!$r['id']) continue; $r['hub_id'] = $h; $r['hub'] = $hubs[$h]['name'] ?? ''; $riders[] = $r; }
        }
        usort($orders, fn ($a, $b) => [!$a['late'], $a['due_at'] ?? PHP_INT_MAX] <=> [!$b['late'], $b['due_at'] ?? PHP_INT_MAX]);
        $free = count(array_filter($riders, fn ($r) => $r['state'] === 'available'));
        return ['ok' => true, 'orders' => $orders, 'riders' => $riders, 'counts' => $counts + ['riders_free' => $free, 'riders' => count($riders)], 'server_time' => time()];
    }

    private static function hubOf(string $orderId): ?int
    {
        $h = DB::table('orders')->where('order_id', $orderId)->value('hub_id');
        return $h !== null && CountryScope::hub((int) $h) ? (int) $h : null;
    }

    public static function act(string $orderId, string $action, ?string $reason, ?int $riderId, bool $auto, array $actor): array
    {
        if (!preg_match('/^[A-Za-z0-9_\-]{1,40}$/', $orderId)) return ['ok' => false, 'message' => 'Order not found.'];
        $h = self::hubOf($orderId);
        if ($h === null) return ['ok' => false, 'message' => 'Order not found.'];
        $pos = new PosConsoleService($h);
        $who = ['guard' => $actor['guard'] ?? 'admin', 'id' => $actor['id'] ?? 0, 'name' => trim(($actor['name'] ?? 'Admin') . ' (admin)')];
        if ($action === 'reassign') {
            if (!$riderId) return ['ok' => false, 'message' => 'Choose the rider.'];
            return $pos->reassign($orderId, $riderId, $who);
        }
        if (!in_array($action, ['accept', 'reject', 'dispatch', 'cancel', 'deliver'], true)) return ['ok' => false, 'message' => 'Unknown action.'];
        return $pos->act($orderId, $action, $reason, $riderId, $who, $auto);
    }
}
