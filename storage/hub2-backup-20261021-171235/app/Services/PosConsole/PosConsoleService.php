<?php

namespace App\Services\PosConsole;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;

/**
 * Freshful POS console - everything the console reads or changes, always limited to ONE hub.
 * Orders table has one row per product line; every row of an order shares order_id and status.
 * Status meanings follow the old POS exactly (see OrderFlow).
 */
class PosConsoleService
{
    private static array $cols = [];
    private static array $tables = [];

    public function __construct(private int $hubId)
    {
    }

    public function hubId(): int
    {
        return $this->hubId;
    }

    /* ------------------------------------------------------------------ helpers */

    private static function has(string $table, string $col): bool
    {
        $k = $table . '.' . $col;
        if (!array_key_exists($k, self::$cols)) {
            try {
                self::$cols[$k] = Schema::hasColumn($table, $col);
            } catch (\Throwable $e) {
                self::$cols[$k] = false;
            }
        }
        return self::$cols[$k];
    }

    private static function table(string $table): bool
    {
        if (!array_key_exists($table, self::$tables)) {
            try {
                self::$tables[$table] = Schema::hasTable($table);
            } catch (\Throwable $e) {
                self::$tables[$table] = false;
            }
        }
        return self::$tables[$table];
    }

    private static function ts($v): ?int
    {
        if (!$v) return null;
        $t = strtotime((string) $v);
        return ($t !== false && $t > 946684800) ? $t : null;
    }

    private static function money($v): float
    {
        return round((float) $v, 2);
    }

    public function hub(): ?object
    {
        return DB::table('hubs')->leftJoin('cities', 'cities.id', '=', 'hubs.city_id')
            ->select('hubs.id', 'hubs.hub', 'hubs.status', 'hubs.city_id', 'cities.city')
            ->where('hubs.id', $this->hubId)->first();
    }

    /** Other active hubs an order can be moved to, own city first. */
    public function hubsForRelocation(): array
    {
        $me = $this->hub();
        $rows = DB::table('hubs')->leftJoin('cities', 'cities.id', '=', 'hubs.city_id')
            ->select('hubs.id', 'hubs.hub', 'hubs.city_id', 'cities.city')
            ->where('hubs.id', '<>', $this->hubId)
            ->whereRaw("LOWER(CAST(hubs.status AS CHAR)) IN ('1', 'active')")   // CAST: MySQL would treat 'active' = 0 as true
            ->orderBy('cities.city')->orderBy('hubs.hub')->get();
        $out = [];
        foreach ($rows as $r) {
            $out[] = ['id' => (int) $r->id, 'name' => (string) $r->hub, 'city' => (string) ($r->city ?? ''),
                'same_city' => $me && (int) $r->city_id === (int) $me->city_id];
        }
        usort($out, fn ($a, $b) => [$b['same_city'], $a['city'], $a['name']] <=> [$a['same_city'], $b['city'], $b['name']]);
        return $out;
    }

    public function riders(): array
    {
        $q = DB::table('delivery_boy')->where('hub_id', $this->hubId);
        if (self::has('delivery_boy', 'status')) {
            $q->where(function ($w) { $w->whereNull('status')->orWhereRaw("LOWER(CAST(status AS CHAR)) IN ('1', 'active')"); });
        }
        $cols = array_values(array_filter(['id', 'name', 'phone', 'employee_id'], fn ($c) => self::has('delivery_boy', $c)));
        return $q->orderBy('name')->get($cols)->map(fn ($r) => [
            'id' => (int) $r->id, 'name' => (string) $r->name, 'phone' => (string) ($r->phone ?? ''), 'code' => (string) ($r->employee_id ?? ''),
        ])->all();
    }

    /** One summary row per order for this hub. */
    private function orderSummaryQuery()
    {
        $sel = [
            'o.order_id',
            DB::raw('MIN(o.id) AS first_id'),
            DB::raw('MAX(o.buyer_id) AS buyer_id'),
            DB::raw('MAX(o.address_id) AS address_id'),
            DB::raw('MAX(o.order_status) AS order_status'),
            DB::raw('MAX(o.payment_type) AS payment_type'),
            DB::raw('MAX(o.payment_status) AS payment_status'),
            DB::raw('MAX(o.delivery_type) AS delivery_type'),
            DB::raw('MAX(o.schedule_date) AS schedule_date'),
            DB::raw('MAX(o.schedule_time) AS schedule_time'),
            DB::raw('MAX(o.date_added) AS date_added'),
            DB::raw('MAX(COALESCE(o.updated_at, o.date_added)) AS changed_at'),
            DB::raw('MAX(o.total_amount) AS total_amount'),
            DB::raw('MAX(o.delivery_charge) AS delivery_charge'),
            DB::raw('MAX(o.rider_id) AS rider_id'),
            DB::raw('COUNT(*) AS line_count'),
        ];
        foreach (['amount_to_collect', 'amount_to_return', 'order_complete_time', 'processing_complete_time',
                  'shipping_complete_time', 'delivery_complete_time', 'reason_for_order_cancel'] as $c) {
            $sel[] = self::has('orders', $c) ? DB::raw("MAX(o.$c) AS $c") : DB::raw("NULL AS $c");
        }
        return DB::table('orders AS o')->select($sel)->where('o.hub_id', $this->hubId)->groupBy('o.order_id');
    }

    private function event(string $orderId, string $action, array $actor, array $detail = [], ?int $hubId = null): void
    {
        try {
            Log::info('[freshful-audit] pos-' . $action, [
                'panel' => 'pos-console', 'by' => ($actor['guard'] ?? '?') . '#' . ($actor['id'] ?? '?'),
                'hub' => $hubId ?? $this->hubId, 'order' => $orderId, 'ip' => request()->ip(),
            ] + $detail);
            if (self::table('pos_order_events')) {
                DB::table('pos_order_events')->insert([
                    'order_id' => mb_substr($orderId, 0, 40), 'hub_id' => $hubId ?? $this->hubId, 'action' => $action,
                    'detail' => $detail ? json_encode($detail, JSON_UNESCAPED_UNICODE) : null,
                    'actor_guard' => $actor['guard'] ?? null, 'actor_id' => $actor['id'] ?? null,
                    'actor_name' => mb_substr((string) ($actor['name'] ?? ''), 0, 120), 'created_at' => now(),
                ]);
            }
        } catch (\Throwable $e) {
            report($e);   // never block the action because of the log
        }
    }

    /* ------------------------------------------------------------------ live board */

    public function feed(): array
    {
        $today = date('Y-m-d');
        $activeRows = (clone $this->orderSummaryQuery())->whereIn('o.order_status', OrderFlow::statusesFor(OrderFlow::ACTIVE))
            ->orderByRaw('MIN(o.id) DESC')->limit(400)->get();
        $todayDone = (clone $this->orderSummaryQuery())->whereIn('o.order_status', OrderFlow::statusesFor(['delivered', 'cancelled']))
            ->where(function ($q) use ($today) {
                $q->whereDate('o.date_added', $today)->orWhereDate('o.updated_at', $today);
            })->orderByRaw('MAX(COALESCE(o.updated_at, o.date_added)) DESC')->limit(200)->get();

        $byId = [];
        foreach ([$activeRows, $todayDone] as $set) {
            foreach ($set as $r) {
                $byId[$r->order_id] = $r;
            }
        }
        $orders = $this->decorate(array_values($byId));

        $counts = array_fill_keys(['new', 'accepted', 'ready', 'out', 'unsettled', 'delivered', 'cancelled', 'late', 'scheduled_later', 'stale'], 0);
        $sig = [];
        foreach ($orders as $o) {
            $counts[$o['board']] = ($counts[$o['board']] ?? 0) + 1;
            if ($o['late']) $counts['late']++;
            if ($o['later']) $counts['scheduled_later']++;
            if ($o['stale']) $counts['stale']++;
            $sig[] = $o['order_id'] . ':' . $o['status'] . ':' . $o['rider_id'] . ':' . ($o['weighed'] ? 1 : 0) . ':' . $o['adjustment'];
        }
        sort($sig);

        $riders = $this->riderSummary($orders);
        return [
            'ok'          => true,
            'server_time' => time(),
            'version'     => md5(implode('|', $sig) . json_encode($riders)),
            'counts'      => $counts,
            'kpis'        => $this->kpis($orders),
            'riders'      => $riders,
            'orders'      => $orders,
        ];
    }

    /** Add customer, area, money, deadline, weighing and allowed actions to summary rows. */
    private function decorate(array $rows): array
    {
        if (!$rows) return [];
        $buyerIds = array_values(array_unique(array_filter(array_map(fn ($r) => (int) $r->buyer_id, $rows))));
        $addrIds = array_values(array_unique(array_filter(array_map(fn ($r) => (int) $r->address_id, $rows))));
        $riderIds = array_values(array_unique(array_filter(array_map(fn ($r) => (int) $r->rider_id, $rows))));
        $orderIds = array_map(fn ($r) => (string) $r->order_id, $rows);

        $buyers = $buyerIds ? DB::table('buyers')->whereIn('id', $buyerIds)->get(['id', 'name', 'phone'])->keyBy('id') : collect();
        $addrCols = array_values(array_filter(['id', 'name', 'phone', 'locality', 'city'], fn ($c) => self::has('buyer_address', $c)));
        $addrs = $addrIds ? DB::table('buyer_address')->whereIn('id', $addrIds)->get($addrCols)->keyBy('id') : collect();
        $riders = $riderIds ? DB::table('delivery_boy')->whereIn('id', $riderIds)->get(['id', 'name', 'phone'])->keyBy('id') : collect();
        $weighed = [];
        if (self::table('pos_order_weights')) {
            foreach (array_chunk($orderIds, 300) as $chunk) {
                foreach (DB::table('pos_order_weights')->whereIn('order_id', $chunk)->distinct()->pluck('order_id') as $id) {
                    $weighed[$id] = true;
                }
            }
        }

        $now = time();
        $endOfToday = strtotime('tomorrow') - 1;
        $out = [];
        foreach ($rows as $r) {
            $b = $buyers[(int) $r->buyer_id] ?? null;
            $a = $addrs[(int) $r->address_id] ?? null;
            $stage = OrderFlow::stage($r->order_status);
            $placed = self::ts($r->date_added);
            $due = OrderFlow::dueAt($r->delivery_type, $r->date_added, $r->schedule_date, $r->schedule_time);
            $active = in_array($stage, OrderFlow::ACTIVE, true);
            $stale = OrderFlow::isStale($stage, $due, $placed, $now);
            $isWeighed = isset($weighed[$r->order_id]) || ($stage === 'accepted' && !empty($r->processing_complete_time));
            $bill = self::money((float) $r->total_amount + (float) $r->delivery_charge);
            $adj = self::money((float) $r->amount_to_collect - (float) $r->amount_to_return);
            $rider = $riders[(int) $r->rider_id] ?? null;
            $since = match ($stage) {
                'accepted' => self::ts($r->processing_complete_time) ?? self::ts($r->order_complete_time),
                'out' => self::ts($r->shipping_complete_time),
                'unsettled' => self::ts($r->delivery_complete_time),
                default => null,
            } ?? self::ts($r->changed_at) ?? $placed;
            $board = $stage === 'accepted' && $isWeighed ? 'ready' : $stage;

            $out[] = [
                'order_id'       => (string) $r->order_id,
                'status'         => (string) $r->order_status,
                'stage'          => $stage,
                'board'          => $board,
                'actions'        => OrderFlow::actionsFor($r->order_status),
                'customer'       => self::customerName($b, $a, (int) $r->buyer_id),
                'phone'          => (string) ($a->phone ?? $b->phone ?? ''),
                'area'           => trim(implode(', ', array_filter([$a->locality ?? null, $a->city ?? null]))),
                'items'          => (int) $r->line_count,
                'bill'           => $bill,
                'adjustment'     => $adj,
                'amount'         => self::money($bill + $adj),
                'expected'       => OrderFlow::expectedCollection($r->payment_type, $bill, $adj),
                'payment_type'   => (string) ($r->payment_type ?: 'Cash'),
                'prepaid'        => OrderFlow::isPrepaid($r->payment_type),
                'payment_status' => (string) ($r->payment_status ?: 'Pending'),
                'delivery_type'  => (string) ($r->delivery_type ?: 'Express'),
                'slot'           => trim((string) $r->schedule_time),
                'schedule_date'  => (string) (OrderFlow::normDate($r->schedule_date) ?? ''),
                'placed_at'      => $placed,
                'since'          => $since,
                'due_at'         => $due,
                'delivered_at'   => self::ts($r->delivery_complete_time),   // SLA: delivered on time or late
                'weighed'        => $isWeighed,
                'late'           => $active && !$stale && $stage !== 'unsettled' && $due !== null && $due < $now,
                'later'          => $active && !$stale && $stage !== 'unsettled' && $due !== null && $due > $endOfToday,
                'stale'          => $stale,
                'rider_id'       => (int) $r->rider_id ?: null,
                'rider'          => $rider->name ?? null,
                'cancel_reason'  => $stage === 'cancelled' ? (string) ($r->reason_for_order_cancel ?? '') : '',
            ];
        }
        return $out;
    }

    /** Best available name: account name, then address name, then phone, then the customer number. */
    private static function customerName(?object $b, ?object $a, int $buyerId): string
    {
        foreach ([$b->name ?? null, $a->name ?? null] as $n) {
            $n = trim((string) $n);
            if ($n !== '' && strcasecmp($n, 'null') !== 0) return $n;
        }
        $p = trim((string) ($a->phone ?? $b->phone ?? ''));
        if ($p !== '') return 'Customer ' . $p;
        return $buyerId ? 'Customer #' . $buyerId : 'Walk-in customer';
    }

    /** Per rider: orders out now, delivered orders whose money is still with the rider. */
    /**
     * Rider desk: every rider of the hub with a live state, plus the smart pick for the next order.
     *   available  at the hub and free (idle_since = since when)
     *   out        carrying orders now
     *   returning  delivered the last order and riding back (eta = expected arrival)
     *   off        marked off duty by the hub
     */
    private function riderSummary(array $orders): array
    {
        $now = time();
        $ret = max(1, (int) config('pos_console.rider_return_minutes', 15));
        $blank = ['out' => 0, 'unsettled' => 0, 'cash_due' => 0.0, 'oldest' => null, 'out_since' => null, 'areas' => [],
            'state' => 'available', 'state_since' => null, 'eta' => null, 'idle_since' => null, 'on_duty' => true, 'delivered_today' => 0, 'smart' => false, 'smart_reason' => ''];
        $list = [];
        foreach ($this->riders() as $r) $list[$r['id']] = $r + $blank;
        foreach ($orders as $o) {
            if (!in_array($o['stage'], ['out', 'unsettled'], true)) continue;
            $rid = (int) $o['rider_id'];
            if (!isset($list[$rid])) {
                $list[$rid] = ['id' => $rid, 'name' => $rid ? ($o['rider'] ?: ('Rider #' . $rid)) : 'No rider assigned', 'phone' => '', 'code' => ''] + $blank;
            }
            if ($o['stage'] === 'out') {
                $list[$rid]['out']++;
                $list[$rid]['out_since'] = min($list[$rid]['out_since'] ?? PHP_INT_MAX, $o['since'] ?? $now);
                if ($o['area'] !== '' && !in_array($o['area'], $list[$rid]['areas'], true)) $list[$rid]['areas'][] = $o['area'];
            } else {
                $list[$rid]['unsettled']++;
                $list[$rid]['cash_due'] = round($list[$rid]['cash_due'] + $o['expected'], 2);
                $list[$rid]['oldest'] = min($list[$rid]['oldest'] ?? PHP_INT_MAX, $o['since'] ?? PHP_INT_MAX);
            }
        }
        $status = self::table('pos_rider_status') ? DB::table('pos_rider_status')->whereIn('rider_id', array_keys($list) ?: [0])->get()->keyBy('rider_id') : collect();
        $del = collect();
        if (self::has('orders', 'delivery_complete_time') && self::has('orders', 'rider_id')) {
            $del = DB::table('orders')->where('hub_id', $this->hubId)->whereNotNull('rider_id')->where('delivery_complete_time', '>=', date('Y-m-d 00:00:00'))
                ->select('rider_id', DB::raw('COUNT(DISTINCT order_id) AS n'), DB::raw('MAX(delivery_complete_time) AS last_at'))->groupBy('rider_id')->get()->keyBy('rider_id');
        }
        foreach ($list as $id => &$r) {
            if (!$id) { $r['state'] = 'none'; continue; }
            $st = $status[$id] ?? null;
            $r['on_duty'] = $st ? (bool) $st->on_duty : true;
            $r['delivered_today'] = (int) ($del[$id]->n ?? 0);
            $last = self::ts($del[$id]->last_at ?? null);
            $back = self::ts($st->back_at ?? null);
            if (!$r['on_duty']) {
                $r['state'] = 'off'; $r['state_since'] = self::ts($st->updated_at ?? null);
            } elseif ($r['out'] > 0) {
                $r['state'] = 'out'; $r['state_since'] = $r['out_since'];
            } elseif ($last && (!$back || $back < $last) && $now < $last + $ret * 60) {
                $r['state'] = 'returning'; $r['state_since'] = $last; $r['eta'] = $last + $ret * 60;
            } else {
                $r['state'] = 'available';
                $marks = array_filter([$back, $last ? $last + $ret * 60 : null]);
                $r['idle_since'] = $marks ? max($marks) : null;
                $r['state_since'] = $r['idle_since'];
            }
        }
        unset($r);
        $pick = $this->pickRider($list);
        if ($pick) { $list[$pick['id']]['smart'] = true; $list[$pick['id']]['smart_reason'] = $pick['smart_reason']; }
        $list = array_values($list);
        $rank = ['available' => 0, 'returning' => 1, 'out' => 2, 'off' => 3, 'none' => 4];
        usort($list, fn ($a, $b) => [$rank[$a['state']] ?? 5, $b['smart'], $a['name']] <=> [$rank[$b['state']] ?? 5, $a['smart'], $b['name']]);
        return $list;
    }

    /**
     * Smart pick (fair and fast): a free rider at the hub, the one waiting longest first (fewer deliveries today breaks ties);
     * if nobody is free, the rider who will be back soonest.
     */
    private function pickRider(array $list): ?array
    {
        $now = time();
        $free = array_values(array_filter($list, fn ($r) => $r['id'] && $r['state'] === 'available'));
        if ($free) {
            usort($free, fn ($a, $b) => [$a['idle_since'] ?? 0, $a['delivered_today'], $a['name']] <=> [$b['idle_since'] ?? 0, $b['delivered_today'], $b['name']]);
            $p = $free[0];
            $p['smart_reason'] = 'at the hub' . ($p['idle_since'] ? ', waiting ' . max(1, (int) round(($now - $p['idle_since']) / 60)) . ' min' : ', free all day')
                . ', ' . $p['delivered_today'] . ($p['delivered_today'] === 1 ? ' delivery' : ' deliveries') . ' today';
            return $p;
        }
        $back = array_values(array_filter($list, fn ($r) => $r['id'] && $r['state'] === 'returning'));
        if ($back) {
            usort($back, fn ($a, $b) => $a['eta'] <=> $b['eta']);
            $p = $back[0];
            $p['smart_reason'] = 'back at the hub in about ' . max(1, (int) round(($p['eta'] - $now) / 60)) . ' min';
            return $p;
        }
        return null;
    }

    /** Live rider list (used when dispatching without the board's feed). */
    private function liveRiders(): array
    {
        $rows = (clone $this->orderSummaryQuery())->whereIn('o.order_status', OrderFlow::statusesFor(['out', 'unsettled']))->limit(400)->get();
        return $this->riderSummary($this->decorate($rows->all()));
    }

    /** Hub marks a rider: back at the hub, off duty, or on duty again. */
    public function riderStatus(int $riderId, string $action, array $actor): array
    {
        if (!in_array($riderId, array_column($this->riders(), 'id'), true)) return ['ok' => false, 'message' => 'That rider does not belong to this hub.'];
        if (!self::table('pos_rider_status')) return ['ok' => false, 'message' => 'Rider status is not set up yet (database table missing).'];
        $now = now();
        $row = ['hub_id' => $this->hubId, 'updated_at' => $now];
        if ($action === 'back') { $row['back_at'] = $now; $row['on_duty'] = 1; }
        elseif ($action === 'off') { $row['on_duty'] = 0; }
        elseif ($action === 'on') { $row['on_duty'] = 1; $row['back_at'] = $now; }
        else return ['ok' => false, 'message' => 'Unknown rider action.'];
        DB::table('pos_rider_status')->updateOrInsert(['rider_id' => $riderId], $row);
        $name = collect($this->riders())->firstWhere('id', $riderId)['name'] ?? ('#' . $riderId);
        Log::info('[freshful-audit] pos-rider-' . $action, ['hub' => $this->hubId, 'rider' => $riderId, 'by' => ($actor['guard'] ?? '?') . '#' . ($actor['id'] ?? '?')]);
        $msg = ['back' => $name . ' is back at the hub.', 'off' => $name . ' is off duty. No orders will be assigned.', 'on' => $name . ' is on duty and available.'][$action];
        return ['ok' => true, 'message' => $msg];
    }

    /** Give an order that is out for delivery to a different rider. */
    public function reassign(string $orderId, int $riderId, array $actor): array
    {
        $riders = $this->riders();
        $rider = collect($riders)->firstWhere('id', $riderId);
        if (!$rider) return ['ok' => false, 'message' => 'That rider does not belong to this hub.'];
        $res = DB::transaction(function () use ($orderId, $riderId) {
            $rows = DB::table('orders')->where('order_id', $orderId)->where('hub_id', $this->hubId)->lockForUpdate()->get(['id', 'order_status']);
            if ($rows->isEmpty()) return ['ok' => false, 'message' => 'Order not found in this hub.'];
            if (OrderFlow::stage($rows->first()->order_status) !== 'out') return ['ok' => false, 'message' => 'Only orders out for delivery can change rider.'];
            $upd = ['rider_id' => $riderId];
            if (self::has('orders', 'updated_at')) $upd['updated_at'] = now();
            DB::table('orders')->where('order_id', $orderId)->where('hub_id', $this->hubId)->update($upd);
            return ['ok' => true];
        });
        if ($res['ok']) {
            $this->event($orderId, 'reassign', $actor, ['text' => 'Rider changed to ' . $rider['name'], 'rider_id' => $riderId]);
            $res['message'] = 'Order ' . $orderId . ' is now with ' . $rider['name'] . '.';
        }
        return $res;
    }

    /** Today's numbers for this hub. */
    private function kpis(array $orders): array
    {
        $today = date('Y-m-d');
        $rows = (clone $this->orderSummaryQuery())->whereDate('o.date_added', $today)->get();
        $count = 0; $sales = 0.0; $cancelled = 0;
        foreach ($rows as $r) {
            $count++;
            if (OrderFlow::stage($r->order_status) === 'cancelled') { $cancelled++; continue; }
            $sales += (float) $r->total_amount + (float) $r->delivery_charge + (float) $r->amount_to_collect - (float) $r->amount_to_return;
        }

        $delivered = 0; $deliverMins = []; $packMins = [];
        if (self::has('orders', 'delivery_complete_time')) {
            $d = DB::table('orders')->where('hub_id', $this->hubId)->whereDate('delivery_complete_time', $today)
                ->select('order_id', DB::raw('MAX(date_added) AS placed'), DB::raw('MAX(delivery_complete_time) AS done'))
                ->groupBy('order_id')->get();
            $delivered = $d->count();
            foreach ($d as $x) {
                $m = (self::ts($x->done) - (self::ts($x->placed) ?? 0)) / 60;
                if ($x->placed && $m >= 0 && $m <= 24 * 60) $deliverMins[] = $m;   // ignore old orders closed today
            }
        }
        if (self::has('orders', 'order_complete_time') && self::has('orders', 'shipping_complete_time')) {
            $p = DB::table('orders')->where('hub_id', $this->hubId)->whereDate('shipping_complete_time', $today)
                ->select('order_id', DB::raw('MAX(order_complete_time) AS acc'), DB::raw('MAX(shipping_complete_time) AS out_at'))
                ->groupBy('order_id')->get();
            foreach ($p as $x) {
                $m = (self::ts($x->out_at) - (self::ts($x->acc) ?? 0)) / 60;
                if ($x->acc && $m >= 0 && $m <= 12 * 60) $packMins[] = $m;
            }
        }

        $withRiders = 0.0; $withRidersCount = 0;
        foreach ($orders as $o) {
            if ($o['stage'] === 'unsettled' && !$o['stale']) { $withRiders += $o['expected']; $withRidersCount++; }
        }
        $settledToday = ['cash' => 0.0, 'upi' => 0.0, 'card' => 0.0, 'received' => 0.0];
        if (self::table('pos_cash_handovers')) {
            $h = DB::table('pos_cash_handovers')->where('hub_id', $this->hubId)->whereDate('created_at', $today)
                ->selectRaw('COALESCE(SUM(cash_amount),0) c, COALESCE(SUM(upi_amount),0) u, COALESCE(SUM(card_amount),0) k, COALESCE(SUM(received),0) r')->first();
            $settledToday = ['cash' => self::money($h->c), 'upi' => self::money($h->u), 'card' => self::money($h->k), 'received' => self::money($h->r)];
        }

        return [
            'orders_today'      => $count,
            'sales_today'       => round($sales, 2),
            'delivered_today'   => $delivered,
            'cancelled_today'   => $cancelled,
            'avg_pack_min'      => $packMins ? (int) round(array_sum($packMins) / count($packMins)) : null,
            'avg_delivery_min'  => $deliverMins ? (int) round(array_sum($deliverMins) / count($deliverMins)) : null,
            'with_riders'       => round($withRiders, 2),
            'with_riders_count' => $withRidersCount,
            'settled_today'     => $settledToday,
        ];
    }

    /* ------------------------------------------------------------------ one order */

    /** Lines of one order with product info (hub-scoped). */
    private function lines(string $orderId)
    {
        return DB::table('orders AS o')
            ->leftJoin('products AS p', 'p.id', '=', 'o.product_id')
            ->leftJoin('items AS i', 'i.id', '=', 'p.item_id')
            ->where('o.order_id', $orderId)->where('o.hub_id', $this->hubId)
            ->select('o.*', 'p.product_name', 'p.product_unit', 'p.unit_quantity', 'p.main_price', 'p.MRP', 'p.product_image', 'p.product_id AS sku', 'i.item AS item_name')
            ->orderBy('o.id')->get();
    }

    public function detail(string $orderId): ?array
    {
        $lines = $this->lines($orderId);
        if ($lines->isEmpty()) return null;

        $summaryRow = (clone $this->orderSummaryQuery())->where('o.order_id', $orderId)->first();
        $summary = $this->decorate([$summaryRow])[0];
        $f = $lines->first();

        $weights = self::table('pos_order_weights')
            ? DB::table('pos_order_weights')->where('order_id', $orderId)->get()->keyBy('order_row_id') : collect();

        $items = [];
        $itemsValue = 0.0;
        foreach ($lines as $l) {
            $qty = (float) $l->quantity;
            $price = (float) $l->main_price;
            $packG = OrderFlow::packGrams($l->unit_quantity, $l->product_unit);
            $w = $weights[(int) $l->id] ?? null;
            $itemsValue += $qty * $price;
            $items[] = [
                'row_id'     => (int) $l->id,
                'name'       => trim((string) ($l->item_name ?: $l->product_name ?: ('Product #' . $l->product_id))),
                'sku'        => (string) ($l->sku ?? ''),
                'unit'       => trim(((float) $l->unit_quantity ? rtrim(rtrim(number_format((float) $l->unit_quantity, 3, '.', ''), '0'), '.') . ' ' : '') . (string) $l->product_unit),
                'qty'        => $qty,
                'price'      => $price,
                'mrp'        => (float) $l->MRP,
                'line'       => round($qty * $price, 2),
                'pack_g'     => $packG,
                'weighable'  => $packG !== null,
                'expected_g' => $packG !== null ? round($packG * $qty, 2) : null,
                'actual_g'   => $w ? (float) $w->actual_g : null,
                'diff'       => $w ? (float) $w->amount_diff : null,
                'image'      => $l->product_image ? url('uploads/images/products/' . $l->product_image) : null,
            ];
        }

        $buyer = DB::table('buyers')->where('id', $f->buyer_id)->first();
        $addr = DB::table('buyer_address')->where('id', $f->address_id)->first();
        $addressText = $addr ? trim(implode(', ', array_filter([
            $addr->house_no ?? null, $addr->street_name ?? null, $addr->locality ?? null, $addr->landmark ?? null, $addr->city ?? null, $addr->pincode ?? null,
        ]))) : '';
        $lat = $addr->latitude ?? ($addr->lat ?? null);
        $lng = $addr->longitude ?? ($addr->lng ?? null);
        $map = ($lat && $lng) ? 'https://www.google.com/maps?q=' . $lat . ',' . $lng
            : ($addressText !== '' ? 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode($addressText) : null);

        $promo = null;
        try {
            $promo = DB::table('home_promotions')
                ->join('buyer_coupon_relation', 'buyer_coupon_relation.promo_id', '=', 'home_promotions.id')
                ->select('home_promotions.title', 'home_promotions.percentage', 'buyer_coupon_relation.discount')
                ->where('buyer_coupon_relation.order_id', $orderId)->first();
        } catch (\Throwable $e) {
            $promo = null;
        }

        // Timeline: stamps from the orders table + everything the POS recorded.
        $timeline = [['label' => 'Order placed', 'at' => $f->date_added, 'by' => null]];
        foreach (['order_complete_time' => 'Accepted', 'processing_complete_time' => 'Weighed',
                  'shipping_complete_time' => 'Out for delivery', 'delivery_complete_time' => 'Delivered'] as $col => $label) {
            if (!empty($f->$col)) $timeline[] = ['label' => $label, 'at' => $f->$col, 'by' => null];
        }
        if (self::table('pos_order_events')) {
            $names = ['accept' => 'Accepted', 'reject' => 'Rejected', 'relocate' => 'Moved from another hub', 'relocate-out' => 'Moved to another hub', 'reassign' => 'Rider changed',
                'weigh' => 'Weighed', 'dispatch' => 'Out for delivery', 'deliver' => 'Delivered (payment with rider)',
                'settle' => 'Settled', 'cancel' => 'Cancelled', 'close-stale' => 'Closed in cleanup'];
            $timeline = [['label' => 'Order placed', 'at' => $f->date_added, 'by' => null]];
            $ev = DB::table('pos_order_events')->where('order_id', $orderId)->orderBy('id')->get();
            $seen = [];
            foreach ($ev as $e) {
                $d = json_decode((string) $e->detail, true) ?: [];
                $label = $names[$e->action] ?? ucfirst($e->action);
                if (!empty($d['text'])) $label .= ': ' . $d['text'];
                $timeline[] = ['label' => $label, 'at' => $e->created_at, 'by' => $e->actor_name];
                $seen[$e->action] = true;
            }
            foreach (['order_complete_time' => ['accept', 'Accepted'], 'shipping_complete_time' => ['dispatch', 'Out for delivery'],
                      'delivery_complete_time' => ['deliver', 'Delivered']] as $col => [$act, $label]) {
                if (!empty($f->$col) && empty($seen[$act]) && empty($seen['settle'])) $timeline[] = ['label' => $label, 'at' => $f->$col, 'by' => null];
            }
            usort($timeline, fn ($a, $b) => strcmp((string) $a['at'], (string) $b['at']));
        }
        if ($summary['stage'] === 'cancelled' && !empty($f->reason_for_order_cancel)) {
            $timeline[] = ['label' => 'Reason: ' . $f->reason_for_order_cancel, 'at' => $f->updated_at ?? null, 'by' => null];
        }

        $settlement = null;
        if (self::table('pos_settlements')) {
            $s = DB::table('pos_settlements AS s')->leftJoin('pos_cash_handovers AS h', 'h.id', '=', 's.handover_id')
                ->where('s.order_id', $orderId)->select('s.*', 'h.cash_amount', 'h.upi_amount', 'h.card_amount', 'h.reference', 'h.by_name', 'h.orders_count', 'h.created_at AS at')->first();
            if ($s) $settlement = (array) $s;
        }

        return $summary + [
            'lines'          => $items,
            'items_value'    => round($itemsValue, 2),
            'total_amount'   => (float) $f->total_amount,
            'delivery_charge'=> (float) $f->delivery_charge,
            'discount'       => $promo ? round((float) $promo->discount, 2) : 0,
            'promo'          => $promo->title ?? null,
            'buyer_email'    => $buyer->email ?? null,
            'address'        => $addressText,
            'address_name'   => $addr->name ?? null,
            'address_type'   => $addr->type ?? ($addr->address_type ?? null),
            'map'            => $map,
            'paid_ref'       => $f->payment_id ?? null,
            'collect'        => (float) ($f->amount_to_collect ?? 0),
            'return'         => (float) ($f->amount_to_return ?? 0),
            'timeline'       => $timeline,
            'settlement'     => $settlement,
            'riders'         => $this->riders(),
        ];
    }

    /* ------------------------------------------------------------------ changes */

    /** accept / reject / dispatch / deliver / cancel */
    public function act(string $orderId, string $action, ?string $reason, ?int $riderId, array $actor, bool $auto = false): array
    {
        if (!in_array($action, OrderFlow::SIMPLE_ACTIONS, true)) return ['ok' => false, 'message' => 'Unknown action.'];
        $smartNote = '';
        if ($action === 'dispatch' && !$riderId && $auto && $this->riders()) {
            $pick = $this->pickRider(array_column($this->liveRiders(), null, 'id'));
            if (!$pick) return ['ok' => false, 'code' => 'no_rider', 'message' => 'No rider is free right now. Choose one manually.'];
            $riderId = (int) $pick['id'];
            $smartNote = ' (smart pick: ' . $pick['smart_reason'] . ')';
        }
        if (in_array($action, ['reject', 'cancel'], true) && trim((string) $reason) === '') {
            return ['ok' => false, 'message' => 'Please choose a reason.'];
        }
        $riders = $action === 'dispatch' ? $this->riders() : [];
        if ($action === 'dispatch') {
            if ($riders && !$riderId) return ['ok' => false, 'message' => 'Choose the rider who is taking this order.'];
            if ($riderId && !in_array($riderId, array_column($riders, 'id'), true)) {
                return ['ok' => false, 'message' => 'That rider does not belong to this hub.'];
            }
        }

        $result = DB::transaction(function () use ($orderId, $action, $reason, $riderId) {
            $rows = DB::table('orders')->where('order_id', $orderId)->where('hub_id', $this->hubId)->lockForUpdate()
                ->get(['id', 'order_status', 'payment_type', 'total_amount', 'delivery_charge', 'product_id']);
            if ($rows->isEmpty()) return ['ok' => false, 'message' => 'Order not found in this hub.'];
            $f = $rows->first();
            $current = (string) $f->order_status;
            $move = OrderFlow::move($current, $action);
            if (!$move['ok']) return $move + ['from' => $current];

            if ($action === 'dispatch' && $this->needsWeighing($orderId)) {
                return ['ok' => false, 'message' => 'Weigh the items first. If the weights are exact, press "Weights exact" in the weighing window.'];
            }

            $to = $move['to'];
            $now = now();
            $autoSettle = false;
            if ($action === 'deliver') {
                $adj = $this->adjustmentOf($orderId);
                $expected = OrderFlow::expectedCollection($f->payment_type, (float) $f->total_amount + (float) $f->delivery_charge, $adj);
                if (abs($expected) < 0.005) { $to = OrderFlow::DELIVERED; $autoSettle = true; }   // prepaid, nothing to collect
            }
            $upd = ['order_status' => $to];
            if (self::has('orders', 'updated_at')) $upd['updated_at'] = $now;
            if ($move['stamp'] && self::has('orders', $move['stamp'])) $upd[$move['stamp']] = $now;
            if (in_array($action, ['reject', 'cancel'], true) && self::has('orders', 'reason_for_order_cancel')) {
                $upd['reason_for_order_cancel'] = mb_substr(trim((string) $reason), 0, 250);
            }
            if ($action === 'dispatch' && $riderId && self::has('orders', 'rider_id')) $upd['rider_id'] = $riderId;
            if ($autoSettle) $upd['payment_status'] = 'Paid';

            DB::table('orders')->where('order_id', $orderId)->where('hub_id', $this->hubId)->update($upd);
            if ($autoSettle && self::table('pos_settlements')) {
                DB::table('pos_settlements')->insertOrIgnore([
                    'handover_id' => 0, 'order_id' => $orderId, 'hub_id' => $this->hubId, 'rider_id' => null,
                    'payment_type' => (string) $f->payment_type, 'bill' => self::money((float) $f->total_amount + (float) $f->delivery_charge),
                    'adjustment' => 0, 'expected' => 0, 'created_at' => $now,
                ]);
            }
            return ['ok' => true, 'from' => $current, 'to' => $to, 'auto_settled' => $autoSettle];
        });

        if ($result['ok']) {
            $text = in_array($action, ['reject', 'cancel'], true) ? trim((string) $reason) : null;
            if ($action === 'dispatch' && $riderId) {
                $text = 'Rider ' . (collect($riders)->firstWhere('id', $riderId)['name'] ?? ('#' . $riderId));
            }
            $this->event($orderId, $action, $actor, array_filter(['text' => $text, 'rider_id' => $riderId, 'from' => $result['from'], 'to' => $result['to']]));
            if ($result['to'] === OrderFlow::DELIVERED) $this->awardReferral($orderId);
            $label = OrderFlow::STAGE_LABEL[OrderFlow::stage($result['to'])];
            $result['message'] = 'Order ' . $orderId . ': ' . ($result['auto_settled'] ? 'Delivered and settled (prepaid)' : $label);
            if ($action === 'dispatch' && $riderId) {
                $result['message'] = 'Order ' . $orderId . ' is out with ' . (collect($riders)->firstWhere('id', $riderId)['name'] ?? 'the rider') . $smartNote . '.';
                $result['rider_id'] = $riderId;
            }
        }
        return $result;
    }

    private function needsWeighing(string $orderId): bool
    {
        $lines = $this->lines($orderId);
        $weighable = $lines->filter(fn ($l) => OrderFlow::packGrams($l->unit_quantity, $l->product_unit) !== null);
        if ($weighable->isEmpty()) return false;
        if (!self::table('pos_order_weights')) return false;
        $done = DB::table('pos_order_weights')->where('order_id', $orderId)->pluck('order_row_id')->map(fn ($x) => (int) $x)->all();
        foreach ($weighable as $l) {
            if (!in_array((int) $l->id, $done, true)) return true;
        }
        return false;
    }

    private function adjustmentOf(string $orderId): float
    {
        $r = DB::table('orders')->where('order_id', $orderId)->where('hub_id', $this->hubId)
            ->selectRaw('MAX(' . (self::has('orders', 'amount_to_collect') ? 'amount_to_collect' : '0') . ') c, MAX(' . (self::has('orders', 'amount_to_return') ? 'amount_to_return' : '0') . ') r')->first();
        return self::money((float) ($r->c ?? 0) - (float) ($r->r ?? 0));
    }

    private function awardReferral(string $orderId): void
    {
        try {
            if (class_exists(\App\Services\BuyerReferralService::class)) {
                app(\App\Services\BuyerReferralService::class)->awardForDeliveredOrder($orderId);
            }
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /** Save the real weight of each line; the bill difference is stored the old-POS way (amount_to_collect / amount_to_return). */
    public function weigh(string $orderId, array $actualByRow, array $actor): array
    {
        if (!self::table('pos_order_weights')) return ['ok' => false, 'message' => 'Weighing is not set up yet (database table missing).'];

        $result = DB::transaction(function () use ($orderId, $actualByRow, $actor) {
            $locked = DB::table('orders')->where('order_id', $orderId)->where('hub_id', $this->hubId)->lockForUpdate()->get(['id', 'order_status']);
            if ($locked->isEmpty()) return ['ok' => false, 'message' => 'Order not found in this hub.'];
            $move = OrderFlow::move($locked->first()->order_status, 'weigh');
            if (!$move['ok']) return $move;

            $lines = $this->lines($orderId);
            $total = 0.0; $saved = []; $now = now();
            foreach ($lines as $l) {
                $packG = OrderFlow::packGrams($l->unit_quantity, $l->product_unit);
                if ($packG === null) continue;
                $key = (string) $l->id;
                if (!array_key_exists($key, $actualByRow) || $actualByRow[$key] === null || $actualByRow[$key] === '') {
                    return ['ok' => false, 'message' => 'Enter the weight for every item sold by weight.'];
                }
                $actual = (float) $actualByRow[$key];
                $expectedG = $packG * (float) $l->quantity;
                if ($actual <= 0 || $actual > max(5 * $expectedG, $expectedG + 5000)) {
                    return ['ok' => false, 'message' => 'The weight for ' . ($l->item_name ?: $l->product_name) . ' looks wrong (' . $actual . ' g). Please check.'];
                }
                $calc = OrderFlow::weightAdjustment($packG, (float) $l->quantity, (float) $l->main_price, $actual);
                $total += $calc['amount'];
                DB::table('pos_order_weights')->updateOrInsert(['order_row_id' => (int) $l->id], [
                    'order_id' => $orderId, 'hub_id' => $this->hubId, 'product_id' => (int) $l->product_id,
                    'qty' => (float) $l->quantity, 'pack_g' => $packG, 'expected_g' => $calc['expected_g'], 'actual_g' => $actual,
                    'pack_price' => (float) $l->main_price, 'amount_diff' => $calc['amount'],
                    'by_id' => $actor['id'] ?? null, 'by_name' => mb_substr((string) ($actor['name'] ?? ''), 0, 120),
                    'created_at' => $now, 'updated_at' => $now,
                ]);
                $saved[] = ['item' => $l->item_name ?: $l->product_name, 'expected_g' => $calc['expected_g'], 'actual_g' => $actual, 'amount' => $calc['amount']];
            }
            if (!$saved) return ['ok' => false, 'message' => 'No item in this order is sold by weight. You can dispatch it directly.'];
            $total = self::money($total);
            $upd = [];
            if (self::has('orders', 'amount_to_collect')) $upd['amount_to_collect'] = $total > 0 ? $total : 0;
            if (self::has('orders', 'amount_to_return')) $upd['amount_to_return'] = $total < 0 ? -$total : 0;
            if (self::has('orders', 'processing_complete_time')) $upd['processing_complete_time'] = $now;
            if (self::has('orders', 'updated_at')) $upd['updated_at'] = $now;
            if ($upd) DB::table('orders')->where('order_id', $orderId)->where('hub_id', $this->hubId)->update($upd);
            return ['ok' => true, 'adjustment' => $total, 'lines' => $saved];
        });

        if ($result['ok']) {
            $adj = $result['adjustment'];
            $text = $adj > 0 ? 'collect ₹' . number_format($adj, 2) . ' extra' : ($adj < 0 ? 'return ₹' . number_format(-$adj, 2) : 'weights exact');
            $this->event($orderId, 'weigh', $actor, ['text' => $text, 'lines' => $result['lines']]);
            $result['message'] = 'Weights saved: ' . $text . '.';
        }
        return $result;
    }

    /** Move a new/accepted order to another hub. It arrives there as a new order (with sound). */
    public function relocate(string $orderId, int $toHub, string $note, array $actor): array
    {
        if ($toHub === $this->hubId) return ['ok' => false, 'message' => 'The order is already in this hub.'];
        $target = collect($this->hubsForRelocation())->firstWhere('id', $toHub);
        if (!$target) return ['ok' => false, 'message' => 'Choose an active hub.'];

        $result = DB::transaction(function () use ($orderId, $toHub) {
            $rows = DB::table('orders')->where('order_id', $orderId)->where('hub_id', $this->hubId)->lockForUpdate()->get(['id', 'order_status']);
            if ($rows->isEmpty()) return ['ok' => false, 'message' => 'Order not found in this hub.'];
            $move = OrderFlow::move($rows->first()->order_status, 'relocate');
            if (!$move['ok']) return $move;
            $upd = ['hub_id' => $toHub, 'order_status' => OrderFlow::PENDING];
            if (self::has('orders', 'rider_id')) $upd['rider_id'] = null;
            if (self::has('orders', 'updated_at')) $upd['updated_at'] = now();
            DB::table('orders')->where('order_id', $orderId)->where('hub_id', $this->hubId)->update($upd);
            if (self::table('pos_order_weights')) DB::table('pos_order_weights')->where('order_id', $orderId)->delete();
            return ['ok' => true];
        });
        if ($result['ok']) {
            $me = $this->hub();
            $note = mb_substr(trim($note), 0, 200);
            $this->event($orderId, 'relocate-out', $actor, ['text' => $target['name'] . ($note ? ' - ' . $note : ''), 'to_hub' => $toHub]);
            $this->event($orderId, 'relocate', $actor, ['text' => ($me->hub ?? ('hub #' . $this->hubId)) . ($note ? ' - ' . $note : ''), 'from_hub' => $this->hubId], $toHub);
            $result['message'] = 'Order ' . $orderId . ' moved to ' . $target['name'] . ($target['city'] ? ', ' . $target['city'] : '') . '.';
        }
        return $result;
    }

    /**
     * Receive money for one or more orders (a rider handing over, or the counter).
     * $cash/$upi/$card are what was actually received. Short or excess needs a note.
     */
    public function settle(array $orderIds, ?int $riderId, float $cash, float $upi, float $card, ?string $ref, ?string $note, array $actor): array
    {
        $orderIds = array_values(array_unique(array_filter(array_map('strval', $orderIds))));
        if (!$orderIds) return ['ok' => false, 'message' => 'Choose at least one order.'];
        if (count($orderIds) > 200) return ['ok' => false, 'message' => 'Too many orders at once (max 200).'];
        if (!self::table('pos_cash_handovers') || !self::table('pos_settlements')) {
            return ['ok' => false, 'message' => 'Settlement is not set up yet (database tables missing).'];
        }
        if ($riderId && !in_array($riderId, array_column($this->riders(), 'id'), true)) {
            $riderId = null;   // rider moved/disabled - still allow the hub to receive the money
        }

        $result = DB::transaction(function () use ($orderIds, $riderId, $cash, $upi, $card, $ref, $note, $actor) {
            $rows = DB::table('orders')->whereIn('order_id', $orderIds)->where('hub_id', $this->hubId)->lockForUpdate()->get();
            $byOrder = $rows->groupBy('order_id');
            $items = []; $expected = 0.0;
            foreach ($orderIds as $id) {
                $g = $byOrder[$id] ?? null;
                if (!$g || $g->isEmpty()) return ['ok' => false, 'message' => 'Order ' . $id . ' is not in this hub.'];
                $f = $g->first();
                if (!OrderFlow::canSettle($f->order_status)) {
                    return ['ok' => false, 'message' => 'Order ' . $id . ' is "' . OrderFlow::STAGE_LABEL[OrderFlow::stage($f->order_status)] . '" and cannot be settled. Refresh and try again.'];
                }
                $bill = self::money((float) $f->total_amount + (float) $f->delivery_charge);
                $adj = self::money((float) ($f->amount_to_collect ?? 0) - (float) ($f->amount_to_return ?? 0));
                $exp = OrderFlow::expectedCollection($f->payment_type, $bill, $adj);
                $expected += $exp;
                $items[] = ['id' => $id, 'bill' => $bill, 'adj' => $adj, 'exp' => $exp, 'type' => (string) $f->payment_type,
                    'rider' => (int) ($f->rider_id ?? 0) ?: null, 'stamped' => !empty($f->delivery_complete_time)];
            }
            $expected = self::money($expected);
            $received = self::money($cash + $upi + $card);
            $diff = self::money($received - $expected);
            if (abs($diff) >= 0.01 && trim((string) $note) === '') {
                return ['ok' => false, 'message' => ($diff < 0 ? 'Short by ₹' . number_format(-$diff, 2) : 'Excess of ₹' . number_format($diff, 2))
                    . '. Write a note explaining the difference.', 'difference' => $diff];
            }
            if (($upi > 0 || $card > 0) && trim((string) $ref) === '' && count($items) === 1) {
                // reference optional, but recorded when given
            }

            $now = now();
            $handoverId = DB::table('pos_cash_handovers')->insertGetId([
                'hub_id' => $this->hubId, 'rider_id' => $riderId ?? ($items[0]['rider'] ?? null), 'orders_count' => count($items),
                'expected' => $expected, 'cash_amount' => self::money($cash), 'upi_amount' => self::money($upi), 'card_amount' => self::money($card),
                'received' => $received, 'difference' => $diff, 'reference' => $ref ? mb_substr(trim($ref), 0, 120) : null,
                'note' => $note ? mb_substr(trim($note), 0, 250) : null, 'by_guard' => $actor['guard'] ?? null, 'by_id' => $actor['id'] ?? null,
                'by_name' => mb_substr((string) ($actor['name'] ?? ''), 0, 120), 'created_at' => $now,
            ]);
            foreach ($items as $it) {
                DB::table('pos_settlements')->insert([
                    'handover_id' => $handoverId, 'order_id' => $it['id'], 'hub_id' => $this->hubId, 'rider_id' => $it['rider'],
                    'payment_type' => $it['type'], 'bill' => $it['bill'], 'adjustment' => $it['adj'], 'expected' => $it['exp'], 'created_at' => $now,
                ]);
                $upd = ['order_status' => OrderFlow::DELIVERED, 'payment_status' => 'Paid'];
                if (self::has('orders', 'updated_at')) $upd['updated_at'] = $now;
                if (!$it['stamped'] && self::has('orders', 'delivery_complete_time')) $upd['delivery_complete_time'] = $now;
                DB::table('orders')->where('order_id', $it['id'])->where('hub_id', $this->hubId)->update($upd);
            }
            return ['ok' => true, 'handover_id' => $handoverId, 'expected' => $expected, 'received' => $received, 'difference' => $diff, 'orders' => array_column($items, 'id')];
        });

        if ($result['ok']) {
            $parts = array_filter(['Cash ₹' . number_format($cash, 2) => $cash != 0, 'UPI ₹' . number_format($upi, 2) => $upi != 0, 'Card ₹' . number_format($card, 2) => $card != 0]);
            $text = implode(' + ', array_keys($parts)) ?: 'Nothing to collect';
            if (abs($result['difference']) >= 0.01) $text .= ($result['difference'] < 0 ? ', short ₹' : ', excess ₹') . number_format(abs($result['difference']), 2);
            foreach ($result['orders'] as $id) {
                $this->event($id, 'settle', $actor, ['text' => count($result['orders']) > 1 ? $text . ' (batch of ' . count($result['orders']) . ')' : $text, 'handover' => $result['handover_id']]);
                $this->awardReferral($id);
            }
            $result['message'] = count($result['orders']) . ' order(s) settled. ' . $text . '.';
        }
        return $result;
    }

    /** Cancel every "old open" order (new / accepted / out) of this hub in one go. */
    public function closeStale(string $reason, array $actor): array
    {
        $reason = mb_substr(trim($reason) !== '' ? trim($reason) : 'Old open order closed in cleanup', 0, 250);
        $cancellable = OrderFlow::statusesFor(['new', 'accepted', 'out']);
        $rows = (clone $this->orderSummaryQuery())->whereIn('o.order_status', $cancellable)
            ->where('o.date_added', '<', date('Y-m-d H:i:s', time() - OrderFlow::STALE_AFTER))->limit(500)->get();
        $ids = array_column(array_filter($this->decorate($rows->all()), fn ($o) => $o['stale']), 'order_id');
        if (!$ids) return ['ok' => true, 'closed' => 0, 'message' => 'There are no old open orders to cancel.'];

        DB::transaction(function () use ($ids, $cancellable, $reason) {
            $upd = ['order_status' => OrderFlow::CANCEL];
            if (self::has('orders', 'updated_at')) $upd['updated_at'] = now();
            if (self::has('orders', 'reason_for_order_cancel')) $upd['reason_for_order_cancel'] = $reason;
            foreach (array_chunk($ids, 100) as $chunk) {
                DB::table('orders')->where('hub_id', $this->hubId)->whereIn('order_id', $chunk)->whereIn('order_status', $cancellable)->update($upd);
            }
        });
        foreach ($ids as $id) $this->event($id, 'close-stale', $actor, ['text' => $reason]);
        return ['ok' => true, 'closed' => count($ids), 'message' => count($ids) . ' old open order(s) closed as cancelled.'];
    }

    /* ------------------------------------------------------------------ lists */

    public function search(string $q): array
    {
        $q = trim($q);
        if (mb_strlen($q) < 3) return [];
        $digits = preg_replace('/\D/', '', $q);
        $query = (clone $this->orderSummaryQuery())->orderByRaw('MIN(o.id) DESC')->limit(25);
        $addrIds = [];
        if (strlen($digits) >= 5) {
            $addrIds = DB::table('buyer_address')->where('phone', 'like', '%' . $digits . '%')->limit(200)->pluck('id')->all();
        }
        $query->where(function ($w) use ($q, $addrIds) {
            $w->where('o.order_id', 'like', '%' . $q . '%');
            if ($addrIds) $w->orWhereIn('o.address_id', $addrIds);
        });
        return $this->decorate($query->get()->all());
    }

    /** Orders placed between two dates, optionally one stage. */
    public function history(string $from, string $to, string $stage = ''): array
    {
        $q = (clone $this->orderSummaryQuery())->whereDate('o.date_added', '>=', $from)->whereDate('o.date_added', '<=', $to)
            ->orderByRaw('MIN(o.id) DESC')->limit(1000);
        if ($stage !== '' && in_array($stage, OrderFlow::STAGES, true)) $q->whereIn('o.order_status', OrderFlow::statusesFor([$stage]));
        return $this->decorate($q->get()->all());
    }

    /* ------------------------------------------------------------------ day end */

    public function dayReport(string $date): array
    {
        $start = $date . ' 00:00:00';
        $end = $date . ' 23:59:59';
        $now = time();
        $isToday = $date === date('Y-m-d');
        $types = [];
        foreach (['Express', 'Scheduled'] as $t) {
            $types[$t] = ['opening' => 0, 'received' => 0, 'delivered' => 0, 'settled' => 0, 'open' => 0, 'cancelled' => 0];
        }
        $typeOf = fn ($r) => strtolower((string) $r->delivery_type) === 'scheduled' ? 'Scheduled' : 'Express';

        // Orders placed on this day.
        $placed = (clone $this->orderSummaryQuery())->whereBetween('o.date_added', [$start, $end])->get();
        $invoice = ['orders' => 0, 'basic' => 0.0, 'discount' => 0.0, 'delivery' => 0.0, 'adjust' => 0.0, 'total' => 0.0];
        $placedIds = [];
        $payMix = [];
        foreach ($placed as $r) {
            $t = $typeOf($r);
            $types[$t]['received']++;
            $stage = OrderFlow::stage($r->order_status);
            if ($stage === 'cancelled') continue;
            $placedIds[] = $r->order_id;
            $adj = (float) $r->amount_to_collect - (float) $r->amount_to_return;
            $invoice['orders']++;
            $invoice['basic'] += (float) $r->total_amount;
            $invoice['delivery'] += (float) $r->delivery_charge;
            $invoice['adjust'] += $adj;
            $pt = $r->payment_type ?: 'Cash';
            $payMix[$pt] = ($payMix[$pt] ?? 0) + (float) $r->total_amount + (float) $r->delivery_charge + $adj;
        }
        if ($placedIds) {
            try {
                $invoice['discount'] = (float) DB::table('buyer_coupon_relation')->whereIn('order_id', $placedIds)->sum('discount');
            } catch (\Throwable $e) {
                $invoice['discount'] = 0.0;
            }
        }
        $invoice['basic'] += $invoice['discount'];   // old POS: base price = total + discount
        $invoice['total'] = $invoice['basic'] - $invoice['discount'] + $invoice['delivery'] + $invoice['adjust'];
        foreach ($invoice as $k => $v) $invoice[$k] = is_float($v) ? round($v, 2) : $v;

        // Opening: placed before this day and not finished when the day began (old open orders listed separately).
        $opening = (clone $this->orderSummaryQuery())->where('o.date_added', '<', $start)
            ->where(function ($q) use ($start) {
                $q->whereIn('o.order_status', OrderFlow::statusesFor(OrderFlow::ACTIVE))->orWhere('o.updated_at', '>=', $start);
            })->limit(2000)->get();
        $staleCount = 0;
        foreach ($opening as $r) {
            $stage = OrderFlow::stage($r->order_status);
            $finished = $stage === 'cancelled' ? self::ts($r->changed_at) : ($stage === 'delivered' ? (self::ts($r->delivery_complete_time) ?? self::ts($r->changed_at)) : null);
            if ($finished !== null && $finished < strtotime($start)) continue;
            $due = OrderFlow::dueAt($r->delivery_type, $r->date_added, $r->schedule_date, $r->schedule_time);
            if (($due ?? self::ts($r->date_added)) < strtotime($start) - OrderFlow::STALE_AFTER) { $staleCount++; continue; }
            $types[$typeOf($r)]['opening']++;
        }

        // Delivered on this day (rider handed to customer), cancelled on this day, still open now.
        if (self::has('orders', 'delivery_complete_time')) {
            $del = (clone $this->orderSummaryQuery())->whereBetween('o.delivery_complete_time', [$start, $end])
                ->whereIn('o.order_status', OrderFlow::statusesFor(['unsettled', 'delivered']))->get();
            foreach ($del as $r) $types[$typeOf($r)]['delivered']++;
        }
        $cancelledList = [];
        $can = (clone $this->orderSummaryQuery())->whereIn('o.order_status', OrderFlow::statusesFor(['cancelled']))
            ->whereBetween('o.updated_at', [$start, $end])->get();
        foreach ($this->decorate($can->all()) as $o) {
            $types[$o['delivery_type'] === 'Scheduled' ? 'Scheduled' : 'Express']['cancelled']++;
            $cancelledList[] = ['order_id' => $o['order_id'], 'customer' => $o['customer'], 'amount' => $o['bill'], 'reason' => $o['cancel_reason'], 'type' => $o['delivery_type']];
        }
        $openNow = (clone $this->orderSummaryQuery())->whereIn('o.order_status', OrderFlow::statusesFor(['new', 'accepted', 'out']))
            ->where('o.date_added', '<=', $end)->get();
        foreach ($this->decorate($openNow->all()) as $o) {
            if (!$o['stale']) $types[$o['delivery_type'] === 'Scheduled' ? 'Scheduled' : 'Express']['open']++;
        }

        // Money received at the hub on this day.
        $money = ['cash' => 0.0, 'upi' => 0.0, 'card' => 0.0, 'received' => 0.0, 'expected' => 0.0, 'short' => 0.0, 'excess' => 0.0, 'handovers' => 0];
        $handovers = [];
        $riderRows = [];
        $riderNames = collect($this->riders())->keyBy('id');
        if (self::table('pos_cash_handovers')) {
            $hs = DB::table('pos_cash_handovers')->where('hub_id', $this->hubId)->whereBetween('created_at', [$start, $end])->orderBy('id')->get();
            foreach ($hs as $h) {
                $money['handovers']++;
                $money['cash'] += (float) $h->cash_amount;
                $money['upi'] += (float) $h->upi_amount;
                $money['card'] += (float) $h->card_amount;
                $money['received'] += (float) $h->received;
                $money['expected'] += (float) $h->expected;
                if ((float) $h->difference < 0) $money['short'] += -(float) $h->difference; else $money['excess'] += (float) $h->difference;
                $rname = $h->rider_id ? ($riderNames[$h->rider_id]['name'] ?? ('Rider #' . $h->rider_id)) : 'Counter';
                $handovers[] = ['time' => $h->created_at, 'rider' => $rname, 'orders' => (int) $h->orders_count, 'expected' => (float) $h->expected,
                    'cash' => (float) $h->cash_amount, 'upi' => (float) $h->upi_amount, 'card' => (float) $h->card_amount,
                    'difference' => (float) $h->difference, 'note' => (string) $h->note, 'ref' => (string) $h->reference, 'by' => (string) $h->by_name];
                $key = $rname;
                $riderRows[$key] = $riderRows[$key] ?? ['rider' => $rname, 'delivered' => 0, 'settled_orders' => 0, 'cash' => 0.0, 'upi' => 0.0, 'card' => 0.0, 'difference' => 0.0, 'pending_orders' => 0, 'pending_amount' => 0.0];
                $riderRows[$key]['settled_orders'] += (int) $h->orders_count;
                $riderRows[$key]['cash'] += (float) $h->cash_amount;
                $riderRows[$key]['upi'] += (float) $h->upi_amount;
                $riderRows[$key]['card'] += (float) $h->card_amount;
                $riderRows[$key]['difference'] += (float) $h->difference;
            }
            $settledToday = DB::table('pos_settlements')->where('hub_id', $this->hubId)->whereBetween('created_at', [$start, $end])->get(['order_id', 'payment_type', 'bill', 'expected', 'handover_id']);
            $sumByOrder = $settledToday->keyBy('order_id');
            $settledRows = (clone $this->orderSummaryQuery())->whereIn('o.order_id', $settledToday->pluck('order_id')->all() ?: ['-'])->get();
            foreach ($settledRows as $r) $types[$typeOf($r)]['settled']++;
            $prepaid = $settledToday->filter(fn ($s) => OrderFlow::isPrepaid($s->payment_type));
            $money['online_orders'] = $prepaid->count();
            $money['online_value'] = round($prepaid->sum('bill'), 2);
            unset($sumByOrder);
        }
        // Delivered-and-settled on this day outside the new POS (old POS, admin panel): shown so nothing is missed.
        $outside = ['orders' => 0, 'value' => 0.0, 'by_type' => []];
        if (self::has('orders', 'updated_at')) {
            $settledIds = self::table('pos_settlements') ? DB::table('pos_settlements')->where('hub_id', $this->hubId)->pluck('order_id')->all() : [];
            $q = (clone $this->orderSummaryQuery())->whereIn('o.order_status', OrderFlow::statusesFor(['delivered']))->whereBetween('o.updated_at', [$start, $end]);
            foreach ($q->get() as $r) {
                if (in_array($r->order_id, $settledIds, true)) continue;
                $v = (float) $r->total_amount + (float) $r->delivery_charge + (float) $r->amount_to_collect - (float) $r->amount_to_return;
                $outside['orders']++;
                $outside['value'] += $v;
                $pt = $r->payment_type ?: 'Cash';
                $outside['by_type'][$pt] = round(($outside['by_type'][$pt] ?? 0) + $v, 2);
                $types[$typeOf($r)]['settled']++;
            }
            $outside['value'] = round($outside['value'], 2);
        }

        // Money still with riders (delivered, not settled) right now.
        $pending = [];
        $un = (clone $this->orderSummaryQuery())->whereIn('o.order_status', OrderFlow::statusesFor(['unsettled']))->get();
        foreach ($this->decorate($un->all()) as $o) {
            $rname = $o['rider'] ?: 'No rider';
            $pending[] = ['order_id' => $o['order_id'], 'customer' => $o['customer'], 'rider' => $rname, 'expected' => $o['expected'],
                'payment_type' => $o['payment_type'], 'since' => $o['since'], 'stale' => $o['stale']];
            if ($o['stale']) continue;
            $riderRows[$rname] = $riderRows[$rname] ?? ['rider' => $rname, 'delivered' => 0, 'settled_orders' => 0, 'cash' => 0.0, 'upi' => 0.0, 'card' => 0.0, 'difference' => 0.0, 'pending_orders' => 0, 'pending_amount' => 0.0];
            $riderRows[$rname]['pending_orders']++;
            $riderRows[$rname]['pending_amount'] += $o['expected'];
        }
        if (self::has('orders', 'delivery_complete_time')) {
            $byRider = DB::table('orders')->where('hub_id', $this->hubId)->whereBetween('delivery_complete_time', [$start, $end])
                ->select('rider_id', DB::raw('COUNT(DISTINCT order_id) AS n'))->groupBy('rider_id')->get();
            foreach ($byRider as $x) {
                $rname = $x->rider_id ? ($riderNames[$x->rider_id]['name'] ?? ('Rider #' . $x->rider_id)) : 'No rider';
                $riderRows[$rname] = $riderRows[$rname] ?? ['rider' => $rname, 'delivered' => 0, 'settled_orders' => 0, 'cash' => 0.0, 'upi' => 0.0, 'card' => 0.0, 'difference' => 0.0, 'pending_orders' => 0, 'pending_amount' => 0.0];
                $riderRows[$rname]['delivered'] += (int) $x->n;
            }
        }
        foreach ($money as $k => $v) if (is_float($v)) $money[$k] = round($v, 2);
        $riderRows = array_values(array_map(function ($r) {
            foreach (['cash', 'upi', 'card', 'difference', 'pending_amount'] as $k) $r[$k] = round($r[$k], 2);
            return $r;
        }, $riderRows));

        $closes = [];
        if (self::table('pos_day_closes')) {
            $closes = DB::table('pos_day_closes')->where('hub_id', $this->hubId)->where('business_date', $date)->orderByDesc('id')
                ->get(['id', 'cash_expected', 'cash_counted', 'difference', 'note', 'by_name', 'emailed_to', 'email_status', 'created_at'])
                ->map(fn ($c) => (array) $c)->all();
        }

        // ---- insights for the report (21 Oct 2026): hour by hour, on-time delivery, top products, rider performance ----
        $hourly = array_fill(0, 24, ['orders' => 0, 'value' => 0.0]);
        foreach ($placed as $r) {
            if (OrderFlow::stage($r->order_status) === 'cancelled') continue;
            $h = (int) date('G', self::ts($r->date_added) ?? strtotime($start));
            $hourly[$h]['orders']++;
            $hourly[$h]['value'] = round($hourly[$h]['value'] + (float) $r->total_amount + (float) $r->delivery_charge, 2);
        }
        $sla = ['delivered' => 0, 'on_time' => 0, 'pct' => null, 'avg_min' => null, 'late' => []];
        $riderSla = [];
        if (self::has('orders', 'delivery_complete_time')) {
            $dv = (clone $this->orderSummaryQuery())->whereBetween('o.delivery_complete_time', [$start, $end])
                ->whereIn('o.order_status', OrderFlow::statusesFor(['unsettled', 'delivered']))->get();
            $mins = [];
            $rNames = collect($this->riders())->keyBy('id');
            foreach ($dv as $r) {
                $doneAt = self::ts($r->delivery_complete_time); $placedAt = self::ts($r->date_added);
                $due = OrderFlow::dueAt($r->delivery_type, $r->date_added, $r->schedule_date, $r->schedule_time);
                if (!$doneAt) continue;
                $sla['delivered']++;
                $onTime = $due === null || $doneAt <= $due;
                if ($onTime) $sla['on_time']++;
                elseif (count($sla['late']) < 15) $sla['late'][] = ['order_id' => $r->order_id, 'type' => $r->delivery_type ?: 'Express', 'late_min' => (int) round(($doneAt - $due) / 60)];
                if ($placedAt && $doneAt >= $placedAt && ($doneAt - $placedAt) <= 86400 && strtolower((string) $r->delivery_type) !== 'scheduled') $mins[] = ($doneAt - $placedAt) / 60;
                $rn = $r->rider_id ? ($rNames[$r->rider_id]['name'] ?? ('Rider #' . $r->rider_id)) : 'No rider';
                $riderSla[$rn] = $riderSla[$rn] ?? ['n' => 0, 'ok' => 0];
                $riderSla[$rn]['n']++; if ($onTime) $riderSla[$rn]['ok']++;
            }
            if ($sla['delivered']) $sla['pct'] = (int) round($sla['on_time'] * 100 / $sla['delivered']);
            if ($mins) $sla['avg_min'] = (int) round(array_sum($mins) / count($mins));
        }
        foreach ($riderRows as &$rr) {
            $x = $riderSla[$rr['rider']] ?? null;
            $rr['on_time_pct'] = $x && $x['n'] ? (int) round($x['ok'] * 100 / $x['n']) : null;
        }
        unset($rr);
        $products = [];
        if ($placedIds) {
            $products = DB::table('orders AS o')->leftJoin('products AS p', 'p.id', '=', 'o.product_id')->leftJoin('items AS i', 'i.id', '=', 'p.item_id')
                ->where('o.hub_id', $this->hubId)->whereIn('o.order_id', $placedIds)
                ->groupBy('o.product_id')
                ->select(DB::raw('MAX(COALESCE(i.item, p.product_name)) AS name'), DB::raw('MAX(p.unit_quantity) AS uq'), DB::raw('MAX(p.product_unit) AS unit'),
                    DB::raw('SUM(o.quantity) AS qty'), DB::raw('SUM(o.quantity * p.main_price) AS value'), DB::raw('COUNT(DISTINCT o.order_id) AS orders'))
                ->orderByDesc('value')->limit(8)->get()
                ->map(fn ($x) => ['name' => (string) ($x->name ?: 'Product'), 'pack' => trim(((float) $x->uq ? rtrim(rtrim(number_format((float) $x->uq, 3, '.', ''), '0'), '.') : '') . ' ' . $x->unit), 'qty' => (float) $x->qty, 'value' => round((float) $x->value, 2), 'orders' => (int) $x->orders])->all();
        }

        return [
            'hourly' => $hourly, 'sla' => $sla, 'products' => $products,
            'date' => $date, 'is_today' => $isToday, 'generated_at' => date('Y-m-d H:i:s', $now),
            'types' => $types, 'invoice' => $invoice, 'pay_mix' => array_map(fn ($v) => round($v, 2), $payMix),
            'money' => $money, 'outside' => $outside, 'handovers' => $handovers, 'riders' => $riderRows,
            'pending' => $pending, 'cancelled' => $cancelledList, 'stale_excluded' => $staleCount,
            'cash_to_handover' => round($money['cash'], 2), 'closes' => $closes,
        ];
    }

    /** Submit the day-end report to head office: saved in pos_day_closes and emailed when an address is set. */
    public function closeDay(string $date, float $cashCounted, string $note, array $actor, ?string $html = null): array
    {
        if (!self::table('pos_day_closes')) return ['ok' => false, 'message' => 'Day close is not set up yet (database table missing).'];
        $rep = $this->dayReport($date);
        $expected = (float) $rep['cash_to_handover'];
        $diff = round($cashCounted - $expected, 2);
        if (abs($diff) >= 0.01 && trim($note) === '') {
            return ['ok' => false, 'message' => 'Cash counted differs from expected by ₹' . number_format($diff, 2) . '. Write a note explaining it.'];
        }
        $emails = array_values(array_filter(array_map('trim', explode(',', (string) config('pos_console.head_office_email', ''))), fn ($e) => filter_var($e, FILTER_VALIDATE_EMAIL)));
        $id = DB::table('pos_day_closes')->insertGetId([
            'hub_id' => $this->hubId, 'business_date' => $date, 'summary' => json_encode($rep, JSON_UNESCAPED_UNICODE),
            'cash_expected' => $expected, 'cash_counted' => round($cashCounted, 2), 'difference' => $diff,
            'note' => mb_substr(trim($note), 0, 500), 'by_guard' => $actor['guard'] ?? null, 'by_id' => $actor['id'] ?? null,
            'by_name' => mb_substr((string) ($actor['name'] ?? ''), 0, 120), 'emailed_to' => $emails ? mb_substr(implode(',', $emails), 0, 250) : null,
            'email_status' => $emails ? 'sending' : 'no head-office email set', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $status = $emails ? 'sent' : 'saved (no head-office email set)';
        $mailer = (string) config('mail.default', '');
        if ($emails && in_array($mailer, ['log', 'array'], true)) {
            $status = 'saved; email NOT sent - mail server not set up (MAIL_MAILER=' . $mailer . ')';
            DB::table('pos_day_closes')->where('id', $id)->update(['email_status' => mb_substr($status, 0, 120)]);
        } elseif ($emails && $html) {
            try {
                $hub = $this->hub();
                Mail::html($html, function ($m) use ($emails, $hub, $date) {
                    $m->to($emails)->subject('Freshful day-end report - ' . ($hub->hub ?? 'Hub') . ' - ' . date('d M Y', strtotime($date)));
                });
            } catch (\Throwable $e) {
                report($e);
                $status = 'email failed: ' . mb_substr($e->getMessage(), 0, 90);
            }
            DB::table('pos_day_closes')->where('id', $id)->update(['email_status' => mb_substr($status, 0, 120)]);
        }
        Log::info('[freshful-audit] pos-day-close', ['hub' => $this->hubId, 'date' => $date, 'by' => ($actor['guard'] ?? '?') . '#' . ($actor['id'] ?? '?'),
            'cash_expected' => $expected, 'cash_counted' => $cashCounted, 'email' => $status]);
        return ['ok' => true, 'id' => $id, 'message' => 'Day-end report submitted to head office (' . $status . ').'];
    }
}
