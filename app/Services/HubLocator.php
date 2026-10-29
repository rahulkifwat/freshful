<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * Freshful - which hub delivers to a map point (21 Oct 2026).
 *
 * Uses the delivery areas drawn for each hub (hubs.all_latlng):
 *   type "polygon": JSON list of {lat, lng} corners  -> point must be inside the shape
 *   type "circle":  "(lat, lng)" or "(lat, lng, km)" -> point within the radius (default config hub_circle_km)
 * Order of choice: inside a drawn area (smallest area wins when areas overlap) > inside a circle (nearest wins)
 * > nearest active hub within hub_fallback_km (the old rule, so customers outside drawn areas are not lost).
 */
class HubLocator
{
    public static function locate(float $lat, float $lng): ?object
    {
        if (!$lat && !$lng) return null;
        $hubs = DB::table('hubs')->whereRaw("LOWER(CAST(status AS CHAR)) IN ('active', '1')")->get();
        $inPolygon = []; $inCircle = []; $nearest = null;
        $circleKm = (float) config('freshful.hub_circle_km', 3);
        $fallbackKm = (float) config('freshful.hub_fallback_km', 100);

        foreach ($hubs as $h) {
            $dist = (is_numeric($h->hub_lat) && is_numeric($h->hub_lng)) ? self::km($lat, $lng, (float) $h->hub_lat, (float) $h->hub_lng) : null;
            $area = self::parseArea((string) ($h->all_latlng ?? ''));
            if ($area['type'] === 'polygon' && self::inPolygon($lat, $lng, $area['points'])) {
                $inPolygon[] = [$h, self::polygonArea($area['points']), $dist];
            } elseif ($area['type'] === 'circle') {
                $d = self::km($lat, $lng, $area['lat'], $area['lng']);
                if ($d <= ($area['km'] ?? $circleKm)) $inCircle[] = [$h, $d];
            }
            if ($dist !== null && $dist <= $fallbackKm && (!$nearest || $dist < $nearest[1])) $nearest = [$h, $dist];
        }
        if ($inPolygon) {
            usort($inPolygon, fn ($a, $b) => $a[1] <=> $b[1]);
            return self::tag($inPolygon[0][0], 'area', $inPolygon[0][2]);
        }
        if ($inCircle) {
            usort($inCircle, fn ($a, $b) => $a[1] <=> $b[1]);
            return self::tag($inCircle[0][0], 'circle', $inCircle[0][1]);
        }
        return $nearest ? self::tag($nearest[0], 'nearest', $nearest[1]) : null;
    }

    private static function tag(object $hub, string $how, ?float $km): object
    {
        $hub->matched_by = $how;
        $hub->distance_in_km = $km !== null ? round($km, 2) : null;
        return $hub;
    }

    /** ['type' => 'polygon', 'points' => [[lat, lng], ...]] | ['type' => 'circle', 'lat', 'lng', 'km'] | ['type' => 'none'] */
    public static function parseArea(string $raw): array
    {
        $raw = trim($raw);
        if ($raw === '') return ['type' => 'none'];
        if ($raw[0] === '[') {
            $j = json_decode($raw, true);
            $pts = [];
            foreach ((array) $j as $p) {
                if (is_array($p) && isset($p['lat'], $p['lng']) && is_numeric($p['lat']) && is_numeric($p['lng'])) $pts[] = [(float) $p['lat'], (float) $p['lng']];
            }
            return count($pts) >= 3 ? ['type' => 'polygon', 'points' => $pts] : ['type' => 'none'];
        }
        if (preg_match('/^\(?\s*(-?\d+(?:\.\d+)?)\s*,\s*(-?\d+(?:\.\d+)?)\s*(?:,\s*(\d+(?:\.\d+)?))?\s*\)?$/', $raw, $m)) {
            return ['type' => 'circle', 'lat' => (float) $m[1], 'lng' => (float) $m[2], 'km' => isset($m[3]) ? (float) $m[3] : null];
        }
        return ['type' => 'none'];
    }

    /** Ray casting; lng is x, lat is y. Points on the edge count as inside. */
    public static function inPolygon(float $lat, float $lng, array $pts): bool
    {
        $inside = false; $n = count($pts);
        for ($i = 0, $j = $n - 1; $i < $n; $j = $i++) {
            [$yi, $xi] = $pts[$i]; [$yj, $xj] = $pts[$j];
            if (abs(($lng - $xi) * ($yj - $yi) - ($lat - $yi) * ($xj - $xi)) < 1e-12
                && $lng >= min($xi, $xj) && $lng <= max($xi, $xj) && $lat >= min($yi, $yj) && $lat <= max($yi, $yj)) return true;
            if ((($yi > $lat) !== ($yj > $lat)) && ($lng < ($xj - $xi) * ($lat - $yi) / (($yj - $yi) ?: 1e-12) + $xi)) $inside = !$inside;
        }
        return $inside;
    }

    private static function polygonArea(array $pts): float
    {
        $a = 0.0; $n = count($pts);
        for ($i = 0, $j = $n - 1; $i < $n; $j = $i++) $a += ($pts[$j][1] + $pts[$i][1]) * ($pts[$j][0] - $pts[$i][0]);
        return abs($a / 2);
    }

    public static function km(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $r = 6371.0; $dLat = deg2rad($lat2 - $lat1); $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;
        return 2 * $r * asin(min(1.0, sqrt($a)));
    }
}
