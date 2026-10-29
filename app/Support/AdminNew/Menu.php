<?php

namespace App\Support\AdminNew;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

/**
 * Freshful new admin (/admin/new) - sidebar menu.
 *
 * One place defines every menu entry. Each entry has a state:
 *   new     - built in the new admin; opens inside the new layout
 *   classic - an existing admin page; opens the classic screen (kept working)
 *   soon    - planned in a later phase; shown, not clickable
 * When a module is rebuilt, only its line here changes from classic to new.
 */
class Menu
{
    /** Group definitions: title, tile colour, entries. */
    private static function definition(): array
    {
        return [
            ['key' => 'overview', 'title' => 'Overview', 'color' => '#3B82F6', 'items' => [
                ['key' => 'dashboard', 'label' => 'Dashboard',          'icon' => 'grid',      'route' => 'adminnew.dashboard'],
                ['key' => 'live',      'label' => 'Live operations',    'icon' => 'pulse',     'soon' => true],
                ['key' => 'ai',        'label' => 'AI Center',          'icon' => 'sparkles',  'soon' => true],
                ['key' => 'sales',     'label' => 'Sales summary',      'icon' => 'chart',     'classic' => 'admin/sale_summary'],
                ['key' => 'invreport', 'label' => 'Inventory report',   'icon' => 'filechart', 'classic' => 'admin/inventory_report_detail'],
            ]],
            ['key' => 'orders', 'title' => 'Orders', 'color' => '#F26A1B', 'items' => [
                ['key' => 'allorders', 'label' => 'All orders',         'icon' => 'receipt',   'classic' => 'admin/all_orders', 'badge' => 'pending_orders'],
                ['key' => 'orderlist', 'label' => 'Order list',         'icon' => 'cart',      'classic' => 'admin/order'],
                ['key' => 'scheduled', 'label' => 'Scheduled orders',   'icon' => 'clock',     'soon' => true],
                ['key' => 'refunds',   'label' => 'Cancellations',      'icon' => 'undo', 'soon' => true],
            ]],
            ['key' => 'catalog', 'title' => 'Catalog', 'color' => '#10B981', 'items' => [
                ['key' => 'products',  'label' => 'Products',           'icon' => 'package',   'classic' => 'admin/products'],
                ['key' => 'maincat',   'label' => 'Categories',         'icon' => 'layers',    'classic' => 'admin/add_category'],
                ['key' => 'subcat',    'label' => 'Sub categories',     'icon' => 'folder',    'classic' => 'admin/category'],
                ['key' => 'items',     'label' => 'Items',              'icon' => 'fish',      'classic' => 'admin/items'],
                ['key' => 'units',     'label' => 'Units',              'icon' => 'scale',     'classic' => 'admin/product_unit'],
                ['key' => 'tags',      'label' => 'Tags',               'icon' => 'tag',       'classic' => 'admin/product_tag'],
                ['key' => 'filters',   'label' => 'Filters',            'icon' => 'filter',    'classic' => 'admin/product_filter'],
                ['key' => 'certs',     'label' => 'Certificates',       'icon' => 'award',     'classic' => 'admin/certificate'],
            ]],
            ['key' => 'inventory', 'title' => 'Inventory', 'color' => '#0EA5A0', 'items' => [
                ['key' => 'stock',     'label' => 'Stock',              'icon' => 'warehouse', 'classic' => 'admin/inventory'],
                ['key' => 'transfers', 'label' => 'Hub transfers',      'icon' => 'transfer',  'soon' => true],
                ['key' => 'wastage',   'label' => 'Wastage',            'icon' => 'trash',     'soon' => true],
                ['key' => 'batches',   'label' => 'Production batches', 'icon' => 'layers',    'soon' => true],
            ]],
            ['key' => 'network', 'title' => 'Hubs and delivery', 'color' => '#06B6D4', 'items' => [
                ['key' => 'hubs',      'label' => 'Hubs',               'icon' => 'store',     'classic' => 'admin/hub'],
                ['key' => 'cities',    'label' => 'Cities',             'icon' => 'building',  'classic' => 'admin/city'],
                ['key' => 'delprice',  'label' => 'Delivery charges',   'icon' => 'truck',     'classic' => 'admin/delivery_price'],
                ['key' => 'zones',     'label' => 'Delivery zones',     'icon' => 'map',       'soon' => true],
                ['key' => 'slots',     'label' => 'Time slots',         'icon' => 'calendar',  'soon' => true],
            ]],
            ['key' => 'people', 'title' => 'People', 'color' => '#8B5CF6', 'items' => [
                ['key' => 'customers', 'label' => 'Customers',          'icon' => 'users',     'classic' => 'admin/buyers'],
                ['key' => 'riders',    'label' => 'Riders',             'icon' => 'bike',      'classicRoute' => 'admin.drivers.index'],
                ['key' => 'riderappr', 'label' => 'Rider approvals',    'icon' => 'shield',    'classicRoute' => 'admin.drivers.pending'],
                ['key' => 'dispatch',  'label' => 'Order assignment',   'icon' => 'transfer',  'classicRoute' => 'admin.drivers.assignment'],
                ['key' => 'staff',     'label' => 'Staff',              'icon' => 'badge', 'children' => [
                    ['key' => 'st-country',  'label' => 'Country managers',       'classic' => 'admin/country_managers'],
                    ['key' => 'st-area',     'label' => 'Area managers',          'classic' => 'admin/area_managers'],
                    ['key' => 'st-ops',      'label' => 'Operation managers',     'classic' => 'admin/operation_managers'],
                    ['key' => 'st-plan',     'label' => 'Planning managers',      'classic' => 'admin/planning_managers'],
                    ['key' => 'st-prod',     'label' => 'Production users',       'classic' => 'admin/production_user'],
                    ['key' => 'st-hub',      'label' => 'Hub users',              'classic' => 'admin/hub_users'],
                    ['key' => 'st-pos',      'label' => 'POS users',              'classic' => 'admin/pos_users'],
                    ['key' => 'st-acc',      'label' => 'Account managers',       'classic' => 'admin/account_managers'],
                    ['key' => 'st-mkt',      'label' => 'Marketing managers',     'classic' => 'admin/marketing_managers'],
                    ['key' => 'st-care',     'label' => 'Customer care managers', 'classic' => 'admin/customer_care_managers'],
                    ['key' => 'st-hr',       'label' => 'HR managers',            'classic' => 'admin/hr_managers'],
                ]],
                ['key' => 'roles',     'label' => 'Roles and permissions', 'icon' => 'shield', 'soon' => true],
            ]],
            ['key' => 'finance', 'title' => 'Finance', 'color' => '#059669', 'items' => [
                ['key' => 'payments',  'label' => 'Payments',           'icon' => 'card',      'soon' => true],
                ['key' => 'cash',      'label' => 'Cash deposits',      'icon' => 'cash',      'soon' => true],
                ['key' => 'wallets',   'label' => 'Customer wallets',   'icon' => 'wallet',    'soon' => true],
            ]],
            ['key' => 'marketing', 'title' => 'Marketing', 'color' => '#EC4899', 'items' => [
                ['key' => 'banners',   'label' => 'Website banners',    'icon' => 'image',     'classic' => 'admin/banner'],
                ['key' => 'appban',    'label' => 'App banners',        'icon' => 'phone',     'classic' => 'admin/app_banner'],
                ['key' => 'offers',    'label' => 'Home offers',        'icon' => 'gift',      'classic' => 'admin/home_offers'],
                ['key' => 'promos',    'label' => 'Promotions',         'icon' => 'megaphone', 'classic' => 'admin/promotions'],
                ['key' => 'coupons',   'label' => 'Coupons and deals',  'icon' => 'ticket',    'classic' => 'admin/coupons_and_deals'],
                ['key' => 'push',      'label' => 'Push notifications', 'icon' => 'bell',      'classic' => 'admin/push'],
                ['key' => 'news',      'label' => 'Newsletter',         'icon' => 'mail',      'classic' => 'admin/news_letters'],
            ]],
            ['key' => 'support', 'title' => 'Support', 'color' => '#F59E0B', 'items' => [
                ['key' => 'contact',   'label' => 'Contact enquiries',  'icon' => 'message',   'classic' => 'admin/contact_us'],
                ['key' => 'franchise', 'label' => 'Franchise enquiries','icon' => 'handshake', 'classic' => 'admin/frenchisee_enquiry'],
                ['key' => 'griev',     'label' => 'Grievances',         'icon' => 'lifebuoy',  'soon' => true],
                ['key' => 'ratings',   'label' => 'Ratings and reviews','icon' => 'star',      'soon' => true],
            ]],
            ['key' => 'settings', 'title' => 'Settings', 'color' => '#64748B', 'items' => [
                ['key' => 'policies',  'label' => 'Policies',           'icon' => 'doc',       'classic' => 'admin/policies?type=privacy-policy'],
                ['key' => 'roletype',  'label' => 'Role types',         'icon' => 'badge',     'classic' => 'admin/role_type'],
                ['key' => 'profile',   'label' => 'My profile',         'icon' => 'user',      'classic' => 'admin/profile'],
                ['key' => 'audit',     'label' => 'Audit log',          'icon' => 'history',   'soon' => true],
            ]],
        ];
    }

    /**
     * Resolve links, states, active entry and badge counts for the current request.
     * Returns ['groups' => [...], 'current' => ['group' => .., 'label' => ..]].
     */
    public static function build(?string $activeKey = null): array
    {
        $badges  = self::badges();
        $groups  = [];
        $current = ['group' => 'Overview', 'label' => 'Dashboard'];

        foreach (self::definition() as $g) {
            $g['active'] = false;
            $items = [];
            foreach ($g['items'] as $it) {
                $it = self::resolve($it, $badges);
                if (!empty($it['children'])) {
                    $kids = [];
                    foreach ($it['children'] as $c) {
                        $kids[] = self::resolve($c + ['icon' => null], $badges);
                    }
                    $it['children'] = $kids;
                }
                $it['active'] = ($activeKey !== null && $it['key'] === $activeKey);
                if ($it['active']) {
                    $g['active'] = true;
                    $current = ['group' => $g['title'], 'label' => $it['label']];
                }
                $items[] = $it;
            }
            $g['items'] = $items;
            $groups[]   = $g;
        }

        return ['groups' => $groups, 'current' => $current];
    }

    /** Turn one definition entry into href + state. */
    private static function resolve(array $it, array $badges): array
    {
        $it['href']  = null;
        $it['state'] = 'soon';

        if (!empty($it['route']) && Route::has($it['route'])) {
            $it['href']  = route($it['route']);
            $it['state'] = 'new';
        } elseif (!empty($it['classic'])) {
            $it['href']  = url($it['classic']);
            $it['state'] = 'classic';
        } elseif (!empty($it['classicRoute']) && Route::has($it['classicRoute'])) {
            $it['href']  = route($it['classicRoute']);
            $it['state'] = 'classic';
        } elseif (!empty($it['children'])) {
            $it['state'] = 'parent';
        }

        $it['count'] = (!empty($it['badge']) && !empty($badges[$it['badge']])) ? (int) $badges[$it['badge']] : null;

        return $it;
    }

    /** Small live counts shown on menu entries. Any failure just hides the badge. */
    private static function badges(): array
    {
        $out = [];
        try {
            $out['pending_orders'] = DB::table('orders')
                ->where('order_status', 'Order Pending')
                ->distinct()
                ->count('order_id');
        } catch (\Throwable $e) {
            $out['pending_orders'] = null;
        }
        return $out;
    }
}
