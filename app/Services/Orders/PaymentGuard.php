<?php

namespace App\Services\Orders;

use App\Exceptions\ApiException;
use Illuminate\Support\Facades\Log;

/**
 * Order payment guard (15 Nov 2026). Used by the website checkout and the old /api/add_order.
 * Online and Wallet orders were saved without any payment being taken or checked. Until that exists, only Cash is accepted.
 * A blank payment type from the old app is saved as Cash (the only method it can really use).
 */
class PaymentGuard
{
    public const MESSAGE = 'Online and wallet payment are not available right now. Please choose Cash on delivery.';

    public static function allowAll(): bool
    {
        return (bool) config('order_guard.allow_online_wallet', false);
    }

    /** 'cash', 'COD', 'Cash on delivery' and blank all mean Cash. Anything else is returned trimmed. */
    public static function normalise($type): string
    {
        $t = trim((string) $type);
        return in_array(strtolower($t), ['', 'cash', 'cod', 'cash on delivery'], true) ? 'Cash' : $t;
    }

    /** Message to show the customer, or null when this payment type may be placed. */
    public static function refusal($type): ?string
    {
        if (self::normalise($type) === 'Cash' || self::allowAll()) return null;
        try { Log::notice('[order-guard] refused payment type', ['type' => substr(trim((string) $type), 0, 20)]); } catch (\Throwable $e) { /* logging must never block */ }
        return self::MESSAGE;
    }

    /** Old app endpoint: returns the payment type to save, or throws the standard API error. */
    public static function forLegacy($type): string
    {
        if ($msg = self::refusal($type)) throw new ApiException($msg);
        return self::normalise($type) === 'Cash' ? 'Cash' : trim((string) $type);
    }
}
