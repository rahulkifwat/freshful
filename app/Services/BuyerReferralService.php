<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BuyerReferralService
{
    public function ensureCode(int $buyerId): string
    {
        return DB::transaction(function () use ($buyerId) {
            $buyer = DB::table('buyers')->where('id', $buyerId)->lockForUpdate()->first();
            if (!$buyer) throw new \RuntimeException('Buyer account was not found.');
            if (!empty($buyer->referral_code)) return (string) $buyer->referral_code;
            do { $code = 'FF' . strtoupper(Str::random(8)); }
            while (DB::table('buyers')->where('referral_code', $code)->exists());
            DB::table('buyers')->where('id', $buyerId)->update(['referral_code' => $code]);
            return $code;
        });
    }

    public function attachNewBuyer(int $buyerId, ?string $code): void
    {
        if (!$code || !DB::table('buyers')->where('id', $buyerId)->exists()) return;
        $referrer = DB::table('buyers')->where('referral_code', strtoupper(trim($code)))->first();
        if (!$referrer || (int) $referrer->id === $buyerId) return;
        DB::transaction(function () use ($buyerId, $referrer, $code) {
            $buyer = DB::table('buyers')->where('id', $buyerId)->lockForUpdate()->first();
            if (!$buyer || !empty($buyer->referred_by_buyer_id)) return;
            DB::table('buyers')->where('id', $buyerId)->update(['referred_by_buyer_id' => $referrer->id]);
            DB::table('buyer_referrals')->insertOrIgnore([
                'referrer_buyer_id' => $referrer->id, 'referred_buyer_id' => $buyerId,
                'referral_code' => strtoupper(trim($code)), 'reward_amount' => 150,
                'status' => 'pending', 'created_at' => now(), 'updated_at' => now(),
            ]);
        });
    }

    public function awardForDeliveredOrder(string $orderId): void
    {
        if (!DB::table('orders')->where('order_id', $orderId)->where('order_status', 'Order Delivered')->exists()) return;
        $referred = DB::table('orders')->where('order_id', $orderId)->value('buyer_id');
        if (!$referred) return;
        // Reward only the referred customer's first non-cancelled order, once delivered and >= ₹400.
        $first = DB::table('orders')->where('buyer_id', $referred)->where('order_status', '<>', 'Order Cancel')
            ->select('order_id', DB::raw('MIN(id) as first_id'), DB::raw('MAX(order_status) as status'), DB::raw('MAX(total_amount) as total'))
            ->groupBy('order_id')->orderBy('first_id')->first();
        if (!$first || (string) $first->order_id !== (string) $orderId || $first->status !== 'Order Delivered' || (float) $first->total < 400) return;

        DB::transaction(function () use ($referred, $orderId) {
            $referral = DB::table('buyer_referrals')->where('referred_buyer_id', $referred)->lockForUpdate()->first();
            if (!$referral || $referral->status !== 'pending') return;
            $buyer = DB::table('buyers')->where('id', $referral->referrer_buyer_id)->lockForUpdate()->first();
            if (!$buyer) return;
            $balance = round((float) ($buyer->wallet_amount ?? 0) + (float) $referral->reward_amount, 2);
            DB::table('buyers')->where('id', $buyer->id)->update(['wallet_amount' => $balance]);
            DB::table('buyer_wallet_ledger')->insertOrIgnore([
                'buyer_id' => $buyer->id, 'event_type' => 'referral_reward', 'amount' => $referral->reward_amount,
                'balance_after' => $balance, 'description' => 'Referral reward for delivered first order',
                'reference' => $orderId, 'idempotency_key' => 'referral:' . $referral->id,
                'created_at' => now(), 'updated_at' => now(),
            ]);
            DB::table('buyer_referrals')->where('id', $referral->id)->update([
                'status' => 'rewarded', 'qualifying_order_id' => $orderId, 'rewarded_at' => now(), 'updated_at' => now(),
            ]);
        });
    }
}
