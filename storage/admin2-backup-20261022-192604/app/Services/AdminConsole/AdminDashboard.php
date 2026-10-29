<?php

namespace App\Services\AdminConsole;

use App\Services\CountryConsole\CountryAnalytics;
use App\Services\CountryConsole\CountryScope;
use App\Services\CountryConsole\CountryStock;
use App\Services\CountryConsole\CountryUnits;
use App\Services\PosConsole\OrderFlow;
use Illuminate\Support\Facades\DB;

/**
 * Freshful admin console (admin update 1, 22 Oct 2026): the admin dashboard.
 * Everything the old admin dashboard showed (customers, revenue, orders by status, products, orders by hub,
 * customers by hub, sales by item), counted correctly (statuses follow the POS rules; revenue includes delivery and
 * weight adjustments), plus a "needs attention" list built from the data.
 */
class AdminDashboard
{
    private static function h(string $t, ?string $c = null): bool { return CountryScope::has($t, $c); }

    public static function data(CountryScope $scope, string $from, string $to): array
    {
        $ids = $scope->ids(); $hubs = CountryScope::hubs();
        // All time (like the old dashboard)
        $all = ['orders' => 0, 'new' => 0, 'ongoing' => 0, 'delivered' => 0, 'cancelled' => 0, 'revenue' => 0.0];
        $sel = [DB::raw('MAX(order_status) AS st'), DB::raw('MAX(total_amount) AS t'), DB::raw('MAX(delivery_charge) AS d')];
        foreach (['amount_to_collect' => 'c', 'amount_to_return' => 'r'] as $col => $a) $sel[] = self::h('orders', $col) ? DB::raw("MAX($col) AS $a") : DB::raw("0 AS $a");
        foreach (array_chunk($ids, 500) as $c) {
            foreach (DB::table('orders')->whereIn('hub_id', $c)->groupBy('order_id')->select($sel)->get() as $o) {
                $all['orders']++;
                $s = OrderFlow::stage($o->st);
                if ($s === 'new') $all['new']++;
                elseif (in_array($s, ['accepted', 'out'], true)) $all['ongoing']++;
                elseif (in_array($s, ['delivered', 'unsettled'], true)) { $all['delivered']++; $all['revenue'] += (float) $o->t + (float) $o->d + (float) $o->c - (float) $o->r; }
                elseif ($s === 'cancelled') $all['cancelled']++;
            }
        }
        $all['revenue'] = round($all['revenue'], 2);
        [$on] = AdminRecords::statusValues('products');
        $prodTotal = (int) DB::table('products')->count();
        $prodOn = (int) DB::table('products')->whereRaw('CAST(status AS CHAR) IN (?, ?)', [$on, 'active'])->count();
        $buyers = (int) DB::table('buyers')->count();
        $newBuyers = self::h('buyers', 'date_added') ? (int) DB::table('buyers')->whereBetween('date_added', [$from . ' 00:00:00', $to . ' 23:59:59'])->count() : null;

        // In the chosen dates
        $rows = CountryAnalytics::orderRows($ids, $from . ' 00:00:00', $to . ' 23:59:59');
        $byHub = []; $byDay = [];
        $sales = 0.0; $orders = 0; $cancel = 0;
        foreach ($rows as $r) {
            $d = date('Y-m-d', $r['at']);
            $byDay[$d] = $byDay[$d] ?? ['orders' => 0, 'sales' => 0.0];
            if ($r['stage'] === 'cancelled') { $cancel++; continue; }
            $orders++; $sales += $r['value'];
            $byHub[$r['hub']] = $byHub[$r['hub']] ?? ['orders' => 0, 'sales' => 0.0];
            $byHub[$r['hub']]['orders']++; $byHub[$r['hub']]['sales'] += $r['value'];
            $byDay[$d]['orders']++; $byDay[$d]['sales'] += $r['value'];
        }
        $hubRows = [];
        foreach ($byHub as $h => $v) $hubRows[] = ['label' => $hubs[$h]['name'] ?? ('Hub ' . $h), 'value' => $v['orders'], 'sales' => round($v['sales'], 2)];
        usort($hubRows, fn ($a, $b) => $b['value'] <=> $a['value']);
        $days = [];
        for ($t = strtotime($from); $t <= strtotime($to) && count($days) < 400; $t += 86400) { $d = date('Y-m-d', $t); $days[] = ['d' => $d, 'orders' => $byDay[$d]['orders'] ?? 0, 'sales' => round($byDay[$d]['sales'] ?? 0, 2)]; }
        $items = array_values(CountryAnalytics::productLines($ids, $from . ' 00:00:00', $to . ' 23:59:59'));
        usort($items, fn ($a, $b) => $b['value'] <=> $a['value']);
        $items = array_map(fn ($i) => ['label' => $i['name'] . ' · ' . $i['pack'], 'value' => $i['value'], 'orders' => $i['orders'], 'qty' => $i['qty']], array_slice($items, 0, 15));
        // Customers by hub (saved addresses), like the old chart
        $users = [];
        if (self::h('buyer_address', 'hub_id')) {
            foreach (DB::table('buyer_address')->whereIn('hub_id', $ids ?: [0])->select('hub_id', DB::raw('COUNT(DISTINCT buyer_id) AS n'))->groupBy('hub_id')->get() as $u) $users[] = ['label' => $hubs[(int) $u->hub_id]['name'] ?? ('Hub ' . $u->hub_id), 'value' => (int) $u->n];
            usort($users, fn ($a, $b) => $b['value'] <=> $a['value']);
        }
        $recent = [];
        foreach (DB::table('orders AS o')->leftJoin('buyers AS b', 'b.id', '=', 'o.buyer_id')->whereIn('o.hub_id', $ids ?: [0])->groupBy('o.order_id')
            ->select('o.order_id', DB::raw('MAX(o.date_added) AS at'), DB::raw('MAX(o.order_status) AS st'), DB::raw('MAX(o.total_amount) + MAX(o.delivery_charge) AS amt'), DB::raw('MAX(o.hub_id) AS hub'), DB::raw('MAX(o.delivery_type) AS type'), DB::raw('MAX(b.name) AS name'))
            ->orderByDesc('at')->limit(10)->get() as $o) {
            $recent[] = ['id' => (string) $o->order_id, 'at' => CountryScope::ts($o->at), 'stage' => OrderFlow::stage($o->st), 'amount' => round((float) $o->amt, 2), 'hub' => $hubs[(int) $o->hub]['name'] ?? '', 'type' => (string) $o->type, 'customer' => (string) $o->name];
        }
        $newCust = [];
        if (self::h('buyers', 'date_added')) foreach (DB::table('buyers')->orderByDesc('id')->limit(8)->get(['id', 'name', 'phone', 'email', 'date_added']) as $b) $newCust[] = ['id' => (int) $b->id, 'name' => (string) $b->name, 'phone' => (string) ($b->phone ?? ''), 'at' => CountryScope::ts($b->date_added)];

        return ['ok' => true,
            'all' => $all + ['customers' => $buyers, 'products' => $prodTotal, 'products_on' => $prodOn],
            'range' => ['orders' => $orders, 'sales' => round($sales, 2), 'cancelled' => $cancel, 'aov' => $orders ? round($sales / $orders, 2) : 0, 'new_customers' => $newBuyers],
            'by_hub' => $hubRows, 'by_day' => $days, 'items' => $items, 'users' => $users, 'recent' => $recent, 'new_customers' => $newCust, 'attention' => self::attention()];
    }

    /** Things in the data that need fixing, each linking to the screen that fixes it. */
    public static function attention(): array
    {
        $a = [];
        try {
            $cat = AdminCatalog::list()['rows'];
            $cnt = function (string $issue) use ($cat) { return count(array_filter($cat, fn ($p) => in_array($issue, $p['issues'], true))); };
            $on = fn (string $issue) => count(array_filter($cat, fn ($p) => $p['active'] && in_array($issue, $p['issues'], true)));
            foreach ([['No price', 'bad', 'products on sale have no selling price'], ['Price above MRP', 'bad', 'products sell above their MRP'], ['Check pack size', 'warn', 'products have an impossible pack size (for example 5 g of chicken)'],
                ['Item missing', 'warn', 'products point to a deleted item (they were hidden in the old admin)'], ['No photo', 'warn', 'products have no photo'], ['Cost above price', 'warn', 'products sell below cost']] as [$k, $tone, $txt]) {
                $n = in_array($k, ['No price', 'Price above MRP', 'No photo'], true) ? $on($k) : $cnt($k);
                if ($n) $a[] = ['tone' => $tone, 'n' => $n, 'text' => $txt, 'go' => 'ad-products?issue=' . rawurlencode($k)];
            }
            $m = AdminCatalog::missing();
            if (!empty($m['total']['codes'])) $a[] = ['tone' => 'bad', 'n' => $m['total']['codes'], 'text' => 'product codes have stock in the hubs (' . CountryScope::q($m['total']['live']) . ' packs) but no product, so nobody can sell them', 'go' => 'ad-missing'];
            $hubs = AdminHubs::list()['rows'];
            $hi = function (string $k) use ($hubs) { return count(array_filter($hubs, fn ($h) => in_array($k, $h['issues'], true))); };
            if ($n = $hi('Location is a corner of the area (old admin)')) $a[] = ['tone' => 'warn', 'n' => $n, 'text' => 'hubs have their location on a corner of the delivery area (old admin bug), so distances are wrong', 'go' => 'ad-hubs'];
            if ($n = $hi('No delivery charge for its city')) $a[] = ['tone' => 'warn', 'n' => $n, 'text' => 'hubs are in a city with no delivery charge set', 'go' => 'ad-delivery'];
            if ($n = $hi('No delivery area')) $a[] = ['tone' => 'bad', 'n' => $n, 'text' => 'hubs have no delivery area drawn', 'go' => 'ad-hubs'];
            if (self::h('home_promotions')) {
                $exp = DB::table('home_promotions')->where('expiry', '!=', '0')->where('expiry', '!=', '')->where('expiry', '<', date('Y-m-d'))->count();
                if ($exp) $a[] = ['tone' => 'info', 'n' => $exp, 'text' => 'promo codes have expired', 'go' => 'ad-promos'];
            }
            $plain = 0;
            foreach (AdminStaff::TYPES as $t) if (self::h($t['table'], 'password')) $plain += DB::table($t['table'])->where('password', '!=', '')->where('password', 'not like', '$2y$%')->count();
            if ($plain) $a[] = ['tone' => 'warn', 'n' => $plain, 'text' => 'staff passwords are stored as plain text (secure them when the staff logins are moved to the new login)', 'go' => 'ad-staff'];
            if (!AdminPush::configured()) $a[] = ['tone' => 'info', 'n' => null, 'text' => 'Push notifications are not set up (Google closed the old service the old admin used)', 'go' => 'ad-push'];
        } catch (\Throwable $e) {
            $a[] = ['tone' => 'info', 'n' => null, 'text' => 'Some checks could not run: ' . mb_substr($e->getMessage(), 0, 120), 'go' => ''];
        }
        return $a;
    }
}
