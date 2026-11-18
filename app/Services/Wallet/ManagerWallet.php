<?php

namespace App\Services\Wallet;

use App\Services\BuyerApp\Payments\Wallet;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * The one way a manager panel adds money to a customer wallet (15 Nov 2026).
 * Before this, four panels each did their own thing: no limit, no reason, nobody recorded, no ledger row,
 * and one of them never changed the balance at all.
 *
 *   who      only the roles in config wallet_guard.roles (default marketing, operation)
 *   how much 1 to max_each per top-up, and max_daily per person per day
 *   why      a reason of at least min_reason characters is required and saved
 *   record   one buyer_wallet_ledger row (event manager_topup, balance_after) written with the new app's Wallet service,
 *            in one transaction with the balance change; plus the old history row so the old history screens still show it
 *   twice    the same top-up from the same person in the same minute is refused
 */
final class ManagerWallet
{
    public const EVENT = 'manager_topup';

    private static function fail(string $m): array { return ['ok' => false, 'message' => $m]; }

    private static function log(string $m, array $c = []): void
    {
        try { Log::notice('[wallet-guard] ' . $m, $c); } catch (\Throwable $e) { /* logging must never block */ }
    }

    /** @param array $in buyer_id, amount, note (or description) @return array{ok: bool, message: string} */
    public static function topup(string $role, ?int $actorId, array $in, ?int $ts = null): array
    {
        $ts = $ts ?? time();
        $role = strtolower(trim($role));
        $allowed = (array) config('wallet_guard.roles', ['marketing', 'operation']);
        if (!in_array($role, $allowed, true)) {
            self::log('refused: role not allowed', ['role' => $role, 'actor' => $actorId]);
            return self::fail('Adding wallet money is not available for your role. Please ask the Marketing or Operation manager.');
        }
        if (!$actorId) return self::fail('Please sign in again.');

        $buyerId = $in['buyer_id'] ?? null;
        if (!is_numeric($buyerId) || (int) $buyerId < 1 || (string) (int) $buyerId !== trim((string) $buyerId)) return self::fail('Please choose a customer.');
        $buyerId = (int) $buyerId;

        $amt = $in['amount'] ?? null;
        if (!is_numeric($amt)) return self::fail('Please enter the amount.');
        $amount = round((float) $amt, 2);
        $max = (float) config('wallet_guard.max_each', 2000);
        if ($amount < 1) return self::fail('The amount must be at least Rs 1.');
        if ($amount > $max) return self::fail('One top-up can be at most Rs ' . rtrim(rtrim(number_format($max, 2, '.', ''), '0'), '.') . '. For more, please ask the admin.');

        $reason = trim((string) ($in['note'] ?? $in['description'] ?? ''));
        $reason = trim(preg_replace('/\s+/', ' ', $reason));
        $min = (int) config('wallet_guard.min_reason', 5);
        if ($min > 0 && mb_strlen($reason) < $min) return self::fail('Please write the reason for this top-up (at least ' . $min . ' characters).');
        $reason = $reason === '' ? 'no reason given' : mb_substr($reason, 0, 120);

        $ref = 'mgr:' . $role . ':' . $actorId;
        $key = 'mgr:' . $role . ':' . $actorId . ':' . $buyerId . ':' . number_format($amount, 2, '.', '') . ':' . intdiv($ts, 60);
        $dailyMax = (float) config('wallet_guard.max_daily', 5000);

        try {
            $new = DB::transaction(function () use ($buyerId, $amount, $reason, $role, $actorId, $ref, $key, $dailyMax, $ts) {
                if (Wallet::lockedBalance($buyerId) === null) throw new \DomainException('Customer not found.');
                $today = (float) DB::table('buyer_wallet_ledger')->where('event_type', self::EVENT)->where('reference', $ref)
                    ->where('created_at', '>=', date('Y-m-d 00:00:00', $ts))->sum('amount');
                if ($today + $amount > $dailyMax + 0.0001) {
                    throw new \DomainException('Your daily limit of Rs ' . rtrim(rtrim(number_format($dailyMax, 2, '.', ''), '0'), '.') . ' is reached. You have added Rs ' . rtrim(rtrim(number_format($today, 2, '.', ''), '0'), '.') . ' today.');
                }
                $bal = Wallet::credit($buyerId, $amount, self::EVENT, ucfirst($role) . ' manager #' . $actorId . ': ' . $reason, $ref, $key);
                self::legacyRow($buyerId, $amount, $reason, $role, $ts);
                return $bal;
            });
        } catch (\DomainException $e) {
            return self::fail($e->getMessage());
        } catch (\Throwable $e) {
            $dup = stripos($e->getMessage(), 'unique') !== false || stripos($e->getMessage(), 'duplicate') !== false;
            self::log($dup ? 'duplicate top-up blocked' : 'top-up failed', ['role' => $role, 'actor' => $actorId, 'buyer' => $buyerId, 'err' => $dup ? '' : substr($e->getMessage(), 0, 120)]);
            return self::fail($dup ? 'This top-up was just added. Please check the wallet history before adding again.' : 'Could not add the money. Nothing was changed. Please try again.');
        }
        self::log('top-up done', ['role' => $role, 'actor' => $actorId, 'buyer' => $buyerId, 'amount' => $amount]);

        return ['ok' => true, 'message' => 'Rs ' . rtrim(rtrim(number_format($amount, 2, '.', ''), '0'), '.') . ' added. New wallet balance: Rs ' . rtrim(rtrim(number_format($new, 2, '.', ''), '0'), '.') . '.'];
    }

    /** The old history screens read these tables; keep them showing the top-up. Best effort, never blocks the top-up. */
    private static function legacyRow(int $buyerId, float $amount, string $reason, string $role, int $ts): void
    {
        try {
            $table = Schema::hasTable('wallet_transaction_history') ? 'wallet_transaction_history' : (Schema::hasTable('wallet_history') ? 'wallet_history' : null);
            if (!$table) return;
            $cols = Schema::getColumnListing($table);
            $row = ['buyer_id' => $buyerId, 'amount' => $amount, 'created_at' => date('Y-m-d H:i:s', $ts)];
            if (in_array('transaction_type', $cols, true)) $row['transaction_type'] = 'credit';
            if (in_array('type', $cols, true)) $row['type'] = 'credit';
            $text = ucfirst($role) . ' manager: ' . $reason;
            if (in_array('note', $cols, true)) $row['note'] = $text;
            elseif (in_array('description', $cols, true)) $row['description'] = $text;
            DB::table($table)->insert(array_intersect_key($row, array_flip($cols)));
        } catch (\Throwable $e) {
            self::log('old history row not written', ['err' => substr($e->getMessage(), 0, 100)]);
        }
    }
}
