<?php

namespace App\Services\BuyerApp;

use App\Services\HubLocator;
use Illuminate\Support\Facades\DB;

/**
 * Does Freshful deliver to this map point? STRICT version for the app.
 *
 * The website's HubLocator::locate() also accepts "the nearest hub within 100 km" as a match, so a customer far
 * from any drawn area is told "We deliver here". The app must not promise that. Here a point is covered only when it
 * is inside a hub's drawn polygon, or inside a hub's circle (radius from the area text, else config
 * freshful.hub_circle_km, default 3 km). Hubs with no drawn area never cover anything.
 *
 * It reuses HubLocator's public helpers (parseArea, inPolygon, km) and changes nothing in it.
 */
final class ServiceArea
{
    public static function check(float $lat, float $lng): array
    {
        $out = [
            'covered' => false, 'matched_by' => null, 'hub' => null, 'city' => null, 'distance_km' => null,
            'nearest_hub_km' => null, 'delivery_charge' => null, 'delivery_charge_set' => false,
        ];

        $hubs = DB::table('hubs')->whereRaw("LOWER(CAST(status AS CHAR)) IN ('active', '1')")->get();
        $circleKm = (float) config('freshful.hub_circle_km', 3);
        $polys = [];
        $circles = [];
        $nearest = null;

        foreach ($hubs as $h) {
            $area = HubLocator::parseArea((string) ($h->all_latlng ?? ''));
            if ($area['type'] === 'polygon' && HubLocator::inPolygon($lat, $lng, $area['points'])) {
                $polys[] = [$h, self::polygonSize($area['points']), HubLocator::km($lat, $lng, (float) $h->hub_lat, (float) $h->hub_lng)];
            } elseif ($area['type'] === 'circle') {
                $d = HubLocator::km($lat, $lng, $area['lat'], $area['lng']);
                if ($d <= ($area['km'] ?? $circleKm)) {
                    $circles[] = [$h, $d];
                }
            }
            if (is_numeric($h->hub_lat) && is_numeric($h->hub_lng) && ((float) $h->hub_lat !== 0.0 || (float) $h->hub_lng !== 0.0)) {
                $d = HubLocator::km($lat, $lng, (float) $h->hub_lat, (float) $h->hub_lng);
                if ($nearest === null || $d < $nearest) {
                    $nearest = $d;
                }
            }
        }
        $out['nearest_hub_km'] = $nearest === null ? null : round($nearest, 1);

        $win = null;
        if ($polys) {
            usort($polys, fn ($a, $b) => $a[1] <=> $b[1]); // overlapping areas: the smallest one wins, like the website
            $win = [$polys[0][0], 'area', $polys[0][2]];
        } elseif ($circles) {
            usort($circles, fn ($a, $b) => $a[1] <=> $b[1]);
            $win = [$circles[0][0], 'circle', $circles[0][1]];
        }
        if (! $win) {
            return $out;
        }

        [$hub, $how, $dist] = $win;
        $out['covered'] = true;
        $out['matched_by'] = $how;
        $out['hub'] = ['id' => (int) $hub->id, 'name' => (string) $hub->hub];
        $out['distance_km'] = round($dist, 1);

        $city = DB::table('cities')->where('id', (int) $hub->city_id)->first();
        $out['city'] = $city ? (string) $city->city : null;

        // Delivery charge per city from delivery_price. Cities without a price stay "not set": the app never guesses 0 or a default.
        $price = DB::table('delivery_price')->where('city', (string) $hub->city_id)->first();
        if ($price && is_numeric($price->price)) {
            $out['delivery_charge'] = (float) $price->price;
            $out['delivery_charge_set'] = true;
        }

        return $out;
    }

    /** Relative size of a polygon (shoelace), only used to pick the smallest of overlapping areas. */
    private static function polygonSize(array $pts): float
    {
        $a = 0.0;
        $n = count($pts);
        for ($i = 0, $j = $n - 1; $i < $n; $j = $i++) {
            $a += ($pts[$j][1] + $pts[$i][1]) * ($pts[$j][0] - $pts[$i][0]);
        }

        return abs($a / 2);
    }
}
