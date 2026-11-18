<?php

namespace App\Services\BuyerApp\Payments;

use App\Services\BuyerApp\Checkout\Checkout;
use App\Services\BuyerApp\Checkout\Slots;
use App\Services\BuyerApp\PaymentOptions;
use Illuminate\Support\Facades\DB;

/**
 * Pay online, then the order is placed. In three steps, like the website wallet top-up but for an order:
 *   1. create   the server works out the total (same as the quote), asks Razorpay for an order of exactly that amount and
 *               remembers the customer's choices in app_payment_intents. Nothing is charged yet and no order exists.
 *   2. (app)    the customer pays inside the Razorpay sheet.
 *   3. confirm  the server checks the signature, asks Razorpay whether the money was really captured for that order and
 *               amount, and only then saves the order (Online, Paid) in one transaction that also marks the intent placed.
 * If the customer paid but the order can no longer be placed (an item sold out, a price changed, the cart was emptied, the
 * slot passed), the paid amount is added to the Freshful wallet at once with a ledger entry. Money is never kept without
 * an order or a wallet credit. status() lets the app recover a payment whose confirmation never arrived (app closed).
 */
final class OnlinePayments
{
    private static function fail(string $code, string $message, array $extra = []): array
    {
        return ['ok' => false, 'code' => $code, 'message' => $message, 'extra' => $extra];
    }

    private static function rupees(int $paise): string
    {
        return rtrim(rtrim(number_format($paise / 100, 2, '.', ''), '0'), '.');
    }

    public static function create(int $buyerId, array $in, ?int $ts = null): array
    {
        if (! in_array('online', PaymentOptions::keys($buyerId), true)) {
            return self::fail('payment_not_available', 'Online payment is not available right now.');
        }
        if (! isset($in['expected_total']) || ! is_numeric($in['expected_total'])) {
            return self::fail('expected_total_required', 'Please refresh your cart and try again.');
        }
        [$slot, $err] = Slots::resolve((string) ($in['delivery_type'] ?? ''), (string) ($in['schedule_date'] ?? ''), (string) ($in['schedule_time'] ?? ''), $ts);
        if ($err) {
            return self::fail($err[0], $err[1]);
        }
        $coupon = trim((string) ($in['coupon_code'] ?? ''));
        $q = Checkout::quote($buyerId, $in['address_id'] ?? 0, $coupon, false, date('Y-m-d', $ts ?? time()));
        if (! $q['ok']) {
            return $q;
        }
        $quote = $q['quote'];
        if ($quote['problems']) {
            return self::fail($quote['problems'][0]['code'], $quote['problems'][0]['message'], ['problems' => $quote['problems']]);
        }
        if ($coupon !== '' && $quote['coupon_error']) {
            return self::fail($quote['coupon_error']['code'], $quote['coupon_error']['message'], ['coupon_error' => $quote['coupon_error']]);
        }
        if (abs((float) $in['expected_total'] - $quote['grand_total']) > 0.009) {
            return self::fail('price_changed', 'Prices or charges changed. Please review your order.', ['quote' => $quote]);
        }
        $paise = (int) round($quote['grand_total'] * 100);
        if ($paise < 100) {
            return self::fail('nothing_to_pay', 'This order is too small to pay online. Please choose another payment method.');
        }

        $rzp = Razorpay::createOrder($paise, 'ffa_' . bin2hex(random_bytes(10)), ['buyer_id' => (string) $buyerId, 'purpose' => 'Freshful app order']);
        if ($rzp === null) {
            return self::fail('payment_start_failed', 'Payment could not be started. Please try again.');
        }
        $now = date('Y-m-d H:i:s');
        DB::table('app_payment_intents')->insert([
            'buyer_id' => $buyerId, 'razorpay_order_id' => $rzp, 'amount_paise' => $paise, 'address_id' => (int) $quote['address_id'],
            'coupon_code' => $coupon !== '' ? $coupon : null, 'delivery_type' => $slot['type'], 'schedule_date' => $slot['date'], 'schedule_time' => $slot['time'],
            'status' => 'created', 'created_at' => $now, 'updated_at' => $now,
        ]);
        $b = DB::table('buyers')->where('id', $buyerId)->first(['name', 'phone', 'email']);
        $creds = Razorpay::credentials();

        return ['ok' => true, 'data' => [
            'key_id' => $creds[0], 'razorpay_order_id' => $rzp, 'amount' => $paise, 'currency' => 'INR',
            'name' => 'Freshful', 'description' => 'Freshful order',
            'prefill' => ['name' => (string) ($b->name ?? ''), 'contact' => (string) ($b->phone ?? ''), 'email' => (string) ($b->email ?? '')],
        ]];
    }

    private static function intent(int $buyerId, string $razorpayOrderId): ?object
    {
        return DB::table('app_payment_intents')->where('buyer_id', $buyerId)->where('razorpay_order_id', $razorpayOrderId)->first() ?: null;
    }

    /** What the app is told about an intent that is already settled. */
    private static function settled(object $i): ?array
    {
        if ($i->status === 'placed') {
            return ['ok' => true, 'order' => ['order_id' => (string) $i->order_id, 'status' => 'Order Pending', 'payment_type' => 'Online', 'payment_status' => 'Paid', 'grand_total' => round($i->amount_paise / 100, 2)], 'already' => true];
        }
        if ($i->status === 'refunded_wallet') {
            return self::fail('order_not_placed_refunded', 'We could not place your order. ₹' . self::rupees((int) $i->amount_paise) . ' was added to your Freshful wallet.', ['refunded_to_wallet' => round($i->amount_paise / 100, 2)]);
        }

        return null;
    }

    /** Step 3 from the app: the customer paid, now verify and place. */
    public static function confirm(int $buyerId, string $razorpayOrderId, string $paymentId, string $signature, ?int $ts = null): array
    {
        $i = self::intent($buyerId, $razorpayOrderId);
        if (! $i) {
            return self::fail('payment_not_found', 'This payment could not be matched to your account. Contact support if money was charged.');
        }
        if ($done = self::settled($i)) {
            return $done;
        }
        if (! Razorpay::signatureOk($razorpayOrderId, $paymentId, $signature)) {
            return self::fail('signature_invalid', 'Payment verification failed. Your order was not placed.');
        }
        $payment = Razorpay::payment($paymentId);
        if ($payment === null) {
            return self::fail('verification_delayed', 'Payment received; verification is delayed. Please check your order in a moment.');
        }
        if (! Razorpay::isCaptured($payment, $razorpayOrderId, (int) $i->amount_paise)) {
            return self::fail('payment_not_captured', 'Payment is not confirmed yet. Your order will be placed after confirmation.');
        }

        return self::finalize($i, $paymentId, $ts);
    }

    /** The app asks about a payment it started (e.g. after being closed): recover it if Razorpay has the money. */
    public static function status(int $buyerId, string $razorpayOrderId, ?int $ts = null): array
    {
        $i = self::intent($buyerId, $razorpayOrderId);
        if (! $i) {
            return self::fail('payment_not_found', 'Payment not found.');
        }
        if ($done = self::settled($i)) {
            return $done;
        }
        $items = Razorpay::orderPayments($razorpayOrderId);
        if ($items === null) {
            return self::fail('verification_delayed', 'We could not check this payment right now. Please try again in a moment.');
        }
        foreach ($items as $p) {
            if (is_array($p) && ! empty($p['id']) && Razorpay::isCaptured($p, $razorpayOrderId, (int) $i->amount_paise)) {
                return self::finalize($i, (string) $p['id'], $ts);
            }
        }

        return self::fail('payment_pending', 'No payment received yet for this order.', ['status' => $i->status]);
    }

    /** Money is captured: place the order, or if that is no longer possible, give the money to the wallet. */
    private static function finalize(object $i, string $paymentId, ?int $ts): array
    {
        $buyerId = (int) $i->buyer_id;
        try {
            $r = Checkout::place($buyerId, [
                'address_id' => $i->address_id, 'delivery_type' => $i->delivery_type, 'schedule_date' => $i->schedule_date, 'schedule_time' => $i->schedule_time,
                'coupon_code' => (string) $i->coupon_code, 'expected_total' => $i->amount_paise / 100,
            ], $ts, ['payment_id' => $paymentId, 'intent_id' => (int) $i->id]);
        } catch (PaymentAlreadyUsed $e) {
            // another confirmation of the same payment won the race: report what it did
            $fresh = DB::table('app_payment_intents')->where('id', $i->id)->first();

            return ($fresh ? self::settled($fresh) : null) ?? self::fail('verification_delayed', 'Please check your order in a moment.');
        }
        if ($r['ok']) {
            $r['order']['payment_id'] = $paymentId;

            return $r;
        }

        // paid but not placeable
        $why = $r['code'];
        $result = DB::transaction(function () use ($i, $paymentId, $why, $r) {
            $n = DB::table('app_payment_intents')->where('id', $i->id)->where('status', 'created')
                ->update(['status' => 'refunded_wallet', 'razorpay_payment_id' => $paymentId, 'note' => mb_substr($why, 0, 255), 'updated_at' => date('Y-m-d H:i:s')]);
            if ($n !== 1) {
                return null; // someone else settled it meanwhile
            }
            $bal = Wallet::credit((int) $i->buyer_id, $i->amount_paise / 100, 'online_unfulfilled', 'Online payment returned: order could not be placed', $paymentId, 'intent-refund:' . $i->id);

            return ['balance' => $bal];
        });
        if ($result === null) {
            $fresh = DB::table('app_payment_intents')->where('id', $i->id)->first();

            return ($fresh ? self::settled($fresh) : null) ?? self::fail('verification_delayed', 'Please check your order in a moment.');
        }

        return self::fail('order_not_placed_refunded', 'We could not place your order (' . rtrim($r['message'], '.') . '). ₹' . self::rupees((int) $i->amount_paise) . ' was added to your Freshful wallet.',
            ['reason_code' => $why, 'refunded_to_wallet' => round($i->amount_paise / 100, 2), 'wallet_balance' => $result['balance']]);
    }
}
