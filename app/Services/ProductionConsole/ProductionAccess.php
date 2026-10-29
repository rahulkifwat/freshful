<?php

namespace App\Services\ProductionConsole;

use App\Services\CountryConsole\CountryScope;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Freshful production console (production update 1, 28 Oct 2026): what a production user may open.
 * Same rule as the old production sidebar: the role_user row of the role whose type contains "production" holds
 * group switches (inventory, production, wastage...) and a comma list of permission keys. A screen is shown when its
 * group switch is on (or the table has no such column) AND one of its keys is in the list.
 * No role or role_user row (or it cannot be read): only the dashboard opens (fail closed, logged).
 * PRODUCTION_USE_ROLE_PERMISSIONS=false: every screen and action is open (an explicit owner choice).
 * The same map protects the API, so a hidden screen cannot be reached by typing its address.
 */
class ProductionAccess
{
    /** screen => [[group flag or null, [keys...]], ...] (any one rule opens it); [] = always open */
    public const SCREENS = [
        'pr-home' => [],
        'pr-plan' => [['production', ['production']]],
        'pr-sheet' => [['production', ['production']]],
        'pr-batches' => [['production', ['production', 'upload_inventory_excel']]],
        'pr-receipts' => [['production', ['production']], ['inventory', ['pending_inward']]],
        'pr-board' => [['production', ['production', 'download_inventory_report']]],
        'pr-products' => [['production', ['products', 'edit_product', 'add_product']]],
        'sheet' => [['inventory', ['hub_inventory']], ['production', ['download_inventory_report', 'production']]],
        'stock' => [['inventory', ['hub_inventory']], ['production', ['production']]],
        'stock-hubs' => [['inventory', ['hub_inventory']], ['production', ['production']]],
        'ledger' => [['inventory', ['hub_inventory']], ['production', ['production']]],
        'inward' => [['inventory', ['pending_inward']], ['communicator', ['accept_inward_stocks']]],
        'sched' => [['inventory', ['scheduled_orders']]],
        'transfers' => [['inventory', ['interhub_moments_view']], ['communicator', ['add_communicator_deposit']]],
        'ihsend' => [['inventory', ['interhub_orders']], ['communicator', ['add_communicator_deposit']]],
        'comm-txn' => [['communicator', ['inward_outward']]],
        'request' => [['communicator', ['request_outward_stocks']]],
        'danger' => [['danger_stock', ['danger_stock_inventory', 'transfer_danger_stock']], ['operation', ['view_danger_stock']]],
        'writeoff' => [['wastage', ['submit_wastage_report', 'add_wastage_stock']], ['wastage_report', ['submit_wastage_report', 'add_wastage_stock']]],
        'wastage' => [['production', ['wastage_reports']], ['wastage', ['sku_wastage']], ['wastage_report', ['sku_wastage']], ['account', ['view_wastage_report', 'pull_wastage_report']]],
        'orders' => [[null, ['all_orders']]],
        'cancels' => [[null, ['all_orders']]],
        'customers' => [['customer', ['all_customers', 'customer_order']]],
        'wallets' => [[null, ['payment_history', 'wallet_payment_history']]],
        'cash' => [['cash_deposit', ['cash_deposit', 'deposit_receipt']], ['account', ['view_cash_deposits_hub', 'view_cash_deposits_all_hub']]],
        'reports' => [['account', ['sales_report', 'pull_sales_report']]],
        'riders' => [[null, ['delivery_boy']]],
        'rider-perf' => [[null, ['delivery_boy']]],
        'rider-add' => [[null, ['delivery_boy', 'add_delivery_boy']]],
        'grievances' => [['customer_care', ['grievance']]],
    ];

    /** API route (name without the "ccp." prefix) => screens that use it. A route not listed here is closed. */
    public const API = [
        'meta' => ['*'], 'order' => ['orders', 'cancels', 'sched', 'customers'],
        'orders' => ['orders', 'sched'], 'cancels' => ['cancels'],
        'stock.products' => ['stock'], 'stock.product' => ['stock', 'sheet', 'pr-board', 'danger'], 'stock.hubs' => ['stock-hubs'], 'stock.hub' => ['stock-hubs', 'writeoff', 'ihsend', 'request', 'pr-board'],
        'sheet' => ['sheet'], 'danger' => ['danger'], 'danger.plan' => ['danger'], 'inward' => ['inward'], 'accept' => ['inward'],
        'ledger' => ['ledger', 'comm-txn'], 'count' => ['stock', 'sheet', 'stock-hubs'],
        'wastage' => ['wastage', 'writeoff'], 'writeoff' => ['writeoff', 'stock', 'sheet', 'danger'],
        'transfers' => ['transfers', 'ihsend', 'request'], 'transfer.create' => ['request'], 'transfer.send' => ['ihsend'],
        'transfer.cancel' => ['request', 'transfers'], 'transfer.dispatch' => ['request', 'transfers'], 'transfer.receive' => ['ihsend', 'transfers'], 'transfer.decline' => ['request', 'transfers'],
        'cash' => ['cash'], 'riders' => ['riders'], 'riders.hub' => ['riders', 'ihsend', 'request', 'transfers', 'rider-add'], 'riders.performance' => ['rider-perf'], 'rider.history' => ['riders', 'rider-perf'],
        'rider.save' => ['rider-add', 'riders'], 'rider.toggle' => ['riders'], 'rider.move' => ['riders'],
        'customers' => ['customers'], 'customer' => ['customers'], 'wallets' => ['wallets'], 'grievances' => ['grievances'], 'sales' => ['reports'],
        // production screens
        'pr.home' => ['pr-home'], 'pr.plan' => ['pr-plan', 'pr-sheet'], 'pr.plan.save' => ['pr-plan'], 'pr.sheet' => ['pr-sheet'],
        'pr.send' => ['pr-plan', 'pr-batches'], 'pr.batches' => ['pr-batches', 'pr-home'], 'pr.batch' => ['pr-batches', 'pr-home', 'pr-receipts'],
        'pr.line.cancel' => ['pr-batches'], 'pr.receipts' => ['pr-receipts'], 'pr.board' => ['pr-board'],
        'pr.stale' => ['pr-batches', 'pr-home'], 'pr.stale.clear' => ['pr-batches'], 'pr.stale.restore' => ['pr-batches'],
        'pr.products' => ['pr-products'], 'pr.product.toggle' => ['pr-products'], 'pr.waste' => ['pr-waste'],
    ];

    /** Writes a production user can never make here (they belong to the hub, POS or head office). */
    public const NEVER = ['order.cancel', 'order.move', 'target.save', 'target.delete', 'targets'];

    /** Extra key needed for these writes (on top of opening the screen). */
    public const WRITE_KEYS = [
        'pr.product.toggle' => ['products', 'edit_product'],
        'pr.stale.clear' => ['production', 'edit_inventory', 'delete_inventory', 'delete_inventory_qty'],
        'pr.stale.restore' => ['production', 'edit_inventory', 'add_inventory'],
        'rider.save' => ['add_delivery_boy', 'update_delivery_boy'],
        'rider.toggle' => ['block_delivery_boy', 'update_delivery_boy'],
        'rider.move' => ['update_delivery_boy'],
        'accept' => ['accept_inward_stocks'],
        'danger.plan' => ['transfer_danger_stock'],
        'count' => ['edit_inventory', 'edit_inventory_qty'],
        'writeoff' => ['submit_wastage_report', 'add_wastage_stock'],
        'transfer.create' => ['request_outward_stocks'],
        'transfer.send' => ['interhub_orders', 'add_communicator_deposit'],
        'transfer.dispatch' => ['request_outward_stocks', 'interhub_orders', 'add_communicator_deposit'],
        'transfer.cancel' => ['request_outward_stocks', 'interhub_orders', 'add_communicator_deposit'],
        'transfer.decline' => ['request_outward_stocks', 'interhub_orders', 'add_communicator_deposit'],
        'transfer.receive' => ['accept_inward_stocks', 'interhub_orders', 'add_communicator_deposit'],
    ];

    /** The role_user row of the production role: ['flags' => [group => bool], 'keys' => [...], 'found' => bool]. */
    public static function role(): array
    {
        static $memo = null;
        if ($memo !== null) return $memo;
        $none = ['flags' => [], 'keys' => [], 'found' => false, 'role' => null, 'open' => false];
        // PRODUCTION_USE_ROLE_PERMISSIONS=false: every screen and action open (an explicit owner choice)
        if (!config('production_console.use_role_permissions', true)) return $memo = ['open' => true] + $none;
        try {
            if (!CountryScope::has('roles') || !CountryScope::has('role_user')) return $memo = $none;
            $col = CountryScope::has('roles', 'type') ? 'type' : (CountryScope::has('roles', 'name') ? 'name' : null);
            if (!$col) return $memo = $none;
            $role = DB::table('roles')->whereRaw('LOWER(' . $col . ') LIKE ?', ['%production%'])->orderBy('id')->first();
            if (!$role) { Log::warning('[production-console] no Production role found: only the dashboard is open'); return $memo = $none; }
            $ru = DB::table('role_user')->where('role_id', $role->id)->first();
            if (!$ru) { Log::warning('[production-console] the Production role has no permissions row: only the dashboard is open'); return $memo = $none; }
            $ru = (array) $ru;
            $keys = array_values(array_unique(array_filter(array_map('trim', explode(',', (string) ($ru['permission'] ?? ''))))));
            $flags = [];
            foreach ($ru as $k => $v) if (!in_array($k, ['id', 'role_id', 'permission', 'dashboard_style', 'created_at', 'updated_at'], true)) $flags[$k] = (string) $v === '1';
            return $memo = ['flags' => $flags, 'keys' => $keys, 'found' => true, 'role' => (string) $role->$col, 'open' => false];
        } catch (\Throwable $e) {
            Log::warning('[production-console] role read failed', ['e' => $e->getMessage()]);
            return $memo = $none;
        }
    }

    private static function ruleOk(array $rule, array $r): bool
    {
        [$flag, $keys] = $rule;
        if ($flag !== null && array_key_exists($flag, $r['flags']) && !$r['flags'][$flag]) return false;
        return (bool) array_intersect($keys, $r['keys']);
    }

    public static function screen(string $screen): bool
    {
        if (!array_key_exists($screen, self::SCREENS)) return false;
        $rules = self::SCREENS[$screen];
        if (!$rules) return true;
        $r = self::role();
        if ($r['open']) return true;
        if (!$r['found']) return false; // fail closed: without the role's permissions only the dashboard opens
        foreach ($rules as $rule) if (self::ruleOk($rule, $r)) return true;
        return false;
    }

    /** Screens this person may open, in menu order. */
    public static function screens(): array
    {
        return array_values(array_filter(array_keys(self::SCREENS), fn ($s) => self::screen($s)));
    }

    /** May this API route (name without prefix) run? Returns null when allowed, or the refusal message. */
    public static function api(string $route): ?string
    {
        if (in_array($route, self::NEVER, true)) return 'This is done by the hub or head office, not from the production console.';
        $screens = self::API[$route] ?? null;
        if ($screens === null) return 'This is not part of the production console.';
        if ($screens !== ['*'] && !array_filter($screens, fn ($s) => self::screen($s))) return 'Your role does not have access to this. Ask the admin to add it to the Production role.';
        $need = self::WRITE_KEYS[$route] ?? null;
        $r = self::role();
        if ($need && !$r['open'] && !array_intersect($need, $r['keys'])) return 'Your role can view this but not change it. Ask the admin to add the permission.';
        return null;
    }
}
