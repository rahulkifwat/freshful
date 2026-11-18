<?php

namespace App\Services\BuyerApp\Payments;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Razorpay calls, done the same way as the website wallet top-up (WalletController): plain HTTPS with the key id and
 * secret from config/razorpay.php. The secret never leaves the server and is never logged.
 */
final class Razorpay
{
    /** @return array{0: string, 1: string}|null */
    public static function credentials(): ?array
    {
        $k = trim((string) config('razorpay.key_id', ''));
        $s = trim((string) config('razorpay.key_secret', ''));

        return ($k !== '' && $s !== '') ? [$k, $s] : null;
    }

    /** Razorpay order id, or null when it could not be created. */
    public static function createOrder(int $paise, string $receipt, array $notes): ?string
    {
        $c = self::credentials();
        if (! $c) {
            return null;
        }
        try {
            $r = Http::withBasicAuth($c[0], $c[1])->acceptJson()->timeout(15)->post('https://api.razorpay.com/v1/orders', [
                'amount' => $paise, 'currency' => 'INR', 'receipt' => $receipt, 'notes' => $notes,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Freshful app Razorpay order failed: ' . get_class($e));

            return null;
        }
        if (! $r->successful() || ! $r->json('id')) {
            Log::warning('Freshful app Razorpay order creation failed', ['status' => $r->status()]);

            return null;
        }

        return (string) $r->json('id');
    }

    /** The payment as Razorpay reports it, or null if Razorpay could not be asked. */
    public static function payment(string $paymentId): ?array
    {
        $c = self::credentials();
        if (! $c) {
            return null;
        }
        try {
            $r = Http::withBasicAuth($c[0], $c[1])->acceptJson()->timeout(15)->get('https://api.razorpay.com/v1/payments/' . rawurlencode($paymentId));
        } catch (\Throwable $e) {
            Log::warning('Freshful app Razorpay payment lookup failed: ' . get_class($e));

            return null;
        }

        return $r->successful() ? (array) $r->json() : null;
    }

    /** Payments made against one Razorpay order (used to recover a payment whose confirmation never reached us). */
    public static function orderPayments(string $razorpayOrderId): ?array
    {
        $c = self::credentials();
        if (! $c) {
            return null;
        }
        try {
            $r = Http::withBasicAuth($c[0], $c[1])->acceptJson()->timeout(15)->get('https://api.razorpay.com/v1/orders/' . rawurlencode($razorpayOrderId) . '/payments');
        } catch (\Throwable $e) {
            Log::warning('Freshful app Razorpay order payments lookup failed: ' . get_class($e));

            return null;
        }

        return $r->successful() ? (array) ($r->json('items') ?? []) : null;
    }

    public static function signatureOk(string $razorpayOrderId, string $paymentId, string $signature): bool
    {
        $c = self::credentials();
        if (! $c || strlen($signature) !== 64) {
            return false;
        }

        return hash_equals(hash_hmac('sha256', $razorpayOrderId . '|' . $paymentId, $c[1]), $signature);
    }

    /** A payment that really is money in the bank for this Razorpay order and amount. */
    public static function isCaptured(?array $payment, string $razorpayOrderId, int $amountPaise): bool
    {
        return $payment !== null
            && ($payment['order_id'] ?? null) === $razorpayOrderId
            && (int) ($payment['amount'] ?? 0) === $amountPaise
            && ($payment['currency'] ?? null) === 'INR'
            && ! empty($payment['captured']);
    }
}
