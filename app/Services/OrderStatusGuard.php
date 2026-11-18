<?php

namespace App\Services;

use App\Services\BuyerApp\Payments\CancelRefund;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Order status guard (Step 4). Decides who may move an order to which status.
 * decide() is pure (no database) so it can be tested on its own.
 * check() reads the order and the logged-in hub user; settleCancel() refunds a paid cancelled order to the wallet.
 */
final class OrderStatusGuard
{
    /** The real forward flow, in order. */
    public const CHAIN = ['Order Pending', 'Order Placed', 'Order Processed', 'Order Shipped', 'Order Delivered'];
    public const CANCEL = 'Order Cancel';

    /** Older labels still present on old rows, mapped to the flow. */
    private const LEGACY = [
        'Order Accepted'   => 'Order Placed',
        'Order Dispatched' => 'Order Shipped',
        'Delivered'        => 'Order Delivered',
    ];

    /** Panels allowed to change order status at all (route name prefix). */
    private const STAFF = ['admin', 'operation_manager', 'customer_care_manager', 'hub'];

    /** Last status from which a non-admin may still cancel. From Shipped (out of the store) only admin can cancel. */
    private const LAST_CANCEL_FOR_STAFF = 'Order Processed';

    /** Normalise a stored or requested status to the flow name, or null when unknown. */
    public static function normalise(?string $status): ?string
    {
        $s = trim((string) $status);
        if ($s === '') {
            return null;
        }
        if (strcasecmp($s, self::CANCEL) === 0) {
            return self::CANCEL;
        }
        if (isset(self::LEGACY[$s])) {
            return self::LEGACY[$s];
        }
        foreach (self::CHAIN as $name) {
            if (strcasecmp($name, $s) === 0) {
                return $name;
            }
        }
        return null;
    }

    /**
     * Pure decision. $current = stored status, $target = requested status, $panel = route panel, $reason = typed reason.
     * Returns ['ok', 'code', 'message', 'from', 'to', 'refund', 'reopen', 'reason'].
     */
    public static function decide(?string $current, string $target, string $panel, string $reason = ''): array
    {
        $from = self::normalise($current);
        $to = self::normalise($target);
        $reason = trim($reason);
        $admin = $panel === 'admin';
        $base = ['from' => $from, 'to' => $to, 'refund' => false, 'reopen' => false, 'reason' => $reason];

        if (! in_array($panel, self::STAFF, true)) {
            return self::no('panel_not_allowed', 'This panel cannot change order status.', $base);
        }
        if ($to === null) {
            return self::no('unknown_target', 'That order status is not part of the flow.', $base);
        }
        if ($from === null) {
            // Unknown stored status: only admin may touch it, and must give a reason.
            if (! $admin) {
                return self::no('unknown_current', 'This order has an unknown status. Only admin can change it.', $base);
            }
            return self::needReason($reason, array_merge($base, ['reopen' => true]));
        }
        if ($from === $to) {
            return ['ok' => true, 'code' => 'same_status', 'message' => 'Nothing changed.'] + $base;
        }

        // Cancelled and delivered orders are locked: only admin can reopen them, with a reason.
        if ($from === self::CANCEL || $from === 'Order Delivered') {
            if (! $admin) {
                $msg = $from === self::CANCEL
                    ? 'A cancelled order can only be reopened by admin.'
                    : 'A delivered order can only be changed by admin.';
                return self::no('locked', $msg, $base);
            }
            return self::needReason($reason, array_merge($base, ['reopen' => true]));
        }

        $fromIdx = array_search($from, self::CHAIN, true);

        if ($to === self::CANCEL) {
            if (! $admin && $fromIdx > array_search(self::LAST_CANCEL_FOR_STAFF, self::CHAIN, true)) {
                return self::no('cancel_admin_only', 'This order has already been shipped. Only admin can cancel it.', $base);
            }
            return self::needReason($reason, array_merge($base, ['refund' => true]));
        }

        $toIdx = array_search($to, self::CHAIN, true);
        if ($toIdx === $fromIdx + 1) {
            // The one allowed forward step.
            return ['ok' => true, 'code' => '', 'message' => ''] + $base;
        }

        // Backward move or a jump of more than one step: admin only, with a reason.
        if (! $admin) {
            $next = self::CHAIN[$fromIdx + 1] ?? 'none';
            return self::no('not_next_step', 'Only the next step is allowed here: ' . $next . '.', $base);
        }
        return self::needReason($reason, array_merge($base, ['reopen' => true]));
    }

    /** Full check for the controller: reads the order and applies decide() plus the database rules. */
    public static function check(string $panel, array $data): array
    {
        $reason = (string) ($data['reason'] ?? '');
        $query = DB::table('orders');
        if (! empty($data['order_id'])) {
            $query->where('order_id', $data['order_id']);
        } else {
            $query->where('id', $data['id']);
        }
        $first = $query->first();
        if (! $first) {
            return ['ok' => false, 'code' => 'not_found', 'message' => 'Order not found.', 'first' => null] + self::base($data, $reason);
        }

        if ($panel === 'hub') {
            $hubId = self::hubUserHubId();
            if ($hubId === null || (int) $first->hub_id !== $hubId) {
                return ['ok' => false, 'code' => 'other_hub', 'message' => 'This order belongs to another hub.', 'first' => $first] + self::base($data, $reason);
            }
        }

        $d = self::decide((string) $first->order_status, (string) $data['order_status'], $panel, $reason);
        $d['first'] = $first;

        if ($d['ok'] && $d['reopen'] && self::fromCancel($first) && self::refundAlreadyDone((string) $first->order_id)) {
            return ['ok' => false, 'code' => 'refund_done', 'message' => 'This order was cancelled and its refund is already in the wallet. Reopening it needs a manual wallet reversal, so it is blocked.', 'first' => $first] + self::base($data, $reason);
        }

        return $d;
    }

    /** Refund a paid order to the wallet after a cancel. Returns the CancelRefund result, or null when nothing is owed. */
    public static function settleCancel(object $first, string $note): ?array
    {
        $paid = strtolower(trim((string) ($first->payment_status ?? ''))) === 'paid';
        if (! $paid) {
            return null;
        }
        return CancelRefund::toWallet((string) $first->order_id, (int) $first->buyer_id, $first, $note);
    }

    private static function fromCancel(object $first): bool
    {
        return self::normalise((string) $first->order_status) === self::CANCEL;
    }

    private static function refundAlreadyDone(string $orderId): bool
    {
        return DB::table('admin_refunds')->where('order_id', $orderId)->where('reason', 'cancelled')->where('status', 'done')->exists();
    }

    private static function hubUserHubId(): ?int
    {
        try {
            $user = Auth::guard('hub_users')->user();
            return $user && isset($user->hub_id) ? (int) $user->hub_id : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    private static function needReason(string $reason, array $base): array
    {
        if ($reason === '') {
            return self::no('reason_required', 'Please give a reason for this change.', $base);
        }
        return ['ok' => true, 'code' => '', 'message' => ''] + $base;
    }

    private static function no(string $code, string $message, array $base): array
    {
        return ['ok' => false, 'code' => $code, 'message' => $message] + $base;
    }

    private static function base(array $data, string $reason): array
    {
        return ['from' => null, 'to' => self::normalise((string) ($data['order_status'] ?? '')), 'refund' => false, 'reopen' => false, 'reason' => $reason];
    }
}