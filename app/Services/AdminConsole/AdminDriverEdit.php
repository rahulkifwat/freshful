<?php

namespace App\Services\AdminConsole;

use App\Exceptions\ApiException;
use App\Services\CountryConsole\CountryScope;
use App\Services\PosConsole\OrderFlow;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Freshful admin console: Driver edit, change hub / city / location, guarded delete (drivers update 2, 30 Oct 2026).
 * Uses only existing tables. Uploaded images are never touched. Every change is written to the change log.
 */
class AdminDriverEdit
{
    private static function has(string $t): bool { try { return Schema::hasTable($t); } catch (\Throwable $e) { return false; } }
    private static function str($v, int $max): string { return mb_substr(trim((string) $v), 0, $max); }
    private static function boy(int $regid): ?object
    {
        if (!self::has('delivery_boy')) return null;
        return DB::table('delivery_boy')->where('regid', (string) $regid)->orderByDesc('id')->first() ?: null;
    }
    private static function openOrders(?object $boy): int
    {
        if (!$boy || !self::has('orders')) return 0;
        try {
            $st = OrderFlow::statusesFor(['new', 'accepted', 'out']);
            return (int) DB::table('orders')->where('rider_id', $boy->id)->whereIn('order_status', $st)->distinct()->count('order_id');
        } catch (\Throwable $e) { return 0; }
    }
    private static function allOrders(?object $boy): int
    {
        if (!$boy || !self::has('orders')) return 0;
        try { return (int) DB::table('orders')->where('rider_id', $boy->id)->count(); } catch (\Throwable $e) { return 0; }
    }

    /** Everything the edit window needs: current values, hub list grouped by city, open orders. */
    public static function data(int $id): array
    {
        $d = DB::table('driver_registration')->where('id', $id)->first();
        if (!$d) throw new ApiException('Driver not found.');
        $p = self::has('driver_profile') ? DB::table('driver_profile')->where('regid', $id)->first() : null;
        $v = self::has('driver_vehicles') ? DB::table('driver_vehicles')->where('regid', $id)->orderByDesc('id')->first() : null;
        $b = self::boy($id);
        $hubs = [];
        foreach (CountryScope::hubs() as $h) {
            $hubs[] = ['id' => (int) $h['id'], 'name' => (string) $h['name'], 'city' => (string) ($h['city'] ?? ''), 'active' => !empty($h['active'])];
        }
        usort($hubs, fn ($a, $c) => strcmp($a['city'] . $a['name'], $c['city'] . $c['name']));
        return ['ok' => true, 'open_orders' => self::openOrders($b), 'hubs' => $hubs, 'fields' => [
            'name' => $d->name, 'emp_no' => $d->emp_no, 'phone' => $d->phone, 'email' => $d->email,
            'hub_id' => $b ? (int) $b->hub_id : 0, 'boy_status' => $b ? $b->status : 'active',
            'address' => $p->address ?? '', 'father_name' => $p->father_name ?? '', 'dob' => $p->dob ?? '',
            'blood_group' => $p->blood_group ?? '', 'language' => $p->language ?? '',
            'emergency_name' => $p->emergency_name ?? '', 'emergency_phone' => $p->emergency_phone ?? '',
            'emergency_relationship' => $p->emergency_relationship ?? '',
            'vehicle_type' => $v->vehicle_type ?? '', 'vehicle_number' => $v->vehicle_number ?? '',
        ]];
    }

    public static function save(int $id, array $in, array $actor): array
    {
        $d = DB::table('driver_registration')->where('id', $id)->first();
        if (!$d) throw new ApiException('Driver not found.');
        $name = self::str($in['name'] ?? '', 150);
        if (mb_strlen($name) < 2) throw new ApiException('Enter the driver name.');
        $phone = preg_replace('/[^0-9+]/', '', (string) ($in['phone'] ?? ''));
        if (strlen($phone) < 8 || strlen($phone) > 20) throw new ApiException('Enter a valid phone number.');
        $email = self::str($in['email'] ?? '', 150);
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) throw new ApiException('Enter a valid email or leave it empty.');
        $emp = self::str($in['emp_no'] ?? '', 50);
        $dob = self::str($in['dob'] ?? '', 10);
        if ($dob !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dob)) throw new ApiException('Date of birth must be a valid date.');
        $bs = (string) ($in['boy_status'] ?? 'active');
        if (!in_array($bs, ['active', 'disabled', 'pending'], true)) $bs = 'active';
        $hub = (int) ($in['hub_id'] ?? 0);
        if ($hub > 0 && !isset(CountryScope::hubs()[$hub])) throw new ApiException('That hub does not exist.');
        $vno = strtoupper(preg_replace('/\s+/', '', self::str($in['vehicle_number'] ?? '', 50)));

        // Duplicates: phone / email / vehicle number must not belong to another driver.
        if (DB::table('driver_registration')->where('phone', $phone)->where('id', '!=', $id)->exists()) throw new ApiException('Another driver already uses this phone number.');
        if ($email !== '' && DB::table('driver_registration')->where('email', $email)->where('id', '!=', $id)->exists()) throw new ApiException('Another driver already uses this email.');
        if ($vno !== '' && self::has('driver_vehicles') && DB::table('driver_vehicles')->where('vehicle_number', $vno)->where('regid', '!=', $id)->exists()) throw new ApiException('This vehicle number belongs to another driver.');

        $boy = self::boy($id);
        $oldHub = $boy ? (int) $boy->hub_id : 0;
        if ($boy && $hub !== $oldHub && self::openOrders($boy) > 0) throw new ApiException('This driver has open orders. Change the hub after they are delivered.');

        $before = self::data($id)['fields'];
        $now = now();
        DB::transaction(function () use ($id, $d, $in, $name, $phone, $email, $emp, $dob, $bs, $hub, $vno, $boy, $now) {
            DB::table('driver_registration')->where('id', $id)->update(['name' => $name, 'phone' => $phone, 'email' => $email !== '' ? $email : $d->email, 'emp_no' => $emp !== '' ? $emp : $d->emp_no]);
            $prof = [
                'name' => $name, 'phone' => $phone, 'father_name' => self::str($in['father_name'] ?? '', 150), 'dob' => $dob !== '' ? $dob : null,
                'blood_group' => self::str($in['blood_group'] ?? '', 10), 'address' => self::str($in['address'] ?? '', 1000),
                'language' => self::str($in['language'] ?? '', 50), 'emergency_name' => self::str($in['emergency_name'] ?? '', 150),
                'emergency_phone' => self::str($in['emergency_phone'] ?? '', 20), 'emergency_relationship' => self::str($in['emergency_relationship'] ?? '', 100),
                'updated_at' => $now,
            ];
            if (DB::table('driver_profile')->where('regid', $id)->exists()) DB::table('driver_profile')->where('regid', $id)->update($prof);
            else DB::table('driver_profile')->insert($prof + ['regid' => $id, 'created_at' => $now]);
            $veh = ['vehicle_type' => self::str($in['vehicle_type'] ?? '', 50), 'vehicle_number' => $vno, 'updated_at' => $now];
            if (DB::table('driver_vehicles')->where('regid', $id)->exists()) DB::table('driver_vehicles')->where('regid', $id)->update($veh);
            elseif ($veh['vehicle_type'] !== '' || $vno !== '') DB::table('driver_vehicles')->insert($veh + ['regid' => $id, 'created_at' => $now]);
            if ($boy) DB::table('delivery_boy')->where('id', $boy->id)->update(['name' => $name, 'phone' => $phone, 'hub_id' => $hub, 'status' => $bs] + ($emp !== '' ? ['employee_id' => $emp] : []));
        });
        CountryScope::forget();
        $after = self::data($id)['fields'];
        $changed = [];
        foreach ($after as $k => $v) { if ((string) ($before[$k] ?? '') !== (string) $v) $changed[] = $k; }
        AdminAudit::log($actor, 'driver.edit', 'Drivers', 'driver_registration', $id, 'Edited driver ' . $name . ($changed ? ' (' . implode(', ', $changed) . ')' : ' (no change)'), $before, $after);
        return ['ok' => true, 'message' => 'Driver saved.'];
    }

    /** Guarded delete: only a driver with no orders and no money history can be removed. Uploaded images stay on disk. */
    public static function remove(int $id, array $actor): array
    {
        $d = DB::table('driver_registration')->where('id', $id)->first();
        if (!$d) throw new ApiException('Driver not found.');
        $boy = self::boy($id);
        if (self::allOrders($boy) > 0) throw new ApiException('This driver has order history, so it cannot be deleted. Deactivate the driver instead.');
        $w = self::has('driver_wallet') ? DB::table('driver_wallet')->where('regid', $id)->first() : null;
        if ($w && abs((float) $w->balance) > 0.001) throw new ApiException('The wallet still has a balance. Settle it first, or deactivate the driver instead.');
        if ($w && self::has('driver_wallet_transactions') && DB::table('driver_wallet_transactions')->where('wallet_id', $w->id)->exists()) throw new ApiException('The wallet has transaction history, so the driver cannot be deleted. Deactivate the driver instead.');
        $snap = (array) $d; unset($snap['password']);
        DB::transaction(function () use ($id, $w, $boy) {
            foreach (['driver_locations', 'driver_doc_reviews', 'driver_bank_details', 'driver_identity_documents'] as $t) {
                if (self::has($t)) DB::table($t)->where('regid', $id)->delete();
            }
            if ($w) DB::table('driver_wallet')->where('id', $w->id)->delete();
            if ($boy) DB::table('delivery_boy')->where('regid', (string) $id)->delete();
            DB::table('driver_registration')->where('id', $id)->delete();
        });
        CountryScope::forget();
        AdminAudit::log($actor, 'driver.delete', 'Drivers', 'driver_registration', $id, 'Deleted driver ' . $d->name, $snap, null);
        return ['ok' => true, 'message' => 'Driver deleted.'];
    }
}
