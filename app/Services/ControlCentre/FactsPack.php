<?php

namespace App\Services\ControlCentre;

use App\Services\CountryConsole\CountryAnalytics;
use App\Services\CountryConsole\CountryScope;

/**
 * Freshful Control Centre update 2 (15 Nov 2026): the only data the analyst may use.
 * A short list of facts [id, label, value, link], taken from the console's own overview figures and the alert and data-health
 * tables. It holds no customer names, phone numbers, e-mails, addresses, passwords or raw orders. Anything that looks like a
 * phone number or e-mail is removed before the pack can leave the server.
 */
class FactsPack
{
    public const ACTIONS = ['orders' => 'Open the orders', 'cash' => 'Open cash at hubs', 'danger' => 'Open danger and expired stock', 'stock' => 'Open stock by product',
        'cancels' => 'Open cancellations', 'reports' => 'Open the sales summary', 'league' => 'Open the hub ranking', 'cc-alerts' => 'Open the alert centre', 'cc-health' => 'Open data health'];

    public static function build(CountryScope $scope, string $from, string $to): array
    {
        $ov = CountryAnalytics::overview($scope, $from, $to);
        $alerts = AlertEngine::on() ? AlertEngine::list(['status' => 'active']) : ['alerts' => []];
        $health = DataHealth::run();
        return self::fromOverview($ov, $alerts['alerts'] ?? [], $health, Metrics::freshness());
    }

    /** Pure function (no database): easy to test. */
    public static function fromOverview(array $ov, array $alerts, array $health, array $fresh): array
    {
        $k = $ov['kpis'] ?? [];
        $f = [];
        $add = function (string $id, string $label, $value, ?string $link = null, string $unit = '') use (&$f) { if ($value === null) return; $f[$id] = ['id' => $id, 'label' => $label, 'value' => is_float($value) ? round($value, 2) : $value, 'unit' => $unit, 'link' => $link]; };
        $add('sales', 'Sales (not cancelled)', (float) ($k['sales'] ?? 0), 'reports', 'Rs');
        $add('sales_prev', 'Sales in the comparison period', (float) ($k['sales_prev'] ?? 0), 'reports', 'Rs');
        $add('sales_vs', 'Sales change vs comparison period', $k['sales_vs'] ?? null, 'reports', '%');
        $add('orders', 'Orders (not cancelled)', (int) ($k['orders'] ?? 0), 'orders');
        $add('orders_prev', 'Orders in the comparison period', (int) ($k['orders_prev'] ?? 0), 'orders');
        $add('orders_vs', 'Orders change vs comparison period', $k['orders_vs'] ?? null, 'orders', '%');
        $add('aov', 'Average order value', (float) ($k['aov'] ?? 0), 'reports', 'Rs');
        $add('cancelled', 'Cancelled orders', (int) ($k['cancelled'] ?? 0), 'cancels');
        $add('cancel_rate', 'Cancellation rate', (float) ($k['cancel_rate'] ?? 0), 'cancels', '%');
        $add('delivered', 'Delivered orders', (int) ($k['delivered'] ?? 0), 'orders');
        $add('on_time', 'On-time delivery', $k['on_time'] ?? null, 'orders', '%');
        $add('avg_min', 'Average express delivery time', $k['avg_min'] ?? null, 'orders', 'min');
        $add('customers', 'Customers who ordered', (int) ($k['customers'] ?? 0), null);
        $add('new_customers', 'New customers', (int) ($k['new_customers'] ?? 0), null);
        $add('repeat_customers', 'Repeat customers', (int) ($k['repeat_customers'] ?? 0), null);
        $add('express', 'Express orders', (int) ($k['express'] ?? 0), 'orders');
        $add('scheduled', 'Scheduled orders', (int) ($k['scheduled'] ?? 0), 'orders');
        $add('wastage_value', 'Wastage value', (float) ($k['wastage_value'] ?? 0), 'stock', 'Rs');
        $add('stock_value', 'Stock value (selling price)', (float) ($k['stock_value'] ?? 0), 'stock', 'Rs');
        $add('danger_value', 'Danger stock value', (float) ($k['danger_value'] ?? 0), 'danger', 'Rs');
        $add('expired_value', 'Expired stock value', (float) ($k['expired_value'] ?? 0), 'danger', 'Rs');
        $add('cash_in_hand', 'Rider cash not yet settled', (float) ($k['cash_in_hand'] ?? 0), 'cash', 'Rs');
        $add('open_now', 'Orders open right now', (int) ($k['open_now'] ?? 0), 'orders');
        $add('late_now', 'Orders late right now', (int) ($k['late_now'] ?? 0), 'orders');
        foreach (array_slice($ov['pay_mix'] ?? [], 0, 5) as $p) { $key = strtolower(preg_replace('/[^a-z0-9]+/i', '_', (string) $p['type'])); $add('pay_' . $key . '_sales', 'Sales paid by ' . $p['type'], (float) $p['sales'], 'reports', 'Rs'); $add('pay_' . $key . '_orders', 'Orders paid by ' . $p['type'], (int) $p['orders'], 'reports'); }
        foreach (array_slice($ov['top_products'] ?? [], 0, 10) as $i => $p) $add('top_product_' . ($i + 1), 'Top product #' . ($i + 1) . ': ' . $p['name'] . ' (sales value, Rs)', (float) $p['value'], 'stock', 'Rs');
        foreach (array_slice($ov['categories'] ?? [], 0, 8) as $i => $c) $add('category_' . ($i + 1), 'Category #' . ($i + 1) . ': ' . $c['name'] . ' (sales value, Rs)', (float) $c['value'], 'reports', 'Rs');
        $hubs = $ov['league']['hubs'] ?? [];
        foreach (array_slice($hubs, 0, 20) as $i => $h) { $add('hub_' . ($h['id'] ?? $i) . '_sales', 'Hub ' . $h['name'] . ': sales', (float) ($h['sales'] ?? 0), 'league', 'Rs'); $add('hub_' . ($h['id'] ?? $i) . '_orders', 'Hub ' . $h['name'] . ': orders', (int) ($h['orders'] ?? 0), 'league'); }
        $by = [];
        foreach ($alerts as $a) { $by[$a['rule']] = ($by[$a['rule']] ?? 0) + 1; }
        $add('alerts_open', 'Open alerts needing attention', count($alerts), 'cc-alerts');
        foreach ($by as $rule => $n) $add('alerts_' . $rule, 'Open alerts: ' . str_replace('_', ' ', $rule), $n, 'cc-alerts');
        foreach ($health as $c) if (in_array($c['status'], ['warn', 'bad'], true)) $add('data_' . $c['key'], 'Data problem: ' . $c['label'] . ' (records affected)', (int) $c['bad'], 'cc-health');
        $missing = ['Margin is unavailable (cost price basis not confirmed).', 'Demand forecast is not built yet.'];
        if (($k['on_time'] ?? null) === null) $missing[] = 'On-time delivery has no timed deliveries in this period.';
        $meta = ['scope' => (string) ($ov['scope'] ?? 'All India'), 'from' => (string) ($ov['from'] ?? ''), 'to' => (string) ($ov['to'] ?? ''), 'compare' => (string) ($ov['compare'] ?? ''),
            'read_at' => (string) ($fresh['now_text'] ?? ''), 'last_order_at' => $fresh['last_order_at'] ?? null, 'orders' => (int) ($k['orders'] ?? 0), 'missing' => $missing];
        return ['facts' => self::clean($f), 'meta' => $meta];
    }

    /** Remove anything that looks like a phone number or e-mail from every text field. */
    public static function clean(array $facts): array
    {
        foreach ($facts as $id => $x) {
            $facts[$id]['label'] = self::scrub((string) $x['label']);
            if (is_string($x['value'])) $facts[$id]['value'] = self::scrub($x['value']);
        }
        return $facts;
    }

    public static function scrub(string $s): string
    {
        $s = preg_replace('/[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}/i', '[removed]', $s);
        return preg_replace('/(?<![\d.])(\+?\d[\d\s\-]{8,}\d)(?![\d])/', '[removed]', $s);
    }
}
