<?php

namespace App\Services\CountryConsole;

use App\Services\HubConsole\HubPeopleService;
use App\Services\HubConsole\HubStock;
use App\Services\HubConsole\HubTransferService;
use App\Services\PosConsole\OrderFlow;
use App\Services\PosConsole\PosConsoleService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Freshful country console (22 Oct 2026): orders, transfers, cash, riders, customers and grievances across all hubs.
 * Order actions use the POS console's own rules (PosConsoleService), so stock is returned on cancel / move
 * and the customer app sees the same statuses. Transfers use the hub transfer service.
 */
class CountryOps
{
    private static function h(string $t, ?string $c = null): bool { return CountryScope::has($t, $c); }

    /* ------------------------------------------------------------------ orders */

    private static function decorate(array $o, array $hub): array
    {
        return $o + ['hub_id' => $hub['id'], 'hub' => $hub['name'], 'city' => $hub['city'], 'state' => $hub['state']];
    }

    /**
     * All orders in the scope. $view: '' (placed in the dates) | live | late | sched-today | sched-tomorrow | sched-upcoming.
     * Returns one page plus the summary of everything that matched.
     */
    public static function orders(CountryScope $scope, string $from, string $to, array $f, int $page = 1, int $per = 50): array
    {
        $okStatus = ['Order Pending', 'Order Placed', 'Order Accepted', 'Order Processed', 'Order Shipped', 'Order Dispatched', 'Order Delivered', 'Order Cancel'];
        $stage = in_array($f['stage'] ?? '', OrderFlow::STAGES, true) || ($f['stage'] ?? '') === 'done' ? (string) $f['stage'] : '';
        $status = in_array($f['status'] ?? '', $okStatus, true) ? (string) $f['status'] : '';
        $type = in_array($f['type'] ?? '', ['Express', 'Scheduled'], true) ? (string) $f['type'] : '';
        $pay = mb_substr(trim((string) ($f['pay'] ?? '')), 0, 20);
        $q = mb_substr(trim((string) ($f['q'] ?? '')), 0, 60);
        $view = (string) ($f['view'] ?? '');
        $hubs = CountryScope::hubs();
        $list = [];
        foreach ($scope->ids() as $h) {
            $hub = $hubs[$h] ?? null; if (!$hub) continue;
            $pos = new PosConsoleService($h);
            if ($q !== '') $rows = $pos->search($q);
            elseif ($view === 'live' || $view === 'late') $rows = $pos->history(date('Y-m-d', time() - OrderFlow::STALE_AFTER), date('Y-m-d'));
            elseif (str_starts_with($view, 'sched-')) $rows = $pos->history(date('Y-m-d', strtotime('-30 days')), date('Y-m-d'));
            else $rows = $pos->history($from, $to, $stage === 'done' ? '' : $stage);
            foreach ($rows as $o) $list[] = self::decorate($o, $hub);
        }
        $today = date('Y-m-d'); $tom = date('Y-m-d', strtotime('+1 day'));
        $list = array_values(array_filter($list, function ($o) use ($view, $stage, $status, $type, $pay, $today, $tom) {
            if ($view === 'live' && (!in_array($o['stage'], ['new', 'accepted', 'out'], true) || $o['stale'] || $o['later'])) return false;
            if ($view === 'late' && !$o['late']) return false;
            if (str_starts_with($view, 'sched-')) {
                if (strcasecmp($o['delivery_type'], 'Scheduled') !== 0 || $o['schedule_date'] === '' || $o['stage'] === 'cancelled') return false;
                if ($view === 'sched-today' && $o['schedule_date'] !== $today) return false;
                if ($view === 'sched-tomorrow' && $o['schedule_date'] !== $tom) return false;
                if ($view === 'sched-upcoming' && $o['schedule_date'] < $today) return false;
            }
            if ($stage === 'done' && !in_array($o['stage'], ['unsettled', 'delivered'], true)) return false;
            if ($stage !== '' && $stage !== 'done' && $o['stage'] !== $stage) return false;
            if ($status !== '' && !($o['status'] === $status || ($status === 'Order Placed' && $o['status'] === 'Order Accepted'))) return false;
            if ($type !== '' && strcasecmp($o['delivery_type'], $type) !== 0) return false;
            if ($pay !== '' && strcasecmp($o['payment_type'], $pay) !== 0) return false;
            return true;
        }));
        if (str_starts_with($view, 'sched-')) usort($list, fn ($a, $b) => [$a['schedule_date'], $a['slot'], $a['hub']] <=> [$b['schedule_date'], $b['slot'], $b['hub']]);
        elseif ($view === 'late' || $view === 'live') usort($list, fn ($a, $b) => [$a['due_at'] ?? PHP_INT_MAX] <=> [$b['due_at'] ?? PHP_INT_MAX]);
        else usort($list, fn ($a, $b) => ($b['placed_at'] ?? 0) <=> ($a['placed_at'] ?? 0));
        $sum = ['orders' => count($list), 'value' => 0.0, 'cancelled' => 0, 'late' => 0, 'by_stage' => array_fill_keys(OrderFlow::STAGES, 0), 'hubs' => []];
        foreach ($list as $o) {
            if ($o['stage'] === 'cancelled') $sum['cancelled']++; else $sum['value'] += $o['amount'];
            if ($o['late']) $sum['late']++;
            if (isset($sum['by_stage'][$o['stage']])) $sum['by_stage'][$o['stage']]++;
            $sum['hubs'][$o['hub_id']] = true;
        }
        $sum['value'] = round($sum['value'], 2); $sum['hubs'] = count($sum['hubs']);
        $pages = max(1, (int) ceil(count($list) / $per)); $page = max(1, min($page, $pages));
        return ['ok' => true, 'orders' => array_slice($list, ($page - 1) * $per, $per), 'all' => $list, 'summary' => $sum, 'page' => $page, 'pages' => $pages, 'per' => $per];
    }

    private static function hubOfOrder(string $orderId): ?int
    {
        $h = DB::table('orders')->where('order_id', $orderId)->value('hub_id');
        return $h !== null ? (int) $h : null;
    }

    public static function order(string $orderId): array
    {
        $h = self::hubOfOrder($orderId);
        if ($h === null) return ['ok' => false, 'message' => 'Order not found.'];
        $pos = new PosConsoleService($h);
        $d = $pos->detail($orderId);
        if (!$d) return ['ok' => false, 'message' => 'Order not found.'];
        $hub = CountryScope::hub($h);
        $events = [];
        if (self::h('pos_order_events')) {
            $cols = array_values(array_filter(['id', 'action', 'hub_id', 'actor_id', 'actor_guard', 'detail', 'created_at', 'actor_name'], fn ($c) => self::h('pos_order_events', $c)));
            foreach (DB::table('pos_order_events')->where('order_id', $orderId)->orderBy('id')->limit(100)->get($cols) as $e) $events[] = (array) $e;
        }
        return ['ok' => true, 'order' => $d, 'hub' => $hub, 'events' => $events, 'move_to' => array_values(array_filter($pos->hubsForRelocation(), fn ($x) => (int) $x['id'] !== $h))];
    }

    /** Cancel any order with a reason (a new order is rejected, an accepted one cancelled; stock comes back). */
    public static function cancel(string $orderId, string $reason, array $actor): array
    {
        $reason = mb_substr(trim(strip_tags($reason)), 0, 200);
        if ($reason === '') return ['ok' => false, 'message' => 'Write the reason for cancelling.'];
        $h = self::hubOfOrder($orderId);
        if ($h === null) return ['ok' => false, 'message' => 'Order not found.'];
        $st = OrderFlow::stage(DB::table('orders')->where('order_id', $orderId)->value('order_status'));
        $res = (new PosConsoleService($h))->act($orderId, $st === 'new' ? 'reject' : 'cancel', $reason . ' (country manager)', null, $actor);
        if (!empty($res['ok'])) Log::info('[freshful-audit] country-order-cancel', ['order' => $orderId, 'hub' => $h, 'by' => $actor['id'] ?? null]);
        return $res + ['message' => !empty($res['ok']) ? 'Order ' . $orderId . ' cancelled.' : ($res['message'] ?? 'Could not cancel.')];
    }

    public static function move(string $orderId, int $toHub, string $note, array $actor): array
    {
        $h = self::hubOfOrder($orderId);
        if ($h === null) return ['ok' => false, 'message' => 'Order not found.'];
        $res = (new PosConsoleService($h))->relocate($orderId, $toHub, mb_substr(trim(strip_tags($note)) . ' (country manager)', 0, 200), $actor);
        if (!empty($res['ok'])) Log::info('[freshful-audit] country-order-move', ['order' => $orderId, 'from' => $h, 'to' => $toHub, 'by' => $actor['id'] ?? null]);
        return $res;
    }

    /* ------------------------------------------------------------------ transfers */

    public const T_STATUS = ['planned' => 'Planned (11:00)', 'requested' => 'Requested', 'in_transit' => 'On the road', 'received' => 'Received', 'declined' => 'Declined', 'cancelled' => 'Cancelled'];

    public static function transfers(CountryScope $scope, string $from, string $to, string $status): array
    {
        if (!HubStock::has('hub_transfers')) return ['ok' => true, 'rows' => [], 'counts' => [], 'available' => false];
        $ids = $scope->ids(); $hubs = CountryScope::hubs(); $prods = CountryStock::products();
        if (!$ids) return ['ok' => true, 'rows' => [], 'counts' => [], 'available' => true];
        $q = DB::table('hub_transfers')->where(fn ($w) => $w->whereIn('from_hub_id', $ids)->orWhereIn('to_hub_id', $ids));
        if ($status === 'open') $q->whereIn('status', ['planned', 'requested', 'in_transit']);
        else { $q->whereBetween('created_at', [$from . ' 00:00:00', $to . ' 23:59:59']); if (isset(self::T_STATUS[$status])) $q->where('status', $status); }
        $heads = $q->orderByDesc('id')->limit(1000)->get();
        $items = [];
        if ($heads->count()) foreach (DB::table('hub_transfer_items')->whereIn('transfer_id', $heads->pluck('id')->all())->get() as $i) $items[(int) $i->transfer_id][] = $i;
        $rows = []; $counts = array_fill_keys(array_keys(self::T_STATUS), 0);
        foreach ($heads as $t) {
            $lines = []; $val = 0.0;
            foreach ($items[(int) $t->id] ?? [] as $i) {
                $qty = (float) ($i->received_qty ?? $i->sent_qty ?? $i->requested_qty ?? 0); $price = $prods[(string) $i->product_code]['price'] ?? 0;
                $lines[] = ['code' => (string) $i->product_code, 'name' => (string) $i->product_name, 'pack' => (string) $i->pack, 'requested' => $i->requested_qty !== null ? (float) $i->requested_qty : null,
                    'sent' => $i->sent_qty !== null ? (float) $i->sent_qty : null, 'received' => $i->received_qty !== null ? (float) $i->received_qty : null];
                $val += $qty * $price;
            }
            $counts[$t->status] = ($counts[$t->status] ?? 0) + 1;
            $rows[] = ['id' => (int) $t->id, 'ref' => (string) $t->ref, 'kind' => (string) $t->kind, 'status' => (string) $t->status, 'status_label' => self::T_STATUS[$t->status] ?? $t->status,
                'from_id' => (int) $t->from_hub_id, 'from' => $hubs[(int) $t->from_hub_id]['name'] ?? ('Hub ' . $t->from_hub_id), 'from_city' => $hubs[(int) $t->from_hub_id]['city'] ?? '',
                'to_id' => (int) $t->to_hub_id, 'to' => $hubs[(int) $t->to_hub_id]['name'] ?? ('Hub ' . $t->to_hub_id), 'to_city' => $hubs[(int) $t->to_hub_id]['city'] ?? '',
                'reason' => (string) ($t->reason ?? ''), 'instruction' => (string) ($t->instruction ?? ''), 'rider' => (string) ($t->rider_name ?? ''), 'meet' => (string) ($t->meet_point ?? ''),
                'created' => CountryScope::ts($t->created_at), 'created_by' => (string) ($t->created_by ?? ''), 'sent' => CountryScope::ts($t->sent_at ?? null), 'received_at' => CountryScope::ts($t->received_at ?? null),
                'close_note' => (string) ($t->close_note ?? ''), 'receive_note' => (string) ($t->receive_note ?? ''), 'lines' => $lines, 'value' => round($val, 2),
                'hours_on_road' => $t->status === 'in_transit' && !empty($t->sent_at) ? round((time() - (int) CountryScope::ts($t->sent_at)) / 3600, 1) : null];
        }
        return ['ok' => true, 'available' => true, 'rows' => $rows, 'counts' => $counts, 'statuses' => self::T_STATUS];
    }

    /**
     * Country manager orders stock to move from one hub to another. It is created as a request from the receiving hub to the
     * sending hub: the sending hub sees it in its console, picks the rider and dispatches (stock leaves only then).
     */
    public static function transferCreate(int $fromHub, int $toHub, array $lines, string $note, array $actor): array
    {
        if (!HubStock::has('hub_transfers')) return ['ok' => false, 'message' => 'Transfers are not installed (hub console update 2).'];
        if (!CountryScope::hub($fromHub) || !CountryScope::hub($toHub)) return ['ok' => false, 'message' => 'Choose both hubs.'];
        if ($fromHub === $toHub) return ['ok' => false, 'message' => 'Choose two different hubs.'];
        $text = 'Country manager instruction' . (trim($note) !== '' ? ': ' . mb_substr(trim(strip_tags($note)), 0, 400) : '');
        $res = (new HubTransferService($toHub))->request($fromHub, $lines, $text, $actor);
        if ($res['ok']) {
            $res['message'] = 'Transfer ' . $res['ref'] . ' created: ' . CountryScope::hub($fromHub)['name'] . ' will dispatch it to ' . CountryScope::hub($toHub)['name'] . ' from its console.';
            Log::info('[freshful-audit] country-transfer', ['from' => $fromHub, 'to' => $toHub, 'ref' => $res['ref'], 'by' => $actor['id'] ?? null]);
        }
        return $res;
    }

    public static function transferCancel(int $id, string $note, array $actor): array
    {
        $t = HubStock::has('hub_transfers') ? DB::table('hub_transfers')->where('id', $id)->first() : null;
        if (!$t) return ['ok' => false, 'message' => 'Transfer not found.'];
        // The hub that may cancel it: the requester for a request, the sender for a planned or dispatched transfer.
        $hub = $t->status === 'requested' ? (int) $t->to_hub_id : (int) $t->from_hub_id;
        $res = (new HubTransferService($hub))->cancel($id, mb_substr(trim(strip_tags($note)), 0, 300) . (trim($note) !== '' ? ' (country manager)' : ''), $actor);
        if ($res['ok']) Log::info('[freshful-audit] country-transfer-cancel', ['id' => $id, 'by' => $actor['id'] ?? null]);
        return $res;
    }

    /* ------------------------------------------------------------------ cash */

    public static function cash(CountryScope $scope, string $from, string $to): array
    {
        $ids = $scope->ids(); $hubs = CountryScope::hubs();
        $inHand = CountryAnalytics::cashInHand($ids);
        $orders = CountryAnalytics::orderRows($ids, $from . ' 00:00:00', $to . ' 23:59:59');
        $pay = [];
        foreach ($orders as $o) if ($o['stage'] !== 'cancelled') { $pay[$o['hub']][$o['pay']] = round(($pay[$o['hub']][$o['pay']] ?? 0) + $o['value'], 2); }
        $deposits = []; $depList = [];
        if (HubStock::has('hub_cash_entries') && $ids) {
            foreach (DB::table('hub_cash_entries')->whereIn('hub_id', $ids)->whereBetween('business_date', [$from, $to])->orderByDesc('id')->limit(2000)->get() as $e) {
                $void = !empty($e->voided_at);
                if (!$void && in_array($e->type, ['bank', 'cms'], true)) $deposits[(int) $e->hub_id] = round(($deposits[(int) $e->hub_id] ?? 0) + (float) $e->amount, 2);
                $depList[] = ['id' => (int) $e->id, 'hub' => $hubs[(int) $e->hub_id]['name'] ?? '', 'date' => (string) $e->business_date, 'type' => (string) $e->type, 'amount' => (float) $e->amount,
                    'reference' => (string) ($e->reference ?? ''), 'note' => (string) ($e->note ?? ''), 'by' => (string) ($e->by_name ?? ''), 'voided' => $void, 'receipt' => !empty($e->receipt_path)];
            }
        }
        $cashIn = [];
        if (HubStock::has('pos_cash_handovers') && $ids) {
            foreach (DB::table('pos_cash_handovers')->whereIn('hub_id', $ids)->whereBetween('created_at', [$from . ' 00:00:00', $to . ' 23:59:59'])->groupBy('hub_id')->select('hub_id', DB::raw('SUM(cash_amount) AS c'), DB::raw('SUM(upi_amount) AS u'), DB::raw('SUM(card_amount) AS k'))->get() as $r) {
                $cashIn[(int) $r->hub_id] = ['cash' => round((float) $r->c, 2), 'upi' => round((float) $r->u, 2), 'card' => round((float) $r->k, 2)];
            }
        }
        $closes = [];
        if (HubStock::has('pos_day_closes') && $ids) {
            foreach (DB::table('pos_day_closes')->whereIn('hub_id', $ids)->whereBetween('business_date', [$from, $to])->groupBy('hub_id')->select('hub_id', DB::raw('COUNT(DISTINCT business_date) AS n'), DB::raw('SUM(difference) AS diff'))->get() as $r) {
                $closes[(int) $r->hub_id] = ['days' => (int) $r->n, 'diff' => round((float) $r->diff, 2)];
            }
        }
        $days = (int) round((strtotime($to) - strtotime($from)) / 86400) + 1;
        $rows = [];
        foreach ($ids as $h) {
            $p = $pay[$h] ?? [];
            $rows[] = ['hub_id' => $h, 'hub' => $hubs[$h]['name'] ?? '', 'city' => $hubs[$h]['city'] ?? '', 'in_hand' => $inHand['by_hub'][$h] ?? null, 'over' => isset($inHand['by_hub'][$h]) && $inHand['by_hub'][$h] > $inHand['limit'],
                'cod_sales' => round($p['Cash'] ?? 0, 2), 'online_sales' => round(($p['Online'] ?? 0), 2), 'wallet_sales' => round(($p['Wallet'] ?? 0), 2),
                'collected_cash' => $cashIn[$h]['cash'] ?? 0, 'collected_upi' => $cashIn[$h]['upi'] ?? 0, 'collected_card' => $cashIn[$h]['card'] ?? 0,
                'deposited' => $deposits[$h] ?? 0, 'day_closes' => $closes[$h]['days'] ?? 0, 'day_close_diff' => $closes[$h]['diff'] ?? 0, 'days' => $days];
        }
        usort($rows, fn ($a, $b) => ($b['in_hand'] ?? -1) <=> ($a['in_hand'] ?? -1));
        $tot = []; foreach (['in_hand', 'cod_sales', 'online_sales', 'wallet_sales', 'collected_cash', 'collected_upi', 'collected_card', 'deposited'] as $k) $tot[$k] = round(array_sum(array_map(fn ($r) => (float) ($r[$k] ?? 0), $rows)), 2);
        return ['ok' => true, 'rows' => $rows, 'deposits' => $depList, 'total' => $tot, 'limit' => $inHand['limit'], 'over' => $inHand['over'], 'available' => $inHand['total'] !== null];
    }

    /* ------------------------------------------------------------------ riders */

    public static function riders(CountryScope $scope): array
    {
        $ids = $scope->ids(); $hubs = CountryScope::hubs();
        if (!$ids) return ['ok' => true, 'rows' => []];
        $cols = array_values(array_filter(['id', 'name', 'phone', 'hub_id', 'status', 'email', 'employee_id', 'date_added', 'created_at'], fn ($c) => self::h('delivery_boy', $c)));
        $riders = DB::table('delivery_boy')->whereIn('hub_id', $ids)->get($cols);
        $today = date('Y-m-d 00:00:00');
        $deliv = []; $open = []; $last30 = [];
        if (self::h('orders', 'rider_id')) {
            foreach (DB::table('orders')->whereIn('hub_id', $ids)->whereNotNull('rider_id')->where('date_added', '>=', date('Y-m-d 00:00:00', strtotime('-29 days')))->groupBy('rider_id', 'order_id')
                ->select('rider_id', 'order_id', DB::raw('MAX(order_status) AS st'), DB::raw('MAX(date_added) AS at'))->get() as $r) {
                $st = OrderFlow::stage($r->st); $rid = (int) $r->rider_id;
                if (in_array($st, ['delivered', 'unsettled'], true)) { $last30[$rid] = ($last30[$rid] ?? 0) + 1; if ((string) $r->at >= $today) $deliv[$rid] = ($deliv[$rid] ?? 0) + 1; }
                if ($st === 'out') $open[$rid] = ($open[$rid] ?? 0) + 1;
            }
        }
        $state = [];
        if (HubStock::has('pos_rider_status')) foreach (DB::table('pos_rider_status')->whereIn('hub_id', $ids)->get() as $s) $state[(int) $s->rider_id] = (array) $s;
        $rows = [];
        foreach ($riders as $r) {
            $id = (int) $r->id; $h = (int) $r->hub_id;
            $rows[] = ['id' => $id, 'name' => (string) $r->name, 'phone' => (string) ($r->phone ?? ''), 'hub' => $hubs[$h]['name'] ?? '', 'city' => $hubs[$h]['city'] ?? '', 'hub_id' => $h,
                'active' => CountryScope::isActive($r->status ?? 'active'), 'today' => $deliv[$id] ?? 0, 'out_now' => $open[$id] ?? 0, 'last30' => $last30[$id] ?? 0,
                'duty' => array_key_exists('on_duty', $state[$id] ?? []) ? ((int) $state[$id]['on_duty'] ? 'On duty' : 'Off duty') : ''];
        }
        usort($rows, fn ($a, $b) => [$b['active'], $b['today'], $a['name']] <=> [$a['active'], $a['today'], $b['name']]);
        return ['ok' => true, 'rows' => $rows, 'total' => ['riders' => count($rows), 'active' => count(array_filter($rows, fn ($r) => $r['active'])), 'out_now' => array_sum(array_column($rows, 'out_now')), 'today' => array_sum(array_column($rows, 'today'))]];
    }

    /* ------------------------------------------------------------------ customers */

    public static function customers(CountryScope $scope, string $q, string $grade, string $segment, int $page = 1, int $per = 50): array
    {
        $ids = $scope->ids(); $hubs = CountryScope::hubs();
        if (!$ids) return ['ok' => true, 'rows' => [], 'grades' => [], 'page' => 1, 'pages' => 1, 'total' => 0];
        $since = date('Y-m-d 00:00:00', strtotime('-89 days'));
        $per90 = []; $life = [];
        foreach (array_chunk($ids, 500) as $c) {
            $rows = DB::table('orders')->whereIn('hub_id', $c)->whereNotNull('buyer_id')->where('buyer_id', '>', 0)->groupBy('buyer_id', 'order_id')
                ->select('buyer_id', 'order_id', DB::raw('MAX(order_status) AS st'), DB::raw('MAX(date_added) AS at'), DB::raw('MAX(hub_id) AS hub'),
                    DB::raw('MAX(total_amount) + MAX(delivery_charge)' . (self::h('orders', 'amount_to_collect') ? ' + MAX(amount_to_collect) - MAX(amount_to_return)' : '') . ' AS v'))->get();
            foreach ($rows as $r) {
                if (OrderFlow::stage($r->st) === 'cancelled') continue;
                $b = (int) $r->buyer_id; $at = (string) $r->at;
                $life[$b] = $life[$b] ?? ['orders' => 0, 'spend' => 0.0, 'first' => $at, 'last' => $at, 'hubs' => []];
                $life[$b]['orders']++; $life[$b]['spend'] += (float) $r->v; if ($at < $life[$b]['first']) $life[$b]['first'] = $at; if ($at > $life[$b]['last']) $life[$b]['last'] = $at;
                $life[$b]['hubs'][(int) $r->hub] = ($life[$b]['hubs'][(int) $r->hub] ?? 0) + 1;
                if ($at >= $since) { $per90[$b] = $per90[$b] ?? ['orders' => 0, 'spend' => 0.0]; $per90[$b]['orders']++; $per90[$b]['spend'] += (float) $r->v; }
            }
        }
        $buyerIds = array_keys($life);
        $info = [];
        $bcols = array_values(array_filter(['id', 'name', 'phone', 'email', 'wallet_amount', 'status'], fn ($c) => self::h('buyers', $c)));
        foreach (array_chunk($buyerIds, 1000) as $c) foreach (DB::table('buyers')->whereIn('id', $c)->get($bcols) as $b) $info[(int) $b->id] = $b;
        $grades = ['gold' => 0, 'silver' => 0, 'bronze' => 0, 'none' => 0];
        $rows = [];
        $term = mb_strtolower(trim($q)); $digits = preg_replace('/\D/', '', $q);
        $now = time();
        foreach ($life as $b => $l) {
            $p = $per90[$b] ?? ['orders' => 0, 'spend' => 0.0];
            $g = class_exists(HubPeopleService::class) ? HubPeopleService::grade($p['orders'], $p['spend']) : 'none';
            $grades[$g] = ($grades[$g] ?? 0) + 1;
            $i = $info[$b] ?? null;
            arsort($l['hubs']); $main = (int) array_key_first($l['hubs']);
            $lastTs = CountryScope::ts($l['last']); $firstTs = CountryScope::ts($l['first']);
            $seg = $firstTs >= strtotime('-29 days') ? 'new' : ($lastTs < strtotime('-60 days') ? 'lapsed' : ($l['orders'] >= 5 ? 'loyal' : 'regular'));
            $row = ['id' => $b, 'name' => trim((string) ($i->name ?? '')) ?: 'Customer #' . $b, 'phone' => (string) ($i->phone ?? ''), 'email' => (string) ($i->email ?? ''), 'wallet' => isset($i->wallet_amount) ? (float) $i->wallet_amount : null,
                'orders' => $l['orders'], 'spend' => round($l['spend'], 2), 'orders90' => $p['orders'], 'spend90' => round($p['spend'], 2), 'grade' => $g, 'segment' => $seg,
                'first' => $firstTs, 'last' => $lastTs, 'days_since' => $lastTs ? (int) floor(($now - $lastTs) / 86400) : null,
                'hub' => $hubs[$main]['name'] ?? '', 'city' => $hubs[$main]['city'] ?? '', 'state' => $hubs[$main]['state'] ?? '', 'hubs' => count($l['hubs'])];
            if ($grade !== '' && $g !== $grade) continue;
            if ($segment !== '' && $seg !== $segment) continue;
            if ($term !== '' && !str_contains(mb_strtolower($row['name'] . ' ' . $row['email'] . ' ' . $b), $term) && !(strlen($digits) >= 4 && str_contains(preg_replace('/\D/', '', $row['phone']), $digits))) continue;
            $rows[] = $row;
        }
        usort($rows, fn ($a, $b) => [$b['spend90'], $b['spend']] <=> [$a['spend90'], $a['spend']]);
        $pages = max(1, (int) ceil(count($rows) / $per)); $page = max(1, min($page, $pages));
        $segs = ['new' => 0, 'regular' => 0, 'loyal' => 0, 'lapsed' => 0];
        foreach ($rows as $r) $segs[$r['segment']]++;
        return ['ok' => true, 'rows' => array_slice($rows, ($page - 1) * $per, $per), 'all' => $rows, 'grades' => $grades, 'segments' => $segs, 'page' => $page, 'pages' => $pages, 'total' => count($rows),
            'rules' => config('hub_console.grades')];
    }

    public static function customer(int $id): array
    {
        $bcols = array_values(array_filter(['id', 'name', 'phone', 'email', 'wallet_amount', 'status', 'date_added', 'created_at'], fn ($c) => self::h('buyers', $c)));
        $b = DB::table('buyers')->where('id', $id)->first($bcols);
        if (!$b) return ['ok' => false, 'message' => 'Customer not found.'];
        $hubs = CountryScope::hubs();
        $orders = [];
        foreach (DB::table('orders')->where('buyer_id', $id)->groupBy('order_id')->select('order_id', DB::raw('MAX(hub_id) AS hub'), DB::raw('MAX(order_status) AS st'), DB::raw('MAX(date_added) AS at'),
            DB::raw('MAX(total_amount) + MAX(delivery_charge) AS v'), DB::raw('MAX(payment_type) AS pay'), DB::raw('MAX(delivery_type) AS type'), DB::raw('COUNT(*) AS items'))->orderByDesc('at')->limit(200)->get() as $o) {
            $orders[] = ['order_id' => (string) $o->order_id, 'hub' => $hubs[(int) $o->hub]['name'] ?? '', 'status' => (string) $o->st, 'stage' => OrderFlow::stage($o->st), 'at' => CountryScope::ts($o->at), 'value' => round((float) $o->v, 2), 'pay' => (string) $o->pay, 'type' => (string) $o->type, 'items' => (int) $o->items];
        }
        $ledger = [];
        if (self::h('buyer_wallet_ledger')) foreach (DB::table('buyer_wallet_ledger')->where('buyer_id', $id)->orderByDesc('id')->limit(100)->get() as $l) $ledger[] = (array) $l;
        $addr = [];
        if (self::h('buyer_address')) { $ac = array_values(array_filter(['id', 'name', 'phone', 'address', 'locality', 'city', 'pincode', 'landmark'], fn ($c) => self::h('buyer_address', $c))); foreach (DB::table('buyer_address')->where('buyer_id', $id)->limit(20)->get($ac) as $a) $addr[] = (array) $a; }
        return ['ok' => true, 'customer' => (array) $b, 'orders' => $orders, 'wallet_ledger' => $ledger, 'addresses' => $addr];
    }

    /* ------------------------------------------------------------------ grievances */

    private static ?array $gv = null;

    private static function grievanceSource(): array
    {
        if (self::$gv !== null) return self::$gv;
        $t = null;
        foreach (['grievance', 'grievances', 'order_grievance', 'order_grievances', 'customer_grievance'] as $c) if (self::h($c)) { $t = $c; break; }
        if (!$t) return self::$gv = ['ok' => false, 'reason' => 'No grievance table was found in the database.'];
        $pick = function (array $c) use ($t) { foreach ($c as $x) if (self::h($t, $x)) return $x; return null; };
        $g = ['ok' => true, 'table' => $t, 'hub' => $pick(['hub_id']), 'order' => $pick(['order_id', 'orderid']), 'date' => $pick(['created_at', 'date_added', 'date', 'added_on']),
            'status' => $pick(['status', 'grievance_status', 'state']), 'text' => $pick(['complaint_details', 'description', 'message', 'comment', 'comments', 'remarks', 'grievance', 'details', 'issue', 'reason']),
            'cat' => $pick(['grievance_category_id', 'category_id', 'grievance_category', 'category', 'type']), 'buyer' => $pick(['customer_id', 'buyer_id', 'user_id'])];
        if (!$g['date']) return self::$gv = ['ok' => false, 'reason' => 'The grievance table has no date column.'];
        $g['cat_table'] = null;
        if ($g['cat'] && str_ends_with($g['cat'], '_id')) foreach (['grievance_category', 'grievance_categories'] as $c) if (self::h($c)) { $g['cat_table'] = $c; break; }
        return self::$gv = $g;
    }

    private static function isOpen($v): bool
    {
        return !in_array(strtolower(trim((string) $v)), ['closed', 'resolved', 'solved', 'done', 'completed', 'inactive', 'rejected'], true);
    }

    public static function grievances(CountryScope $scope, string $from, string $to): array
    {
        $g = self::grievanceSource();
        if (!$g['ok']) return ['ok' => true, 'available' => false, 'reason' => $g['reason'], 'rows' => []];
        $ids = $scope->ids(); $hubs = CountryScope::hubs(); $all = count($ids) === count($hubs);
        $q = DB::table($g['table'] . ' AS g')->whereBetween('g.' . $g['date'], [$from . ' 00:00:00', $to . ' 23:59:59']);
        $cols = ['g.id', 'g.' . $g['date'] . ' AS d'];
        foreach (['order' => 'o', 'status' => 's', 'text' => 'x', 'buyer' => 'b', 'cat' => 'c', 'hub' => 'h'] as $k => $a) if ($g[$k]) $cols[] = 'g.' . $g[$k] . ' AS ' . $a;
        if (!$all) {
            if ($g['hub']) $q->whereIn('g.' . $g['hub'], $ids);
            elseif ($g['order']) $q->whereIn('g.' . $g['order'], DB::table('orders')->whereIn('hub_id', $ids)->select('order_id'));
            else return ['ok' => true, 'available' => false, 'reason' => 'Grievances have no hub or order, so they can only be shown for All India.', 'rows' => []];
        }
        if ($g['cat_table']) {
            $nc = self::h($g['cat_table'], 'name') ? 'name' : (self::h($g['cat_table'], 'title') ? 'title' : (self::h($g['cat_table'], 'category') ? 'category' : null));
            if ($nc) { $q->leftJoin($g['cat_table'] . ' AS gc', 'gc.id', '=', 'g.' . $g['cat']); $cols[] = 'gc.' . $nc . ' AS cn'; }
        }
        try { $rows = $q->orderByDesc('g.' . $g['date'])->limit(2000)->get($cols); } catch (\Throwable $e) { return ['ok' => true, 'available' => false, 'reason' => 'Could not read grievances.', 'rows' => []]; }
        $orderHub = [];
        if ($g['order'] && !$g['hub']) {
            $oids = $rows->pluck('o')->filter()->unique()->values()->all();
            foreach (array_chunk($oids, 1000) as $c) foreach (DB::table('orders')->whereIn('order_id', $c)->groupBy('order_id')->select('order_id', DB::raw('MAX(hub_id) AS h'))->get() as $o) $orderHub[(string) $o->order_id] = (int) $o->h;
        }
        $buyers = [];
        if ($g['buyer']) { $bi = $rows->pluck('b')->filter()->unique()->all(); foreach (array_chunk($bi, 1000) as $c) foreach (DB::table('buyers')->whereIn('id', $c)->get(['id', 'name', 'phone']) as $b) $buyers[(int) $b->id] = $b; }
        $out = []; $byCat = []; $byHub = []; $open = 0;
        foreach ($rows as $r) {
            $hid = isset($r->h) ? (int) $r->h : ($orderHub[(string) ($r->o ?? '')] ?? 0);
            $cat = isset($r->cn) && $r->cn !== null ? (string) $r->cn : (isset($r->c) && !is_numeric($r->c) ? (string) $r->c : 'Uncategorised');
            $isOpen = isset($r->s) ? self::isOpen($r->s) : null; if ($isOpen) $open++;
            $b = isset($r->b) ? ($buyers[(int) $r->b] ?? null) : null;
            $out[] = ['id' => (int) $r->id, 'at' => CountryScope::ts($r->d), 'order_id' => (string) ($r->o ?? ''), 'status' => (string) ($r->s ?? ''), 'open' => $isOpen, 'text' => mb_substr(strip_tags((string) ($r->x ?? '')), 0, 400),
                'category' => $cat, 'customer' => (string) ($b->name ?? ''), 'phone' => (string) ($b->phone ?? ''), 'hub' => $hubs[$hid]['name'] ?? '', 'city' => $hubs[$hid]['city'] ?? ''];
            $byCat[$cat] = ($byCat[$cat] ?? 0) + 1;
            $hn = $hubs[$hid]['name'] ?? 'Unknown hub'; $byHub[$hn] = ($byHub[$hn] ?? 0) + 1;
        }
        arsort($byCat); arsort($byHub);
        return ['ok' => true, 'available' => true, 'rows' => $out, 'open' => $g['status'] ? $open : null, 'total' => count($out),
            'by_category' => array_map(fn ($k, $v) => ['name' => $k, 'count' => $v], array_keys($byCat), array_values($byCat)),
            'by_hub' => array_map(fn ($k, $v) => ['name' => $k, 'count' => $v], array_keys($byHub), array_values($byHub))];
    }
}
