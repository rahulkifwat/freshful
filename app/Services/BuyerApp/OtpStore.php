<?php

namespace App\Services\BuyerApp;

/**
 * Storage the OTP rules need. Kept as an interface so the rules in OtpService
 * can be tested without a framework or database.
 * All times are unix timestamps (seconds).
 */
interface OtpStore
{
    /** Newest code for this phone/purpose that is not yet used: ['id','code_hash','expires_ts'] or null. */
    public function latestOpen(string $phone, string $purpose): ?array;

    /** When the newest code for this phone was issued (any purpose), or null. */
    public function lastIssuedAt(string $phone): ?int;

    /** How many codes were issued for this phone since the given time. */
    public function countSince(string $phone, int $sinceTs): int;

    /** Save a new code (hash only, never the code itself). Returns its id. */
    public function insert(string $phone, string $purpose, string $codeHash, int $expiresTs, ?string $ip, int $nowTs): int;

    /** Mark every still-open code of this phone as used, except the given id. */
    public function consumeOpenExcept(string $phone, int $exceptId, int $nowTs): void;

    /** Add one wrong/any try to this code and return the new total. */
    public function incrementAttempts(int $id): int;

    /** Mark a code as used. True only for the single caller that actually used it. */
    public function consume(int $id, int $nowTs): bool;

    public function delete(int $id): void;

    public function purgeOlderThan(int $ts): void;
}
