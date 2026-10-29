<?php

namespace App\Services\CountryConsole;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Monthly sales / order targets for All India, a state, a city or a hub (table country_targets, 22 Oct 2026).
 * Progress = month-to-date actual vs target; projection = month-to-date / days passed x days in the month.
 * Pace = projection as % of target (100 = on track).
 */
class CountryTargets
{
    public const SCOPES = ['india' => 'All India', 'state' => 'State', 'city' => 'City', 'hub' => 'Hub'];

    public static function on(): bool { return CountryScope::has('country_targets'); }

    private static function label(string $scope, int $id): string
    {
        if ($scope === 'india') return 'All India';
        foreach (CountryScope::hubs() as $h) {
            if ($scope === 'hub' && $h['id'] === $id) return $h['name'] . ($h['city'] ? ', ' . $h['city'] : '');
            if ($scope === 'city' && $h['city_id'] === $id) return $h['city'] ?: 'City ' . $id;
            if ($scope === 'state' && $h['state_id'] === $id) return $h['state'] ?: 'State ' . $id;
        }
        return self::SCOPES[$scope] . ' #' . $id;
    }

    private static function hubIds(string $scope, int $id): array
    {
        return (new CountryScope($scope === 'hub' ? ['hub' => $id] : ($scope === 'city' ? ['city' => $id] : ($scope === 'state' ? ['state' => $id] : []))))->ids();
    }

    /** Progress of one target row for its month. */
    private static function progress(object $t): array
    {
        $month = (string) $t->month; $start = $month . '-01';
        $dim = (int) date('t', strtotime($start)); $end = date('Y-m-t', strtotime($start));
        $cur = date('Y-m');
        $passed = $month < $cur ? $dim : ($month > $cur ? 0 : (int) date('j'));
        $toDay = $month === $cur ? date('Y-m-d') : $end;
        $rows = $passed > 0 ? CountryAnalytics::orderRows(self::hubIds((string) $t->scope, (int) $t->scope_id), $start . ' 00:00:00', $toDay . ' 23:59:59') : [];
        $sales = 0.0; $orders = 0;
        foreach ($rows as $r) if ($r['stage'] !== 'cancelled') { $sales += $r['value']; $orders++; }
        // Today is only partly over: count it as a part day so the projection is not too low in the morning.
        $daysDone = $month === $cur ? max(0.25, $passed - 1 + (time() - strtotime('today')) / 86400) : $passed;
        $proj = $daysDone > 0 ? $sales / $daysDone * $dim : 0.0; $projO = $daysDone > 0 ? $orders / $daysDone * $dim : 0.0;
        $st = (float) $t->sales_target; $ot = (int) $t->orders_target;
        return ['id' => (int) $t->id, 'scope' => (string) $t->scope, 'scope_id' => (int) $t->scope_id, 'label' => self::label((string) $t->scope, (int) $t->scope_id), 'month' => $month,
            'sales_target' => $st, 'orders_target' => $ot, 'sales' => round($sales, 2), 'orders' => $orders, 'days_passed' => $passed, 'days' => $dim,
            'sales_pct' => $st > 0 ? round($sales * 100 / $st, 1) : null, 'orders_pct' => $ot > 0 ? round($orders * 100 / $ot, 1) : null,
            'projected' => round($proj, 2), 'projected_orders' => (int) round($projO),
            'pace' => $st > 0 && $passed > 0 ? (int) round($proj * 100 / $st) : null, 'needed_per_day' => $st > 0 && $dim - $passed > 0 ? round(max(0, $st - $sales) / ($dim - $passed + ($month === $cur ? 1 : 0)), 2) : null,
            'note' => (string) ($t->note ?? ''), 'by' => (string) ($t->created_by ?? '')];
    }

    public static function list(string $month): array
    {
        if (!self::on()) return ['ok' => true, 'available' => false, 'rows' => []];
        if (!preg_match('/^\d{4}-\d{2}$/', $month)) $month = date('Y-m');
        $list = DB::table('country_targets')->where('month', $month)->orderByRaw("FIELD(scope, 'india', 'state', 'city', 'hub')")->orderBy('scope_id')->get()->all();
        // Area / operation managers see only targets for their own hubs, cities or states.
        if (CountryScope::$allowed !== null) $list = array_values(array_filter($list, fn ($t) => $t->scope !== 'india' && self::hubIds((string) $t->scope, (int) $t->scope_id)));
        $rows = array_map(fn ($t) => self::progress($t), $list);
        return ['ok' => true, 'available' => true, 'month' => $month, 'rows' => $rows, 'scopes' => self::SCOPES];
    }

    /** Targets that match the current filter (for the command centre). */
    public static function progressFor(CountryScope $s): array
    {
        if (!self::on()) return [];
        if (CountryScope::$allowed !== null && !$s->hubId && !$s->city && !$s->state) return [];
        [$scope, $id] = $s->hubId ? ['hub', $s->hubId] : ($s->city ? ['city', $s->city] : ($s->state ? ['state', $s->state] : ['india', 0]));
        $t = DB::table('country_targets')->where('month', date('Y-m'))->where('scope', $scope)->where('scope_id', $id)->first();
        return $t ? [self::progress($t)] : [];
    }

    public static function save(string $scope, int $scopeId, string $month, float $sales, int $orders, string $note, array $actor): array
    {
        if (!self::on()) return ['ok' => false, 'message' => 'Targets are not installed.'];
        if (!isset(self::SCOPES[$scope])) return ['ok' => false, 'message' => 'Choose All India, a state, a city or a hub.'];
        if (!preg_match('/^\d{4}-\d{2}$/', $month) || $month < '2020-01' || $month > date('Y-m', strtotime('+13 months'))) return ['ok' => false, 'message' => 'Choose the month.'];
        if ($scope === 'india') $scopeId = 0;
        elseif ($scopeId <= 0 || !self::hubIds($scope, $scopeId)) return ['ok' => false, 'message' => 'Choose which ' . strtolower(self::SCOPES[$scope]) . '.'];
        if ($sales < 0 || $sales > 1e10 || $orders < 0 || $orders > 10000000 || ($sales <= 0 && $orders <= 0)) return ['ok' => false, 'message' => 'Enter a sales target and/or an orders target.'];
        $row = ['sales_target' => round($sales, 2), 'orders_target' => $orders, 'note' => mb_substr(trim(strip_tags($note)), 0, 250) ?: null, 'created_by_id' => $actor['id'] ?? null, 'created_by' => mb_substr((string) ($actor['name'] ?? ''), 0, 120), 'updated_at' => now()];
        $ex = DB::table('country_targets')->where('scope', $scope)->where('scope_id', $scopeId)->where('month', $month)->first();
        if ($ex) DB::table('country_targets')->where('id', $ex->id)->update($row);
        else DB::table('country_targets')->insert($row + ['scope' => $scope, 'scope_id' => $scopeId, 'month' => $month, 'created_at' => now()]);
        Log::info('[freshful-audit] country-target', ['scope' => $scope, 'id' => $scopeId, 'month' => $month, 'sales' => $sales, 'orders' => $orders, 'by' => $actor['id'] ?? null]);
        return ['ok' => true, 'message' => 'Target saved for ' . self::label($scope, $scopeId) . ', ' . date('M Y', strtotime($month . '-01')) . '.'];
    }

    public static function delete(int $id, array $actor): array
    {
        if (!self::on()) return ['ok' => false, 'message' => 'Targets are not installed.'];
        $n = DB::table('country_targets')->where('id', $id)->delete();
        if ($n) Log::info('[freshful-audit] country-target-delete', ['id' => $id, 'by' => $actor['id'] ?? null]);
        return ['ok' => (bool) $n, 'message' => $n ? 'Target removed.' : 'Target not found.'];
    }
}
