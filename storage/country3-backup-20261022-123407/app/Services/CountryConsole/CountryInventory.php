<?php

namespace App\Services\CountryConsole;

use App\Services\HubConsole\HubStock;
use Illuminate\Support\Facades\DB;

/**
 * Hub inventory sheet (22 Oct 2026, owner's Excel layout): one row per product, one column group per hub.
 * Per hub: Live, Fresh, Danger, Expired, Incoming, Transferring out (+ received, sold, wasted for the day).
 *
 * Today: live = inventory.live_stock now; fresh / danger / expired from the stock batches (exact);
 *        incoming = sent to the hub and not yet accepted (inventory.stock) + transfers on the road to the hub;
 *        transferring out = transfers from the hub that are planned, requested or on the road.
 * A past day: closing stock = live now minus every logged change after that day (hub_stock_moves).
 *        Stock is used oldest first, so what is left at closing is the newest stock:
 *        fresh = min(closing, received that day), danger = min(rest, received the day before), expired = the rest.
 *        incoming / transferring out = transfers received / sent that day.
 *        The stock log starts when the hub console went live; earlier days cannot be rebuilt.
 */
class CountryInventory
{
    public const METRICS = ['live' => 'Live', 'fresh' => 'Fresh', 'danger' => 'Danger', 'expired' => 'Expired', 'in' => 'Incoming', 'out' => 'Transferring out', 'recv' => 'Received', 'sold' => 'Sold', 'waste' => 'Wasted'];
    private const IN_TYPES = ['inward', 'transfer_in'];

    public static function historyStart(): ?string
    {
        if (!CountryScope::has('hub_stock_moves')) return null;
        $m = DB::table('hub_stock_moves')->min('created_at');
        return $m ? date('Y-m-d', strtotime((string) $m)) : null;
    }

    public static function sheet(CountryScope $scope, string $date, string $category = '', string $q = '', bool $onlyStocked = false): array
    {
        $today = date('Y-m-d');
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || $date > $today) $date = $today;
        $start = self::historyStart();
        $note = null;
        if ($date < $today && (!$start || $date < $start)) { $note = 'The stock log starts on ' . ($start ? date('d M Y', strtotime($start)) : 'the day the hub console went live') . '. Earlier days cannot be rebuilt, so today is shown.'; $date = $today; }
        $ids = $scope->ids(); $hubs = CountryScope::hubs();
        $isToday = $date === $today;
        if ($isToday) CountryStock::reconcile($ids);
        $prods = CountryStock::products();
        $cells = [];   // code => hub => metrics
        $blank = fn () => array_fill_keys(array_keys(self::METRICS), 0.0);
        $set = function ($code, $h, $k, $v) use (&$cells, $blank) { $cells[$code][$h] = $cells[$code][$h] ?? $blank(); $cells[$code][$h][$k] = round($cells[$code][$h][$k] + $v, 3); };

        $dayFrom = $date . ' 00:00:00'; $dayTo = $date . ' 23:59:59';
        // Live stock now, and pending inward (only meaningful today).
        $live = [];
        foreach (array_chunk($ids, 500) as $c) foreach (DB::table('inventory')->whereIn('hub_id', $c)->get(['hub_id', 'product_id', 'live_stock', 'stock']) as $r) {
            $live[(string) $r->product_id][(int) $r->hub_id] = (float) $r->live_stock;
            if ($isToday && (float) $r->stock > 0) $set((string) $r->product_id, (int) $r->hub_id, 'in', (float) $r->stock);
        }
        $moves = CountryScope::has('hub_stock_moves');
        // Day movements: received, sold, wasted, transfers in / out.
        $day = []; $prevRecv = []; $after = [];
        if ($moves && $ids) {
            foreach (array_chunk($ids, 500) as $c) {
                foreach (DB::table('hub_stock_moves')->whereIn('hub_id', $c)->whereBetween('created_at', [date('Y-m-d 00:00:00', strtotime($date . ' -1 day')), $dayTo])
                    ->groupBy('hub_id', 'product_code', 'type', DB::raw('DATE(created_at)'))->select('hub_id', 'product_code', 'type', DB::raw('DATE(created_at) AS d'), DB::raw('SUM(change_qty) AS q'))->get() as $m) {
                    $code = (string) $m->product_code; $h = (int) $m->hub_id; $mq = (float) $m->q;
                    if ((string) $m->d !== $date) { if (in_array($m->type, self::IN_TYPES, true) || ($m->type === 'count' && $mq > 0)) $prevRecv[$code][$h] = ($prevRecv[$code][$h] ?? 0) + max(0, $mq); continue; }
                    $day[$code][$h][$m->type] = ($day[$code][$h][$m->type] ?? 0) + $mq;
                }
                if (!$isToday) foreach (DB::table('hub_stock_moves')->whereIn('hub_id', $c)->where('created_at', '>', $dayTo)->groupBy('hub_id', 'product_code')->select('hub_id', 'product_code', DB::raw('SUM(change_qty) AS q'))->get() as $m) $after[(string) $m->product_code][(int) $m->hub_id] = (float) $m->q;
            }
        }
        foreach ($day as $code => $byHub) foreach ($byHub as $h => $t) {
            $set($code, $h, 'recv', max(0, $t['inward'] ?? 0));
            $set($code, $h, 'sold', max(0, -(($t['sale'] ?? 0) + ($t['return'] ?? 0))));
            $set($code, $h, 'waste', max(0, -($t['wastage'] ?? 0)));
            if (!$isToday) { $set($code, $h, 'in', max(0, $t['transfer_in'] ?? 0)); $set($code, $h, 'out', max(0, -(($t['transfer_out'] ?? 0) + ($t['transfer_back'] ?? 0)))); }
        }
        // Stock position.
        if ($isToday) {
            $today0 = date('Y-m-d'); $yest = date('Y-m-d', strtotime('-1 day'));
            foreach ($live as $code => $byHub) foreach ($byHub as $h => $v) $set($code, $h, 'live', $v);
            if (HubStock::lotsOn() && $ids) foreach (array_chunk($ids, 500) as $c) {
                foreach (DB::table('hub_stock_lots')->whereIn('hub_id', $c)->where('qty_left', '>', 0)->groupBy('hub_id', 'product_code')->select('hub_id', 'product_code',
                    DB::raw("SUM(CASE WHEN received_on >= '$today0' THEN qty_left ELSE 0 END) AS f"), DB::raw("SUM(CASE WHEN received_on < '$today0' AND received_on >= '$yest' THEN qty_left ELSE 0 END) AS d"),
                    DB::raw("SUM(CASE WHEN received_on < '$yest' THEN qty_left ELSE 0 END) AS e"))->get() as $l) {
                    $set((string) $l->product_code, (int) $l->hub_id, 'fresh', (float) $l->f); $set((string) $l->product_code, (int) $l->hub_id, 'danger', (float) $l->d); $set((string) $l->product_code, (int) $l->hub_id, 'expired', (float) $l->e);
                }
            }
            // Transfers on the road to the hub (incoming) and from the hub (planned / requested / on the road).
            if (CountryScope::has('hub_transfers') && $ids) {
                $open = DB::table('hub_transfers AS t')->join('hub_transfer_items AS i', 'i.transfer_id', '=', 't.id')->whereIn('t.status', ['planned', 'requested', 'in_transit'])
                    ->where(fn ($w) => $w->whereIn('t.from_hub_id', $ids)->orWhereIn('t.to_hub_id', $ids))->get(['t.status', 't.from_hub_id', 't.to_hub_id', 'i.product_code', 'i.requested_qty', 'i.sent_qty']);
                $in = array_flip($ids);
                foreach ($open as $o) {
                    $tq = (float) ($o->status === 'in_transit' ? $o->sent_qty : $o->requested_qty);
                    if ($tq <= 0) continue;
                    if ($o->status === 'in_transit' && isset($in[(int) $o->to_hub_id])) $set((string) $o->product_code, (int) $o->to_hub_id, 'in', $tq);
                    if (isset($in[(int) $o->from_hub_id])) $set((string) $o->product_code, (int) $o->from_hub_id, 'out', $tq);
                }
            }
        } else {
            $codes = array_unique(array_merge(array_keys($live), array_keys($after), array_keys($day)));
            foreach ($codes as $code) foreach ($ids as $h) {
                if (!isset($live[$code][$h]) && !isset($after[$code][$h]) && !isset($day[$code][$h])) continue;
                $close = round(($live[$code][$h] ?? 0) - ($after[$code][$h] ?? 0), 3);
                $recvDay = 0.0; foreach (($day[$code][$h] ?? []) as $type => $dq) if (in_array($type, self::IN_TYPES, true) || ($type === 'count' && $dq > 0)) $recvDay += max(0, $dq);
                $c = max(0, $close);
                $f = min($c, $recvDay); $d = min($c - $f, $prevRecv[$code][$h] ?? 0); $e = max(0, $c - $f - $d);
                $set($code, $h, 'live', $close); $set($code, $h, 'fresh', $f); $set($code, $h, 'danger', $d); $set($code, $h, 'expired', $e);
            }
        }
        // Rows.
        $term = mb_strtolower(trim($q));
        $rows = []; $tot = []; $grand = $blank(); $sn = 0;
        foreach ($prods as $code => $p) {
            $c = $cells[$code] ?? [];
            if (!$p['active'] && !$c) continue;
            if ($category !== '' && strcasecmp($p['category'], $category) !== 0) continue;
            if ($term !== '' && !str_contains(mb_strtolower($p['name'] . ' ' . $code . ' ' . $p['category']), $term)) continue;
            $sum = $blank();
            foreach ($c as $h => $m) foreach ($m as $k => $v) $sum[$k] = round($sum[$k] + $v, 3);
            if ($onlyStocked && $sum['live'] <= 0 && $sum['in'] <= 0) continue;
            foreach ($c as $h => $m) { $tot[$h] = $tot[$h] ?? $blank(); foreach ($m as $k => $v) $tot[$h][$k] = round($tot[$h][$k] + $v, 3); }
            foreach ($sum as $k => $v) $grand[$k] = round($grand[$k] + $v, 3);
            $rows[] = ['sn' => ++$sn, 'id' => $p['id'], 'code' => $code, 'name' => $p['name'], 'category' => $p['category'], 'pack' => $p['pack'], 'uom' => trim(preg_replace('/^[\d.\s]+/', '', $p['pack'])) ?: $p['pack'],
                'price' => $p['price'], 'active' => $p['active'], 'total' => $sum, 'value' => round(max(0, $sum['live']) * $p['price'], 2), 'cells' => (object) $c];
        }
        $cols = [];
        foreach ($ids as $h) if (isset($hubs[$h])) $cols[] = ['id' => $h, 'name' => $hubs[$h]['name'], 'city' => $hubs[$h]['city'], 'active' => $hubs[$h]['active'], 'total' => $tot[$h] ?? $blank()];
        $cats = array_values(array_unique(array_map(fn ($p) => $p['category'], $prods))); sort($cats);
        return ['ok' => true, 'date' => $date, 'today' => $isToday, 'note' => $note, 'history_start' => $start, 'metrics' => self::METRICS, 'hubs' => $cols, 'rows' => $rows, 'total' => $grand,
            'value' => round(array_sum(array_column($rows, 'value')), 2), 'categories' => $cats, 'scope' => $scope->label()];
    }

    /** Excel workbook in the owner's layout. $metric = one metric per hub column, or 'all' = a column group per hub. */
    public static function xlsx(array $s, string $metric): string
    {
        $m = self::METRICS;
        $keys = $metric === 'all' ? ['live', 'fresh', 'danger', 'in', 'out'] : [array_key_exists($metric, $m) ? $metric : 'live'];
        $x = new SimpleXlsx();
        $title = 'Hub inventory - ' . $s['scope'] . ' - ' . date('d M Y', strtotime($s['date'])) . (count($keys) === 1 ? ' - ' . $m[$keys[0]] : '');
        $x->row([$title], 'title');
        $x->row([]);
        $head1 = ['S.No', 'Products', 'Ids', 'Category', 'UOM'];
        $fixed = count($head1);
        if (count($keys) === 1) {
            $x->row(array_merge($head1, ['Total'], array_map(fn ($h) => $h['name'], $s['hubs'])), 'head');
        } else {
            $r1 = $head1; $r2 = array_fill(0, $fixed, '');
            foreach (array_merge([['name' => 'Total']], $s['hubs']) as $h) { $r1[] = $h['name']; foreach ($keys as $i => $k) { if ($i) $r1[] = ''; $r2[] = $m[$k]; } }
            $row1 = $x->row($r1, 'head'); $x->row($r2, 'head');
            for ($i = 0; $i < $fixed; $i++) $x->merge($i, $row1, $i, $row1 + 1);
            $col = $fixed; foreach (array_merge([0], $s['hubs']) as $_) { $x->merge($col, $row1, $col + count($keys) - 1, $row1); $col += count($keys); }
        }
        foreach ($s['rows'] as $r) {
            $line = [$r['sn'], $r['name'], $r['code'], $r['category'], $r['uom']];
            foreach ($keys as $k) $line[] = $r['total'][$k];
            foreach ($s['hubs'] as $h) { $c = ((array) $r['cells'])[$h['id']] ?? null; foreach ($keys as $k) $line[] = $c ? $c[$k] : null; }
            $x->row($line);
        }
        $tl = ['', 'Total', '', '', ''];
        if (count($keys) === 1) { $tl[] = $s['total'][$keys[0]]; foreach ($s['hubs'] as $h) $tl[] = $h['total'][$keys[0]]; }
        else { foreach ($keys as $k) $tl[] = $s['total'][$k]; foreach ($s['hubs'] as $h) foreach ($keys as $k) $tl[] = $h['total'][$k]; }
        $x->row($tl, 'head');
        $x->widths([6, 38, 11, 18, 9]);
        $x->freeze($fixed, count($keys) === 1 ? 3 : 4);
        return $x->build('Hub inventory');
    }
}
