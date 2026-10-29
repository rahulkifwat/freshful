<?php

namespace App\Services\PosConsole;

/**
 * Freshful POS console - order status rules (pure PHP, no database).
 *
 * Uses ONLY status names already stored in the orders table, so the customer app,
 * the rider app, the hub panel and the admin keep understanding every order.
 *
 *   stage      saved as                         next allowed step
 *   new        Order Pending                    accept -> Order Placed, reject -> Order Cancel
 *   accepted   Order Placed (+ legacy Accepted) pack   -> Order Processed, cancel
 *   packing    Order Processed                  dispatch -> Order Dispatched, cancel
 *   out        Order Dispatched / Order Shipped deliver -> Order Delivered, cancel (failed delivery)
 *   delivered  Order Delivered                  (final; payment can be settled)
 *   cancelled  Order Cancel                     (final)
 */
class OrderFlow
{
    public const PENDING    = 'Order Pending';
    public const PLACED     = 'Order Placed';
    public const ACCEPTED   = 'Order Accepted';   // legacy POS value, treated as "accepted"
    public const PROCESSED  = 'Order Processed';
    public const DISPATCHED = 'Order Dispatched';
    public const SHIPPED    = 'Order Shipped';    // legacy value, treated as "out for delivery"
    public const DELIVERED  = 'Order Delivered';
    public const CANCEL     = 'Order Cancel';

    /** Stage key for every status value we know (case-insensitive lookup). */
    private const STAGE = [
        'order pending'    => 'new',
        'order placed'     => 'accepted',
        'order accepted'   => 'accepted',
        'order processed'  => 'packing',
        'order dispatched' => 'out',
        'order shipped'    => 'out',
        'dispatched'       => 'out',
        'order delivered'  => 'delivered',
        'delivered'        => 'delivered',
        'order cancel'     => 'cancelled',
        'cancelled'        => 'cancelled',
    ];

    /** action => [allowed from stages, saved status, timestamp column to set] */
    private const ACTIONS = [
        'accept'   => [['new'], self::PLACED, 'order_complete_time'],
        'pack'     => [['accepted'], self::PROCESSED, 'processing_complete_time'],
        'dispatch' => [['packing', 'accepted'], self::DISPATCHED, 'shipping_complete_time'],
        'deliver'  => [['out'], self::DELIVERED, 'delivery_complete_time'],
        'reject'   => [['new'], self::CANCEL, null],
        'cancel'   => [['accepted', 'packing', 'out'], self::CANCEL, null],
    ];

    public const STAGES = ['new', 'accepted', 'packing', 'out', 'delivered', 'cancelled'];

    public const STAGE_LABEL = [
        'new'       => 'New',
        'accepted'  => 'Accepted',
        'packing'   => 'Packing',
        'out'       => 'Out for delivery',
        'delivered' => 'Delivered',
        'cancelled' => 'Cancelled',
        'other'     => 'Other',
    ];

    public static function stage(?string $status): string
    {
        return self::STAGE[strtolower(trim((string) $status))] ?? 'other';
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
     * Validate a move. Returns ['ok' => true, 'to' => status, 'stamp' => column|null]
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

    /** Settle (mark paid) is allowed once the order is out for delivery or delivered, and not yet paid. */
    public static function canSettle(?string $status, ?string $paymentStatus): bool
    {
        return in_array(self::stage($status), ['out', 'delivered'], true)
            && strtolower(trim((string) $paymentStatus)) !== 'paid';
    }

    /**
     * When the order is due, as a unix time.
     * Express: 90 minutes after it was placed. Scheduled: end of the chosen slot on the chosen date
     * (slot text like "7 AM - 9 AM", "07:00-09:00", "6:30 PM to 8:30 PM"); falls back to 90 minutes.
     */
    public static function dueAt(?string $deliveryType, ?string $dateAdded, ?string $scheduleDate, ?string $scheduleTime): ?int
    {
        $placed = $dateAdded ? strtotime($dateAdded) : false;
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
