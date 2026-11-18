<?php

namespace App\Services\BuyerApp\Payments;

use Illuminate\Support\Facades\DB;

/**
 * Wallet money for the app, written the way the website top-up and the admin refund write it: buyers.wallet_amount plus
 * one buyer_wallet_ledger row with balance_after and a UNIQUE idempotency_key, so the same event can never be applied twice.
 * Call inside a database transaction. A debit is stored with a negative amount.
 */
final class Wallet
{
    public static function lockedBalance(int $buyerId): ?float
    {
        $v = DB::table('buyers')->where('id', $buyerId)->lockForUpdate()->value('wallet_amount');

        return $v === null ? null : round((float) $v, 2);
    }

    /** @return float the new balance */
    public static function debit(int $buyerId, float $amount, string $event, string $description, string $reference, string $key): float
    {
        return self::move($buyerId, -round($amount, 2), $event, $description, $reference, $key);
    }

    /** @return float the new balance */
    public static function credit(int $buyerId, float $amount, string $event, string $description, string $reference, string $key): float
    {
        return self::move($buyerId, round($amount, 2), $event, $description, $reference, $key);
    }

    private static function move(int $buyerId, float $signed, string $event, string $description, string $reference, string $key): float
    {
        $bal = self::lockedBalance($buyerId);
        if ($bal === null) {
            throw new \RuntimeException('Buyer account not found for the wallet.');
        }
        $new = round($bal + $signed, 2);
        if ($new < 0) {
            throw new \RuntimeException('Wallet would go below zero.');
        }
        $now = date('Y-m-d H:i:s');
        // plain insert on purpose: a repeated idempotency_key throws and rolls the whole order back, instead of paying twice
        DB::table('buyer_wallet_ledger')->insert([
            'buyer_id' => $buyerId, 'event_type' => $event, 'amount' => $signed, 'balance_after' => $new,
            'description' => mb_substr($description, 0, 180), 'reference' => mb_substr($reference, 0, 100), 'idempotency_key' => mb_substr($key, 0, 140),
            'created_at' => $now, 'updated_at' => $now,
        ]);
        DB::table('buyers')->where('id', $buyerId)->update(['wallet_amount' => $new]);

        return $new;
    }
}
