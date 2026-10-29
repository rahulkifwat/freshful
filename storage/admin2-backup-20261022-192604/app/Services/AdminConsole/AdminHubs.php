<?php

namespace App\Services\AdminConsole;

use App\Services\CountryConsole\CountryScope;
use Illuminate\Support\Facades\DB;

/**
 * Freshful admin console (admin update 1, 22 Oct 2026): hubs and their delivery areas.
 * Saved in the old format, which the website, the app and the hub finder read:
 *   type polygon: all_latlng = [{"lat":..,"lng":..}, ...]      type circle: all_latlng = "(lat, lng)" (centre; radius = hub finder default)
 * Fix: the old admin saved the FIRST CORNER of a drawn area as the hub's location (hub_lat / hub_lng), so distances
 * from the hub were wrong. Here the hub location is its own pin (default: the middle of the area).
 */
class AdminHubs
{
    private static function h(string $t, ?string $c = null): bool { return CountryScope::has($t, $c); }

    private static function inIndia(float $lat, float $lng): bool { return $lat >= 6 && $lat <= 37.5 && $lng >= 68 && $lng <= 98; }

    /** Parse all_latlng in either old format. */
    public static function shape(?string $type, ?string $raw): array
    {
        $raw = trim((string) $raw);
        if ($raw === '') return ['kind' => null, 'points' => []];
        $j = json_decode($raw, true);
        if (is_array($j)) {
            $pts = [];
            foreach ($j as $p) {
                if (is_array($p) && isset($p['lat'], $p['lng']) && is_numeric($p['lat']) && is_numeric($p['lng'])) $pts[] = [(float) $p['lat'], (float) $p['lng']];
                elseif (is_array($p) && isset($p[0], $p[1]) && is_numeric($p[0]) && is_numeric($p[1])) $pts[] = [(float) $p[0], (float) $p[1]];
            }
            if (count($pts) >= 3) return ['kind' => 'polygon', 'points' => $pts];
            if (count($pts) === 1) return ['kind' => 'circle', 'points' => $pts];
        }
        if (preg_match('/^\(?\s*(-?\d+(?:\.\d+)?)\s*,\s*(-?\d+(?:\.\d+)?)\s*\)?$/', $raw, $m)) return ['kind' => 'circle', 'points' => [[(float) $m[1], (float) $m[2]]]];
        return ['kind' => null, 'points' => [], 'bad' => true];
    }

    /** Middle of a polygon (area centroid; falls back to the average of the corners). */
    public static function centre(array $pts): array
    {
        $n = count($pts); if (!$n) return [0, 0];
        $a = 0; $cx = 0; $cy = 0;
        for ($i = 0; $i < $n; $i++) {
            [$y1, $x1] = $pts[$i]; [$y2, $x2] = $pts[($i + 1) % $n];
            $f = $x1 * $y2 - $x2 * $y1; $a += $f; $cx += ($x1 + $x2) * $f; $cy += ($y1 + $y2) * $f;
        }
        if (abs($a) < 1e-12) return [array_sum(array_column($pts, 0)) / $n, array_sum(array_column($pts, 1)) / $n];
        return [$cy / (3 * $a), $cx / (3 * $a)];
    }

    private static function inside(array $pts, float $lat, float $lng): bool
    {
        $in = false; $n = count($pts);
        for ($i = 0, $j = $n - 1; $i < $n; $j = $i++) {
            [$yi, $xi] = $pts[$i]; [$yj, $xj] = $pts[$j];
            if ((($yi > $lat) !== ($yj > $lat)) && ($lng < ($xj - $xi) * ($lat - $yi) / (($yj - $yi) ?: 1e-12) + $xi)) $in = !$in;
        }
        return $in;
    }

    public static function list(): array
    {
        $cities = DB::table('cities')->pluck('city', 'id')->all();
        $users = [];
        foreach (['hub_user' => 'hub', 'pos_user' => 'pos'] as $t => $k) if (self::h($t, 'hub_id')) foreach (DB::table($t)->select('hub_id', DB::raw('COUNT(*) AS n'))->groupBy('hub_id')->get() as $r) $users[(int) $r->hub_id][$k] = (int) $r->n;
        $riders = [];
        foreach (['delivery_boy', 'riders', 'drivers'] as $t) { if (self::h($t, 'hub_id')) { foreach (DB::table($t)->select('hub_id', DB::raw('COUNT(*) AS n'))->groupBy('hub_id')->get() as $r) $riders[(int) $r->hub_id] = (int) $r->n; break; } }
        $addr = self::h('buyer_address', 'hub_id') ? DB::table('buyer_address')->select('hub_id', DB::raw('COUNT(*) AS n'))->groupBy('hub_id')->pluck('n', 'hub_id')->all() : [];
        $price = self::h('delivery_price') ? DB::table('delivery_price')->pluck('price', 'city')->all() : [];
        $rows = [];
        foreach (DB::table('hubs')->orderBy('hub')->get() as $h) {
            $s = self::shape($h->type ?? null, $h->all_latlng ?? null);
            $lat = is_numeric($h->hub_lat) ? (float) $h->hub_lat : null; $lng = is_numeric($h->hub_lng) ? (float) $h->hub_lng : null;
            $issues = [];
            if (!$s['kind']) $issues[] = 'No delivery area';
            if ($lat === null || $lng === null || !self::inIndia($lat, $lng)) $issues[] = 'No hub location';
            if ($s['kind'] === 'polygon' && $lat !== null && $lng !== null) {
                $first = $s['points'][0];
                if (abs($first[0] - $lat) < 1e-7 && abs($first[1] - $lng) < 1e-7) $issues[] = 'Location is a corner of the area (old admin)';
                elseif (!self::inside($s['points'], $lat, $lng)) $issues[] = 'Location is outside its area';
            }
            if (!isset($price[(string) $h->city_id])) $issues[] = 'No delivery charge for its city';
            $rows[] = ['id' => (int) $h->id, 'name' => (string) $h->hub, 'city_id' => (int) $h->city_id, 'city' => (string) ($cities[(int) $h->city_id] ?? ''), 'type' => (string) ($h->type ?? ''),
                'kind' => $s['kind'], 'points' => $s['points'], 'lat' => $lat, 'lng' => $lng, 'active' => AdminRecords::isOn($h->status), 'issues' => $issues,
                'hub_users' => $users[(int) $h->id]['hub'] ?? 0, 'pos_users' => $users[(int) $h->id]['pos'] ?? 0, 'riders' => $riders[(int) $h->id] ?? 0, 'addresses' => (int) ($addr[(int) $h->id] ?? 0),
                'centre' => $s['kind'] === 'polygon' ? self::centre($s['points']) : null];
        }
        return ['ok' => true, 'rows' => $rows, 'cities' => DB::table('cities')->orderBy('city')->get(['id', 'city', 'status'])->map(fn ($c) => ['id' => (int) $c->id, 'name' => (string) $c->city, 'active' => AdminRecords::isOn($c->status)])->all(),
            'circle_km' => (float) config('admin_console.circle_km', 3)];
    }

    public static function save(?int $id, array $in, array $actor): array
    {
        $old = $id ? DB::table('hubs')->where('id', $id)->first() : null;
        if ($id && !$old) return ['ok' => false, 'message' => 'Hub not found.'];
        $name = trim((string) ($in['name'] ?? ''));
        if ($name === '' || mb_strlen($name) > 100) return ['ok' => false, 'message' => 'Enter the hub name.'];
        $dup = DB::table('hubs')->whereRaw('LOWER(TRIM(hub)) = ?', [mb_strtolower($name)]); if ($old) $dup->where('id', '!=', $id);
        if ($dup->exists()) return ['ok' => false, 'message' => 'A hub called "' . $name . '" already exists.'];
        $city = (int) ($in['city_id'] ?? 0);
        if (!DB::table('cities')->where('id', $city)->exists()) return ['ok' => false, 'message' => 'Choose the city.'];
        $kind = (string) ($in['kind'] ?? '');
        $pts = [];
        foreach ((array) ($in['points'] ?? []) as $p) {
            $la = is_array($p) ? ($p[0] ?? $p['lat'] ?? null) : null; $ln = is_array($p) ? ($p[1] ?? $p['lng'] ?? null) : null;
            if (!is_numeric($la) || !is_numeric($ln) || !self::inIndia((float) $la, (float) $ln)) return ['ok' => false, 'message' => 'A point of the delivery area is outside India. Draw it again.'];
            $pts[] = [round((float) $la, 7), round((float) $ln, 7)];
        }
        $lat = $in['lat'] ?? null; $lng = $in['lng'] ?? null;
        if ($kind === 'polygon') {
            if (count($pts) < 3) return ['ok' => false, 'message' => 'Draw the delivery area with at least 3 corners.'];
            if (count($pts) > 500) return ['ok' => false, 'message' => 'The delivery area has too many corners (at most 500).'];
            if (!is_numeric($lat) || !is_numeric($lng)) [$lat, $lng] = self::centre($pts);
            if (!self::inside($pts, (float) $lat, (float) $lng) && empty($in['confirm_outside'])) return ['ok' => false, 'message' => 'The hub pin is outside its delivery area. Move the pin inside, or tick "the hub is outside its area".'];
            $all = json_encode(array_map(fn ($p) => ['lat' => $p[0], 'lng' => $p[1]], $pts));
        } elseif ($kind === 'circle') {
            if (!is_numeric($lat) || !is_numeric($lng)) { if (!$pts) return ['ok' => false, 'message' => 'Put the hub pin on the map.']; [$lat, $lng] = $pts[0]; }
            $all = '(' . round((float) $lat, 7) . ', ' . round((float) $lng, 7) . ')';
        } else return ['ok' => false, 'message' => 'Choose how the delivery area is set: drawn area or circle.'];
        if (!self::inIndia((float) $lat, (float) $lng)) return ['ok' => false, 'message' => 'The hub pin is outside India.'];
        $v = ['hub' => $name, 'city_id' => $city, 'type' => $kind, 'all_latlng' => $all, 'hub_lat' => (string) round((float) $lat, 7), 'hub_lng' => (string) round((float) $lng, 7)];
        if (array_key_exists('active', $in)) { [$on, $off] = AdminRecords::statusValues('hubs'); $v['status'] = filter_var($in['active'], FILTER_VALIDATE_BOOLEAN) ? $on : $off; }
        elseif (!$old) $v['status'] = AdminRecords::statusValues('hubs')[1]; // new hubs start switched off until checked
        try { if ($old) DB::table('hubs')->where('id', $id)->update($v); else $id = (int) DB::table('hubs')->insertGetId($v); }
        catch (\Throwable $e) { return ['ok' => false, 'message' => 'Could not save: ' . AdminRecords::dbError($e)]; }
        $new = DB::table('hubs')->where('id', $id)->first();
        AdminAudit::log($actor, $old ? 'edit' : 'add', 'Locations', 'hubs', $id, ($old ? 'Edited hub ' : 'Added hub ') . $name, $old, $new);
        CountryScope::forget();
        return ['ok' => true, 'id' => $id, 'message' => $old ? 'Hub saved.' : 'Hub added. It is switched off: add its staff and stock, then switch it on to take orders.'];
    }

    public static function toggle(int $id, array $actor): array
    {
        $old = DB::table('hubs')->where('id', $id)->first();
        if (!$old) return ['ok' => false, 'message' => 'Hub not found.'];
        [$on, $off] = AdminRecords::statusValues('hubs');
        $to = AdminRecords::isOn($old->status) ? $off : $on;
        if (AdminRecords::isOn($to) && !self::shape($old->type ?? null, $old->all_latlng ?? null)['kind']) return ['ok' => false, 'message' => 'Draw the delivery area before switching the hub on.'];
        DB::table('hubs')->where('id', $id)->update(['status' => $to]);
        AdminAudit::log($actor, AdminRecords::isOn($to) ? 'switch on' : 'switch off', 'Locations', 'hubs', $id, (AdminRecords::isOn($to) ? 'Opened hub ' : 'Closed hub ') . $old->hub, $old, DB::table('hubs')->where('id', $id)->first());
        CountryScope::forget();
        return ['ok' => true, 'on' => AdminRecords::isOn($to), 'message' => $old->hub . (AdminRecords::isOn($to) ? ' is open for orders.' : ' is closed: new orders will not go to it.')];
    }
}
