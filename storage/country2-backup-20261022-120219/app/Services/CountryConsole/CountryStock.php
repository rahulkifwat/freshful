<?php

namespace App\Services\CountryConsole;

use App\Services\HubConsole\HubConsoleService;
use App\Services\HubConsole\HubDangerService;
use App\Services\HubConsole\HubStock;
use App\Services\PosConsole\OrderFlow;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Freshful country console (22 Oct 2026): stock across all hubs.
 * Every change still goes through the hub stock engine (HubStock::adjust via the hub services), so the hub's own console,
 * its stock ledger and stock age stay exactly right. The country console never writes inventory directly.
 * Stock age (owner's rule): received today = fresh, yesterday = danger (sell today), 2+ days = expired.
 */
class CountryStock
{
    private static function h(string $t, ?string $c = null): bool { return CountryScope::has($t, $c); }

    /** products.product_id (code) => [id, name, pack, price, category, active] for all products (once per request). */
    private static ?array $prods = null;

    public static function products(): array
    {
        if (self::$prods !== null) return self::$prods;
        $q = DB::table('products AS p')->select('p.id', 'p.product_id', 'p.product_name', 'p.unit_quantity', 'p.product_unit', 'p.main_price', 'p.status');
        if (self::h('items')) $q->leftJoin('items AS i', 'i.id', '=', 'p.item_id')->addSelect('i.item');
        $catSel = null;
        if (self::h('category')) {
            $viaItem = self::h('items') && self::h('items', 'cat_id'); $viaProduct = self::h('products', 'category_id');
            if ($viaItem) $q->leftJoin('category AS c', 'c.id', '=', 'i.cat_id');
            if ($viaProduct) $q->leftJoin('category AS c2', 'c2.id', '=', 'p.category_id');
            if ($viaItem && $viaProduct) $q->addSelect(DB::raw('COALESCE(c.name, c2.name) AS category'));
            elseif ($viaItem) $q->addSelect('c.name AS category');
            elseif ($viaProduct) $q->addSelect('c2.name AS category');
        }
        $out = [];
        foreach ($q->get() as $p) {
            if (!$p->product_id) continue;
            $uq = (float) $p->unit_quantity;
            $out[(string) $p->product_id] = ['id' => (int) $p->id, 'code' => (string) $p->product_id, 'name' => trim((string) (($p->item ?? null) ?: $p->product_name)),
                'pack' => trim(($uq ? CountryScope::q($uq) : '') . ' ' . $p->product_unit), 'price' => (float) $p->main_price,
                'category' => trim((string) ($p->category ?? '')) ?: 'Uncategorised', 'active' => CountryScope::isActive($p->status)];
        }
        return self::$prods = $out;
    }

    private static function byId(): array
    {
        $o = []; foreach (self::products() as $p) $o[$p['id']] = $p; return $o;
    }

    /** Make sure every hub's stock batches match its live stock (same reconcile the hub console does on open). */
    public static function reconcile(array $ids): void
    {
        if (!HubStock::lotsOn()) return;
        foreach ($ids as $h) { try { HubStock::ages($h); } catch (\Throwable $e) { Log::warning('[country-console] ages failed', ['hub' => $h, 'e' => $e->getMessage()]); } }
    }

    /** hub => code => [fresh, danger, expired, oldest] from the batches (no reconcile). */
    private static function ageMap(array $ids): array
    {
        if (!$ids || !HubStock::lotsOn()) return [];
        $today = date('Y-m-d'); $yest = date('Y-m-d', strtotime('-1 day'));
        $out = [];
        foreach (array_chunk($ids, 500) as $chunk) {
            $rows = DB::table('hub_stock_lots')->whereIn('hub_id', $chunk)->where('qty_left', '>', 0)->groupBy('hub_id', 'product_code')
                ->select('hub_id', 'product_code',
                    DB::raw("SUM(CASE WHEN received_on >= '$today' THEN qty_left ELSE 0 END) AS f"),
                    DB::raw("SUM(CASE WHEN received_on < '$today' AND received_on >= '$yest' THEN qty_left ELSE 0 END) AS d"),
                    DB::raw("SUM(CASE WHEN received_on < '$yest' THEN qty_left ELSE 0 END) AS e"),
                    DB::raw('MIN(received_on) AS o'))->get();
            foreach ($rows as $r) $out[(int) $r->hub_id][(string) $r->product_code] = ['fresh' => round((float) $r->f, 3), 'danger' => round((float) $r->d, 3), 'expired' => round((float) $r->e, 3), 'oldest' => (string) $r->o];
        }
        return $out;
    }

    /** Inventory rows of the hubs: hub => code => row */
    private static function invMap(array $ids): array
    {
        if (!$ids) return [];
        $out = [];
        $cols = ['id', 'hub_id', 'product_id', 'stock', 'live_stock', 'fresh_stock', 'variance'];
        if (self::h('inventory', 'updated_at')) $cols[] = 'updated_at';
        foreach (array_chunk($ids, 500) as $chunk) foreach (DB::table('inventory')->whereIn('hub_id', $chunk)->get($cols) as $r) $out[(int) $r->hub_id][(string) $r->product_id] = $r;
        return $out;
    }

    /** Current stock picture of the hubs (overview). */
    public static function snapshot(array $ids): array
    {
        $prods = self::products(); $inv = self::invMap($ids); $ages = self::ageMap($ids);
        $s = ['value' => 0.0, 'fresh_value' => 0.0, 'danger_qty' => 0.0, 'danger_value' => 0.0, 'expired_qty' => 0.0, 'expired_value' => 0.0, 'danger_items' => 0, 'expired_items' => 0, 'out_items' => 0, 'pending_lines' => 0, 'by_hub' => []];
        foreach ($ids as $h) {
            $b = ['value' => 0.0, 'danger_value' => 0.0, 'expired_value' => 0.0, 'out' => 0];
            foreach ($inv[$h] ?? [] as $code => $r) {
                $p = $prods[$code] ?? null; $price = $p['price'] ?? 0;
                $live = (float) $r->live_stock;
                if ($live > 0) $b['value'] += $live * $price;
                elseif ($p && $p['active']) { $b['out']++; $s['out_items']++; }
                if ((float) $r->stock > 0) $s['pending_lines']++;
            }
            foreach ($ages[$h] ?? [] as $code => $a) {
                $price = $prods[$code]['price'] ?? 0;
                $s['fresh_value'] += $a['fresh'] * $price;
                if ($a['danger'] > 0) { $s['danger_items']++; $s['danger_qty'] += $a['danger']; $b['danger_value'] += $a['danger'] * $price; }
                if ($a['expired'] > 0) { $s['expired_items']++; $s['expired_qty'] += $a['expired']; $b['expired_value'] += $a['expired'] * $price; }
            }
            foreach ($b as $k => $v) if (is_float($v)) $b[$k] = round($v, 2);
            $s['value'] += $b['value']; $s['danger_value'] += $b['danger_value']; $s['expired_value'] += $b['expired_value'];
            $s['by_hub'][$h] = $b;
        }
        foreach (['value', 'fresh_value', 'danger_value', 'expired_value'] as $k) $s[$k] = round($s[$k], 2);
        foreach (['danger_qty', 'expired_qty'] as $k) $s[$k] = round($s[$k], 3);
        return $s;
    }

    /** Sales (not cancelled) per hub and product code: qty today and over the last 7 full days. */
    private static function salesMap(array $ids): array
    {
        if (!$ids) return [];
        $out = [];
        $from7 = date('Y-m-d 00:00:00', strtotime('-7 days')); $today = date('Y-m-d 00:00:00');
        foreach (array_chunk($ids, 500) as $chunk) {
            $rows = DB::table('orders AS o')->join('products AS p', 'p.id', '=', 'o.product_id')->whereIn('o.hub_id', $chunk)->where('o.date_added', '>=', $from7)
                ->whereNotIn('o.order_status', OrderFlow::statusesFor(['cancelled']))->groupBy('o.hub_id', 'p.product_id')
                ->select('o.hub_id', 'p.product_id AS code', DB::raw("SUM(CASE WHEN o.date_added >= '$today' THEN o.quantity ELSE 0 END) AS t"), DB::raw("SUM(CASE WHEN o.date_added < '$today' THEN o.quantity ELSE 0 END) AS w"))->get();
            foreach ($rows as $r) $out[(int) $r->hub_id][(string) $r->code] = ['today' => (float) $r->t, 'per_day' => round((float) $r->w / 7, 3)];
        }
        return $out;
    }

    /* ------------------------------------------------------------------ stock by product */

    public static function byProduct(CountryScope $scope, string $q = '', string $category = '', string $status = ''): array
    {
        $ids = $scope->ids();
        self::reconcile($ids);
        $prods = self::products(); $inv = self::invMap($ids); $ages = self::ageMap($ids); $sales = self::salesMap($ids);
        $low = (float) config('hub_console.low_stock', 5);
        $rows = [];
        foreach ($prods as $code => $p) {
            $r = ['id' => $p['id'], 'code' => $code, 'name' => $p['name'], 'pack' => $p['pack'], 'category' => $p['category'], 'price' => $p['price'], 'active' => $p['active'],
                'live' => 0.0, 'fresh' => 0.0, 'danger' => 0.0, 'expired' => 0.0, 'pending' => 0.0, 'value' => 0.0, 'hubs_stocked' => 0, 'hubs_out' => 0, 'hubs_low' => 0, 'hubs_listed' => 0,
                'sold_today' => 0.0, 'per_day' => 0.0];
            foreach ($ids as $h) {
                $i = $inv[$h][$code] ?? null; $a = $ages[$h][$code] ?? null; $s = $sales[$h][$code] ?? null;
                if ($s) { $r['sold_today'] += $s['today']; $r['per_day'] += $s['per_day']; }
                if (!$i) continue;
                $r['hubs_listed']++;
                $live = (float) $i->live_stock;
                $r['live'] += $live; $r['pending'] += max(0, (float) $i->stock);
                if ($live > 0) { $r['hubs_stocked']++; $r['value'] += $live * $p['price']; if ($live <= $low) $r['hubs_low']++; } else $r['hubs_out']++;
                if ($a) { $r['fresh'] += $a['fresh']; $r['danger'] += $a['danger']; $r['expired'] += $a['expired']; }
            }
            if (!$p['active'] && $r['hubs_listed'] === 0) continue;
            foreach (['live', 'fresh', 'danger', 'expired', 'pending', 'sold_today', 'per_day'] as $k) $r[$k] = round($r[$k], 3);
            $r['value'] = round($r['value'], 2);
            $r['cover_days'] = $r['per_day'] > 0 ? round(max(0, $r['live']) / $r['per_day'], 1) : null;
            $r['status'] = $r['live'] <= 0 ? 'out' : ($r['expired'] > 0 ? 'expired' : ($r['danger'] > 0 ? 'danger' : ($r['hubs_out'] > 0 ? 'gaps' : 'ok')));
            $rows[] = $r;
        }
        $term = mb_strtolower(trim($q));
        $rows = array_values(array_filter($rows, function ($r) use ($term, $category, $status) {
            if ($term !== '' && !str_contains(mb_strtolower($r['name'] . ' ' . $r['code'] . ' ' . $r['category']), $term)) return false;
            if ($category !== '' && strcasecmp($r['category'], $category) !== 0) return false;
            if ($status === 'out' && $r['status'] !== 'out') return false;
            if ($status === 'gaps' && $r['hubs_out'] === 0) return false;
            if ($status === 'danger' && $r['danger'] <= 0) return false;
            if ($status === 'expired' && $r['expired'] <= 0) return false;
            if ($status === 'pending' && $r['pending'] <= 0) return false;
            if ($status === 'inactive' && $r['active']) return false;
            return true;
        }));
        usort($rows, fn ($a, $b) => [$b['value'], $a['name']] <=> [$a['value'], $b['name']]);
        $cats = array_values(array_unique(array_map(fn ($p) => $p['category'], $prods))); sort($cats);
        $tot = ['live_value' => round(array_sum(array_column($rows, 'value')), 2), 'products' => count($rows), 'out' => count(array_filter($rows, fn ($r) => $r['status'] === 'out')),
            'danger_qty' => round(array_sum(array_column($rows, 'danger')), 3), 'expired_qty' => round(array_sum(array_column($rows, 'expired')), 3)];
        return ['ok' => true, 'rows' => $rows, 'categories' => $cats, 'total' => $tot, 'hubs' => count($ids), 'low_at' => $low];
    }

    /** One product in every hub of the scope. */
    public static function productHubs(CountryScope $scope, int $productId): array
    {
        $p = self::byId()[$productId] ?? null;
        if (!$p) return ['ok' => false, 'message' => 'Product not found.'];
        $ids = $scope->ids(); $hubs = CountryScope::hubs();
        self::reconcile($ids);
        $inv = self::invMap($ids); $ages = self::ageMap($ids); $sales = self::salesMap($ids);
        $rows = [];
        foreach ($ids as $h) {
            $i = $inv[$h][$p['code']] ?? null; $a = $ages[$h][$p['code']] ?? null; $s = $sales[$h][$p['code']] ?? ['today' => 0, 'per_day' => 0];
            $live = $i ? (float) $i->live_stock : 0.0;
            $rows[] = ['hub_id' => $h, 'hub' => $hubs[$h]['name'] ?? '', 'city' => $hubs[$h]['city'] ?? '', 'state' => $hubs[$h]['state'] ?? '', 'listed' => (bool) $i,
                'live' => $live, 'fresh' => $a['fresh'] ?? 0, 'danger' => $a['danger'] ?? 0, 'expired' => $a['expired'] ?? 0, 'oldest' => $a['oldest'] ?? null,
                'pending' => $i ? (float) $i->stock : 0, 'variance' => $i ? (float) $i->variance : 0, 'sold_today' => $s['today'], 'per_day' => $s['per_day'],
                'cover_days' => $s['per_day'] > 0 ? round(max(0, $live) / $s['per_day'], 1) : null, 'value' => round(max(0, $live) * $p['price'], 2),
                'updated' => $i && isset($i->updated_at) ? CountryScope::ts($i->updated_at) : null];
        }
        usort($rows, fn ($a, $b) => [$b['live'], $a['hub']] <=> [$a['live'], $b['hub']]);
        return ['ok' => true, 'product' => $p, 'rows' => $rows];
    }

    /* ------------------------------------------------------------------ stock by hub */

    public static function byHub(CountryScope $scope): array
    {
        $ids = $scope->ids(); $hubs = CountryScope::hubs();
        self::reconcile($ids);
        $prods = self::products(); $inv = self::invMap($ids); $ages = self::ageMap($ids);
        $low = (float) config('hub_console.low_stock', 5);
        $lastIn = [];
        if (self::h('hub_stock_moves') && $ids) {
            foreach (array_chunk($ids, 500) as $c) foreach (DB::table('hub_stock_moves')->whereIn('hub_id', $c)->where('type', 'inward')->groupBy('hub_id')->select('hub_id', DB::raw('MAX(created_at) AS m'))->get() as $r) $lastIn[(int) $r->hub_id] = CountryScope::ts($r->m);
        }
        $waste = self::wastageTotals($ids, date('Y-m-d', strtotime('-6 days')), date('Y-m-d'));
        $rows = [];
        foreach ($ids as $h) {
            $r = ['hub_id' => $h, 'hub' => $hubs[$h]['name'] ?? '', 'city' => $hubs[$h]['city'] ?? '', 'state' => $hubs[$h]['state'] ?? '', 'type' => $hubs[$h]['type'] ?? '', 'active' => $hubs[$h]['active'] ?? false,
                'skus' => 0, 'in_stock' => 0, 'out' => 0, 'low' => 0, 'value' => 0.0, 'danger_qty' => 0.0, 'danger_value' => 0.0, 'expired_qty' => 0.0, 'expired_value' => 0.0,
                'pending_lines' => 0, 'last_inward' => $lastIn[$h] ?? null, 'wastage_7d' => $waste['by_hub'][$h]['value'] ?? 0];
            foreach ($inv[$h] ?? [] as $code => $i) {
                $p = $prods[$code] ?? null; if (!$p) continue;
                $r['skus']++; $live = (float) $i->live_stock;
                if ($live > 0) { $r['in_stock']++; $r['value'] += $live * $p['price']; if ($live <= $low) $r['low']++; } elseif ($p['active']) $r['out']++;
                if ((float) $i->stock > 0) $r['pending_lines']++;
            }
            foreach ($ages[$h] ?? [] as $code => $a) {
                $price = $prods[$code]['price'] ?? 0;
                $r['danger_qty'] += $a['danger']; $r['danger_value'] += $a['danger'] * $price; $r['expired_qty'] += $a['expired']; $r['expired_value'] += $a['expired'] * $price;
            }
            foreach (['value', 'danger_value', 'expired_value'] as $k) $r[$k] = round($r[$k], 2);
            foreach (['danger_qty', 'expired_qty'] as $k) $r[$k] = round($r[$k], 3);
            $r['health'] = $r['expired_value'] > 0 ? 'bad' : (($r['danger_value'] > 0 || $r['out'] > 0) ? 'warn' : 'ok');
            $rows[] = $r;
        }
        usort($rows, fn ($a, $b) => [$a['health'] === 'ok', -$a['expired_value'], -$a['danger_value'], $a['hub']] <=> [$b['health'] === 'ok', -$b['expired_value'], -$b['danger_value'], $b['hub']]);
        return ['ok' => true, 'rows' => $rows];
    }

    /** Products of one hub with stock (for the write-off and transfer forms). */
    public static function hubProducts(int $hubId): array
    {
        if (!CountryScope::hub($hubId)) return ['ok' => false, 'message' => 'Hub not found.'];
        self::reconcile([$hubId]);
        $prods = self::products(); $inv = self::invMap([$hubId])[$hubId] ?? []; $ages = self::ageMap([$hubId])[$hubId] ?? [];
        $rows = [];
        foreach ($inv as $code => $i) {
            $p = $prods[$code] ?? null; if (!$p) continue;
            $a = $ages[$code] ?? ['fresh' => 0, 'danger' => 0, 'expired' => 0];
            $rows[] = ['id' => $p['id'], 'code' => $code, 'name' => $p['name'], 'pack' => $p['pack'], 'price' => $p['price'], 'live' => (float) $i->live_stock, 'fresh' => $a['fresh'], 'danger' => $a['danger'], 'expired' => $a['expired']];
        }
        usort($rows, fn ($a, $b) => strcasecmp($a['name'], $b['name']));
        return ['ok' => true, 'rows' => $rows];
    }

    /* ------------------------------------------------------------------ danger & expired */

    /** Hubs (names) that have danger stock but no 11:00 plan today, once the plan time has passed. */
    public static function plansNotRun(array $hubIds): array
    {
        if (!$hubIds || !HubStock::has('hub_daily_runs') || date('H:i') < (string) config('hub_console.danger_plan_time', '11:00')) return [];
        $ran = DB::table('hub_daily_runs')->whereIn('hub_id', $hubIds)->where('run_date', date('Y-m-d'))->where('kind', 'danger_plan')->pluck('hub_id')->map(fn ($v) => (int) $v)->all();
        $hubs = CountryScope::hubs();
        return array_values(array_map(fn ($h) => $hubs[$h]['name'] ?? ('Hub ' . $h), array_diff($hubIds, $ran)));
    }

    public static function stuckTransfers(array $hubIds): int
    {
        if (!$hubIds || !HubStock::has('hub_transfers')) return 0;
        return (int) DB::table('hub_transfers')->where('status', 'in_transit')->where('sent_at', '<', date('Y-m-d H:i:s', time() - 6 * 3600))
            ->where(fn ($w) => $w->whereIn('from_hub_id', $hubIds)->orWhereIn('to_hub_id', $hubIds))->count();
    }

    /** Danger and expired stock in every hub of the scope, with each hub's plan (where it should go). */
    public static function danger(CountryScope $scope): array
    {
        if (!HubStock::lotsOn()) return ['ok' => false, 'message' => 'Stock age tracking is not installed yet (hub console update 5).'];
        $hubs = CountryScope::hubs(); $rows = []; $byHub = [];
        $runs = HubStock::has('hub_daily_runs') ? DB::table('hub_daily_runs')->where('run_date', date('Y-m-d'))->where('kind', 'danger_plan')->pluck('created_at', 'hub_id')->all() : [];
        $transfers = [];
        if (HubStock::has('hub_transfers')) {
            foreach (DB::table('hub_transfers')->where('kind', 'plan')->whereDate('created_at', date('Y-m-d'))->whereIn('status', ['planned', 'in_transit', 'received'])->get(['from_hub_id', 'status']) as $t) {
                $transfers[(int) $t->from_hub_id][$t->status] = ($transfers[(int) $t->from_hub_id][$t->status] ?? 0) + 1;
            }
        }
        $prods = self::products();
        foreach ($scope->ids() as $h) {
            try { $st = (new HubDangerService($h))->status(); } catch (\Throwable $e) { continue; }
            if (empty($st['ok'])) continue;
            $hv = ['hub_id' => $h, 'hub' => $hubs[$h]['name'] ?? '', 'city' => $hubs[$h]['city'] ?? '', 'active' => $hubs[$h]['active'] ?? false, 'danger' => 0.0, 'expired' => 0.0, 'over' => 0.0, 'value' => 0.0, 'expired_value' => 0.0, 'lines' => 0,
                'plan_ran' => isset($runs[$h]) ? CountryScope::ts($runs[$h]) : null, 'planned' => $transfers[$h]['planned'] ?? 0, 'dispatched' => ($transfers[$h]['in_transit'] ?? 0) + ($transfers[$h]['received'] ?? 0), 'nearby' => count($st['nearby'] ?? [])];
            foreach ($st['rows'] as $r) {
                $price = $prods[$r['code']]['price'] ?? 0;
                $rows[] = $r + ['hub_id' => $h, 'hub' => $hv['hub'], 'city' => $hv['city'], 'price' => $price, 'value' => round(($r['danger'] + $r['expired']) * $price, 2), 'expired_value' => round($r['expired'] * $price, 2)];
                $hv['danger'] += $r['danger']; $hv['expired'] += $r['expired']; $hv['over'] += $r['over']; $hv['value'] += ($r['danger'] + $r['expired']) * $price; $hv['expired_value'] += $r['expired'] * $price; $hv['lines']++;
            }
            if ($hv['lines']) { foreach (['danger', 'expired', 'over'] as $k) $hv[$k] = round($hv[$k], 3); $hv['value'] = round($hv['value'], 2); $hv['expired_value'] = round($hv['expired_value'], 2); $byHub[] = $hv; }
        }
        usort($rows, fn ($a, $b) => [$b['expired'] > 0, $b['value']] <=> [$a['expired'] > 0, $a['value']]);
        usort($byHub, fn ($a, $b) => [$b['expired_value'], $b['value']] <=> [$a['expired_value'], $a['value']]);
        return ['ok' => true, 'rows' => $rows, 'hubs' => $byHub, 'plan_time' => (string) config('hub_console.danger_plan_time', '11:00'),
            'total' => ['value' => round(array_sum(array_column($byHub, 'value')), 2), 'expired_value' => round(array_sum(array_column($byHub, 'expired_value')), 2), 'danger' => round(array_sum(array_column($byHub, 'danger')), 3), 'expired' => round(array_sum(array_column($byHub, 'expired')), 3)]];
    }

    /** Run (or re-run) today's danger stock plan for hubs. Dispatched transfers are never touched. */
    public static function runPlans(array $hubIds, array $actor): array
    {
        $made = []; $msgs = [];
        foreach (array_slice($hubIds, 0, 300) as $h) {
            if (!CountryScope::hub($h)) continue;
            try { $r = (new HubDangerService($h))->runDaily(true, ['id' => $actor['id'] ?? null, 'name' => mb_substr(($actor['name'] ?? 'Country manager') . ' (country)', 0, 120)]); } catch (\Throwable $e) { $msgs[] = (CountryScope::hub($h)['name'] ?? $h) . ': failed'; continue; }
            if (!empty($r['transfers'])) $made = array_merge($made, $r['transfers']);
        }
        Log::info('[freshful-audit] country-danger-plan', ['hubs' => $hubIds, 'transfers' => $made, 'by' => $actor['id'] ?? null]);
        return ['ok' => true, 'transfers' => $made, 'message' => ($made ? count($made) . ' transfer(s) planned: ' . implode(', ', array_slice($made, 0, 6)) . (count($made) > 6 ? '...' : '') . '. Each hub dispatches them from its console.' : 'Plan recalculated. No danger stock needs to move right now.') . ($msgs ? ' ' . implode('; ', $msgs) : '')];
    }

    /* ------------------------------------------------------------------ wastage / write-off */

    public static function reasons(): array { return (array) config('country_console.writeoff_reasons', []); }

    /** Write off stock in any hub. Oldest stock is taken first, through the hub's own write-off (same ledger, same stock age). */
    public static function writeOff(int $hubId, int $productId, float $qty, string $reason, string $note, array $actor): array
    {
        $hub = CountryScope::hub($hubId);
        if (!$hub) return ['ok' => false, 'message' => 'Choose the hub.'];
        $reasons = self::reasons();
        if (!isset($reasons[$reason])) return ['ok' => false, 'message' => 'Choose the reason for the write-off.'];
        if ($reason === 'other' && trim($note) === '') return ['ok' => false, 'message' => 'Write what happened (reason "Other").'];
        $label = preg_replace('/\s*\(.*\)$/', '', $reasons[$reason]);
        $text = $label . ' [' . $reason . ']' . (trim($note) !== '' ? ' - ' . mb_substr(trim(strip_tags($note)), 0, 160) : '') . ' (country manager)';
        $res = (new HubDangerService($hubId))->writeOff($productId, round($qty, 3), $text, $actor);
        if ($res['ok']) {
            $res['message'] = $hub['name'] . ': ' . $res['message'];
            Log::info('[freshful-audit] country-wastage', ['hub' => $hubId, 'product' => $productId, 'qty' => $qty, 'reason' => $reason, 'by' => $actor['id'] ?? null]);
        }
        return $res;
    }

    /** Reason key of a wastage note: "[spoiled]" tag from this console, otherwise guessed from the words used in the hub console. */
    public static function reasonOf(?string $note): string
    {
        $n = mb_strtolower((string) $note);
        if (preg_match('/\[(\w+)\]/', $n, $m) && isset(self::reasons()[$m[1]])) return $m[1];
        $words = ['expired' => ['expir', 'old stock', '2 day', 'two day'], 'spoiled' => ['spoil', 'rotten', 'rot ', 'smell', 'stale', 'fung', 'decay', 'bad'], 'damaged' => ['damag', 'broken', 'leak', 'crush', 'torn', 'transit'],
            'quality' => ['quality', 'reject', 'colour', 'color', 'texture'], 'return' => ['return', 'customer'], 'missing' => ['missing', 'theft', 'stolen', 'short']];
        foreach ($words as $k => $list) foreach ($list as $w) if (str_contains($n, $w)) return $k;
        return 'other';
    }

    /** Wastage quantity and value per hub in the dates (stock engine write-offs). */
    public static function wastageTotals(array $ids, string $from, string $to): array
    {
        $out = ['qty' => 0.0, 'value' => 0.0, 'by_hub' => []];
        if (!$ids || !self::h('hub_stock_moves')) return $out;
        $prods = self::products();
        foreach (array_chunk($ids, 500) as $c) {
            $rows = DB::table('hub_stock_moves')->whereIn('hub_id', $c)->where('type', 'wastage')->whereBetween('created_at', [$from . ' 00:00:00', $to . ' 23:59:59'])
                ->groupBy('hub_id', 'product_code')->select('hub_id', 'product_code', DB::raw('SUM(change_qty) AS q'))->get();
            foreach ($rows as $r) {
                $q = -(float) $r->q; $v = $q * ($prods[(string) $r->product_code]['price'] ?? 0);
                $h = (int) $r->hub_id; $out['by_hub'][$h] = $out['by_hub'][$h] ?? ['qty' => 0.0, 'value' => 0.0];
                $out['by_hub'][$h]['qty'] = round($out['by_hub'][$h]['qty'] + $q, 3); $out['by_hub'][$h]['value'] = round($out['by_hub'][$h]['value'] + $v, 2);
                $out['qty'] += $q; $out['value'] += $v;
            }
        }
        $out['qty'] = round($out['qty'], 3); $out['value'] = round($out['value'], 2);
        return $out;
    }

    /** Full wastage report: every write-off with value, by reason / hub / product / category / day, wastage % of sales, plus old-panel records. */
    public static function wastageReport(CountryScope $scope, string $from, string $to): array
    {
        $ids = $scope->ids(); $hubs = CountryScope::hubs(); $prods = self::products(); $reasons = self::reasons();
        $list = [];
        if ($ids && self::h('hub_stock_moves')) {
            foreach (array_chunk($ids, 500) as $c) {
                foreach (DB::table('hub_stock_moves')->whereIn('hub_id', $c)->where('type', 'wastage')->whereBetween('created_at', [$from . ' 00:00:00', $to . ' 23:59:59'])->orderByDesc('id')->limit(5000)->get() as $m) {
                    $p = $prods[(string) $m->product_code] ?? null; $q = -(float) $m->change_qty;
                    $rk = self::reasonOf($m->note);
                    $list[] = ['id' => (int) $m->id, 'at' => CountryScope::ts($m->created_at), 'hub_id' => (int) $m->hub_id, 'hub' => $hubs[(int) $m->hub_id]['name'] ?? '', 'city' => $hubs[(int) $m->hub_id]['city'] ?? '',
                        'code' => (string) $m->product_code, 'name' => $p['name'] ?? (string) $m->product_code, 'pack' => $p['pack'] ?? '', 'category' => $p['category'] ?? '', 'qty' => round($q, 3),
                        'value' => round($q * ($p['price'] ?? 0), 2), 'reason' => $rk, 'reason_label' => preg_replace('/\s*\(.*\)$/', '', $reasons[$rk] ?? ucfirst($rk)), 'note' => trim(preg_replace(['/\s*\[\w+\]/', '/^Written off:\s*/'], '', (string) $m->note)), 'by' => (string) $m->by_name,
                        'lots' => isset($m->lots) && $m->lots ? json_decode((string) $m->lots, true) : null];
                }
            }
        }
        usort($list, fn ($a, $b) => $b['at'] <=> $a['at']);
        $group = function (callable $k, callable $label) use ($list) {
            $o = [];
            foreach ($list as $r) { $key = $k($r); $o[$key] = $o[$key] ?? ['key' => $key, 'name' => $label($r), 'qty' => 0.0, 'value' => 0.0, 'entries' => 0]; $o[$key]['qty'] = round($o[$key]['qty'] + $r['qty'], 3); $o[$key]['value'] = round($o[$key]['value'] + $r['value'], 2); $o[$key]['entries']++; }
            $o = array_values($o); usort($o, fn ($a, $b) => $b['value'] <=> $a['value']); return $o;
        };
        $byReason = $group(fn ($r) => $r['reason'], fn ($r) => $r['reason_label']);
        $byProduct = $group(fn ($r) => $r['code'], fn ($r) => $r['name'] . ($r['pack'] ? ' (' . $r['pack'] . ')' : ''));
        $byCat = $group(fn ($r) => $r['category'], fn ($r) => $r['category']);
        // Per hub with its sales in the same dates, so the % is comparable between big and small hubs.
        $orders = CountryAnalytics::orderRows($ids, $from . ' 00:00:00', $to . ' 23:59:59');
        $sales = []; foreach ($orders as $o) if ($o['stage'] !== 'cancelled') $sales[$o['hub']] = ($sales[$o['hub']] ?? 0) + $o['value'];
        $byHub = [];
        foreach ($group(fn ($r) => $r['hub_id'], fn ($r) => $r['hub']) as $g) {
            $s = round($sales[$g['key']] ?? 0, 2);
            $byHub[] = $g + ['city' => $hubs[$g['key']]['city'] ?? '', 'sales' => $s, 'pct' => $s > 0 ? round($g['value'] * 100 / $s, 2) : null];
        }
        $days = [];
        $span = (int) round((strtotime($to) - strtotime($from)) / 86400);
        for ($i = 0; $i <= $span; $i++) $days[date('Y-m-d', strtotime($from . " +$i days"))] = 0.0;
        foreach ($list as $r) { $d = date('Y-m-d', $r['at']); if (isset($days[$d])) $days[$d] = round($days[$d] + $r['value'], 2); }
        $totalSales = round(array_sum($sales), 2); $totalValue = round(array_sum(array_column($list, 'value')), 2);
        // Records made in the old PHP panels (table wastage_stock). They were never taken off live stock; shown for history only.
        $legacy = [];
        if (self::h('wastage_stock') && $ids) {
            $dc = self::h('wastage_stock', 'date_added') ? 'date_added' : (self::h('wastage_stock', 'created_at') ? 'created_at' : null);
            $qc = self::h('wastage_stock', 'waste_stock') ? 'waste_stock' : (self::h('wastage_stock', 'wastage_stock') ? 'wastage_stock' : null);
            if ($dc && $qc && self::h('wastage_stock', 'hub_id') && self::h('wastage_stock', 'product_id')) {
                $byPid = self::byId();
                foreach (DB::table('wastage_stock')->whereIn('hub_id', $ids)->whereBetween($dc, [$from . ' 00:00:00', $to . ' 23:59:59'])->orderByDesc($dc)->limit(1000)->get(['id', 'hub_id', 'product_id', $qc . ' AS q', $dc . ' AS d']) as $w) {
                    $p = $byPid[(int) $w->product_id] ?? ($prods[(string) $w->product_id] ?? null);
                    $legacy[] = ['at' => CountryScope::ts($w->d), 'hub' => $hubs[(int) $w->hub_id]['name'] ?? '', 'name' => $p['name'] ?? ('#' . $w->product_id), 'qty' => (float) $w->q, 'value' => round((float) $w->q * ($p['price'] ?? 0), 2)];
                }
            }
        }
        return ['ok' => true, 'rows' => array_slice($list, 0, 2000), 'count' => count($list), 'by_reason' => $byReason, 'by_hub' => $byHub, 'by_product' => array_slice($byProduct, 0, 50), 'by_category' => $byCat,
            'days' => array_map(fn ($d, $v) => ['date' => $d, 'value' => $v], array_keys($days), array_values($days)),
            'total' => ['qty' => round(array_sum(array_column($list, 'qty')), 3), 'value' => $totalValue, 'sales' => $totalSales, 'pct' => $totalSales > 0 ? round($totalValue * 100 / $totalSales, 2) : null],
            'limit_pct' => (float) config('country_console.wastage_limit_pct', 3), 'reasons' => $reasons, 'legacy' => $legacy];
    }

    /* ------------------------------------------------------------------ inward, ledger, correction */

    /** Stock sent to hubs and not yet received (inventory.stock > 0). */
    public static function pendingInward(CountryScope $scope): array
    {
        $ids = $scope->ids(); $hubs = CountryScope::hubs(); $prods = self::products();
        $rows = [];
        foreach (self::invMap($ids) as $h => $list) foreach ($list as $code => $i) {
            if ((float) $i->stock <= 0) continue;
            $p = $prods[$code] ?? null;
            $rows[] = ['hub_id' => $h, 'hub' => $hubs[$h]['name'] ?? '', 'city' => $hubs[$h]['city'] ?? '', 'code' => $code, 'name' => $p['name'] ?? $code, 'pack' => $p['pack'] ?? '', 'qty' => (float) $i->stock,
                'value' => round((float) $i->stock * ($p['price'] ?? 0), 2), 'live' => (float) $i->live_stock, 'since' => isset($i->updated_at) ? CountryScope::ts($i->updated_at) : null];
        }
        usort($rows, fn ($a, $b) => [$a['since'] ?? PHP_INT_MAX, $a['hub']] <=> [$b['since'] ?? PHP_INT_MAX, $b['hub']]);
        return ['ok' => true, 'rows' => $rows, 'total' => ['lines' => count($rows), 'value' => round(array_sum(array_column($rows, 'value')), 2), 'hubs' => count(array_unique(array_column($rows, 'hub_id')))]];
    }

    public const MOVE_TYPES = ['inward' => 'Received (inward)', 'sale' => 'Sold (order accepted)', 'return' => 'Returned (order cancelled / moved)', 'transfer_out' => 'Sent to another hub',
        'transfer_in' => 'Received from another hub', 'transfer_back' => 'Transfer cancelled (back)', 'count' => 'Stock count correction', 'wastage' => 'Written off (wastage)'];

    public static function ledger(CountryScope $scope, string $from, string $to, string $type, string $q): array
    {
        $ids = $scope->ids(); $hubs = CountryScope::hubs(); $prods = self::products();
        if (!$ids || !self::h('hub_stock_moves')) return ['ok' => true, 'rows' => [], 'summary' => [], 'types' => self::MOVE_TYPES];
        $m = DB::table('hub_stock_moves')->whereIn('hub_id', $ids)->whereBetween('created_at', [$from . ' 00:00:00', $to . ' 23:59:59']);
        if ($type !== '' && isset(self::MOVE_TYPES[$type])) $m->where('type', $type);
        $term = trim($q);
        if ($term !== '') {
            $codes = array_keys(array_filter($prods, fn ($p) => str_contains(mb_strtolower($p['name']), mb_strtolower($term))));
            $m->where(function ($w) use ($term, $codes) { $w->where('product_code', 'like', '%' . $term . '%')->orWhere('ref', 'like', '%' . $term . '%')->orWhere('note', 'like', '%' . $term . '%'); if ($codes) $w->orWhereIn('product_code', array_slice($codes, 0, 500)); });
        }
        $rows = []; $sum = [];
        foreach ($m->orderByDesc('id')->limit(3000)->get() as $r) {
            $p = $prods[(string) $r->product_code] ?? null;
            $rows[] = ['id' => (int) $r->id, 'at' => CountryScope::ts($r->created_at), 'hub' => $hubs[(int) $r->hub_id]['name'] ?? '', 'city' => $hubs[(int) $r->hub_id]['city'] ?? '', 'code' => (string) $r->product_code,
                'name' => $p['name'] ?? (string) $r->product_code, 'type' => (string) $r->type, 'before' => (float) $r->before_qty, 'change' => (float) $r->change_qty, 'after' => (float) $r->after_qty,
                'value' => round((float) $r->change_qty * ($p['price'] ?? 0), 2), 'ref' => $r->ref ?? null, 'note' => (string) $r->note, 'by' => (string) $r->by_name];
            $sum[$r->type] = $sum[$r->type] ?? ['qty' => 0.0, 'value' => 0.0, 'n' => 0];
            $sum[$r->type]['qty'] = round($sum[$r->type]['qty'] + (float) $r->change_qty, 3); $sum[$r->type]['value'] = round($sum[$r->type]['value'] + (float) $r->change_qty * ($p['price'] ?? 0), 2); $sum[$r->type]['n']++;
        }
        return ['ok' => true, 'rows' => $rows, 'summary' => $sum, 'types' => self::MOVE_TYPES, 'capped' => count($rows) >= 3000];
    }

    /** Physical count correction for a hub (same as the hub's own stock count; logged with the country manager's name). */
    public static function correct(int $hubId, int $productId, float $counted, string $note, array $actor): array
    {
        $hub = CountryScope::hub($hubId);
        if (!$hub) return ['ok' => false, 'message' => 'Choose the hub.'];
        if (trim($note) === '') return ['ok' => false, 'message' => 'Write why the stock is being corrected (for example: physical count by ...).'];
        $res = (new HubConsoleService($hubId))->stockCount($productId, round($counted, 3), mb_substr('Country manager: ' . trim(strip_tags($note)), 0, 240), $actor);
        if ($res['ok']) { $res['message'] = $hub['name'] . ': ' . $res['message']; Log::info('[freshful-audit] country-stock-count', ['hub' => $hubId, 'product' => $productId, 'counted' => $counted, 'by' => $actor['id'] ?? null]); }
        return $res;
    }
}
