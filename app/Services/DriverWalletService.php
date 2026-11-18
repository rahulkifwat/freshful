<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DriverWalletService
{
    public const BONUS_THRESHOLD =2;   // is se upar ke orders par bonus
    public const BONUS_PER_ORDER = 10.00; // INR

    /**
     * Wallet row ko lock karke return karta hai. Wallet na ho to bana deta hai.
     * Sirf DB::transaction ke andar call karo.
     */
    public function lockWallet(int $driverId): object
    {
        DB::table('driver_wallet')->insertOrIgnore([
            'regid'    => $driverId,
            'balance'  => 0,
            'currency' => 'INR',
            'status'   => 'active',
        ]);

        return DB::table('driver_wallet')->where('regid', $driverId)->lockForUpdate()->first();
    }

    /**
     * Order deliver hone ke baad call karo (same transaction me).
     * Agar is month ka delivered count 100 se upar hai to 10 INR credit karta hai.
     *
     * @return float|null  credited amount, ya null agar bonus nahi mila
     */
    public function creditMonthlyBonusIfEligible(int $driverId, string $orderId, string $now): ?float
    {
        $monthStart = Carbon::parse($now)->startOfMonth()->toDateTimeString();
        $monthEnd   = Carbon::parse($now)->startOfMonth()->addMonth()->toDateTimeString();

        // Wallet lock => same driver ki concurrent deliveries ek ek karke chalengi
        $wallet = $this->lockWallet($driverId);

        $count = DB::table('orders')
            ->where('rider_id', $driverId)
            ->where('order_status', 'Order Delivered')
            ->where('delivery_complete_time', '>=', $monthStart)
            ->where('delivery_complete_time', '<', $monthEnd)
            ->distinct()
            ->count('order_id');

        if ($count <= self::BONUS_THRESHOLD) {
            return null;
        }

        if ($wallet->status !== 'active') {
            return null;
        }

        // Same order par dobara bonus na mile
        $ref = 'bonus:'.$orderId;
        $already = DB::table('driver_wallet_transactions')
            ->where('wallet_id', $wallet->id)
            ->where('type', 'credit')
            ->where('reference_id', $ref)
            ->exists();
        if ($already) {
            return null;
        }

        $amount = self::BONUS_PER_ORDER;

        DB::table('driver_wallet')->where('id', $wallet->id)->update([
            'balance'    => DB::raw('balance + '.number_format($amount, 2, '.', '')),
            'updated_at' => $now,
        ]);

        DB::table('driver_wallet_transactions')->insert([
            'wallet_id'    => $wallet->id,
            'regid'        => $driverId,
            'type'         => 'credit',
            'amount'       => $amount,
            'reference_id' => $ref,
            'description'  => "Monthly bonus: order #{$count} of the month",
            'status'       => 'completed',
            'created_at'   => $now,
        ]);

        return $amount;
    }
}