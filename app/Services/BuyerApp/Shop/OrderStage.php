<?php

namespace App\Services\BuyerApp\Shop;

/**
 * What a customer sees for an order, and when the customer may cancel it.
 * Pure PHP (no database), so the rules can be tested on their own.
 *
 * The status words mean the same as in the POS (OrderFlow):
 *   Order Pending                      new, waiting for the hub to accept
 *   Order Placed / Order Accepted      accepted by the hub (stock is taken off the shelf)
 *   Order Dispatched / Order Shipped   out for delivery
 *   Order Processed                    delivered, money still with the rider
 *   Order Delivered                    delivered and settled
 *   Order Cancel                       cancelled
 */
final class OrderStage
{
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

    /** Words for the customer. Delivered-but-unsettled is simply "Delivered" to a customer. */
    public const LABEL = [
        'new'       => 'Order placed',
        'accepted'  => 'Accepted by store',
        'out'       => 'Out for delivery',
        'unsettled' => 'Delivered',
        'delivered' => 'Delivered',
        'cancelled' => 'Cancelled',
        'other'     => 'In progress',
    ];

    /** Payment types that may already be paid: cancelling needs a refund, which comes with the payments slice. */
    private const NEEDS_REFUND = ['online', 'card', 'wallet'];

    public const CANCEL_REASONS = [
        1 => 'Ordered by mistake',
        2 => 'Want to change items or quantity',
        3 => 'Want to change delivery address or time',
        4 => 'Delivery is taking too long',
        5 => 'Found a better price elsewhere',
        6 => 'Other',
    ];

    public const EXPRESS_MINUTES = 90;

    public static function stage(?string $status): string
    {
        return self::STAGE[strtolower(trim((string) $status))] ?? 'other';
    }

    /** A saved time as text, or null when it is empty / not a real time. */
    public static function cleanTime($value): ?string
    {
        $s = trim((string) $value);
        if ($s === '' || str_starts_with($s, '0000') || strtotime($s) === false) {
            return null;
        }

        return $s;
    }

    /**
     * Four steps for the tracking screen.
     * @param array $o keys: order_status, date_added, order_complete_time, shipping_complete_time, delivery_complete_time
     */
    public static function timeline(array $o): array
    {
        $stage = self::stage($o['order_status'] ?? '');
        $reached = [
            'new'       => 1,
            'accepted'  => 2,
            'out'       => 3,
            'unsettled' => 4,
            'delivered' => 4,
        ];
        $level = $reached[$stage] ?? 1;

        $steps = [
            ['key' => 'placed',    'label' => 'Order placed',      'done' => true,           'time' => self::cleanTime($o['date_added'] ?? null)],
            ['key' => 'accepted',  'label' => 'Accepted by store', 'done' => $level >= 2,    'time' => self::cleanTime($o['order_complete_time'] ?? null)],
            ['key' => 'out',       'label' => 'Out for delivery',  'done' => $level >= 3,    'time' => self::cleanTime($o['shipping_complete_time'] ?? null)],
            ['key' => 'delivered', 'label' => 'Delivered',         'done' => $level >= 4,    'time' => self::cleanTime($o['delivery_complete_time'] ?? null)],
        ];

        if ($stage === 'cancelled') {
            // Whatever happened before, the order ended here.
            $steps = [$steps[0], ['key' => 'cancelled', 'label' => 'Cancelled', 'done' => true, 'time' => null]];

            return ['current' => 'cancelled', 'steps' => $steps];
        }

        $current = 'placed';
        foreach ($steps as $s) {
            if ($s['done']) {
                $current = $s['key'];
            }
        }
        // A step that is done but has no saved time keeps time = null (the app just shows the tick).
        return ['current' => $current, 'steps' => $steps];
    }

    /** Express orders are due 90 minutes after they were placed. Scheduled orders show their slot instead (null here). */
    public static function expectedBy(?string $deliveryType, ?string $dateAdded): ?string
    {
        if (strtolower(trim((string) $deliveryType)) !== 'express') {
            return null;
        }
        $placed = $dateAdded ? strtotime($dateAdded) : false;

        return $placed === false ? null : date('Y-m-d H:i:s', $placed + self::EXPRESS_MINUTES * 60);
    }

    /**
     * May the customer cancel now?
     * Only while the order is still new. After the hub accepts, stock has been taken off the shelf and the hub or
     * support has to decide. Orders that may be paid already are refused until refunds exist (payments slice).
     *
     * @return array{ok: bool, code: string, message: string}
     */
    public static function cancelDecision(?string $status, ?string $paymentType, bool $stockTaken): array
    {
        $stage = self::stage($status);

        if ($stage === 'cancelled') {
            return self::no('already_cancelled', 'This order is already cancelled.');
        }
        if ($stage === 'unsettled' || $stage === 'delivered') {
            return self::no('already_delivered', 'This order has already been delivered.');
        }
        if ($stage === 'out') {
            return self::no('order_on_the_way', 'Your order is already out for delivery, so it cannot be cancelled here. Please contact support.');
        }
        if ($stage === 'accepted' || ($stage === 'new' && $stockTaken)) {
            return self::no('order_accepted', 'The store has already accepted your order and is preparing it, so it cannot be cancelled here. Please contact support.');
        }
        if ($stage !== 'new') {
            return self::no('not_cancellable', 'This order cannot be cancelled here. Please contact support.');
        }
        if (in_array(strtolower(trim((string) $paymentType)), self::NEEDS_REFUND, true)) {
            return self::no('refund_pending', 'Orders paid by wallet, card or online cannot be cancelled in the app yet. Please contact support.');
        }

        return ['ok' => true, 'code' => '', 'message' => ''];
    }

    private static function no(string $code, string $message): array
    {
        return ['ok' => false, 'code' => $code, 'message' => $message];
    }
}
