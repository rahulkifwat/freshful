<?php

namespace App\Services\BuyerApp\Payments;

use Illuminate\Support\Facades\DB;

/**
 * Money back for an order the customer cancelled in the app after paying in advance (wallet or online): it goes to the
 * Freshful wallet, in the same transaction as the cancel.
 *
 * It writes EXACTLY what the admin refund screen (AdminCare::refund, wallet method, reason "cancelled") writes:
 *   ledger  event "refund", positive amount, key  order-refund:<ORDER>:cancelled
 *   table   admin_refunds  (order, reason cancelled, method wallet, status done)
 * so the admin screen shows the order as already refunded and cannot refund it a second time, and this step does nothing
 * if an admin has already refunded it.
 */
final class CancelRefund
{
    /** The bill: items after discount plus delivery (what the customer paid). */
    public static function amountFor(object $firstRow): float
    {
        return round((float) $firstRow->total_amount + (float) $firstRow->delivery_charge, 2);
    }

    /** @return array{amount: float, wallet_balance: float|null, already: bool}|null null when there is nothing to refund */
    public static function toWallet(string $orderId, int $buyerId, object $firstRow, string $note = 'Cancelled by the customer in the app'): ?array
    {
        $amount = self::amountFor($firstRow);
        if ($amount <= 0) {
            return null;
        }
        $key = 'order-refund:' . $orderId . ':cancelled';
        $done = DB::table('admin_refunds')->where('order_id', $orderId)->where('reason', 'cancelled')->where('status', 'done')->exists()
            || DB::table('buyer_wallet_ledger')->where('idempotency_key', $key)->exists();
        if ($done) {
            return ['amount' => $amount, 'wallet_balance' => null, 'already' => true];
        }

        $balance = Wallet::credit($buyerId, $amount, 'refund', 'Refund for order ' . $orderId . ' (cancelled)', $orderId, $key);
        $now = date('Y-m-d H:i:s');
        $row = [
            'buyer_id' => $buyerId, 'hub_id' => (int) $firstRow->hub_id ?: null, 'amount' => $amount, 'method' => 'wallet', 'status' => 'done',
            'reference' => '', 'note' => mb_substr($note, 0, 255), 'ticket_id' => null, 'by_name' => 'Customer (app)', 'updated_at' => $now,
        ];
        $existing = DB::table('admin_refunds')->where('order_id', $orderId)->where('reason', 'cancelled')->first();
        if ($existing) {
            DB::table('admin_refunds')->where('id', $existing->id)->update($row);
        } else {
            DB::table('admin_refunds')->insert($row + ['order_id' => $orderId, 'reason' => 'cancelled', 'created_at' => $now]);
        }

        return ['amount' => $amount, 'wallet_balance' => $balance, 'already' => false];
    }
}
