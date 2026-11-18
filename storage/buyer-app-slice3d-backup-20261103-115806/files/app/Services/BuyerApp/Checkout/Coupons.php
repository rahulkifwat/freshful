<?php

namespace App\Services\BuyerApp\Checkout;

use Illuminate\Support\Facades\DB;

/**
 * Promo codes, worked out on the server. Table home_promotions (title = the code):
 *   percentage     % of the items total, taken off the order
 *   max_amount     the most one order can be reduced by (required: 0 means the coupon is not set up)
 *   min_amount     the order must be at least this much
 *   max_usage      how many times ONE customer may use the code (0 = no limit); counted from buyer_coupon_relation,
 *                  not counting cancelled orders
 *   user_eligible  'everyone' or 'new user' (a customer with no earlier non-cancelled order)
 *   expiry         date Y-m-d, valid through that day; '0' or '' = never
 * Safety: a percentage above buyer_app_commerce.max_coupon_percent (default 50) is treated as not set up, so a
 * mistyped 99 can never reach customers. The text shown in the app is built from these numbers, never from the
 * free-text description (which on live contradicts the numbers).
 */
final class Coupons
{
    public static function maxPercent(): float
    {
        return (float) config('buyer_app_commerce.max_coupon_percent', 50);
    }

    public static function find(string $code): ?object
    {
        $code = trim($code);
        if ($code === '') {
            return null;
        }

        return DB::table('home_promotions')->whereRaw('LOWER(title) = ?', [strtolower($code)])->first() ?: null;
    }

    public static function percent(object $row): ?float
    {
        $p = trim(str_replace('%', '', (string) $row->percentage));
        if (! is_numeric($p) || (float) $p <= 0) {
            return null;
        }

        return (float) $p;
    }

    /** null = fine; otherwise the coupon is not set up properly and must not be offered. */
    private static function setupProblem(object $row): ?string
    {
        $p = self::percent($row);
        if ($p === null || $p > self::maxPercent() || (float) $row->max_amount <= 0) {
            return 'coupon_unavailable';
        }
        $exp = trim((string) $row->expiry);
        if ($exp !== '' && $exp !== '0' && ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $exp)) {
            return 'coupon_unavailable';
        }

        return null;
    }

    private static function money(float $v): string
    {
        return '₹' . number_format($v, 0, '.', ',');
    }

    public static function describe(object $row): string
    {
        $p = self::percent($row) ?? 0;
        $parts = [rtrim(rtrim(number_format($p, 2, '.', ''), '0'), '.') . '% off up to ' . self::money((float) $row->max_amount)];
        if ((float) $row->min_amount > 0) {
            $parts[] = 'on orders of ' . self::money((float) $row->min_amount) . ' or more';
        }
        $text = implode(' ', $parts);
        if ((string) $row->user_eligible === 'new user') {
            $text .= '. New customers only';
        }
        $exp = trim((string) $row->expiry);
        if ($exp !== '' && $exp !== '0' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $exp)) {
            $text .= '. Valid till ' . date('j M Y', strtotime($exp));
        }

        return $text . '.';
    }

    private static function timesUsed(object $row, int $buyerId): int
    {
        $orderIds = DB::table('buyer_coupon_relation')->where('promo_id', (string) $row->id)->where('user_id', (string) $buyerId)->pluck('order_id')->all();
        if (! $orderIds) {
            return 0;
        }

        return DB::table('orders')->whereIn('order_id', $orderIds)->where('order_status', '!=', 'Order Cancel')->distinct()->count('order_id');
    }

    /** @return array{ok: bool, code: ?string, message: ?string, discount: float, short_by: ?float} */
    public static function evaluate(object $row, int $buyerId, float $subtotal, string $today): array
    {
        $no = fn (string $code, string $msg, ?float $short = null) => ['ok' => false, 'code' => $code, 'message' => $msg, 'discount' => 0.0, 'short_by' => $short];

        if (self::setupProblem($row)) {
            return $no('coupon_unavailable', 'This coupon is not available right now.');
        }
        $exp = trim((string) $row->expiry);
        if ($exp !== '' && $exp !== '0' && $today > $exp) {
            return $no('coupon_expired', 'This coupon has expired.');
        }
        if ((string) $row->user_eligible === 'new user'
            && DB::table('orders')->where('buyer_id', $buyerId)->where('order_status', '!=', 'Order Cancel')->exists()) {
            return $no('coupon_new_users_only', 'This coupon is for new customers only.');
        }
        $limit = (int) $row->max_usage;
        if ($limit > 0 && self::timesUsed($row, $buyerId) >= $limit) {
            return $no('coupon_limit_reached', 'You have already used this coupon the maximum number of times.');
        }
        if ($subtotal + 0.0001 < (float) $row->min_amount) {
            $short = round((float) $row->min_amount - $subtotal, 2);

            return $no('coupon_min_order', 'Add ' . self::money($short) . ' more to use this coupon.', $short);
        }
        $discount = round(min($subtotal * (self::percent($row) ?? 0) / 100, (float) $row->max_amount, $subtotal), 2);

        return ['ok' => true, 'code' => null, 'message' => null, 'discount' => $discount, 'short_by' => null];
    }

    /** Coupons to show on the coupon screen for the current cart total. Expired and not-set-up ones are hidden. */
    public static function listFor(int $buyerId, float $subtotal, string $today): array
    {
        $out = [];
        foreach (DB::table('home_promotions')->orderByDesc('id')->get() as $row) {
            if (self::setupProblem($row)) {
                continue;
            }
            $r = self::evaluate($row, $buyerId, $subtotal, $today);
            if ($r['code'] === 'coupon_expired') {
                continue;
            }
            $exp = trim((string) $row->expiry);
            $out[] = [
                'id' => (int) $row->id,
                'code' => (string) $row->title,
                'description' => self::describe($row),
                'percent' => self::percent($row),
                'max_discount' => (float) $row->max_amount,
                'min_order' => (float) $row->min_amount,
                'new_users_only' => (string) $row->user_eligible === 'new user',
                'expires' => ($exp !== '' && $exp !== '0') ? $exp : null,
                'image' => (string) ($row->image ?? ''),
                'eligible' => $r['ok'],
                'reason' => $r['message'],
                'reason_code' => $r['code'],
                'short_by' => $r['short_by'],
                'savings' => $r['ok'] ? $r['discount'] : null,
            ];
        }
        usort($out, fn ($a, $b) => [$b['eligible'], (float) $b['savings']] <=> [$a['eligible'], (float) $a['savings']]);

        return $out;
    }
}
