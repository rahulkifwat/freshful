<?php

namespace App\Services\CountryConsole;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Freshful country console (22 Oct 2026): which hubs a screen covers.
 * Every screen takes the same filters: state, city, hub, hub type and hub status, plus a date range.
 * Hubs -> cities (cities.state_id) -> states. The states table is optional: without it the state filter is hidden.
 */
class CountryScope
{
    private static array $cols = [];
    private static ?array $hubs = null;

    public static function has(string $t, ?string $c = null): bool
    {
        $k = $t . '.' . $c;
        if (!array_key_exists($k, self::$cols)) {
            try { self::$cols[$k] = $c === null ? Schema::hasTable($t) : Schema::hasColumn($t, $c); } catch (\Throwable $e) { self::$cols[$k] = false; }
        }
        return self::$cols[$k];
    }

    public static function ts($v): ?int
    {
        if (!$v) return null;
        $t = strtotime((string) $v);
        return ($t !== false && $t > 946684800) ? $t : null;
    }

    public static function q($n): string
    {
        return rtrim(rtrim(number_format((float) $n, 3, '.', ''), '0'), '.');
    }

    public static function isActive($status): bool
    {
        return in_array(strtolower(trim((string) $status)), ['active', '1'], true);
    }

    /** Name column of the states table, or null. */
    public static function stateNameCol(): ?string
    {
        if (!self::has('states')) return null;
        foreach (['name', 'state', 'state_name', 'title'] as $c) if (self::has('states', $c)) return $c;
        return null;
    }

    /** Every hub with its city and state: [id => [...]] (once per request). */
    public static function hubs(): array
    {
        if (self::$hubs !== null) return self::$hubs;
        $q = DB::table('hubs AS h')->select('h.id', 'h.hub', 'h.city_id', 'h.status');
        foreach (['type', 'hub_lat', 'hub_lng'] as $c) if (self::has('hubs', $c)) $q->addSelect('h.' . $c);
        $withCity = self::has('cities');
        if ($withCity) {
            $q->leftJoin('cities AS c', 'c.id', '=', 'h.city_id')->addSelect('c.city');
            if (self::has('cities', 'state_id')) {
                $q->addSelect('c.state_id');
                $sn = self::stateNameCol();
                if ($sn) $q->leftJoin('states AS s', 's.id', '=', 'c.state_id')->addSelect('s.' . $sn . ' AS state');
            }
        }
        $out = [];
        foreach ($q->orderBy('h.hub')->get() as $h) {
            $out[(int) $h->id] = [
                'id' => (int) $h->id, 'name' => trim((string) $h->hub) ?: ('Hub ' . $h->id),
                'city_id' => (int) ($h->city_id ?? 0), 'city' => trim((string) ($h->city ?? '')),
                'state_id' => (int) ($h->state_id ?? 0), 'state' => trim((string) ($h->state ?? '')),
                'type' => trim((string) ($h->type ?? '')), 'active' => self::isActive($h->status),
                'lat' => is_numeric($h->hub_lat ?? null) ? (float) $h->hub_lat : null, 'lng' => is_numeric($h->hub_lng ?? null) ? (float) $h->hub_lng : null,
            ];
        }
        return self::$hubs = $out;
    }

    public static function hub(int $id): ?array
    {
        return self::hubs()[$id] ?? null;
    }

    public static function forget(): void { self::$hubs = null; }

    /* ------------------------------------------------------------------ instance: one filter set */

    public int $state = 0;
    public int $city = 0;
    public int $hubId = 0;
    public string $type = '';
    public string $status = 'all';   // all | active | inactive

    public function __construct(array $f = [])
    {
        $this->state = max(0, (int) ($f['state'] ?? 0));
        $this->city = max(0, (int) ($f['city'] ?? 0));
        $this->hubId = max(0, (int) ($f['hub'] ?? 0));
        $this->type = mb_substr(trim((string) ($f['htype'] ?? '')), 0, 40);
        $st = (string) ($f['hstatus'] ?? 'all');
        $this->status = in_array($st, ['all', 'active', 'inactive'], true) ? $st : 'all';
    }

    /** Hub ids covered by the filters. */
    public function ids(): array
    {
        $out = [];
        foreach (self::hubs() as $h) {
            if ($this->hubId && $h['id'] !== $this->hubId) continue;
            if ($this->city && $h['city_id'] !== $this->city) continue;
            if ($this->state && $h['state_id'] !== $this->state) continue;
            if ($this->type !== '' && strcasecmp($h['type'], $this->type) !== 0) continue;
            if ($this->status === 'active' && !$h['active']) continue;
            if ($this->status === 'inactive' && $h['active']) continue;
            $out[] = $h['id'];
        }
        return $out;
    }

    public function label(): string
    {
        if ($this->hubId && ($h = self::hub($this->hubId))) return $h['name'] . ($h['city'] ? ', ' . $h['city'] : '');
        $hubs = self::hubs();
        if ($this->city) { foreach ($hubs as $h) if ($h['city_id'] === $this->city) return $h['city'] . ($h['state'] ? ', ' . $h['state'] : ''); return 'City #' . $this->city; }
        if ($this->state) { foreach ($hubs as $h) if ($h['state_id'] === $this->state && $h['state'] !== '') return $h['state']; return 'State #' . $this->state; }
        return 'All India' . ($this->type !== '' ? ' (' . $this->type . ')' : '');
    }

    /** Options for the filter bar. */
    public static function options(): array
    {
        $hubs = self::hubs();
        $states = []; $cities = []; $types = [];
        foreach ($hubs as $h) {
            if ($h['state_id']) $states[$h['state_id']] = ['id' => $h['state_id'], 'name' => $h['state'] !== '' ? $h['state'] : 'State ' . $h['state_id']];
            if ($h['city_id']) $cities[$h['city_id']] = ['id' => $h['city_id'], 'name' => $h['city'] !== '' ? $h['city'] : 'City ' . $h['city_id'], 'state_id' => $h['state_id']];
            if ($h['type'] !== '') $types[strtolower($h['type'])] = $h['type'];
        }
        $sort = fn ($a, $b) => strcasecmp($a['name'], $b['name']);
        $states = array_values($states); usort($states, $sort);
        $cities = array_values($cities); usort($cities, $sort);
        return ['states' => self::stateNameCol() ? $states : [], 'cities' => $cities, 'types' => array_values($types),
            'hubs' => array_values(array_map(fn ($h) => ['id' => $h['id'], 'name' => $h['name'], 'city_id' => $h['city_id'], 'state_id' => $h['state_id'], 'city' => $h['city'], 'type' => $h['type'], 'active' => $h['active']], $hubs))];
    }

    /* ------------------------------------------------------------------ dates */

    /** [from, to] as Y-m-d, valid, from <= to, at most max_days long. Default: today. */
    public static function range(?string $from, ?string $to, string $default = 'today'): array
    {
        $ok = fn ($d) => is_string($d) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $d) && strtotime($d) !== false;
        $t = date('Y-m-d');
        $f = $ok($from) ? $from : ($default === '30' ? date('Y-m-d', strtotime('-29 days')) : ($default === '7' ? date('Y-m-d', strtotime('-6 days')) : $t));
        $e = $ok($to) ? $to : $t;
        if ($f > $e) [$f, $e] = [$e, $f];
        $max = (int) config('country_console.max_days', 400);
        if ((strtotime($e) - strtotime($f)) / 86400 > $max) $f = date('Y-m-d', strtotime($e . ' -' . $max . ' days'));
        return [$f, $e];
    }
}
