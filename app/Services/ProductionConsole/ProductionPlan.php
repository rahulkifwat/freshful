<?php

namespace App\Services\ProductionConsole;

use App\Services\CountryConsole\CountryScope;
use App\Services\CountryConsole\CountryStock;
use App\Services\CountryConsole\CountryUnits;
use App\Services\HubConsole\HubStock;
use App\Services\PosConsole\OrderFlow;
use Illuminate\Support\Facades\DB;

/**
 * Freshful production console (production update 1, 28 Oct 2026): what to produce for each hub.
 *
 * Forecast for one hub, product and delivery date D (packs):
 *   daily  = (1 - w) x average daily sales of the last N full days + w x average of the same weekday in the last 4 weeks
 *            (sales = orders not cancelled, counted on their delivery day: scheduled orders on the schedule date,
 *             express orders on the day they were placed; w = PRODUCTION_WEEKDAY_WEIGHT, N = PRODUCTION_HISTORY_DAYS)
 *   booked = scheduled orders already booked for D (not cancelled)
 *   demand = the larger of daily and booked. For today: only what is still to come (daily x the hub's share of a normal
 *            day's orders after now, at least the new orders not yet accepted).
 *   usable stock (owner's rule: sell within 2 days):
 *            today    = fresh + danger + stock sent and not yet received (if sent in the last 2 days);
 *            Stock dispatched FOR the plan date (waiting or received) is that date's plan, never extra usable stock; stock sent for a
 *            later date is kept out too. Waiting stock is dated only from production's dispatch lines (see readStock).
 *            tomorrow = what will still be fresh tomorrow: today's fresh + on the way, minus what today's remaining
 *                       demand takes after the danger stock is used up (oldest first);
 *            later    = 0 (everything in the hubs now will have expired).
 *   suggested = whole packs of (demand + safety% of daily - usable), never below 0. A need from the forecast alone is made only
 *            once it reaches PRODUCTION_MIN_PACK_SHARE of a pack (default 0.35); booked orders are always covered in full.
 * The production manager can change every number; the plan is saved per date, hub and product.
 */
class ProductionPlan
{
    private static function h(string $t, ?string $c = null): bool { return CountryScope::has($t, $c); }

    public static function dates(): array
    {
        $out = []; $n = max(1, (int) config('production_console.forecast.days_ahead', 3));
        for ($i = 0; $i <= $n; $i++) {
            $d = date('Y-m-d', strtotime('+' . $i . ' days'));
            $out[] = ['date' => $d, 'label' => $i === 0 ? 'Today' : ($i === 1 ? 'Tomorrow' : date('D d M', strtotime($d)))];
        }
        return $out;
    }

    public static function validDate(?string $d): string
    {
        $ok = array_column(self::dates(), 'date');
        return in_array($d, $ok, true) ? $d : ($ok[1] ?? $ok[0]);
    }

    /** Sales history per hub and code: [hub][code][Y-m-d] = packs (delivery day). Cancelled orders left out. */
    private static array $histMemo = [];
    private static array $bookMemo = [];

    public static function history(array $ids, int $days): array
    {
        if (!$ids) return [];
        $mk = $days . '|' . implode(',', $ids);
        if (isset(self::$histMemo[$mk])) return self::$histMemo[$mk];
        return self::$histMemo[$mk] = self::readHistory($ids, $days);
    }

    private static function readHistory(array $ids, int $days): array
    {
        $from = date('Y-m-d 00:00:00', strtotime('-' . ($days + 10) . ' days'));
        $out = [];
        foreach (array_chunk($ids, 400) as $chunk) {
            $rows = DB::table('orders AS o')->join('products AS p', 'p.id', '=', 'o.product_id')
                ->whereIn('o.hub_id', $chunk)->where('o.date_added', '>=', $from)
                ->whereNotIn('o.order_status', OrderFlow::statusesFor(['cancelled']))
                ->groupBy('o.hub_id', 'p.product_id', 'o.delivery_type', 'o.schedule_date', DB::raw('DATE(o.date_added)'))
                ->select('o.hub_id', 'p.product_id AS code', 'o.delivery_type', 'o.schedule_date', DB::raw('DATE(o.date_added) AS d'), DB::raw('SUM(o.quantity) AS q'))->get();
            foreach ($rows as $r) {
                $sched = strtolower((string) $r->delivery_type) === 'scheduled';
                $day = $sched ? (OrderFlow::normDate($r->schedule_date) ?? (string) $r->d) : (string) $r->d;
                $code = CountryStock::canon((string) $r->code);
                $out[(int) $r->hub_id][$code][$day] = ($out[(int) $r->hub_id][$code][$day] ?? 0) + (float) $r->q;
            }
        }
        return $out;
    }

    /** Scheduled orders booked for a date, and (for today) new orders not yet accepted: [hub][code] => [booked, open] */
    public static function booked(array $ids, string $date): array
    {
        if (!$ids) return [];
        $mk = $date . '|' . implode(',', $ids);
        if (isset(self::$bookMemo[$mk])) return self::$bookMemo[$mk];
        return self::$bookMemo[$mk] = self::readBooked($ids, $date);
    }

    private static function readBooked(array $ids, string $date): array
    {
        $today = date('Y-m-d'); $out = [];
        $newSt = OrderFlow::statusesFor(['new']);
        foreach (array_chunk($ids, 400) as $chunk) {
            $rows = DB::table('orders AS o')->join('products AS p', 'p.id', '=', 'o.product_id')
                ->whereIn('o.hub_id', $chunk)->where('o.date_added', '>=', date('Y-m-d 00:00:00', strtotime('-20 days')))
                ->whereNotIn('o.order_status', OrderFlow::statusesFor(['cancelled']))
                ->select('o.hub_id', 'p.product_id AS code', 'o.delivery_type', 'o.schedule_date', 'o.order_status', 'o.quantity', 'o.date_added')->get();
            foreach ($rows as $r) {
                $sched = strtolower((string) $r->delivery_type) === 'scheduled';
                $day = $sched ? OrderFlow::normDate($r->schedule_date) : substr((string) $r->date_added, 0, 10);
                if ($day !== $date) continue;
                $code = CountryStock::canon((string) $r->code); $h = (int) $r->hub_id;
                $cell = $out[$h][$code] ?? ['booked' => 0.0, 'open' => 0.0];
                if ($sched) $cell['booked'] += (float) $r->quantity;
                if ($date === $today && in_array((string) $r->order_status, $newSt, true)) $cell['open'] += (float) $r->quantity;
                $out[$h][$code] = $cell;
            }
        }
        return $out;
    }

    /** Share of a normal day's orders still to come after now (0..1), per hub (same rule as the hub danger plan). */
    public static function restOfDay(array $ids): array
    {
        $mins = (int) date('G') * 60 + (int) date('i');
        $even = round(max(0, min(1, (21 * 60 - $mins) / (14 * 60))), 3);
        $out = array_fill_keys($ids, $even);
        if (!$ids) return $out;
        $rows = DB::table('orders')->whereIn('hub_id', $ids)->where('date_added', '>=', date('Y-m-d 00:00:00', strtotime('-14 days')))->where('date_added', '<', date('Y-m-d 00:00:00'))
            ->groupBy('hub_id', 'order_id')->select('hub_id', DB::raw('MAX(date_added) AS at'))->get();
        $by = [];
        foreach ($rows as $r) $by[(int) $r->hub_id][] = (string) $r->at;
        foreach ($by as $h => $list) {
            if (count($list) < 10) continue;
            $after = 0;
            foreach ($list as $at) { $t = strtotime($at); if ($t && ((int) date('G', $t) * 60 + (int) date('i', $t)) >= $mins) $after++; }
            $out[$h] = round($after / count($list), 3);
        }
        return $out;
    }

    /** Stock picture per hub and code: live, fresh, danger, expired, pending (recent / stale). */
    private static array $stockMemo = [];

    public static function stock(array $ids): array
    {
        if (!$ids) return [];
        $mk = implode(',', $ids);
        if (isset(self::$stockMemo[$mk])) return self::$stockMemo[$mk];
        return self::$stockMemo[$mk] = self::readStock($ids);
    }

    public static function forget(): void { self::$stockMemo = []; self::$histMemo = []; self::$bookMemo = []; }

    private static function readStock(array $ids): array
    {
        CountryStock::reconcile($ids);
        $out = [];
        $maxAge = max(1, (int) config('production_console.forecast.pending_max_days', 2));
        $staleBefore = time() - $maxAge * 86400;
        $hasUpd = self::h('inventory', 'updated_at');
        // Waiting stock that production sent is known exactly from its dispatch lines; only the rest is judged by the row's date.
        // Waiting stock is judged only from production's own dispatch lines (inventory.updated_at changes on every sale, so it cannot date it):
        //   recent  = open lines sent in the last PRODUCTION_PENDING_MAX_DAYS days -> counted as coming;
        //   overdue = open lines older than that (not received) -> not counted; cancel them in Dispatches;
        //   untracked = waiting stock no open line explains (old panels, 2023 test data) -> not counted; can be cleared.
        $open = []; $recent = [];
        if (self::h('production_batch_lines')) {
            foreach (array_chunk($ids, 400) as $chunk) {
                foreach (DB::table('production_batch_lines')->whereIn('hub_id', $chunk)->where('status', 'sent')
                    ->groupBy('hub_id', 'product_code')->select('hub_id', 'product_code', DB::raw('SUM(qty) AS q'),
                        DB::raw("SUM(CASE WHEN created_at >= '" . date('Y-m-d H:i:s', $staleBefore) . "' THEN qty ELSE 0 END) AS r"), DB::raw('MIN(created_at) AS o'))->get() as $t) {
                    $c = CountryStock::canon((string) $t->product_code);
                    $open[(int) $t->hub_id][$c] = (float) $t->q; $recent[(int) $t->hub_id][$c] = (float) $t->r;
                }
            }
        }
        foreach (array_chunk($ids, 400) as $chunk) {
            foreach (DB::table('inventory')->whereIn('hub_id', $chunk)->get() as $r) {
                $code = CountryStock::canon((string) $r->product_id); $h = (int) $r->hub_id;
                $pending = max(0, (float) $r->stock);
                $op = min($pending, $open[$h][$code] ?? 0.0);
                $ok = min($op, $recent[$h][$code] ?? 0.0);
                $out[$h][$code] = ['inv_id' => (int) $r->id, 'live' => max(0, (float) $r->live_stock), 'fresh' => max(0, (float) $r->fresh_stock), 'danger' => 0.0, 'expired' => 0.0,
                    'pending' => $pending, 'pending_ok' => $ok, 'stale' => round($pending - $ok, 3), 'overdue' => round($op - $ok, 3), 'untracked' => round($pending - $op, 3),
                    'since' => $hasUpd ? CountryScope::ts($r->updated_at ?? null) : null];
            }
        }
        if (HubStock::lotsOn()) {
            $today = date('Y-m-d'); $yest = date('Y-m-d', strtotime('-1 day'));
            foreach (array_chunk($ids, 400) as $chunk) {
                $rows = DB::table('hub_stock_lots')->whereIn('hub_id', $chunk)->where('qty_left', '>', 0)->groupBy('hub_id', 'product_code')
                    ->select('hub_id', 'product_code',
                        DB::raw("SUM(CASE WHEN received_on >= '$today' THEN qty_left ELSE 0 END) AS f"),
                        DB::raw("SUM(CASE WHEN received_on < '$today' AND received_on >= '$yest' THEN qty_left ELSE 0 END) AS d"),
                        DB::raw("SUM(CASE WHEN received_on < '$yest' THEN qty_left ELSE 0 END) AS e"))->get();
                foreach ($rows as $r) {
                    $h = (int) $r->hub_id; $code = CountryStock::canon((string) $r->product_code);
                    $cell = $out[$h][$code] ?? ['inv_id' => 0, 'live' => 0.0, 'fresh' => 0.0, 'danger' => 0.0, 'expired' => 0.0, 'pending' => 0.0, 'pending_ok' => 0.0, 'stale' => 0.0, 'overdue' => 0.0, 'untracked' => 0.0, 'since' => null];
                    $cell['fresh'] = round((float) $r->f, 3); $cell['danger'] = round((float) $r->d, 3); $cell['expired'] = round((float) $r->e, 3);
                    $out[$h][$code] = $cell;
                }
            }
        } else {
            // No batches: live minus today's fresh counts as danger (the old panel's rule).
            foreach ($out as $h => $list) foreach ($list as $c => $cell) $out[$h][$c]['danger'] = max(0, $cell['live'] - $cell['fresh']);
        }
        return $out;
    }

    /** Packs already sent for a plan date (not cancelled): [hub][code] => qty */
    public static function sentFor(string $date, array $ids): array
    {
        if (!self::h('production_batch_lines') || !$ids) return [];
        $out = [];
        $rows = DB::table('production_batch_lines AS l')->join('production_batches AS b', 'b.id', '=', 'l.batch_id')
            ->where('b.plan_date', $date)->where('l.status', '!=', 'cancelled')->whereIn('l.hub_id', $ids)
            ->groupBy('l.hub_id', 'l.product_code')->select('l.hub_id', 'l.product_code', DB::raw('SUM(l.qty) AS q'))->get();
        foreach ($rows as $r) $out[(int) $r->hub_id][CountryStock::canon((string) $r->product_code)] = (float) $r->q;
        return $out;
    }

    /** Packs sent in the last few days for a plan date AFTER $date (not cancelled): [hub][code] => qty */
    public static function sentAfter(string $date, array $ids): array
    {
        if (!self::h('production_batch_lines') || !$ids) return [];
        $out = [];
        $rows = DB::table('production_batch_lines AS l')->join('production_batches AS b', 'b.id', '=', 'l.batch_id')
            ->where('b.plan_date', '>', $date)->where('l.status', '!=', 'cancelled')->whereIn('l.hub_id', $ids)
            ->where('l.created_at', '>=', date('Y-m-d 00:00:00', strtotime('-3 days')))
            ->groupBy('l.hub_id', 'l.product_code')->select('l.hub_id', 'l.product_code', DB::raw('SUM(l.qty) AS q'))->get();
        foreach ($rows as $r) $out[(int) $r->hub_id][CountryStock::canon((string) $r->product_code)] = (float) $r->q;
        return $out;
    }

    /** Saved plan for a date: [hub][code] => [planned, note, by, at] */
    public static function saved(string $date, array $ids): array
    {
        if (!self::h('production_plan_lines') || !$ids) return [];
        $out = [];
        foreach (DB::table('production_plan_lines')->where('plan_date', $date)->whereIn('hub_id', $ids)->get() as $r) {
            $out[(int) $r->hub_id][CountryStock::canon((string) $r->product_code)] = ['planned' => (float) $r->planned_qty, 'note' => (string) ($r->note ?? ''), 'by' => (string) ($r->by_name ?? ''), 'at' => CountryScope::ts($r->updated_at)];
        }
        return $out;
    }

    /**
     * The plan screen: one row per product with every hub's forecast, usable stock, suggestion, plan and what was sent.
     * $category / $q filter products; $onlyNeed hides products with nothing to make.
     */
    public static function build(CountryScope $scope, string $date, string $q = '', string $category = '', bool $onlyNeed = false): array
    {
        $date = self::validDate($date);
        $today = date('Y-m-d'); $tomorrow = date('Y-m-d', strtotime('+1 day'));
        $hubs = CountryScope::hubs();
        $ids = array_values(array_filter($scope->ids(), fn ($h) => $hubs[$h]['active'] ?? false));
        $cfg = (array) config('production_console.forecast', []);
        $days = max(3, min(60, (int) ($cfg['history_days'] ?? 14)));
        $w = max(0.0, min(1.0, (float) ($cfg['weekday_weight'] ?? 0.4)));
        $safety = max(0.0, min(100.0, (float) ($cfg['safety_pct'] ?? 10))) / 100;
        $minShare = max(0.05, min(1.0, (float) ($cfg['min_pack_share'] ?? 0.35)));

        $prods = CountryStock::products();
        $hist = self::history($ids, max($days, 28));
        $book = self::booked($ids, $date);
        $bookToday = $date === $today ? $book : self::booked($ids, $today);
        $stock = self::stock($ids);
        $rest = self::restOfDay($ids);
        $sent = self::sentFor($date, $ids);
        $sentLater = self::sentAfter($date, $ids);
        $plan = self::saved($date, $ids);

        // the history days (full days before today) and the same weekdays as the plan date
        $histDays = []; for ($i = 1; $i <= $days; $i++) $histDays[] = date('Y-m-d', strtotime('-' . $i . ' days'));
        $wd = (int) date('N', strtotime($date)); $sameDays = [];
        for ($i = 1; $i <= 35 && count($sameDays) < 4; $i++) { $d = date('Y-m-d', strtotime('-' . $i . ' days')); if ((int) date('N', strtotime($d)) === $wd) $sameDays[] = $d; }

        $daily = function (int $h, string $code) use ($hist, $histDays, $sameDays, $w) {
            $m = $hist[$h][$code] ?? null; if (!$m) return 0.0;
            $a = 0.0; foreach ($histDays as $d) $a += $m[$d] ?? 0; $a /= max(1, count($histDays));
            $b = 0.0; foreach ($sameDays as $d) $b += $m[$d] ?? 0; $b /= max(1, count($sameDays));
            return round((1 - $w) * $a + $w * $b, 3);
        };

        $qq = mb_strtolower(trim($q)); $cat = trim($category);
        $rows = []; $tot = ['demand' => 0.0, 'suggested' => 0.0, 'planned' => 0.0, 'sent' => 0.0, 'to_send' => 0.0, 'usable' => 0.0, 'value' => 0.0, 'products' => 0, 'stale' => 0.0];
        $cats = [];
        foreach ($prods as $code => $p) {
            if (!$p['active']) {
                // switched-off products are planned only if something is already planned or sent for them
                $any = false; foreach ($ids as $h) if (!empty($plan[$h][$code]) || !empty($sent[$h][$code])) { $any = true; break; }
                if (!$any) continue;
            }
            $cats[$p['category']] = true;
            if ($cat !== '' && strcasecmp($p['category'], $cat) !== 0) continue;
            if ($qq !== '' && !str_contains(mb_strtolower($p['name'] . ' ' . $code), $qq)) continue;
            $row = ['id' => $p['id'], 'code' => $code, 'name' => $p['name'], 'category' => $p['category'], 'pack' => $p['pack'], 'price' => $p['price'], 'active' => $p['active'],
                'kind' => $p['kind'], 'per' => $p['per'], 'uom' => $p['uom'], 'suspect' => $p['suspect'], 'hubs' => [],
                'daily' => 0.0, 'booked' => 0.0, 'demand' => 0.0, 'usable' => 0.0, 'suggested' => 0.0, 'planned' => 0.0, 'sent' => 0.0, 'to_send' => 0.0, 'stale' => 0.0, 'expired' => 0.0];
            foreach ($ids as $h) {
                $s = $stock[$h][$code] ?? ['live' => 0.0, 'fresh' => 0.0, 'danger' => 0.0, 'expired' => 0.0, 'pending' => 0.0, 'pending_ok' => 0.0, 'stale' => 0.0, 'overdue' => 0.0, 'untracked' => 0.0];
                $dly = $daily($h, $code);
                $bk = $book[$h][$code]['booked'] ?? 0.0; $open = $book[$h][$code]['open'] ?? 0.0;
                if ($date === $today) {
                    $demand = max($dly * ($rest[$h] ?? 0), $open);
                    $usable = $s['fresh'] + $s['danger'] + $s['pending_ok'];
                } else {
                    $demand = max($dly, $bk);
                    if ($date === $tomorrow) {
                        $todayNeed = max($dly * ($rest[$h] ?? 0), $bookToday[$h][$code]['open'] ?? 0.0);
                        $usable = max(0, $s['fresh'] + $s['pending_ok'] - max(0, $todayNeed - $s['danger']));
                    } else $usable = 0.0;
                }
                // stock dispatched FOR this day (waiting or already received) is this day's plan, not extra stock; stock sent for a later day is kept for that day
                $usable = max(0.0, $usable - ($sent[$h][$code] ?? 0.0) - ($sentLater[$h][$code] ?? 0.0));
                // whole packs: booked / waiting orders are always covered; a forecast-only need is rounded up once it reaches min_pack_share of a pack
                $need = round($demand + $dly * $safety - $usable, 3);
                $must = max(0, (int) ceil(round(max($bk, $open) - $usable, 3)));
                $sug = max($must, $need >= $minShare ? (int) ceil($need) : 0);
                $pl = isset($plan[$h][$code]) ? $plan[$h][$code]['planned'] : (float) $sug;
                $sn = $sent[$h][$code] ?? 0.0;
                $cell = ['daily' => round($dly, 2), 'booked' => $bk, 'open' => $open, 'demand' => round($demand, 2), 'usable' => round($usable, 2), 'live' => $s['live'], 'fresh' => $s['fresh'], 'danger' => $s['danger'],
                    'expired' => $s['expired'], 'pending' => $s['pending'], 'stale' => $s['stale'], 'untracked' => $s['untracked'], 'overdue' => $s['overdue'], 'suggested' => $sug, 'planned' => $pl, 'saved' => isset($plan[$h][$code]), 'sent' => $sn, 'to_send' => max(0, $pl - $sn), 'note' => $plan[$h][$code]['note'] ?? ''];
                if ($dly || $bk || $s['live'] || $s['pending'] || $sug || $pl || $sn) $row['hubs'][(string) $h] = $cell;
                foreach (['daily', 'booked', 'demand', 'usable', 'suggested', 'planned', 'sent', 'to_send', 'stale', 'expired'] as $k) $row[$k] += $cell[$k];
            }
            foreach (['daily', 'booked', 'demand', 'usable'] as $k) $row[$k] = round($row[$k], 2);
            if ($onlyNeed && $row['planned'] <= 0 && $row['suggested'] <= 0 && $row['sent'] <= 0) continue;
            $row['planned_text'] = CountryUnits::text($row['planned'], $p);
            $rows[] = $row;
            foreach (['demand', 'suggested', 'planned', 'sent', 'to_send', 'usable', 'stale'] as $k) $tot[$k] += $row[$k];
            $tot['value'] += $row['planned'] * $p['price'];
            if ($row['planned'] > 0) $tot['products']++;
        }
        usort($rows, fn ($a, $b) => [$b['planned'] > 0, $b['planned'] * max(1, $b['price']), $a['name']] <=> [$a['planned'] > 0, $a['planned'] * max(1, $a['price']), $b['name']]);
        foreach ($tot as $k => $v) if (is_float($v)) $tot[$k] = round($v, 2);
        $cl = array_keys($cats); sort($cl, SORT_NATURAL | SORT_FLAG_CASE);
        return ['ok' => true, 'date' => $date, 'dates' => self::dates(), 'is_today' => $date === $today,
            'hubs' => array_map(fn ($h) => ['id' => $h, 'name' => $hubs[$h]['name'], 'city' => $hubs[$h]['city'], 'rest' => $rest[$h] ?? null], $ids),
            'rows' => $rows, 'total' => $tot, 'categories' => $cl,
            'rule' => ['history_days' => $days, 'weekday_weight' => $w, 'safety_pct' => round($safety * 100, 1), 'pending_max_days' => (int) ($cfg['pending_max_days'] ?? 2), 'min_pack_share' => $minShare, 'same_weekdays' => $sameDays],
            'generated_at' => time()];
    }

    /** Save planned packs. $cells = [[hub_id, code, planned, note?], ...]. Whole packs only. */
    public static function save(string $date, array $cells, array $actor): array
    {
        if (!self::h('production_plan_lines')) return ['ok' => false, 'message' => 'The production tables are not installed yet.'];
        $date = self::validDate($date);
        $cells = array_slice($cells, 0, 3000);
        $hubs = CountryScope::hubs(); $prods = CountryStock::products();
        $max = (float) config('production_console.dispatch.max_packs_per_line', 5000);
        $now = now(); $n = 0; $bad = [];
        DB::transaction(function () use ($cells, $date, $hubs, $prods, $max, $actor, $now, &$n, &$bad) {
            foreach ($cells as $c) {
                $h = (int) ($c['hub_id'] ?? 0); $code = CountryStock::canon((string) ($c['code'] ?? '')); $v = $c['planned'] ?? null;
                if (!isset($hubs[$h]) || !isset($prods[$code])) { $bad[] = 'unknown hub or product'; continue; }
                if (!is_numeric($v) || (float) $v < 0 || (float) $v > $max || floor((float) $v) != (float) $v) { $bad[] = $prods[$code]['name'] . ': whole packs from 0 to ' . (int) $max; continue; }
                $note = mb_substr(trim((string) ($c['note'] ?? '')), 0, 250);
                $exists = DB::table('production_plan_lines')->where('plan_date', $date)->where('hub_id', $h)->where('product_code', $code)->first();
                $vals = ['planned_qty' => (float) $v, 'suggested_qty' => is_numeric($c['suggested'] ?? null) ? max(0.0, min($max, (float) $c['suggested'])) : ($exists->suggested_qty ?? 0), 'note' => $note !== '' ? $note : null,
                    'by_id' => $actor['id'] ?? null, 'by_name' => mb_substr((string) ($actor['name'] ?? ''), 0, 120), 'updated_at' => $now];
                if ($exists) DB::table('production_plan_lines')->where('id', $exists->id)->update($vals);
                else DB::table('production_plan_lines')->insert($vals + ['plan_date' => $date, 'hub_id' => $h, 'product_code' => $code, 'created_at' => $now]);
                $n++;
            }
        });
        if ($n) ProductionLog::write($actor, 'plan', 'production_plan_lines', $date, 'Saved the production plan for ' . $date . ' (' . $n . ' line' . ($n === 1 ? '' : 's') . ')');
        if (!$n) return ['ok' => false, 'message' => $bad ? 'Nothing saved: ' . $bad[0] . '.' : 'Nothing to save.'];
        return ['ok' => true, 'saved' => $n, 'message' => 'Plan saved (' . $n . ' line' . ($n === 1 ? '' : 's') . ').' . ($bad ? ' ' . count($bad) . ' skipped: ' . $bad[0] . '.' : '')];
    }

    /** Production sheet for the floor: total packs and kg / pcs per product for a date (plan, or suggestion where nothing is saved). */
    public static function sheet(CountryScope $scope, string $date): array
    {
        $b = self::build($scope, $date, '', '', true);
        $rows = []; $cat = [];
        foreach ($b['rows'] as $r) {
            if ($r['planned'] <= 0) continue;
            $p = ['kind' => $r['kind'], 'per' => $r['per']];
            $hubs = [];
            foreach ($r['hubs'] as $h => $c) if ($c['planned'] > 0) $hubs[] = ['hub_id' => (int) $h, 'planned' => $c['planned'], 'sent' => $c['sent']];
            $rows[] = ['code' => $r['code'], 'name' => $r['name'], 'category' => $r['category'], 'pack' => $r['pack'], 'kind' => $r['kind'], 'per' => $r['per'], 'uom' => $r['uom'],
                'packs' => $r['planned'], 'amount' => CountryUnits::amount($r['planned'], $p), 'amount_text' => CountryUnits::text($r['planned'], $p), 'sent' => $r['sent'], 'hubs' => $hubs];
            $k = $r['kind'] === 'count' ? 'pcs' : ($r['kind'] === 'volume' ? 'L' : 'kg');
            $cat[$r['category']][$k] = round(($cat[$r['category']][$k] ?? 0) + CountryUnits::amount($r['planned'], $p), 3);
        }
        usort($rows, fn ($a, $b) => [$a['category'], $a['name']] <=> [$b['category'], $b['name']]);
        ksort($cat, SORT_NATURAL | SORT_FLAG_CASE);
        return ['ok' => true, 'date' => $b['date'], 'dates' => $b['dates'], 'hubs' => $b['hubs'], 'rows' => $rows, 'by_category' => $cat, 'total' => $b['total'], 'generated_at' => time()];
    }
}
