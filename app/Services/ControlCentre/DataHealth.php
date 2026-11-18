<?php

namespace App\Services\ControlCentre;

use App\Services\CountryConsole\CountryScope;
use Illuminate\Support\Facades\DB;

/**
 * Freshful Control Centre (15 Nov 2026): data quality checks. Read only. Every check says what it looked at,
 * how many records are affected, and which numbers it can make wrong. status: ok | warn | bad | na (cannot be checked).
 */
class DataHealth
{
    private static function chk(string $key, string $label, ?int $bad, ?int $total, string $affects, string $hint, int $warnAt = 1, int $badPct = 5): array
    {
        if ($bad === null) return ['key' => $key, 'label' => $label, 'status' => 'na', 'bad' => 0, 'total' => 0, 'affects' => $affects, 'hint' => $hint, 'detail' => 'Cannot be checked: a table or column is missing.'];
        $pct = $total ? $bad * 100 / $total : 0;
        $st = $bad < $warnAt ? 'ok' : ($pct >= $badPct ? 'bad' : 'warn');
        return ['key' => $key, 'label' => $label, 'status' => $st, 'bad' => $bad, 'total' => (int) $total, 'affects' => $affects, 'hint' => $hint,
            'detail' => $bad ? number_format($bad) . ' of ' . number_format((int) $total) . ' affected.' : 'No problem found in ' . number_format((int) $total) . '.'];
    }

    private static function safe(callable $fn): ?int
    {
        try { return (int) $fn(); } catch (\Throwable $e) { return null; }
    }

    public static function run(): array
    {
        $o = CountryScope::has('orders');
        $orders = $o ? self::safe(fn () => DB::table('orders')->distinct()->count('order_id')) : null;
        $c = [];
        $c[] = self::chk('pay_blank', 'Orders without a payment type', $o ? self::safe(fn () => DB::table('orders')->where(function ($q) { $q->whereNull('payment_type')->orWhere('payment_type', ''); })->distinct()->count('order_id')) : null, $orders,
            'Cash, card and wallet splits; cash expected from riders.', 'Fix the order source so a payment type is always saved. Until then these orders are counted as Cash.');
        $c[] = self::chk('type_blank', 'Orders without a delivery type', $o ? self::safe(fn () => DB::table('orders')->where(function ($q) { $q->whereNull('delivery_type')->orWhere('delivery_type', ''); })->distinct()->count('order_id')) : null, $orders,
            'Express vs scheduled split and on-time delivery.', 'Counted as Express. Make the checkout always save a delivery type.');
        $c[] = self::chk('date_bad', 'Orders with no usable order date', $o ? self::safe(fn () => DB::table('orders')->where(function ($q) { $q->whereNull('date_added')->orWhere('date_added', '<', '2000-01-01'); })->distinct()->count('order_id')) : null, $orders,
            'Every report: such orders are left out of all date ranges.', 'Find the app path that creates them. They cannot be placed in a period.');
        $c[] = self::chk('hub_missing', 'Orders not linked to a hub', $o ? self::safe(fn () => DB::table('orders')->where(function ($q) { $q->whereNull('hub_id')->orWhere('hub_id', 0); })->distinct()->count('order_id')) : null, $orders,
            'Hub, city and state totals: these orders appear in no hub.', 'Assign a hub at checkout.');
        $hasP = CountryScope::has('products');
        $cut = date('Y-m-d H:i:s', strtotime('-90 days'));
        $orphan = fn () => DB::table('orders AS o')->leftJoin('products AS p', 'p.id', '=', 'o.product_id')->whereNull('p.id');
        $lines = $o ? self::safe(fn () => DB::table('orders')->where('date_added', '>=', $cut)->count()) : null;
        $recent = ($o && $hasP) ? self::safe(fn () => $orphan()->where('o.date_added', '>=', $cut)->count()) : null;
        $c[] = self::chk('product_orphan', 'Order lines (last 90 days) whose product no longer exists', $recent, $lines,
            'Product-wise sales and stock cover.', 'Do not delete products that have orders; switch them off instead.');
        if ($recent !== null) {
            $older = self::safe(fn () => $orphan()->where('o.date_added', '<', $cut)->count());
            if ($older) $c[count($c) - 1]['detail'] .= ' Plus ' . number_format($older) . ' older order lines (legacy orders) that point to removed products; they are not counted here.';
        }
        $inv = CountryScope::has('inventory');
        $c[] = self::chk('stock_negative', 'Stock lines below zero', $inv ? self::safe(fn () => DB::table('inventory')->whereRaw('CAST(live_stock AS DECIMAL(14,3)) < 0')->count()) : null, $inv ? self::safe(fn () => DB::table('inventory')->count()) : null,
            'Days of cover, expired stock and stock value.', 'Do a physical count at the hub and correct through the stock engine.');
        $c[] = self::chk('stock_orphan', 'Stock lines for products that do not exist', ($inv && $hasP) ? self::safe(fn () => DB::table('inventory AS i')->leftJoin('products AS p', 'p.product_id', '=', 'i.product_id')->whereNull('p.id')->count()) : null, $inv ? self::safe(fn () => DB::table('inventory')->count()) : null,
            'Stock totals and stock value in the database. The console screens already ignore these old lines.', 'These are old product codes no longer in the catalogue. Do not delete yet: ask for a safe clean-up list first (it shows each line and its stock).');
        $c[] = self::chk('lots', 'Stock age tracking installed', CountryScope::has('hub_stock_lots') ? 0 : null, 1, 'Expired and danger stock alerts.', 'Install hub console update 5.');
        $cost = CountryScope::has('products', 'cost_price');
        $c[] = self::chk('cost_missing', 'Products without a cost price', $cost ? self::safe(fn () => DB::table('products')->where(function ($q) { $q->whereNull('cost_price')->orWhereRaw('CAST(cost_price AS DECIMAL(14,2)) <= 0'); })->count()) : null, $cost ? self::safe(fn () => DB::table('products')->count()) : null,
            'Margin. Margin stays unavailable until cost price is complete and its basis is confirmed.', 'Fill cost price for every product and confirm whether it is per pack or per kg.', 1, 20);
        return $c;
    }

    public static function summary(array $checks): array
    {
        $n = ['ok' => 0, 'warn' => 0, 'bad' => 0, 'na' => 0];
        foreach ($checks as $c) $n[$c['status']]++;
        return $n + ['overall' => $n['bad'] ? 'bad' : ($n['warn'] ? 'warn' : 'ok')];
    }
}
