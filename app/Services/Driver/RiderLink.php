<?php

namespace App\Services\Driver;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * One place that answers: which delivery_boy row is this rider-app driver?
 *
 * The rider app logs in with a driver_registration id. The POS, hubs and consoles store the
 * delivery_boy id in orders.rider_id. This class turns the first into the second, so an order
 * assigned anywhere reaches the right rider's app.
 *
 * Marker: orderlink-v1 (many() added: orderlink-v2)
 */
class RiderLink
{
    /** @var array<int,int> */
    private static array $cache = [];

    private static ?bool $hasLink = null;

    /**
     * delivery_boy.id for this driver, or -1 when there is none.
     * -1 never matches an order. (0 must not be returned: orders.rider_id is 0 for "no rider yet".)
     */
    public static function boyId(int $driverId): int
    {
        if ($driverId <= 0) {
            return -1;
        }
        if (! isset(self::$cache[$driverId])) {
            self::$cache[$driverId] = self::resolve($driverId);
        }

        return self::$cache[$driverId];
    }

    /**
     * Many drivers at once, for the admin lists. Read only (no self-heal writes), same rule as boyId().
     * Returns [driver id => the whole delivery_boy row]. Drivers with no row are left out.
     *
     * @param  array<int,int|string>  $driverIds
     * @return array<int,object>
     */
    public static function many(array $driverIds): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $driverIds), fn ($i) => $i > 0)));
        if (! $ids) {
            return [];
        }

        $byLink = [];
        if (self::linkColumn()) {
            foreach (DB::table('delivery_boy')->whereIn('driver_id', $ids)->get() as $r) {
                $byLink[(int) $r->driver_id][] = $r;
            }
        }
        $byRegid = [];
        $need = array_values(array_diff($ids, array_keys($byLink)));
        if ($need) {
            foreach (DB::table('delivery_boy')->whereIn('regid', array_map('strval', $need))->get() as $r) {
                $byRegid[(int) $r->regid][] = $r;
            }
        }
        $phones = DB::table('driver_registration')->whereIn('id', $ids)->pluck('phone', 'id');

        $out = [];
        foreach ($ids as $id) {
            $rows = $byLink[$id] ?? $byRegid[$id] ?? [];
            if (! $rows) {
                continue;
            }
            $out[$id] = self::sorted($rows, self::tail((string) ($phones[$id] ?? '')))[0];
        }

        return $out;
    }

    public static function forget(): void
    {
        self::$cache = [];
        self::$hasLink = null;
    }

    private static function resolve(int $driverId): int
    {
        $rows = [];
        if (self::linkColumn()) {
            $rows = DB::table('delivery_boy')->where('driver_id', $driverId)->get()->all();
        }
        $alreadyLinked = ! empty($rows);

        if (! $rows) {
            // Older rows: the app stored the driver id in regid (old push tokens there never equal a plain number).
            $rows = DB::table('delivery_boy')->where('regid', (string) $driverId)->get()->all();
        }
        if (! $rows) {
            return -1;
        }

        $pick = self::choose($rows, $driverId);

        if (! $alreadyLinked && self::linkColumn()) {
            DB::table('delivery_boy')->where('id', $pick)->update(['driver_id' => $driverId]);
        }

        return $pick;
    }

    /** Active first, then the row whose phone matches the driver's phone, then the oldest row. */
    private static function choose(array $rows, int $driverId): int
    {
        $phone = self::tail((string) DB::table('driver_registration')->where('id', $driverId)->value('phone'));

        return (int) self::sorted($rows, $phone)[0]->id;
    }

    /** The one ranking rule, shared by boyId() and many() so they can never disagree. */
    private static function sorted(array $rows, string $phone): array
    {
        usort($rows, function ($a, $b) use ($phone) {
            $ka = [self::active($a->status ?? '') ? 0 : 1, ($phone !== '' && self::tail((string) ($a->phone ?? '')) === $phone) ? 0 : 1, (int) $a->id];
            $kb = [self::active($b->status ?? '') ? 0 : 1, ($phone !== '' && self::tail((string) ($b->phone ?? '')) === $phone) ? 0 : 1, (int) $b->id];

            return $ka <=> $kb;
        });

        return $rows;
    }

    private static function active($status): bool
    {
        return in_array(strtolower(trim((string) $status)), ['active', '1'], true);
    }

    private static function tail(string $phone): string
    {
        $d = preg_replace('/\D+/', '', $phone) ?? '';

        return strlen($d) > 10 ? substr($d, -10) : $d;
    }

    private static function linkColumn(): bool
    {
        if (self::$hasLink === null) {
            try {
                self::$hasLink = Schema::hasColumn('delivery_boy', 'driver_id');
            } catch (\Throwable $e) {
                self::$hasLink = false;
            }
        }

        return self::$hasLink;
    }
}
