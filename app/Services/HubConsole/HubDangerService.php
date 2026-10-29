<?php

namespace App\Services\HubConsole;

use App\Services\PosConsole\OrderFlow;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Danger stock and the daily 11:00 plan (22 Oct 2026). Owner's process:
 *   Stock must sell within 2 days. Day 1 = fresh, day 2 = danger (must be sold today).
 *   Every day at about 11:00 the system looks at each hub's danger stock. What the hub is not expected to sell by
 *   closing ("over-danger") is planned to go to a nearby hub that sells that product and is running short.
 *   The two hubs' riders meet at the midpoint, exchange, and the receiving hub accepts the inward stock.
 *
 * CTO decisions:
 *   - Expected sale for the rest of today = the product's average daily sale at this hub over the last 7 days,
 *     times the share of a normal day's orders that still comes after the current time (from this hub's last 14 days).
 *   - A nearby hub is an active hub within hub_console.nearby_km (default 25 km, from the hub coordinates).
 *     If coordinates are missing, hubs in the same city count as nearby.
 *   - A nearby hub "needs" the product when its own expected sale for the rest of today is more than its live stock.
 *     Over-danger goes to the hubs with the biggest need first. If no hub needs it, it is not moved: the hub is told
 *     to sell it at a discount today or write it off at closing.
 *   - The plan only PREPARES transfers (status "planned"). Stock leaves the hub when the manager presses Dispatch
 *     (with the rider), so the system never shows stock on the road that is still on the shelf.
 *   - The hub manager sees only the destination hub's NAME, never other hubs' stock or sales.
 */
class HubDangerService
{
    public function __construct(private int $hubId) {}

    private static function q(float $n): string { return rtrim(rtrim(number_format($n, 3, '.', ''), '0'), '.'); }

    /** Average daily quantity sold per product code, last 7 full days: [hub_id => [code => qty/day]] */
    private static function demand(array $hubIds): array
    {
        if (!$hubIds) return [];
        $from = date('Y-m-d 00:00:00', strtotime('-7 days')); $to = date('Y-m-d 00:00:00');
        $rows = DB::table('orders AS o')->join('products AS p', 'p.id', '=', 'o.product_id')
            ->whereIn('o.hub_id', $hubIds)->where('o.date_added', '>=', $from)->where('o.date_added', '<', $to)
            ->whereNotIn('o.order_status', OrderFlow::statusesFor(['cancelled']))
            ->groupBy('o.hub_id', 'p.product_id')->select('o.hub_id', 'p.product_id AS code', DB::raw('SUM(o.quantity) AS q'))->get();
        $out = [];
        foreach ($rows as $r) $out[(int) $r->hub_id][(string) $r->code] = round((float) $r->q / 7, 3);
        return $out;
    }

    /** Share of a normal day's orders still to come after now (0..1), from the hub's last 14 days. */
    private static function restOfDay(int $hubId): float
    {
        $rows = DB::table('orders')->where('hub_id', $hubId)->where('date_added', '>=', date('Y-m-d 00:00:00', strtotime('-14 days')))->where('date_added', '<', date('Y-m-d 00:00:00'))
            ->groupBy('order_id')->select(DB::raw('MAX(date_added) AS at'))->pluck('at');
        $mins = (int) date('G') * 60 + (int) date('i');
        if ($rows->count() >= 10) {
            $after = 0;
            foreach ($rows as $at) { $t = strtotime((string) $at); if ($t && ((int) date('G', $t) * 60 + (int) date('i', $t)) >= $mins) $after++; }
            return round($after / $rows->count(), 3);
        }
        // Not enough history: assume orders spread evenly from 07:00 to 21:00.
        return round(max(0, min(1, (21 * 60 - $mins) / (14 * 60))), 3);
    }

    private static function km(float $a1, float $o1, float $a2, float $o2): float
    {
        $r = 6371; $da = deg2rad($a2 - $a1); $do = deg2rad($o2 - $o1);
        $h = sin($da / 2) ** 2 + cos(deg2rad($a1)) * cos(deg2rad($a2)) * sin($do / 2) ** 2;
        return round(2 * $r * asin(min(1, sqrt($h))), 1);
    }

    /** Active hubs near this hub: [[id, hub, km|null]] nearest first. */
    public function nearby(): array
    {
        $all = DB::table('hubs')->whereRaw("LOWER(CAST(status AS CHAR)) IN ('active','1')")->get(['id', 'hub', 'city_id', 'hub_lat', 'hub_lng']);
        $me = $all->firstWhere('id', $this->hubId) ?? DB::table('hubs')->where('id', $this->hubId)->first(['id', 'hub', 'city_id', 'hub_lat', 'hub_lng']);
        if (!$me) return [];
        $max = (float) config('hub_console.nearby_km', 25);
        $out = [];
        foreach ($all as $h) {
            if ((int) $h->id === $this->hubId) continue;
            $km = (is_numeric($me->hub_lat) && is_numeric($me->hub_lng) && is_numeric($h->hub_lat) && is_numeric($h->hub_lng))
                ? self::km((float) $me->hub_lat, (float) $me->hub_lng, (float) $h->hub_lat, (float) $h->hub_lng) : null;
            if ($km !== null ? $km <= $max : (int) $h->city_id === (int) $me->city_id) $out[] = ['id' => (int) $h->id, 'hub' => (string) $h->hub, 'km' => $km];
        }
        usort($out, fn ($a, $b) => [$a['km'] ?? 999, $a['hub']] <=> [$b['km'] ?? 999, $b['hub']]);
        return $out;
    }

    /** The danger-stock picture for this hub, with the planned destinations. */
    public function status(): array
    {
        if (!HubStock::lotsOn()) return ['ok' => false, 'message' => 'Stock age tracking is not installed yet.'];
        $ages = HubStock::ages($this->hubId);
        $near = $this->nearby();
        $ids = array_merge([$this->hubId], array_column($near, 'id'));
        $dem = self::demand($ids);
        $share = self::restOfDay($this->hubId);
        $shares = [];
        foreach ($near as $n) $shares[$n['id']] = self::restOfDay($n['id']);
        $codes = array_keys(array_filter($ages, fn ($a) => $a['danger'] > 0 || $a['expired'] > 0));
        $names = [];
        if ($codes) {
            foreach (DB::table('products AS p')->leftJoin('items AS i', 'i.id', '=', 'p.item_id')->whereIn('p.product_id', $codes)->get(['p.id', 'p.product_id', 'p.product_name', 'p.unit_quantity', 'p.product_unit', 'i.item']) as $p) {
                $uq = (float) $p->unit_quantity;
                $names[(string) $p->product_id] = ['id' => (int) $p->id, 'name' => trim((string) ($p->item ?: $p->product_name)), 'pack' => trim(($uq ? self::q($uq) : '') . ' ' . $p->product_unit)];
            }
        }
        $live = $codes && $near ? DB::table('inventory')->whereIn('hub_id', array_column($near, 'id'))->whereIn('product_id', $codes)->get(['hub_id', 'product_id', 'live_stock'])
            ->groupBy('product_id')->map(fn ($g) => $g->pluck('live_stock', 'hub_id')->map(fn ($v) => (float) $v)->all())->all() : [];
        $rows = [];
        foreach ($codes as $c) {
            $a = $ages[$c]; $n = $names[$c] ?? ['id' => 0, 'name' => $c, 'pack' => ''];
            $perDay = (float) ($dem[$this->hubId][$c] ?? 0);
            $rest = round($perDay * $share, 3);
            $over = round(max(0, $a['danger'] - $rest), 3);
            // Who needs it: nearby hub's expected sale for the rest of today minus its live stock.
            $takers = [];
            foreach ($near as $h) {
                $need = round((float) ($dem[$h['id']][$c] ?? 0) * ($shares[$h['id']] ?? 0) - max(0, (float) ($live[$c][$h['id']] ?? 0)), 3);
                if ($need > 0.0005) $takers[] = $h + ['need' => $need];
            }
            usort($takers, fn ($x, $y) => [$y['need'], $x['km'] ?? 999] <=> [$x['need'], $y['km'] ?? 999]);
            $plan = []; $left = $over;
            foreach ($takers as $t) {
                if ($left <= 0.0005) break;
                $qty = floor(min($left, $t['need']) * 1000) / 1000;
                if ($qty < 0.5 && $left >= 0.5) $qty = min($left, 1.0);   // packs move in whole units where possible
                if ($qty <= 0.0005) continue;
                $plan[] = ['to_hub' => $t['id'], 'to' => $t['hub'], 'km' => $t['km'], 'qty' => round($qty, 3)];
                $left = round($left - $qty, 3);
            }
            $rows[] = ['code' => $c, 'id' => $n['id'], 'name' => $n['name'], 'pack' => $n['pack'], 'fresh' => $a['fresh'], 'danger' => $a['danger'], 'expired' => $a['expired'],
                'oldest' => $a['oldest'], 'per_day' => $perDay, 'rest_today' => $rest, 'over' => $over, 'plan' => $plan, 'no_taker' => $left,
                'advice' => $a['expired'] > 0 ? 'Expired: write it off (or move it today if still good).'
                    : ($over <= 0 ? 'Will sell today at the normal pace.' : ($left > 0 ? 'Sell at a discount today; write off what is left at closing.' : 'Send to the planned hub.'))];
        }
        usort($rows, fn ($x, $y) => [$y['expired'] > 0, $y['over']] <=> [$x['expired'] > 0, $x['over']]);
        $run = HubStock::has('hub_daily_runs') ? DB::table('hub_daily_runs')->where('hub_id', $this->hubId)->where('run_date', date('Y-m-d'))->where('kind', 'danger_plan')->first() : null;
        return ['ok' => true, 'rows' => $rows, 'rest_share' => $share, 'nearby' => array_map(fn ($n) => ['hub' => $n['hub'], 'km' => $n['km']], $near),
            'plan_time' => (string) config('hub_console.danger_plan_time', '11:00'), 'plan_ran_at' => $run ? strtotime((string) $run->created_at) : null,
            'totals' => ['danger' => round(array_sum(array_column($rows, 'danger')), 3), 'expired' => round(array_sum(array_column($rows, 'expired')), 3), 'over' => round(array_sum(array_column($rows, 'over')), 3)]];
    }

    /**
     * Create today's planned transfers (once per hub per day, from the plan time onwards). $force = recalculate now:
     * today's plan transfers that are still only "planned" are replaced. Dispatched ones are never touched.
     */
    public function runDaily(bool $force = false, ?array $actor = null): array
    {
        if (!HubStock::lotsOn() || !HubStock::has('hub_daily_runs') || !HubStock::has('hub_transfers')) return ['ok' => false, 'message' => 'Not installed.'];
        $at = (string) config('hub_console.danger_plan_time', '11:00');
        if (!$force && date('H:i') < $at) return ['ok' => true, 'ran' => false, 'message' => 'The plan runs at ' . $at . '.'];
        $today = date('Y-m-d');
        if (!$force && DB::table('hub_daily_runs')->where('hub_id', $this->hubId)->where('run_date', $today)->where('kind', 'danger_plan')->exists()) return ['ok' => true, 'ran' => false];
        $actor = $actor ?: ['id' => null, 'name' => 'System (' . $at . ' plan)'];
        $st = $this->status();
        if (!$st['ok']) return $st;
        $byHub = [];
        foreach ($st['rows'] as $r) foreach ($r['plan'] as $p) if ($r['id']) $byHub[$p['to_hub']][] = ['product_id' => $r['id'], 'code' => $r['code'], 'name' => $r['name'], 'pack' => $r['pack'], 'qty' => $p['qty']];
        $made = [];
        try {
            DB::transaction(function () use ($force, $today, $byHub, $actor, &$made) {
                if ($force) {
                    $old = DB::table('hub_transfers')->where('from_hub_id', $this->hubId)->where('kind', 'plan')->where('status', 'planned')->whereDate('created_at', $today)->pluck('id');
                    if ($old->count()) DB::table('hub_transfers')->whereIn('id', $old)->update(['status' => 'cancelled', 'close_note' => 'Replaced by a recalculated plan', 'closed_at' => now(), 'updated_at' => now()]);
                    DB::table('hub_daily_runs')->where('hub_id', $this->hubId)->where('run_date', $today)->where('kind', 'danger_plan')->delete();
                }
                DB::table('hub_daily_runs')->insert(['hub_id' => $this->hubId, 'run_date' => $today, 'kind' => 'danger_plan', 'summary' => json_encode(['hubs' => count($byHub)]), 'created_at' => now()]);
                foreach ($byHub as $to => $lines) {
                    do { $ref = 'IH' . date('ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 5)); } while (DB::table('hub_transfers')->where('ref', $ref)->exists());
                    $head = ['ref' => $ref, 'kind' => 'plan', 'from_hub_id' => $this->hubId, 'to_hub_id' => $to, 'status' => 'planned', 'reason' => 'danger',
                        'instruction' => 'Danger stock: received yesterday, must be sold today.', 'created_by_id' => $actor['id'] ?? null, 'created_by' => mb_substr((string) ($actor['name'] ?? ''), 0, 120),
                        'created_at' => now(), 'updated_at' => now()];
                    if (HubStock::has('hub_transfers', 'meet_point')) { [$mp, $la, $lo] = HubTransferService::meetPoint($this->hubId, (int) $to); $head += ['meet_point' => $mp, 'meet_lat' => $la, 'meet_lng' => $lo]; }
                    $id = DB::table('hub_transfers')->insertGetId($head);
                    foreach ($lines as $l) DB::table('hub_transfer_items')->insert(['transfer_id' => $id, 'product_code' => $l['code'], 'product_name' => mb_substr($l['name'], 0, 160), 'pack' => mb_substr($l['pack'], 0, 40), 'requested_qty' => $l['qty']]);
                    $made[] = $ref;
                }
            });
        } catch (\Illuminate\Database\QueryException $e) {
            return ['ok' => true, 'ran' => false];   // another request created today's plan at the same moment
        }
        Log::info('[freshful-audit] hub-danger-plan', ['hub' => $this->hubId, 'transfers' => $made, 'force' => $force]);
        return ['ok' => true, 'ran' => true, 'transfers' => $made, 'message' => $made ? count($made) . ' transfer(s) planned: ' . implode(', ', $made) . '. Check them in Transfer danger stock and dispatch.' : 'No danger stock needs to move today.'];
    }

    /** Write off stock that cannot be sold (expired / spoiled). Oldest stock is taken first. */
    public function writeOff(int $productId, float $qty, string $reason, array $actor): array
    {
        if (trim($reason) === '') return ['ok' => false, 'message' => 'Write the reason (expired, spoiled, damaged).'];
        if ($qty <= 0 || $qty > 100000) return ['ok' => false, 'message' => 'Enter the quantity to write off.'];
        $code = (string) DB::table('products')->where('id', $productId)->value('product_id');
        if ($code === '') return ['ok' => false, 'message' => 'Product not found.'];
        $res = DB::transaction(function () use ($code, $qty, $reason, $actor) {
            $live = (float) (DB::table('inventory')->where('product_id', $code)->where('hub_id', $this->hubId)->lockForUpdate()->value('live_stock') ?? 0);
            if ($qty > $live + 0.0005) return ['ok' => false, 'message' => 'Only ' . self::q($live) . ' in stock.'];
            [, $after, $lots] = HubStock::adjust($this->hubId, $code, -$qty, 'wastage', null, 'Written off: ' . trim($reason), $actor);
            return ['ok' => true, 'message' => self::q($qty) . ' written off as wastage. Live stock is now ' . self::q($after) . '.'];
        });
        if ($res['ok']) Log::info('[freshful-audit] hub-wastage', ['hub' => $this->hubId, 'product' => $productId, 'qty' => $qty, 'by' => $actor['id'] ?? null]);
        return $res;
    }
}
