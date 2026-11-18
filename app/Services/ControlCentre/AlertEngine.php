<?php

namespace App\Services\ControlCentre;

use App\Services\AdminConsole\AdminAudit;
use App\Services\CountryConsole\CountryAnalytics;
use App\Services\CountryConsole\CountryScope;
use App\Services\PosConsole\OrderFlow;
use Illuminate\Support\Facades\DB;

/**
 * Freshful Control Centre (15 Nov 2026): the alert centre.
 * Every alert is computed from real records by a fixed rule (no AI guesses), carries its period, impact, confidence and
 * next step, and can be acknowledged, assigned or dismissed. Each action is written to cc_alert_events and the admin audit log.
 * An alert whose condition clears is marked "resolved" by the next scan; a dismissed alert stays dismissed.
 */
class AlertEngine
{
    public const RULES = ['stuck_orders', 'cash_pending', 'cancel_rate', 'expired_stock', 'low_cover', 'data_quality'];
    /** Checks that stay on the Data health page only (a permanent alert about them would just be noise). */
    public const INFO_ONLY = ['cost_missing', 'lots'];
    public const ACTIONS = ['acknowledge', 'assign', 'dismiss', 'reopen'];

    public static function on(): bool { return CountryScope::has('cc_alerts') && CountryScope::has('cc_alert_events'); }

    private static function hubName(int $id): string { return CountryScope::hubs()[$id]['name'] ?? ('Hub ' . $id); }
    private static function rs(float $v): string { return 'Rs ' . number_format($v, 0); }

    /** All alerts that are true right now, for every hub. Pure read. */
    public static function candidates(int $now, ?array $hubIds = null): array
    {
        $out = [];
        $ids = $hubIds ?? (new CountryScope([]))->ids();
        $to = date('Y-m-d 23:59:59', $now); $from7 = date('Y-m-d 00:00:00', $now - 6 * 86400);
        $pf = date('Y-m-d', $now - 6 * 86400); $pt = date('Y-m-d', $now);
        $rows = CountryAnalytics::orderRows($ids, $from7, $to);

        $stuck = []; $cash = []; $byHub = [];
        foreach ($rows as $r) {
            $h = $r['hub'];
            $byHub[$h]['all'] = ($byHub[$h]['all'] ?? 0) + 1;
            if ($r['stage'] === 'cancelled') { $byHub[$h]['can'] = ($byHub[$h]['can'] ?? 0) + 1; continue; }
            if (in_array($r['stage'], ['new', 'accepted', 'out'], true) && (($r['due'] && $r['due'] < $now - 1800) || OrderFlow::isStale($r['stage'], $r['due'], $r['at'], $now))) {
                $stuck[$h]['n'] = ($stuck[$h]['n'] ?? 0) + 1; $stuck[$h]['v'] = ($stuck[$h]['v'] ?? 0) + $r['value'];
            }
            if ($r['stage'] === 'unsettled' && $r['at'] < $now - 86400) {
                $cash[$h]['n'] = ($cash[$h]['n'] ?? 0) + 1; $cash[$h]['v'] = ($cash[$h]['v'] ?? 0) + $r['value'];
            }
        }
        foreach ($stuck as $h => $s) {
            $out[] = ['rule' => 'stuck_orders', 'fingerprint' => 'stuck_orders|' . $h, 'severity' => $s['n'] >= 5 ? 'bad' : 'warn', 'hub_id' => $h,
                'title' => $s['n'] . ' open order' . ($s['n'] > 1 ? 's' : '') . ' late or stuck at ' . self::hubName($h),
                'detail' => 'Not yet delivered and past the promised time by over 30 minutes, or open for 3 days.', 'impact_value' => round($s['v'], 2), 'impact_label' => 'Order value at risk',
                'confidence' => 'high', 'period_from' => $pf, 'period_to' => $pt, 'records' => $s['n'], 'link' => 'orders', 'next_step' => 'Open the orders, call the rider or hub, then deliver or cancel with a reason.'];
        }
        foreach ($cash as $h => $s) {
            $out[] = ['rule' => 'cash_pending', 'fingerprint' => 'cash_pending|' . $h, 'severity' => $s['v'] >= 20000 ? 'bad' : 'warn', 'hub_id' => $h,
                'title' => self::rs($s['v']) . ' delivered cash not settled at ' . self::hubName($h),
                'detail' => $s['n'] . ' delivered order' . ($s['n'] > 1 ? 's' : '') . ' older than 24 hours still waiting for the hub to settle the rider cash.', 'impact_value' => round($s['v'], 2), 'impact_label' => 'Cash not yet banked',
                'confidence' => 'high', 'period_from' => $pf, 'period_to' => $pt, 'records' => $s['n'], 'link' => 'cash', 'next_step' => 'Ask the hub to settle the rider cash today and record any shortage.'];
        }
        foreach ($byHub as $h => $s) {
            $all = (int) ($s['all'] ?? 0); $can = (int) ($s['can'] ?? 0);
            if ($all < 10) continue;
            $rate = round($can * 100 / $all, 1);
            if ($rate < 20) continue;
            $out[] = ['rule' => 'cancel_rate', 'fingerprint' => 'cancel_rate|' . $h, 'severity' => $rate >= 35 ? 'bad' : 'warn', 'hub_id' => $h,
                'title' => $rate . '% of orders cancelled at ' . self::hubName($h) . ' (last 7 days)',
                'detail' => $can . ' of ' . $all . ' orders were cancelled. The network threshold is 20%.', 'impact_value' => null, 'impact_label' => $can . ' lost orders',
                'confidence' => $all >= 30 ? 'medium' : 'low', 'period_from' => $pf, 'period_to' => $pt, 'records' => $can, 'link' => 'cancels', 'next_step' => 'Read the cancellation reasons and fix the biggest one (stock, delay, or price).'];
        }
        foreach (self::expired($now) as $a) $out[] = $a + ['period_from' => $pf, 'period_to' => $pt];
        foreach (self::cover($now, count($rows)) as $a) $out[] = $a + ['period_from' => $pf, 'period_to' => $pt];
        foreach (DataHealth::run() as $c) {
            if ($c['status'] !== 'bad' || in_array($c['key'], self::INFO_ONLY, true)) continue;
            $out[] = ['rule' => 'data_quality', 'fingerprint' => 'data_quality|' . $c['key'], 'severity' => 'warn', 'hub_id' => null, 'title' => 'Data problem: ' . $c['label'],
                'detail' => $c['detail'] . ' Affects: ' . $c['affects'], 'impact_value' => null, 'impact_label' => 'Reports may be wrong', 'confidence' => 'high', 'period_from' => null, 'period_to' => null,
                'records' => (int) $c['bad'], 'link' => 'cc-health', 'next_step' => $c['hint']];
        }
        return $out;
    }

    /** Expired stock per hub, worked out like the hub console does: lots received 2+ days ago, capped by what is really in stock
     *  (live stock minus the newer lots, because the oldest stock is sold first). Value is estimated at selling price. */
    private static function expired(int $now): array
    {
        if (!CountryScope::has('hub_stock_lots') || !CountryScope::has('inventory') || !CountryScope::has('products', 'main_price')) return [];
        try {
            $yest = date('Y-m-d', $now - 86400);
            $young = []; $old = [];
            foreach (DB::table('hub_stock_lots')->where('qty_left', '>', 0)->get(['hub_id', 'product_code', 'received_on', 'qty_left']) as $l) {
                $k = (int) $l->hub_id . '|' . $l->product_code;
                if ((string) $l->received_on >= $yest) $young[$k] = ($young[$k] ?? 0) + (float) $l->qty_left; else $old[$k] = ($old[$k] ?? 0) + (float) $l->qty_left;
            }
            if (!$old) return [];
            $live = []; foreach (DB::table('inventory')->get(['hub_id', 'product_id', 'live_stock']) as $i) $live[(int) $i->hub_id . '|' . $i->product_id] = (float) $i->live_stock;
            $price = []; foreach (DB::table('products')->get(['product_id', 'main_price']) as $p) $price[(string) $p->product_id] = (float) $p->main_price;
        } catch (\Throwable $e) { return []; }
        $by = [];
        foreach ($old as $k => $oldQty) {
            [$h, $code] = explode('|', $k, 2);
            if (!isset($price[$code])) continue; // old stock line for a product that is no longer in the catalogue: the console ignores these too
            $exp = min($oldQty, max(0.0, ($live[$k] ?? 0.0) - ($young[$k] ?? 0.0)));
            if ($exp < 0.0005) continue;
            $by[(int) $h]['n'] = ($by[(int) $h]['n'] ?? 0) + 1;
            $by[(int) $h]['v'] = ($by[(int) $h]['v'] ?? 0) + $exp * ($price[$code] ?? 0.0);
        }
        $out = [];
        foreach ($by as $h => $x) {
            $v = round($x['v'], 2); $n = $x['n'];
            $out[] = ['rule' => 'expired_stock', 'fingerprint' => 'expired_stock|' . $h, 'severity' => $v >= 10000 ? 'bad' : 'warn', 'hub_id' => $h,
                'title' => $n . ' product' . ($n > 1 ? 's' : '') . ' with expired stock at ' . self::hubName($h),
                'detail' => 'Stock received 2 or more days ago and still on the shelf (your freshness rule). Quantity is capped at the hub\'s live stock. Value is estimated at selling price.', 'impact_value' => $v, 'impact_label' => 'Estimated stock value at risk',
                'confidence' => 'medium', 'records' => $n, 'link' => 'danger', 'next_step' => 'Check the shelf. Sell at a discount, transfer to a busier hub, or write off with a reason today. If the stock count is wrong, correct it first.',
                'period_from' => null, 'period_to' => null];
        }
        return $out;
    }

    private static function cover(int $now, int $orders7): array
    {
        if (!CountryScope::has('inventory') || !CountryScope::has('products')) return [];
        try {
            $from = date('Y-m-d 00:00:00', $now - 7 * 86400);
            $sales = DB::table('orders AS o')->join('products AS p', 'p.id', '=', 'o.product_id')->where('o.date_added', '>=', $from)->whereNotIn('o.order_status', OrderFlow::statusesFor(['cancelled']))
                ->groupBy('o.hub_id', 'p.product_id')->select('o.hub_id', 'p.product_id AS code', DB::raw('SUM(CAST(o.quantity AS DECIMAL(14,3))) AS q'))->get();
            $per = [];
            foreach ($sales as $s) $per[(int) $s->hub_id][(string) $s->code] = (float) $s->q / 7;
            $inv = DB::table('inventory')->get(['hub_id', 'product_id', 'live_stock']);
        } catch (\Throwable $e) { return []; }
        $low = [];
        foreach ($inv as $i) {
            $pd = $per[(int) $i->hub_id][(string) $i->product_id] ?? 0.0;
            if ($pd < 1) continue; // too little demand to judge cover
            $cover = max(0.0, (float) $i->live_stock) / $pd;
            if ($cover < 1.5) $low[(int) $i->hub_id] = ($low[(int) $i->hub_id] ?? 0) + 1;
        }
        $out = [];
        foreach ($low as $h => $n) {
            $out[] = ['rule' => 'low_cover', 'fingerprint' => 'low_cover|' . $h, 'severity' => $n >= 5 ? 'bad' : 'warn', 'hub_id' => $h,
                'title' => $n . ' product' . ($n > 1 ? 's' : '') . ' will run out within 1.5 days at ' . self::hubName($h),
                'detail' => 'Live stock divided by the average sold per day over the last 7 days. Estimated: depends on the hub stock count being right.', 'impact_value' => null, 'impact_label' => 'Possible lost sales',
                'confidence' => $orders7 >= 30 ? 'medium' : 'low', 'records' => $n, 'link' => 'stock', 'next_step' => 'Raise a transfer or purchase for these products before the next order peak.'];
        }
        return $out;
    }

    private static function stamp(int $now): string { return date('Y-m-d H:i:s', $now); }

    private static function event(int $id, string $action, array $actor, ?string $note, int $now): void
    {
        DB::table('cc_alert_events')->insert(['alert_id' => $id, 'action' => $action, 'actor' => mb_substr((string) ($actor['name'] ?? 'system'), 0, 120), 'actor_role' => mb_substr((string) ($actor['role'] ?? ''), 0, 30),
            'note' => $note !== null ? mb_substr($note, 0, 300) : null, 'created_at' => self::stamp($now)]);
    }

    /** Refresh the table from the rules. Returns counts. Safe to run any time (idempotent). */
    public static function scan(?int $now = null, ?array $hubIds = null): array
    {
        if (!self::on()) return ['ok' => false, 'message' => 'The alert tables are not installed. Run the migration first.'];
        $now = $now ?? time();
        $cand = self::candidates($now, $hubIds);
        $seen = []; $new = 0; $reopened = 0; $resolved = 0;
        foreach ($cand as $c) {
            $fp = $c['fingerprint']; $seen[$fp] = true;
            $row = DB::table('cc_alerts')->where('fingerprint', $fp)->first();
            $vals = array_diff_key($c, ['fingerprint' => 1]) + ['last_seen' => self::stamp($now), 'updated_at' => self::stamp($now)];
            if (!$row) {
                $id = DB::table('cc_alerts')->insertGetId(['fingerprint' => $fp, 'status' => 'open', 'first_seen' => self::stamp($now)] + $vals);
                self::event($id, 'raised', [], null, $now); $new++;
            } else {
                $upd = $vals;
                if ($row->status === 'resolved') { $upd['status'] = 'open'; self::event((int) $row->id, 'reopened', [], 'Condition returned', $now); $reopened++; }
                DB::table('cc_alerts')->where('id', $row->id)->update($upd);
            }
        }
        $q = DB::table('cc_alerts')->whereIn('rule', self::RULES)->whereIn('status', ['open', 'acknowledged']);
        if ($hubIds !== null) $q->whereIn('hub_id', $hubIds);
        foreach ($q->get(['id', 'fingerprint']) as $o) {
            if (isset($seen[$o->fingerprint])) continue;
            DB::table('cc_alerts')->where('id', $o->id)->update(['status' => 'resolved', 'updated_at' => self::stamp($now)]);
            self::event((int) $o->id, 'resolved', [], 'Condition cleared', $now); $resolved++;
        }
        return ['ok' => true, 'found' => count($cand), 'new' => $new, 'reopened' => $reopened, 'resolved' => $resolved, 'at' => self::stamp($now)];
    }

    /** Scan when the alerts are older than $maxAge seconds (or were never made). Used when the Alert centre opens. */
    public static function ensureFresh(int $maxAge = 600, ?int $now = null): ?array
    {
        if (!self::on()) return null;
        $now = $now ?? time();
        $last = CountryScope::ts(DB::table('cc_alerts')->max('last_seen'));
        if ($last !== null && $now - $last < $maxAge) return null;
        return self::scan($now);
    }

    public static function list(array $f = []): array
    {
        if (!self::on()) return ['ok' => false, 'message' => 'The alert tables are not installed. Run the migration first.'];
        $status = (string) ($f['status'] ?? 'active');
        $q = DB::table('cc_alerts');
        if ($status === 'active') $q->whereIn('status', ['open', 'acknowledged']); elseif (in_array($status, ['open', 'acknowledged', 'dismissed', 'resolved'], true)) $q->where('status', $status);
        if (in_array($f['severity'] ?? '', ['bad', 'warn', 'info'], true)) $q->where('severity', $f['severity']);
        if (!empty($f['hub'])) $q->where('hub_id', (int) $f['hub']);
        $rows = $q->orderByRaw("CASE severity WHEN 'bad' THEN 0 WHEN 'warn' THEN 1 ELSE 2 END")->orderByDesc('impact_value')->limit(300)->get();
        $hubs = CountryScope::hubs();
        $list = [];
        foreach ($rows as $r) {
            $list[] = ['id' => (int) $r->id, 'rule' => $r->rule, 'severity' => $r->severity, 'title' => $r->title, 'detail' => $r->detail, 'impact_value' => $r->impact_value !== null ? (float) $r->impact_value : null,
                'impact_label' => $r->impact_label, 'confidence' => $r->confidence, 'period_from' => $r->period_from, 'period_to' => $r->period_to, 'hub_id' => $r->hub_id ? (int) $r->hub_id : null,
                'hub' => $r->hub_id ? ($hubs[(int) $r->hub_id]['name'] ?? '') : '', 'records' => (int) $r->records, 'link' => $r->link, 'next_step' => $r->next_step, 'status' => $r->status,
                'assigned_name' => $r->assigned_name, 'first_seen' => $r->first_seen, 'last_seen' => $r->last_seen];
        }
        $counts = [];
        foreach (DB::table('cc_alerts')->groupBy('status')->select('status', DB::raw('COUNT(*) AS n'))->get() as $c) $counts[$c->status] = (int) $c->n;
        return ['ok' => true, 'alerts' => $list, 'counts' => $counts, 'last_scan' => CountryScope::ts(DB::table('cc_alerts')->max('last_seen'))];
    }

    public static function history(int $id): array
    {
        if (!self::on()) return ['ok' => false, 'message' => 'The alert tables are not installed.'];
        $rows = DB::table('cc_alert_events')->where('alert_id', $id)->orderByDesc('id')->limit(50)->get();
        return ['ok' => true, 'events' => $rows->map(fn ($e) => ['action' => $e->action, 'actor' => $e->actor, 'role' => $e->actor_role, 'note' => $e->note, 'at' => CountryScope::ts($e->created_at)])->all()];
    }

    /** Acknowledge, assign, dismiss or reopen. Every action is logged. */
    public static function act(int $id, string $action, array $actor, array $in = [], ?int $now = null): array
    {
        if (!self::on()) return ['ok' => false, 'message' => 'The alert tables are not installed.'];
        if (!in_array($action, self::ACTIONS, true)) return ['ok' => false, 'message' => 'Unknown action.'];
        $now = $now ?? time();
        $a = DB::table('cc_alerts')->where('id', $id)->first();
        if (!$a) return ['ok' => false, 'message' => 'Alert not found.'];
        $note = trim((string) ($in['note'] ?? ''));
        $upd = ['updated_at' => self::stamp($now)];
        if ($action === 'acknowledge') {
            if ($a->status !== 'open') return ['ok' => false, 'message' => 'Only an open alert can be acknowledged.'];
            $upd['status'] = 'acknowledged';
        } elseif ($action === 'assign') {
            $who = trim((string) ($in['assignee'] ?? ''));
            if (mb_strlen($who) < 2) return ['ok' => false, 'message' => 'Type who this alert is assigned to.'];
            if (in_array($a->status, ['dismissed', 'resolved'], true)) return ['ok' => false, 'message' => 'This alert is closed. Reopen it first.'];
            $upd['assigned_name'] = mb_substr($who, 0, 120); $upd['assigned_to'] = !empty($in['self']) ? (int) ($actor['id'] ?? 0) : null;
            if ($a->status === 'open') $upd['status'] = 'acknowledged';
            $note = $note !== '' ? $note : 'Assigned to ' . $upd['assigned_name'];
        } elseif ($action === 'dismiss') {
            if (mb_strlen($note) < 3) return ['ok' => false, 'message' => 'Write a short reason for dismissing this alert.'];
            if (in_array($a->status, ['dismissed', 'resolved'], true)) return ['ok' => false, 'message' => 'This alert is already closed.'];
            $upd['status'] = 'dismissed';
        } else { // reopen
            if (!in_array($a->status, ['dismissed', 'resolved', 'acknowledged'], true)) return ['ok' => false, 'message' => 'This alert is already open.'];
            $upd['status'] = 'open';
        }
        DB::table('cc_alerts')->where('id', $id)->update($upd);
        self::event($id, $action, $actor, $note !== '' ? $note : null, $now);
        try { AdminAudit::log($actor, 'alert_' . $action, 'control_centre', 'cc_alerts', $id, mb_substr($a->title, 0, 200), ['status' => $a->status, 'assigned' => $a->assigned_name], ['status' => $upd['status'] ?? $a->status, 'assigned' => $upd['assigned_name'] ?? $a->assigned_name]); } catch (\Throwable $e) { /* audit failure must not block */ }
        return ['ok' => true, 'message' => ['acknowledge' => 'Acknowledged.', 'assign' => 'Assigned.', 'dismiss' => 'Dismissed. It stays dismissed until you reopen it.', 'reopen' => 'Reopened.'][$action], 'status' => $upd['status'] ?? $a->status];
    }
}
