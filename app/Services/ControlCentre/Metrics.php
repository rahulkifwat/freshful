<?php

namespace App\Services\ControlCentre;

use App\Services\CountryConsole\CountryScope;
use Illuminate\Support\Facades\DB;

/**
 * Freshful Control Centre (15 Nov 2026): the one place where every business number is defined in words.
 * Nothing here invents data: a metric that the database cannot support is listed as "unavailable" with the reason.
 */
class Metrics
{
    /** key => [label, definition, source, available(bool|callable), why_unavailable] */
    public static function definitions(): array
    {
        $cost = CountryScope::has('products', 'cost_price');
        return [
            'sales' => ['Sales', 'Total bill of orders that are not cancelled: item total + delivery charge + cash to collect - amount returned. One order is counted once, even when it has many product lines.', 'orders (grouped by order_id), date_added', true, null],
            'orders' => ['Orders', 'Count of distinct order_id that are not cancelled, by the date the order was placed (date_added).', 'orders.order_id, orders.date_added', true, null],
            'aov' => ['Average order value', 'Sales divided by Orders for the same period and filters.', 'derived', true, null],
            'cancel_rate' => ['Cancellation rate', 'Cancelled orders divided by all orders placed in the period.', 'orders.order_status', true, null],
            'on_time' => ['On-time delivery', 'Delivered orders that arrived at or before the promised time, among delivered orders that have both times recorded.', 'orders.delivery_complete_time, schedule_date, schedule_time', CountryScope::has('orders', 'delivery_complete_time'), 'Delivery completion time is not recorded.'],
            'cash_pending' => ['Cash not settled', 'Orders delivered by the rider but whose cash is not yet settled at the hub (status Order Processed).', 'orders.order_status', true, null],
            'stock_cover' => ['Days of cover', 'Live stock divided by average units sold per day over the last 7 days, per hub and product. Estimated: stock is the hub count, not a physical audit.', 'inventory.live_stock, orders.quantity', CountryScope::has('inventory'), 'Inventory table missing.'],
            'expired_stock' => ['Expired stock', 'Stock lots received 2 or more days ago that still have quantity left. Policy: fresh today, danger yesterday, expired from 2 days.', 'hub_stock_lots.received_on, qty_left', CountryScope::has('hub_stock_lots'), 'Stock age tracking is not installed.'],
            'margin' => ['Gross margin', 'Selling price minus cost price per unit sold.', 'products.cost_price', false, $cost ? 'Cost price exists but its basis (per pack or per kg) is not confirmed, so margin is not shown.' : 'There is no cost price column.'],
            'forecast' => ['Demand forecast', 'Expected units per product and hub for coming days.', 'order history', false, 'Not built yet (planned for update 3). With few orders, any forecast would be low confidence.'],
        ];
    }

    /** What the screen should say about how fresh the data is. */
    public static function freshness(): array
    {
        $now = time();
        $last = null;
        try { $v = DB::table('orders')->max('date_added'); $last = CountryScope::ts($v); } catch (\Throwable $e) { $last = null; }
        $inv = null;
        if (CountryScope::has('inventory', 'updated_at')) { try { $inv = CountryScope::ts(DB::table('inventory')->max('updated_at')); } catch (\Throwable $e) { $inv = null; } }
        return ['now' => $now, 'now_text' => date('d M Y, h:i A', $now), 'last_order_at' => $last, 'last_stock_update' => $inv,
            'note' => 'Figures are read live from the database each time this page loads. Time zone: Asia/Kolkata.'];
    }

    public static function listForScreen(): array
    {
        $out = [];
        foreach (self::definitions() as $k => $d) {
            $ok = is_callable($d[3]) ? (bool) ($d[3])() : (bool) $d[3];
            $out[] = ['key' => $k, 'label' => $d[0], 'definition' => $d[1], 'source' => $d[2], 'available' => $ok, 'why' => $ok ? null : $d[4]];
        }
        return $out;
    }
}
