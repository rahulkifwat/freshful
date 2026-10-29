<?php

namespace App\Services\PosConsole;

/**
 * Freshful POS console - order status rules (pure PHP, no database).
 *
 * Uses ONLY the status names the old POS, the hub panel, the customer app and the rider API already use,
 * with the SAME meaning as the old POS (pos/head.php, pos/settle_order.php):
 *
 *   stage      saved as                          meaning / next steps
 *   new        Order Pending                     accept, reject, move to another hub
 *   accepted   Order Placed (+ legacy Accepted)  weigh items (bill adjusts), dispatch with rider, cancel, move hub
 *              ("ready" on the board = accepted AND weighed; not a separate status)
 *   out        Order Dispatched / Order Shipped  delivered (-> unsettled), settle directly, cancel (returned)
 *   unsettled  Order Processed                   delivered, payment still with the rider ("Unsettled invoice")
 *   delivered  Order Delivered                   settled - money received at the hub (final)
 *   cancelled  Order Cancel                      (final)
 */
class OrderFlow
{
    public const PENDING    = 'Order Pending';
    public const PLACED     = 'Order Placed';
    public const ACCEPTED   = 'Order Accepted';   // legacy value, treated as "accepted"
    public const PROCESSED  = 'Order Processed';  // old POS: delivered, not yet settled
    public const DISPATCHED = 'Order Dispatched';
    public const SHIPPED    = 'Order Shipped';    // legacy value, treated as "out"
    public const DELIVERED  = 'Order Delivered';  // old POS: settled
    public const CANCEL     = 'Order Cancel';

    /** Stage key for every status value we know (case-insensitive lookup). */
    private const STAGE = [
        'order pending'    => 'new',
        'order placed'     => 'accepted',
        'order accepted'   => 'accepted',
        'order dispatched' => 'out',
        'order shipped'    => 'out',
        'dispatched'       => 'out',
        'order processed'  => 'unsettled',
        'order delivered'  => 'delivered',
        'delivered'        => 'delivered',
        'order cancel'     => 'cancelled',
        'cancelled'        => 'cancelled',
    ];

    /** action => [allowed from stages, saved status (null = unchanged), timestamp column to set] */
    private const ACTIONS = [
        'accept'   => [['new'], self::PLACED, 'order_complete_time'],
        'reject'   => [['new'], self::CANCEL, null],
        'relocate' => [['new', 'accepted'], self::PENDING, null],
        'weigh'    => [['accepted'], null, 'processing_complete_time'],
        'dispatch' => [['accepted'], self::DISPATCHED, 'shipping_complete_time'],
        'deliver'  => [['out'], self::PROCESSED, 'delivery_complete_time'],
        'settle'   => [['out', 'unsettled'], self::DELIVERED, 'delivery_complete_time'],
        'cancel'   => [['accepted', 'out'], self::CANCEL, null],
    ];

    /** Actions the cashier triggers with the generic action endpoint. */
    public const SIMPLE_ACTIONS = ['accept', 'reject', 'dispatch', 'deliver', 'cancel'];

    public const ACTIVE = ['new', 'accepted', 'out', 'unsettled'];

    public const STAGES = ['new', 'accepted', 'out', 'unsettled', 'delivered', 'cancelled'];

    public const STAGE_LABEL = [
        'new'       => 'New',
        'accepted'  => 'Accepted',
        'ready'     => 'Ready to dispatch',
        'out'       => 'Out for delivery',
        'unsettled' => 'Delivered - to settle',
        'delivered' => 'Settled',
        'cancelled' => 'Cancelled',
        'other'     => 'Other',
    ];

    /** Payment types that were paid before delivery (nothing to collect except weight adjustments). */
    public const PREPAID = ['online'];

    public static function stage(?string $status): string
    {
        return self::STAGE[strtolower(trim((string) $status))] ?? 'other';
    }

    public static function isPrepaid(?string $paymentType): bool
    {
        return in_array(strtolower(trim((string) $paymentType)), self::PREPAID, true);
    }

    /** Statuses that belong to the given stages (for SQL whereIn). */
    public static function statusesFor(array $stages): array
    {
        $out = [];
        foreach (self::STAGE as $status => $stage) {
            if (in_array($stage, $stages, true)) {
                $out[] = ucwords($status);
            }
        }
        return array_values(array_unique($out));
    }

    /** Actions the POS may offer for an order in this status. */
    public static function actionsFor(?string $status): array
    {
        $stage = self::stage($status);
        $out = [];
        foreach (self::ACTIONS as $action => [$from]) {
            if (in_array($stage, $from, true)) {
                $out[] = $action;
            }
        }
        return $out;
    }

    /**
     * Validate a move. Returns ['ok' => true, 'to' => status|null, 'stamp' => column|null]
     * or ['ok' => false, 'message' => text for the cashier].
     */
    public static function move(?string $currentStatus, string $action): array
    {
        if (!isset(self::ACTIONS[$action])) {
            return ['ok' => false, 'message' => 'Unknown action.'];
        }
        [$from, $to, $stamp] = self::ACTIONS[$action];
        $stage = self::stage($currentStatus);
        if (!in_array($stage, $from, true)) {
            return ['ok' => false, 'message' => 'This order is already "' . self::STAGE_LABEL[$stage] . '". Refresh to see the latest status.'];
        }
        return ['ok' => true, 'to' => $to, 'stamp' => $stamp];
    }

    public static function canSettle(?string $status): bool
    {
        return in_array(self::stage($status), ['out', 'unsettled'], true);
    }

    /** Grams in one pack, or null when the product is not sold by weight (pieces, litres...). */
    public static function packGrams($unitQuantity, ?string $unit): ?float
    {
        $q = (float) $unitQuantity;
        $u = strtolower(preg_replace('/[^a-z]/i', '', (string) $unit));
        if ($q <= 0) return null;
        if (in_array($u, ['kg', 'kgs', 'kilo', 'kilogram', 'kilograms'], true)) return $q * 1000;
        if (in_array($u, ['g', 'gm', 'gms', 'gram', 'grams', 'grm', 'grms'], true)) return $q;
        return null;
    }

    /**
     * Weight adjustment for one line: (actual - expected) grams at the pack price.
     * Same rule as the old POS (invoice.php), but the cashier enters the TOTAL weight of the line.
     */
    public static function weightAdjustment(float $packGrams, float $qty, float $packPrice, float $actualGrams): array
    {
        $expected = $packGrams * $qty;
        $diff = $packGrams > 0 ? ($actualGrams - $expected) * ($packPrice / $packGrams) : 0.0;
        return ['expected_g' => round($expected, 2), 'diff_g' => round($actualGrams - $expected, 2), 'amount' => round($diff, 2)];
    }

    /**
     * What the hub must receive for this order.
     * COD-type: bill + adjustment. Prepaid (Online): only the adjustment (negative = refund to customer).
     */
    public static function expectedCollection(?string $paymentType, float $bill, float $adjustment): float
    {
        return round(self::isPrepaid($paymentType) ? $adjustment : $bill + $adjustment, 2);
    }

    /**
     * When the order is due, as a unix time.
     * Express: 90 minutes after it was placed. Scheduled: end of the chosen slot on the chosen date
     * (slot text like "7 AM - 9 AM", "07:00-09:00", "6:30 PM to 8:30 PM"); falls back to 90 minutes.
     */
    public static function dueAt(?string $deliveryType, ?string $dateAdded, ?string $scheduleDate, ?string $scheduleTime): ?int
    {
        $placed = $dateAdded ? strtotime($dateAdded) : false;
        $scheduleDate = self::normDate($scheduleDate);
        if (strtolower((string) $deliveryType) === 'scheduled' && $scheduleDate) {
            $end = self::slotEnd((string) $scheduleTime);
            if ($end !== null) {
                $ts = strtotime($scheduleDate . ' ' . $end);
                if ($ts !== false) {
                    return $ts;
                }
            }
            $day = strtotime($scheduleDate . ' 21:00');
            if ($day !== false) {
                return $day;
            }
        }
        return $placed !== false ? $placed + 90 * 60 : null;
    }

    /** Orders still open this long after they were due are "old open" (kept off the live board, no alarm). */
    public const STALE_AFTER = 3 * 86400;

    public static function isStale(string $stage, ?int $dueAt, ?int $placedAt, int $now): bool
    {
        if (!in_array($stage, self::ACTIVE, true)) return false;
        $ref = $dueAt ?? $placedAt;
        return $ref !== null && $ref < $now - self::STALE_AFTER;
    }

    /** Any saved date text as Y-m-d, or null when it is empty / not a real date. */
    public static function normDate($value): ?string
    {
        $s = trim((string) $value);
        if ($s === '' || str_starts_with($s, '0000') || strcasecmp($s, 'null') === 0 || strcasecmp($s, 'Invalid Date') === 0) return null;
        if (ctype_digit($s) && strlen($s) >= 9) {   // unix time (seconds or milliseconds)
            $n = (int) $s; if ($n > 100000000000) $n = intdiv($n, 1000);
            return date('Y-m-d', $n);
        }
        if (preg_match('/^(\d{4})[-\/.](\d{1,2})[-\/.](\d{1,2})/', $s, $m) && checkdate((int) $m[2], (int) $m[3], (int) $m[1])) {
            return sprintf('%04d-%02d-%02d', $m[1], $m[2], $m[3]);
        }
        if (preg_match('/^(\d{1,2})[-\/.](\d{1,2})[-\/.](\d{2,4})/', $s, $m)) {   // Indian style: day first
            $y = (int) $m[3]; if ($y < 100) $y += 2000;
            return checkdate((int) $m[2], (int) $m[1], $y) ? sprintf('%04d-%02d-%02d', $y, $m[2], $m[1]) : null;
        }
        if (preg_match('/^\d{4}[-\/.]\d{1,2}[-\/.]\d{1,2}/', $s)) return null;   // numeric but impossible (e.g. 2026-02-31)
        $ts = strtotime($s);
        return ($ts !== false && $ts > 946684800) ? date('Y-m-d', $ts) : null;   // after year 2000
    }

    /** Last time mentioned in a slot text, as "H:i" (24h), or null. */
    public static function slotEnd(string $slot): ?string
    {
        if (!preg_match_all('/(\d{1,2})(?:[:.](\d{2}))?\s*(am|pm)?/i', $slot, $m, PREG_SET_ORDER)) {
            return null;
        }
        $last = end($m);
        $h = (int) $last[1];
        $min = isset($last[2]) && $last[2] !== '' ? (int) $last[2] : 0;
        $ampm = strtolower($last[3] ?? '');
        if ($ampm === '' && count($m) > 1) {
            $ampm = strtolower($m[0][3] ?? '');   // "7 - 9 PM" style
        }
        if ($ampm === 'pm' && $h < 12) $h += 12;
        if ($ampm === 'am' && $h === 12) $h = 0;
        if ($h > 23 || $min > 59) return null;
        if (stripos($slot, 'min') !== false && $ampm === '') return null;   // "60 min" is a duration, not a time
        return sprintf('%02d:%02d', $h, $min);
    }
}
