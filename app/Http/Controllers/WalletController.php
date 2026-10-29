<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class WalletController extends Controller
{
    private function buyerId(): ?int
    {
        $buyer = Auth::guard('buyers')->user() ?: Auth::user();
        return $buyer ? (int) $buyer->id : null;
    }

    public function createTopupOrder(Request $request)
    {
        $buyerId = $this->buyerId();
        if (!$buyerId) return response()->json(['message' => 'Please log in to add wallet money.'], 401);
        $data = $request->validate(['amount' => ['required', 'integer', 'min:100', 'max:10000']]);
        $key = config('razorpay.key_id');
        $secret = config('razorpay.key_secret');
        if (!$key || !$secret) return response()->json(['message' => 'Wallet top-up is temporarily unavailable. Please contact Freshful support.'], 503);

        $paise = (int) $data['amount'] * 100;
        $receipt = 'ffw_' . Str::lower(Str::random(20));
        try {
            $response = Http::withBasicAuth($key, $secret)->acceptJson()->timeout(15)->post('https://api.razorpay.com/v1/orders', [
                'amount' => $paise, 'currency' => 'INR', 'receipt' => $receipt,
                'notes' => ['buyer_id' => (string) $buyerId, 'purpose' => 'Freshful wallet top-up'],
            ]);
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['message' => 'Unable to start payment right now. Please try again.'], 502);
        }
        if (!$response->successful() || !$response->json('id')) {
            \Log::warning('Freshful wallet order creation failed', ['buyer_id' => $buyerId, 'status' => $response->status()]);
            return response()->json(['message' => 'Payment could not be started. Please try again.'], 502);
        }

        DB::table('buyer_wallet_topups')->insert([
            'buyer_id' => $buyerId, 'amount_paise' => $paise, 'currency' => 'INR',
            'razorpay_order_id' => $response->json('id'), 'status' => 'pending',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        return response()->json(['key' => $key, 'order_id' => $response->json('id'), 'amount' => $paise, 'currency' => 'INR', 'name' => 'Freshful Wallet']);
    }

    public function verifyTopup(Request $request)
    {
        $buyerId = $this->buyerId();
        if (!$buyerId) return response()->json(['message' => 'Please log in again.'], 401);
        $data = $request->validate([
            'razorpay_order_id' => ['required', 'string', 'max:100'],
            'razorpay_payment_id' => ['required', 'string', 'max:100'],
            'razorpay_signature' => ['required', 'string', 'size:64'],
        ]);
        $key = config('razorpay.key_id');
        $secret = config('razorpay.key_secret');
        $topup = DB::table('buyer_wallet_topups')->where('buyer_id', $buyerId)->where('razorpay_order_id', $data['razorpay_order_id'])->first();
        if (!$key || !$secret || !$topup) return response()->json(['message' => 'This payment could not be matched to your wallet. Contact support if money was charged.'], 422);
        $expected = hash_hmac('sha256', $topup->razorpay_order_id . '|' . $data['razorpay_payment_id'], $secret);
        if (!hash_equals($expected, $data['razorpay_signature'])) return response()->json(['message' => 'Payment verification failed. Your wallet was not changed.'], 422);

        try {
            $paymentResponse = Http::withBasicAuth($key, $secret)->acceptJson()->timeout(15)
                ->get('https://api.razorpay.com/v1/payments/' . rawurlencode($data['razorpay_payment_id']));
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['message' => 'Payment received; verification is delayed. Please refresh your wallet shortly.'], 503);
        }
        $payment = $paymentResponse->json();
        if (!$paymentResponse->successful() || ($payment['order_id'] ?? null) !== $topup->razorpay_order_id
            || (int) ($payment['amount'] ?? 0) !== (int) $topup->amount_paise
            || ($payment['currency'] ?? null) !== 'INR' || empty($payment['captured'])) {
            return response()->json(['message' => 'Payment is not captured yet. Your wallet will update after confirmation.'], 202);
        }

        $balance = DB::transaction(function () use ($topup, $data, $buyerId) {
            $lockedTopup = DB::table('buyer_wallet_topups')->where('id', $topup->id)->lockForUpdate()->first();
            $buyer = DB::table('buyers')->where('id', $buyerId)->lockForUpdate()->first();
            if (!$buyer) throw new \RuntimeException('Buyer account not found during wallet credit.');
            if ($lockedTopup->status === 'credited') return (float) $buyer->wallet_amount;
            $balance = round((float) ($buyer->wallet_amount ?? 0) + ((int) $lockedTopup->amount_paise / 100), 2);
            DB::table('buyers')->where('id', $buyerId)->update(['wallet_amount' => $balance]);
            DB::table('buyer_wallet_ledger')->insertOrIgnore([
                'buyer_id' => $buyerId, 'event_type' => 'topup', 'amount' => $lockedTopup->amount_paise / 100,
                'balance_after' => $balance, 'description' => 'Wallet added using Razorpay',
                'reference' => $data['razorpay_payment_id'], 'idempotency_key' => 'topup:' . $lockedTopup->id,
                'created_at' => now(), 'updated_at' => now(),
            ]);
            DB::table('buyer_wallet_topups')->where('id', $lockedTopup->id)->update([
                'razorpay_payment_id' => $data['razorpay_payment_id'], 'status' => 'credited',
                'paid_at' => now(), 'updated_at' => now(),
            ]);
            return $balance;
        });
        return response()->json(['message' => 'Wallet updated successfully.', 'balance' => number_format($balance, 2, '.', '')]);
    }
}
