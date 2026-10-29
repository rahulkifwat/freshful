<?php

namespace App\Services\ProductionConsole;

use App\Services\CountryConsole\CountryScope;
use App\Services\CountryConsole\CountryStock;
use App\Services\CountryConsole\CountryUnits;
use Illuminate\Support\Facades\DB;

/**
 * Freshful production console (production update 1, 28 Oct 2026): sending produced stock to the hubs.
 *
 * Same mechanism as the old panel: what production sends waits at the hub as inventory.stock ("pending inward").
 * The hub counts it in with "Accept inward stocks" (hub console / country console), which clears inventory.stock,
 * records any difference as variance and adds the received packs as today's fresh batch through the hub stock engine.
 * So production never touches live stock. Every send is a numbered dispatch note (PB-YYMMDD-NNN) with its lines.
 *
 * Received or not: a line is received when the hub's receipt (hub_stock_moves type "inward") for that product comes
 * after the line was sent (receipt id above the line's moves_before mark, so clocks and same-second events cannot confuse it). One receipt can cover several sends; what the hub counted in is given to the dispatch lines first,
 * oldest first, and anything over that belongs to older pending stock.
 * A line can be cancelled only while its packs are still waiting at the hub (the pending quantity is taken back).
 */
class ProductionDispatch
{
    private static function h(string $t, ?string $c = null): bool { return CountryScope::has($t, $c); }
    public static function on(): bool { return self::h('production_batches') && self::h('production_batch_lines'); }

    /** lines: [[hub_id, code, qty], ...] in packs (whole numbers). */
    public static function send(array $lines, ?string $planDate, string $note, string $vehicle, array $actor, string $clientRef = ''): array
    {
        if (!self::on()) return ['ok' => false, 'message' => 'The production tables are not installed yet.'];
        $clientRef = preg_match('/^[A-Za-z0-9\-]{8,40}$/', $clientRef) ? $clientRef : '';
        if ($clientRef !== '' && ($dup = DB::table('production_batches')->where('client_ref', $clientRef)->first())) {
            return ['ok' => true, 'batch' => ['id' => (int) $dup->id, 'no' => (string) $dup->batch_no, 'lines' => (int) $dup->lines, 'hubs' => (int) $dup->hubs, 'packs' => (float) $dup->packs, 'value' => (float) $dup->value], 'message' => $dup->batch_no . ' was already sent (nothing sent twice).'];
        }
        $hubs = CountryScope::hubs(); $prods = CountryStock::products();
        $max = (float) config('production_console.dispatch.max_packs_per_line', 5000);
        $maxLines = (int) config('production_console.dispatch.max_lines', 600);
        $clean = []; $errors = [];
        foreach (array_slice($lines, 0, $maxLines + 1) as $l) {
            $h = (int) ($l['hub_id'] ?? 0); $code = CountryStock::canon((string) ($l['code'] ?? '')); $q = $l['qty'] ?? null;
            if (!isset($hubs[$h])) { $errors[] = 'A hub is not in your list.'; continue; }
            if (!$hubs[$h]['active']) { $errors[] = $hubs[$h]['name'] . ' is switched off.'; continue; }
            if (!isset($prods[$code])) { $errors[] = 'Unknown product ' . $code . '.'; continue; }
            if (!is_numeric($q) || (float) $q <= 0) continue;
            if (floor((float) $q) != (float) $q) { $errors[] = $prods[$code]['name'] . ': send whole packs (' . $prods[$code]['pack'] . ' each).'; continue; }
            $k = $h . '|' . $code;
            $clean[$k] = ['hub_id' => $h, 'code' => $code, 'qty' => ($clean[$k]['qty'] ?? 0) + (int) $q];
            if ($clean[$k]['qty'] > $max) { $errors[] = $prods[$code]['name'] . ' to ' . $hubs[$h]['name'] . ': more than ' . (int) $max . ' packs in one line.'; continue; }
        }
        ksort($clean); // same lock order in every send, so two sends at once cannot deadlock
        if ($errors) return ['ok' => false, 'message' => $errors[0] . (count($errors) > 1 ? ' (' . (count($errors) - 1) . ' more problem' . (count($errors) > 2 ? 's' : '') . ')' : ''), 'errors' => array_slice($errors, 0, 20)];
        if (!$clean) return ['ok' => false, 'message' => 'Nothing to send. Enter the packs for at least one hub.'];
        if (count($clean) > $maxLines) return ['ok' => false, 'message' => 'Too many lines in one dispatch (max ' . $maxLines . '). Send it in parts.'];
        $planDate = $planDate && preg_match('/^\d{4}-\d{2}-\d{2}$/', $planDate) ? $planDate : date('Y-m-d');
        $note = mb_substr(trim($note), 0, 250); $vehicle = mb_substr(trim($vehicle), 0, 60);

        $res = DB::transaction(function () use ($clean, $planDate, $note, $vehicle, $actor, $prods, $clientRef) {
            $now = now();
            // number = date + the row id: unique without locking a counter
            $bid = DB::table('production_batches')->insertGetId(['batch_no' => 'PB-TMP-' . bin2hex(random_bytes(8)), 'client_ref' => $clientRef !== '' ? $clientRef : null, 'plan_date' => $planDate, 'status' => 'sent', 'lines' => 0, 'hubs' => 0, 'packs' => 0, 'value' => 0,
                'vehicle' => $vehicle !== '' ? $vehicle : null, 'note' => $note !== '' ? $note : null, 'by_id' => $actor['id'] ?? null, 'by_name' => mb_substr((string) ($actor['name'] ?? ''), 0, 120), 'created_at' => $now, 'updated_at' => $now]);
            $packs = 0; $value = 0.0; $hubSet = [];
            $mark = self::h('hub_stock_moves') ? (int) (DB::table('hub_stock_moves')->max('id') ?? 0) : 0;
            foreach ($clean as $l) {
                $row = DB::table('inventory')->where('hub_id', $l['hub_id'])->where('product_id', $l['code'])->lockForUpdate()->first();
                $before = $row ? max(0, (float) $row->stock) : 0.0;
                if ($row) {
                    $upd = ['stock' => DB::raw('stock + ' . (int) $l['qty'])];
                    if (self::h('inventory', 'updated_at')) $upd['updated_at'] = $now;
                    DB::table('inventory')->where('id', $row->id)->update($upd);
                } else {
                    $ins = ['product_id' => $l['code'], 'hub_id' => $l['hub_id'], 'stock' => (int) $l['qty'], 'live_stock' => 0, 'fresh_stock' => 0, 'variance' => 0];
                    if (self::h('inventory', 'created_at')) { $ins['created_at'] = $now; $ins['updated_at'] = $now; }
                    DB::table('inventory')->insert($ins);
                }
                $price = (float) ($prods[$l['code']]['price'] ?? 0);
                DB::table('production_batch_lines')->insert(['batch_id' => $bid, 'hub_id' => $l['hub_id'], 'product_code' => $l['code'], 'qty' => $l['qty'], 'price' => $price,
                    'pending_before' => $before, 'moves_before' => $mark, 'status' => 'sent', 'created_at' => $now, 'updated_at' => $now]);
                $packs += $l['qty']; $value += $l['qty'] * $price; $hubSet[$l['hub_id']] = true;
            }
            $no = 'PB-' . date('ymd') . '-' . str_pad((string) $bid, 4, '0', STR_PAD_LEFT);
            DB::table('production_batches')->where('id', $bid)->update(['batch_no' => $no, 'lines' => count($clean), 'hubs' => count($hubSet), 'packs' => $packs, 'value' => round($value, 2)]);
            return ['id' => $bid, 'no' => $no, 'lines' => count($clean), 'hubs' => count($hubSet), 'packs' => $packs, 'value' => round($value, 2)];
        }, 3);
        ProductionLog::write($actor, 'send', 'production_batches', $res['no'], 'Sent ' . $res['no'] . ' to ' . $res['hubs'] . ' hub' . ($res['hubs'] === 1 ? '' : 's') . ': ' . $res['lines'] . ' lines, ' . $res['packs'] . ' packs');
        return ['ok' => true, 'batch' => $res, 'message' => $res['no'] . ' sent: ' . $res['packs'] . ' packs to ' . $res['hubs'] . ' hub' . ($res['hubs'] === 1 ? '' : 's') . '. The hubs will see it in Accept inward stocks.'];
    }

    /** Match open lines with the hubs' receipts (hub_stock_moves "inward"), and update batch status. Cheap; runs on every read. */
    public static function sync(?array $hubIds = null): void
    {
        if (!self::on()) return;
        if ($hubIds === null) $hubIds = array_keys(CountryScope::hubs());
        if (!$hubIds) return;
        $open = DB::table('production_batch_lines')->whereIn('hub_id', $hubIds)->where('status', 'sent')->where('created_at', '>=', date('Y-m-d H:i:s', strtotime('-90 days')))->orderBy('created_at')->get();
        if ($open->isEmpty()) return;
        $byKey = [];
        foreach ($open as $l) $byKey[$l->hub_id . '|' . $l->product_code][] = $l;
        $hasMoves = self::h('hub_stock_moves');
        $touched = [];
        foreach ($byKey as $k => $lines) {
            [$h, $code] = explode('|', $k, 2);
            $floor = min(array_map(fn ($l) => (int) $l->moves_before, $lines));
            $moves = $hasMoves ? DB::table('hub_stock_moves')->where('hub_id', (int) $h)->where('product_code', $code)->where('type', 'inward')->where('id', '>', $floor)->orderBy('id')->get() : collect();
            $used = DB::table('production_batch_lines')->whereNotNull('move_id')->where('hub_id', (int) $h)->where('product_code', $code)->pluck('move_id')->map(fn ($v) => (int) $v)->all();
            foreach ($moves as $m) {
                if (in_array((int) $m->id, $used, true)) continue;
                // every open line sent before this receipt is covered by it
                $covered = array_values(array_filter($lines, fn ($l) => $l->status === 'sent' && (int) $m->id > (int) $l->moves_before));
                if (!$covered) continue;
                // what the hub counted in goes to the production lines first (oldest first); anything left belongs to older pending stock
                $left = max(0.0, (float) $m->change_qty);
                foreach ($covered as $l) {
                    $share = round(min((float) $l->qty, $left), 3); $left = max(0.0, $left - $share);
                    DB::table('production_batch_lines')->where('id', $l->id)->where('status', 'sent')->update(['status' => 'received', 'received_qty' => $share, 'received_at' => $m->created_at, 'move_id' => $m->id, 'updated_at' => now()]);
                    $l->status = 'received'; $touched[$l->batch_id] = true;
                }
            }
            // Received in the old panel (no receipt record): nothing is waiting at the hub any more.
            $still = array_values(array_filter($lines, fn ($l) => $l->status === 'sent'));
            if ($still) {
                $pending = (float) (DB::table('inventory')->where('hub_id', (int) $h)->where('product_id', $code)->value('stock') ?? 0);
                if ($pending <= 0) foreach ($still as $l) {
                    DB::table('production_batch_lines')->where('id', $l->id)->where('status', 'sent')->update(['status' => 'received', 'received_qty' => null, 'received_at' => now(), 'updated_at' => now()]);
                    $l->status = 'received'; $touched[$l->batch_id] = true;
                }
            }
        }
        foreach (array_keys($touched) as $b) self::batchStatus((int) $b);
    }

    private static function batchStatus(int $id): void
    {
        $c = DB::table('production_batch_lines')->where('batch_id', $id)->groupBy('status')->select('status', DB::raw('COUNT(*) AS n'))->pluck('n', 'status')->all();
        $sent = (int) ($c['sent'] ?? 0); $rec = (int) ($c['received'] ?? 0); $can = (int) ($c['cancelled'] ?? 0);
        $st = $sent === 0 ? ($rec === 0 && $can > 0 ? 'cancelled' : 'received') : ($rec > 0 ? 'partly' : 'sent');
        DB::table('production_batches')->where('id', $id)->update(['status' => $st, 'updated_at' => now()]);
    }

    public static function list(CountryScope $scope, string $from, string $to, string $status = ''): array
    {
        if (!self::on()) return ['ok' => true, 'rows' => [], 'total' => [], 'installed' => false];
        $ids = $scope->ids();
        self::sync($ids);
        $q = DB::table('production_batches')->where('created_at', '>=', $from . ' 00:00:00')->where('created_at', '<=', $to . ' 23:59:59')->orderByDesc('id')->limit(500);
        if (in_array($status, ['sent', 'partly', 'received', 'cancelled'], true)) $q->where('status', $status);
        $rows = [];
        $batches = $q->get();
        $lineAgg = [];
        if ($batches->isNotEmpty()) {
            foreach (DB::table('production_batch_lines')->whereIn('batch_id', $batches->pluck('id')->all())->get() as $l) {
                $a = $lineAgg[$l->batch_id] ?? ['in_scope' => 0, 'sent' => 0, 'received' => 0, 'cancelled' => 0, 'short' => 0.0, 'got' => 0.0, 'hubs' => []];
                if (in_array((int) $l->hub_id, $ids, true)) $a['in_scope']++;
                $a[$l->status] = ($a[$l->status] ?? 0) + 1;
                if ($l->status === 'received' && $l->received_qty !== null) { $a['got'] += (float) $l->received_qty; if ((float) $l->received_qty < (float) $l->qty) $a['short'] += (float) $l->qty - (float) $l->received_qty; }
                $a['hubs'][(int) $l->hub_id] = true;
                $lineAgg[$l->batch_id] = $a;
            }
        }
        $hubs = CountryScope::hubs();
        foreach ($batches as $b) {
            $a = $lineAgg[$b->id] ?? null;
            if (!$a || !$a['in_scope']) continue;
            $rows[] = ['id' => (int) $b->id, 'no' => (string) $b->batch_no, 'plan_date' => (string) $b->plan_date, 'status' => (string) $b->status, 'lines' => (int) $b->lines, 'hubs' => (int) $b->hubs,
                'hub_names' => implode(', ', array_slice(array_map(fn ($h) => $hubs[$h]['name'] ?? ('Hub ' . $h), array_keys($a['hubs'])), 0, 4)) . (count($a['hubs']) > 4 ? ' +' . (count($a['hubs']) - 4) : ''),
                'packs' => (float) $b->packs, 'value' => (float) $b->value, 'waiting' => (int) $a['sent'], 'received' => (int) $a['received'], 'cancelled' => (int) $a['cancelled'], 'short' => round($a['short'], 3),
                'vehicle' => (string) ($b->vehicle ?? ''), 'note' => (string) ($b->note ?? ''), 'by' => (string) ($b->by_name ?? ''), 'at' => CountryScope::ts($b->created_at)];
        }
        $t = ['batches' => count($rows), 'packs' => array_sum(array_column($rows, 'packs')), 'value' => round(array_sum(array_column($rows, 'value')), 2), 'waiting' => array_sum(array_column($rows, 'waiting')), 'short' => round(array_sum(array_column($rows, 'short')), 3)];
        return ['ok' => true, 'installed' => true, 'rows' => $rows, 'total' => $t];
    }

    public static function batch(int $id): array
    {
        if (!self::on()) return ['ok' => false, 'message' => 'The production tables are not installed yet.'];
        self::sync();
        $b = DB::table('production_batches')->where('id', $id)->first();
        if (!$b) return ['ok' => false, 'message' => 'Dispatch not found.'];
        $inScope = DB::table('production_batch_lines')->where('batch_id', $id)->whereIn('hub_id', array_keys(CountryScope::hubs()) ?: [0])->exists();
        if (!$inScope) return ['ok' => false, 'message' => 'Dispatch not found.'];
        $hubs = CountryScope::hubs(); $prods = CountryStock::products();
        $lines = [];
        foreach (DB::table('production_batch_lines')->where('batch_id', $id)->orderBy('hub_id')->orderBy('product_code')->get() as $l) {
            if (CountryScope::$allowed !== null && !isset($hubs[(int) $l->hub_id])) continue;
            $p = $prods[(string) $l->product_code] ?? ['name' => $l->product_code, 'pack' => '', 'kind' => 'count', 'per' => 1, 'uom' => 'pcs'];
            $lines[] = ['id' => (int) $l->id, 'hub_id' => (int) $l->hub_id, 'hub' => $hubs[(int) $l->hub_id]['name'] ?? ('Hub ' . $l->hub_id), 'city' => $hubs[(int) $l->hub_id]['city'] ?? '',
                'code' => (string) $l->product_code, 'name' => $p['name'], 'pack' => $p['pack'], 'kind' => $p['kind'], 'per' => $p['per'], 'uom' => $p['uom'],
                'qty' => (float) $l->qty, 'price' => (float) $l->price, 'value' => round((float) $l->qty * (float) $l->price, 2), 'pending_before' => (float) $l->pending_before,
                'status' => (string) $l->status, 'received' => $l->received_qty !== null ? (float) $l->received_qty : null, 'received_at' => CountryScope::ts($l->received_at),
                'cancel_note' => (string) ($l->cancel_note ?? ''), 'amount_text' => CountryUnits::text((float) $l->qty, $p)];
        }
        return ['ok' => true, 'batch' => ['id' => (int) $b->id, 'no' => (string) $b->batch_no, 'plan_date' => (string) $b->plan_date, 'status' => (string) $b->status, 'packs' => (float) $b->packs, 'value' => (float) $b->value,
            'vehicle' => (string) ($b->vehicle ?? ''), 'note' => (string) ($b->note ?? ''), 'by' => (string) ($b->by_name ?? ''), 'at' => CountryScope::ts($b->created_at)], 'lines' => $lines];
    }

    public static function cancelLine(int $lineId, string $note, array $actor): array
    {
        if (!self::on()) return ['ok' => false, 'message' => 'The production tables are not installed yet.'];
        $note = mb_substr(trim($note), 0, 250);
        if ($note === '') return ['ok' => false, 'message' => 'Write why this line is cancelled.'];
        self::sync();
        $res = DB::transaction(function () use ($lineId, $note) {
            $l = DB::table('production_batch_lines')->where('id', $lineId)->lockForUpdate()->first();
            if (!$l) return ['ok' => false, 'message' => 'Line not found.'];
            if (!isset(CountryScope::hubs()[(int) $l->hub_id])) return ['ok' => false, 'message' => 'This hub is not in your list.'];
            if ($l->status !== 'sent') return ['ok' => false, 'message' => $l->status === 'received' ? 'The hub has already received this.' : 'This line is already cancelled.'];
            $inv = DB::table('inventory')->where('hub_id', $l->hub_id)->where('product_id', $l->product_code)->lockForUpdate()->first();
            // the hub may have received it a moment ago
            if (CountryScope::has('hub_stock_moves') && DB::table('hub_stock_moves')->where('hub_id', $l->hub_id)->where('product_code', $l->product_code)->where('type', 'inward')->where('id', '>', (int) $l->moves_before)->exists())
                return ['ok' => false, 'message' => 'The hub has just received this product. Refresh the dispatch to see what arrived.'];
            $pending = $inv ? (float) $inv->stock : 0.0;
            if ($pending < (float) $l->qty) return ['ok' => false, 'message' => 'Only ' . CountryScope::q($pending) . ' packs are still waiting at the hub, so this line cannot be taken back. Ask the hub to receive what arrived.'];
            $upd = ['stock' => DB::raw('stock - ' . (int) $l->qty)];
            if (CountryScope::has('inventory', 'updated_at')) $upd['updated_at'] = now();
            DB::table('inventory')->where('id', $inv->id)->update($upd);
            DB::table('production_batch_lines')->where('id', $l->id)->update(['status' => 'cancelled', 'cancel_note' => $note, 'cancelled_at' => now(), 'updated_at' => now()]);
            return ['ok' => true, 'batch_id' => (int) $l->batch_id, 'qty' => (float) $l->qty, 'code' => (string) $l->product_code, 'hub' => (int) $l->hub_id];
        }, 3);
        if (!$res['ok']) return $res;
        self::batchStatus($res['batch_id']);
        $b = DB::table('production_batches')->where('id', $res['batch_id'])->value('batch_no');
        ProductionLog::write($actor, 'cancel', 'production_batch_lines', $lineId, 'Cancelled ' . CountryScope::q($res['qty']) . ' packs of ' . $res['code'] . ' to hub ' . $res['hub'] . ' in ' . $b . ': ' . $note);
        return ['ok' => true, 'message' => 'Line cancelled. ' . CountryScope::q($res['qty']) . ' packs are no longer expected at the hub.'];
    }

    /**
     * Receiving accuracy: what hubs received against what was sent (from the hubs' receipts), per hub and product.
     * Covers every receipt in the dates, whether it came from a production dispatch or older pending stock.
     */
    public static function receipts(CountryScope $scope, string $from, string $to): array
    {
        $ids = $scope->ids(); $hubs = CountryScope::hubs(); $prods = CountryStock::products();
        if (!$ids || !self::h('hub_stock_moves')) return ['ok' => true, 'rows' => [], 'by_hub' => [], 'total' => ['receipts' => 0]];
        $rows = []; $byHub = [];
        $moves = DB::table('hub_stock_moves')->whereIn('hub_id', $ids)->where('type', 'inward')->where('created_at', '>=', $from . ' 00:00:00')->where('created_at', '<=', $to . ' 23:59:59')->orderByDesc('created_at')->limit(3000)->get();
        foreach ($moves as $m) {
            $code = CountryStock::canon((string) $m->product_code); $p = $prods[$code] ?? null;
            $exp = $m->expected_qty !== null ? (float) $m->expected_qty : null; $got = (float) $m->change_qty;
            $diff = $exp !== null ? round($got - $exp, 3) : 0.0; $price = (float) ($p['price'] ?? 0);
            $rows[] = ['at' => CountryScope::ts($m->created_at), 'hub_id' => (int) $m->hub_id, 'hub' => $hubs[(int) $m->hub_id]['name'] ?? '', 'code' => $code, 'name' => $p['name'] ?? $code, 'pack' => $p['pack'] ?? '',
                'kind' => $p['kind'] ?? 'count', 'per' => $p['per'] ?? 1, 'uom' => $p['uom'] ?? 'pcs', 'sent' => $exp, 'received' => $got, 'diff' => $diff, 'diff_value' => round($diff * $price, 2), 'note' => (string) ($m->note ?? ''), 'by' => (string) ($m->by_name ?? '')];
            $b = $byHub[(int) $m->hub_id] ?? ['hub_id' => (int) $m->hub_id, 'hub' => $hubs[(int) $m->hub_id]['name'] ?? '', 'city' => $hubs[(int) $m->hub_id]['city'] ?? '', 'receipts' => 0, 'exact' => 0, 'short' => 0, 'over' => 0, 'short_value' => 0.0, 'over_value' => 0.0];
            $b['receipts']++;
            if (abs($diff) < 0.0005) $b['exact']++; elseif ($diff < 0) { $b['short']++; $b['short_value'] += -$diff * $price; } else { $b['over']++; $b['over_value'] += $diff * $price; }
            $byHub[(int) $m->hub_id] = $b;
        }
        foreach ($byHub as &$b) { $b['accuracy'] = $b['receipts'] ? round($b['exact'] * 100 / $b['receipts'], 1) : null; $b['short_value'] = round($b['short_value'], 2); $b['over_value'] = round($b['over_value'], 2); }
        unset($b);
        $bh = array_values($byHub); usort($bh, fn ($a, $c) => [$c['short_value'], $c['receipts']] <=> [$a['short_value'], $a['receipts']]);
        $n = count($rows); $exact = array_sum(array_column($bh, 'exact'));
        return ['ok' => true, 'rows' => $rows, 'by_hub' => $bh, 'total' => ['receipts' => $n, 'accuracy' => $n ? round($exact * 100 / $n, 1) : null, 'short_value' => round(array_sum(array_column($bh, 'short_value')), 2), 'over_value' => round(array_sum(array_column($bh, 'over_value')), 2)]];
    }

    /* ------------------------------------------------------------------ old waiting stock */

    /** Waiting stock at hubs that no open production dispatch explains (old panels, 2023 test data), per hub; plus recent clears. */
    public static function stale(CountryScope $scope): array
    {
        $ids = $scope->ids(); $hubs = CountryScope::hubs(); $prods = CountryStock::products();
        $days = max(1, (int) config('production_console.forecast.pending_max_days', 2));
        $byHub = []; $lines = 0; $packs = 0.0; $value = 0.0;
        foreach (ProductionPlan::stock($ids) as $h => $list) {
            foreach ($list as $code => $st) {
                if ($st['untracked'] <= 0) continue;
                $v = $st['untracked'] * ($prods[$code]['price'] ?? 0);
                $b = $byHub[$h] ?? ['hub_id' => $h, 'hub' => $hubs[$h]['name'] ?? '', 'city' => $hubs[$h]['city'] ?? '', 'lines' => 0, 'packs' => 0.0, 'value' => 0.0, 'oldest' => null, 'unknown' => 0];
                $b['lines']++; $b['packs'] += $st['untracked']; $b['value'] = round($b['value'] + $v, 2);
                if ($st['since'] && ($b['oldest'] === null || $st['since'] < $b['oldest'])) $b['oldest'] = $st['since'];
                if (!isset($prods[$code])) $b['unknown']++;
                $byHub[$h] = $b; $lines++; $packs += $st['untracked']; $value += $v;
            }
        }
        $bh = array_values($byHub); usort($bh, fn ($a, $b) => $b['value'] <=> $a['value']);
        $cleared = [];
        if (self::h('production_pending_clears') && $ids) {
            foreach (DB::table('production_pending_clears')->whereIn('hub_id', $ids)->where('created_at', '>=', date('Y-m-d', strtotime('-60 days')))->orderByDesc('id')->limit(500)->get() as $c) {
                $p = $prods[(string) $c->product_code] ?? null;
                $cleared[] = ['id' => (int) $c->id, 'hub' => $hubs[(int) $c->hub_id]['name'] ?? '', 'code' => (string) $c->product_code, 'name' => $p['name'] ?? (string) $c->product_code, 'qty' => (float) $c->qty,
                    'since' => CountryScope::ts($c->waiting_since), 'note' => (string) $c->note, 'by' => (string) $c->by_name, 'at' => CountryScope::ts($c->created_at), 'restored' => CountryScope::ts($c->restored_at), 'restored_by' => (string) ($c->restored_by ?? '')];
            }
        }
        return ['ok' => true, 'days' => $days, 'hubs' => $bh, 'total' => ['lines' => $lines, 'packs' => round($packs, 3), 'value' => round($value, 2)], 'cleared' => $cleared, 'installed' => self::h('production_pending_clears')];
    }

    /** Clear old waiting stock in the chosen hubs (all in scope when $hubIds is empty). Each line is kept so it can be put back. */
    public static function clearStale(CountryScope $scope, array $hubIds, string $note, array $actor): array
    {
        if (!self::h('production_pending_clears')) return ['ok' => false, 'message' => 'The production tables are not installed yet.'];
        $note = mb_substr(trim($note), 0, 250);
        if (mb_strlen($note) < 4) return ['ok' => false, 'message' => 'Write why this old stock is being cleared.'];
        $ids = $scope->ids();
        $pick = array_values(array_intersect($ids, array_map('intval', $hubIds)));
        if ($hubIds && !$pick) return ['ok' => false, 'message' => 'Choose hubs from your list.'];
        $target = $pick ?: $ids;
        $stale = [];
        foreach (ProductionPlan::stock($target) as $h => $list) foreach ($list as $code => $st) if ($st['untracked'] > 0 && $st['inv_id']) $stale[$st['inv_id']] = true;
        ksort($stale);
        $res = DB::transaction(function () use ($stale, $note, $actor) {
            $n = 0; $packs = 0.0; $now = now();
            foreach (array_keys($stale) as $invId) {
                $r = DB::table('inventory')->where('id', $invId)->lockForUpdate()->first(['id', 'hub_id', 'product_id', 'stock', 'updated_at']);
                if (!$r) continue;
                // recomputed under the lock: only what no open production dispatch line explains is cleared
                $openQ = (float) DB::table('production_batch_lines')->where('hub_id', $r->hub_id)->where('product_code', $r->product_id)->where('status', 'sent')->sum('qty');
                $q = (int) floor(max(0, (float) $r->stock - $openQ));
                if ($q <= 0) continue;
                DB::table('production_pending_clears')->insert(['inventory_id' => $r->id, 'hub_id' => $r->hub_id, 'product_code' => mb_substr((string) $r->product_id, 0, 40), 'qty' => $q,
                    'waiting_since' => $r->updated_at, 'note' => $note, 'by_id' => $actor['id'] ?? null, 'by_name' => mb_substr((string) ($actor['name'] ?? ''), 0, 120), 'created_at' => $now, 'updated_at' => $now]);
                // only the old part is taken off; what production sent recently stays waiting. The row's date is kept.
                DB::table('inventory')->where('id', $r->id)->update(['stock' => DB::raw('stock - ' . $q), 'updated_at' => $r->updated_at]);
                $n++; $packs += $q;
            }
            return ['n' => $n, 'packs' => $packs];
        }, 3);
        if (!$res['n']) return ['ok' => false, 'message' => 'There is no old waiting stock in those hubs.'];
        ProductionLog::write($actor, 'clear', 'inventory', count($target) . ' hubs', 'Cleared old waiting stock: ' . $res['n'] . ' lines, ' . CountryScope::q($res['packs']) . ' packs. Reason: ' . $note);
        ProductionPlan::forget();
        return ['ok' => true, 'message' => 'Cleared ' . $res['n'] . ' old lines (' . CountryScope::q($res['packs']) . ' packs). Each one can be put back from the list below.'];
    }

    /** Put one cleared line back as waiting at the hub. */
    public static function restoreClear(int $id, array $actor): array
    {
        if (!self::h('production_pending_clears')) return ['ok' => false, 'message' => 'The production tables are not installed yet.'];
        $res = DB::transaction(function () use ($id, $actor) {
            $c = DB::table('production_pending_clears')->where('id', $id)->lockForUpdate()->first();
            if (!$c) return ['ok' => false, 'message' => 'Not found.'];
            if (!isset(CountryScope::hubs()[(int) $c->hub_id])) return ['ok' => false, 'message' => 'This hub is not in your list.'];
            if ($c->restored_at) return ['ok' => false, 'message' => 'This was already put back.'];
            $inv = DB::table('inventory')->where('id', $c->inventory_id)->lockForUpdate()->first();
            if (!$inv) return ['ok' => false, 'message' => 'The stock line no longer exists.'];
            DB::table('inventory')->where('id', $inv->id)->update(['stock' => DB::raw('stock + ' . (int) round((float) $c->qty)), 'updated_at' => $inv->updated_at]);
            DB::table('production_pending_clears')->where('id', $id)->update(['restored_at' => now(), 'restored_by' => mb_substr((string) ($actor['name'] ?? ''), 0, 120), 'updated_at' => now()]);
            return ['ok' => true, 'qty' => (float) $c->qty, 'code' => (string) $c->product_code, 'hub' => (int) $c->hub_id];
        });
        if (!$res['ok']) return $res;
        ProductionLog::write($actor, 'restore', 'inventory', $id, 'Put back ' . CountryScope::q($res['qty']) . ' waiting packs of ' . $res['code'] . ' at hub ' . $res['hub']);
        return ['ok' => true, 'message' => 'Put back: ' . CountryScope::q($res['qty']) . ' packs are waiting at the hub again.'];
    }
}
