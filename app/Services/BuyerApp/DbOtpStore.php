<?php

namespace App\Services\BuyerApp;

use Illuminate\Support\Facades\DB;

/** OtpStore on the buyer_app_otps table (times are unix timestamps, so there are no time-zone surprises). */
class DbOtpStore implements OtpStore
{
    private const TABLE = 'buyer_app_otps';

    public function latestOpen(string $phone, string $purpose): ?array
    {
        $row = DB::table(self::TABLE)
            ->where('phone', $phone)
            ->where('purpose', $purpose)
            ->whereNull('consumed_ts')
            ->orderByDesc('id')
            ->first(['id', 'code_hash', 'expires_ts']);

        return $row ? ['id' => (int) $row->id, 'code_hash' => (string) $row->code_hash, 'expires_ts' => (int) $row->expires_ts] : null;
    }

    public function lastIssuedAt(string $phone): ?int
    {
        $ts = DB::table(self::TABLE)->where('phone', $phone)->max('created_ts');

        return $ts === null ? null : (int) $ts;
    }

    public function countSince(string $phone, int $sinceTs): int
    {
        return (int) DB::table(self::TABLE)->where('phone', $phone)->where('created_ts', '>=', $sinceTs)->count();
    }

    public function insert(string $phone, string $purpose, string $codeHash, int $expiresTs, ?string $ip, int $nowTs): int
    {
        return (int) DB::table(self::TABLE)->insertGetId([
            'phone'       => $phone,
            'purpose'     => $purpose,
            'code_hash'   => $codeHash,
            'attempts'    => 0,
            'expires_ts'  => $expiresTs,
            'consumed_ts' => null,
            'created_ts'  => $nowTs,
            'ip'          => $ip !== null ? substr($ip, 0, 45) : null,
        ]);
    }

    public function consumeOpenExcept(string $phone, int $exceptId, int $nowTs): void
    {
        DB::table(self::TABLE)
            ->where('phone', $phone)
            ->whereNull('consumed_ts')
            ->where('id', '!=', $exceptId)
            ->update(['consumed_ts' => $nowTs]);
    }

    public function incrementAttempts(int $id): int
    {
        DB::table(self::TABLE)->where('id', $id)->increment('attempts');

        return (int) DB::table(self::TABLE)->where('id', $id)->value('attempts');
    }

    public function consume(int $id, int $nowTs): bool
    {
        return DB::table(self::TABLE)->where('id', $id)->whereNull('consumed_ts')->update(['consumed_ts' => $nowTs]) === 1;
    }

    public function delete(int $id): void
    {
        DB::table(self::TABLE)->where('id', $id)->delete();
    }

    public function purgeOlderThan(int $ts): void
    {
        DB::table(self::TABLE)->where('created_ts', '<', $ts)->delete();
    }
}
