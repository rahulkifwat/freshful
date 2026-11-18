<?php

namespace App\Services\Driver;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * empno-v1: the standard rider number  CITY-HUB-SERIAL  (for example AMR-AMB-1001).
 * - CITY comes from cities.city_code (a short code; if empty, the first 3 letters of the city name).
 * - HUB is the hub's own short name (hubs.hub).
 * - SERIAL is one global counter (rider_serials), first 1001, never reused, the same in every city.
 * The number is given once, when a rider is created or first linked to a hub. Moving a rider to another hub keeps it.
 * Nothing here touches the rider app or its API.
 */
class EmployeeNo
{
    private const MAX_LEN = 20;

    /** True when the text already has the standard shape (so it must not be overwritten). */
    public static function isStd(?string $v): bool
    {
        return (bool) preg_match('/^[A-Z0-9]{2,6}-[A-Z0-9]{1,8}-\d{4,}$/', (string) $v);
    }

    /** City code and hub code for a hub, or a plain-words error. */
    public static function codes(int $hubId): array
    {
        $hub = DB::table('hubs')->where('id', $hubId)->first();
        if (!$hub) throw new \RuntimeException('That hub does not exist.');
        $hubCode = substr(strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) $hub->hub)), 0, 8);
        if ($hubCode === '') throw new \RuntimeException('This hub has no short name, so a rider number cannot be made. Set the hub name first.');
        $city = DB::table('cities')->where('id', (int) $hub->city_id)->first();
        if (!$city) throw new \RuntimeException('Hub ' . $hubCode . ' points to a city that does not exist, so a rider number cannot be made. Fix the hub city first.');
        $cityCode = '';
        try { if (Schema::hasColumn('cities', 'city_code')) $cityCode = (string) ($city->city_code ?? ''); } catch (\Throwable $e) {}
        $cityCode = substr(strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $cityCode)), 0, 6);
        if ($cityCode === '') $cityCode = substr(strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) $city->city)), 0, 3);
        if ($cityCode === '') throw new \RuntimeException('The city of this hub has no name or code, so a rider number cannot be made.');
        return [$cityCode, $hubCode];
    }

    /** The number that would be given next at this hub. Changes nothing. */
    public static function peek(int $hubId): string
    {
        [$c, $h] = self::codes($hubId);
        $n = (int) DB::table('rider_serials')->where('name', 'rider')->value('next_no');
        return self::build($c, $h, $n > 0 ? $n : 1001);
    }

    /** Take the next number at this hub. The counter only ever goes up. */
    public static function issue(int $hubId): string
    {
        [$c, $h] = self::codes($hubId);
        return DB::transaction(function () use ($c, $h) {
            DB::table('rider_serials')->insertOrIgnore(['name' => 'rider', 'next_no' => 1001]);
            $row = DB::table('rider_serials')->where('name', 'rider')->lockForUpdate()->first();
            $n = max(1001, (int) $row->next_no);
            for ($i = 0; $i < 50; $i++, $n++) {
                $code = self::build($c, $h, $n);
                if (!DB::table('delivery_boy')->where('employee_id', $code)->exists()) {
                    DB::table('rider_serials')->where('name', 'rider')->update(['next_no' => $n + 1]);
                    return $code;
                }
            }
            throw new \RuntimeException('Could not make a free rider number. Try again.');
        });
    }

    private static function build(string $c, string $h, int $n): string
    {
        $code = $c . '-' . $h . '-' . $n;
        if (strlen($code) > self::MAX_LEN) throw new \RuntimeException('The rider number ' . $code . ' is longer than ' . self::MAX_LEN . ' characters. Shorten the city or hub code.');
        return $code;
    }
}
