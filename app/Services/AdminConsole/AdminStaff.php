<?php

namespace App\Services\AdminConsole;

use App\Services\CountryConsole\CountryScope;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Freshful admin console (admin update 1, 22 Oct 2026): staff logins of every panel, and roles / permissions.
 * The 11 staff tables of the old admin are shown on one screen. Passwords:
 *   - never shown, never logged; a new password must be at least 8 characters;
 *   - saved in the same form the table already uses, so the existing login pages keep accepting them:
 *     if any row of that table already holds a secure (bcrypt) password, new ones are saved secure too; a table that
 *     still holds only plain passwords keeps plain ones until its login is moved to secure passwords (ADMIN_STAFF_PASSWORDS=hash forces secure).
 * Staff are switched off, never deleted.
 */
class AdminStaff
{
    /** key => table, label, extra fields */
    public const TYPES = [
        'hub' => ['table' => 'hub_user', 'label' => 'Hub users', 'one' => 'Hub user', 'hub' => 'one', 'login' => 'hub/login'],
        'pos' => ['table' => 'pos_user', 'label' => 'POS users', 'one' => 'POS user', 'hub' => 'one', 'login' => 'pos/console/login'],
        'area' => ['table' => 'area_managers', 'label' => 'Area managers', 'one' => 'Area manager', 'hub' => 'many', 'city' => true, 'login' => 'area_manager/login'],
        'operation' => ['table' => 'operation_managers', 'label' => 'Operation managers', 'one' => 'Operation manager', 'login' => 'operation_manager/login'],
        'country' => ['table' => 'country_managers', 'label' => 'Country managers', 'one' => 'Country manager', 'login' => 'country_manager/login'],
        'account' => ['table' => 'account_managers', 'label' => 'Account managers', 'one' => 'Account manager', 'login' => 'account_manager/login'],
        'marketing' => ['table' => 'marketing_managers', 'label' => 'Marketing managers', 'one' => 'Marketing manager', 'login' => 'marketing_manager/login'],
        'care' => ['table' => 'customer_care_managers', 'label' => 'Customer care', 'one' => 'Customer care manager', 'login' => 'customer_care_manager/login'],
        'hr' => ['table' => 'hr_managers', 'label' => 'HR managers', 'one' => 'HR manager', 'login' => 'hr_manager/login'],
        'planning' => ['table' => 'planning_managers', 'label' => 'Planning managers', 'one' => 'Planning manager', 'login' => 'planning_manager/login'],
        'production' => ['table' => 'production', 'label' => 'Production users', 'one' => 'Production user', 'login' => 'production/login'],
    ];

    private static function h(string $t, ?string $c = null): bool { return CountryScope::has($t, $c); }

    public static function isHashed(?string $p): bool { return is_string($p) && preg_match('/^\$(2[aby]|argon2i|argon2id)\$/', $p) === 1; }

    /** How this table stores passwords: hash | plain. */
    public static function passwordMode(string $table): string
    {
        $cfg = strtolower((string) config('admin_console.staff_passwords', 'auto'));
        if ($cfg === 'hash' || $cfg === 'plain') return $cfg;
        try {
            foreach (DB::table($table)->whereNotNull('password')->where('password', '!=', '')->limit(500)->pluck('password') as $p) if (self::isHashed((string) $p)) return 'hash';
        } catch (\Throwable $e) {}
        return 'plain';
    }

    public static function meta(): array
    {
        $types = [];
        foreach (self::TYPES as $k => $t) {
            if (!self::h($t['table'])) continue;
            $types[] = ['key' => $k, 'label' => $t['label'], 'one' => $t['one'], 'hub' => $t['hub'] ?? null, 'city' => !empty($t['city']) && self::h($t['table'], 'city_id'),
                'count' => (int) DB::table($t['table'])->count(), 'passwords' => self::passwordMode($t['table']), 'login' => url($t['login'])];
        }
        return ['ok' => true, 'types' => $types];
    }

    public static function list(string $type): array
    {
        $t = self::TYPES[$type] ?? null;
        if (!$t || !self::h($t['table'])) return ['ok' => false, 'message' => 'Unknown staff type.'];
        $hubs = CountryScope::hubs();
        $cities = self::h('cities') ? DB::table('cities')->pluck('city', 'id')->all() : [];
        $rows = [];
        foreach (DB::table($t['table'])->orderBy('name')->get() as $u) {
            $hubIds = array_values(array_filter(array_map('intval', preg_split('/[\s,;|]+/', (string) ($u->hub_id ?? '')))));
            $rows[] = ['id' => (int) $u->id, 'name' => (string) ($u->name ?? ''), 'email' => (string) ($u->email ?? ''), 'phone' => (string) ($u->phone ?? ''), 'active' => AdminRecords::isOn($u->status ?? 'active'),
                'hub_ids' => $hubIds, 'hubs' => implode(', ', array_map(fn ($h) => $hubs[$h]['name'] ?? ('#' . $h), $hubIds)), 'city_id' => (int) ($u->city_id ?? 0), 'city' => (string) ($cities[(int) ($u->city_id ?? 0)] ?? ''),
                'address' => (string) ($u->address ?? ''), 'added' => (string) ($u->date_added ?? ''), 'secure' => self::isHashed($u->password ?? null), 'has_password' => trim((string) ($u->password ?? '')) !== ''];
        }
        return ['ok' => true, 'rows' => $rows, 'passwords' => self::passwordMode($t['table'])];
    }

    public static function save(string $type, ?int $id, array $in, array $actor): array
    {
        $t = self::TYPES[$type] ?? null;
        if (!$t || !self::h($t['table'])) return ['ok' => false, 'message' => 'Unknown staff type.'];
        $tb = $t['table'];
        $old = $id ? DB::table($tb)->where('id', $id)->first() : null;
        if ($id && !$old) return ['ok' => false, 'message' => 'That user no longer exists.'];
        $name = trim((string) ($in['name'] ?? '')); $email = strtolower(trim((string) ($in['email'] ?? ''))); $phone = preg_replace('/\D+/', '', (string) ($in['phone'] ?? ''));
        if ($name === '' || mb_strlen($name) > 100) return ['ok' => false, 'message' => 'Enter the name.'];
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) return ['ok' => false, 'message' => 'Enter a valid email (it is the login name).'];
        if ($phone !== '' && (strlen($phone) < 10 || strlen($phone) > 12)) return ['ok' => false, 'message' => 'Enter a 10-digit mobile number.'];
        if (strlen($phone) === 12 && str_starts_with($phone, '91')) $phone = substr($phone, 2);
        $dup = DB::table($tb)->whereRaw('LOWER(email) = ?', [$email]); if ($old) $dup->where('id', '!=', $old->id);
        if ($dup->exists()) return ['ok' => false, 'message' => 'Another ' . strtolower($t['one']) . ' already uses ' . $email . '.'];
        if ($phone !== '' && in_array($type, ['pos', 'hub'], true)) { $dp = DB::table($tb)->where('phone', $phone); if ($old) $dp->where('id', '!=', $old->id); if ($dp->exists()) return ['ok' => false, 'message' => 'Another ' . strtolower($t['one']) . ' already uses this mobile number.']; }
        $v = ['name' => $name, 'email' => $email];
        if (self::h($tb, 'phone')) $v['phone'] = $phone;
        if (self::h($tb, 'address') && array_key_exists('address', $in)) $v['address'] = mb_substr(trim((string) $in['address']), 0, 255);
        $hubs = CountryScope::hubs();
        if (($t['hub'] ?? null) === 'one') {
            $h = (int) ($in['hub_id'] ?? 0);
            if (!isset($hubs[$h])) return ['ok' => false, 'message' => 'Choose the hub.'];
            $v['hub_id'] = $h;
        } elseif (($t['hub'] ?? null) === 'many' && self::h($tb, 'hub_id')) {
            $hs = array_values(array_unique(array_filter(array_map('intval', (array) ($in['hub_ids'] ?? [])), fn ($x) => isset($hubs[$x]))));
            if (!$hs) return ['ok' => false, 'message' => 'Choose at least one hub.'];
            $v['hub_id'] = implode(',', $hs);
            if (!empty($t['city']) && self::h($tb, 'city_id')) $v['city_id'] = (int) ($in['city_id'] ?? 0) ?: (int) ($hubs[$hs[0]]['city_id'] ?? 0);
        }
        $pw = (string) ($in['password'] ?? '');
        if (!$old && $pw === '') return ['ok' => false, 'message' => 'Set a password for the new user.'];
        if ($pw !== '') {
            if (mb_strlen($pw) < 8) return ['ok' => false, 'message' => 'The password must be at least 8 characters.'];
            if (mb_strlen($pw) > 72) return ['ok' => false, 'message' => 'The password is too long.'];
            $v['password'] = self::passwordMode($tb) === 'hash' ? Hash::make($pw) : $pw;
        }
        if (array_key_exists('active', $in) && self::h($tb, 'status')) { [$on, $off] = AdminRecords::statusValues($tb); $v['status'] = filter_var($in['active'], FILTER_VALIDATE_BOOLEAN) ? $on : $off; }
        if (!$old) {
            if (self::h($tb, 'status') && !isset($v['status'])) $v['status'] = AdminRecords::statusValues($tb)[0];
            if (self::h($tb, 'date_added')) $v['date_added'] = date('Y-m-d H:i:s');
            foreach (['image' => '', 'address' => ''] as $k => $d) if (self::h($tb, $k) && !isset($v[$k])) $v[$k] = $d;
        }
        try {
            if ($old) DB::table($tb)->where('id', $id)->update($v); else $id = (int) DB::table($tb)->insertGetId($v);
        } catch (\Throwable $e) { return ['ok' => false, 'message' => 'Could not save: ' . AdminRecords::dbError($e)]; }
        $new = DB::table($tb)->where('id', $id)->first();
        AdminAudit::log($actor, $old ? ($pw !== '' ? 'edit + password' : 'edit') : 'add', 'Staff', $tb, $id, ($old ? 'Edited ' : 'Added ') . strtolower($t['one']) . ' ' . $email . ($pw !== '' && $old ? ' (password changed)' : ''), $old, $new);
        return ['ok' => true, 'id' => $id, 'message' => $t['one'] . ($old ? ' saved.' : ' added. They can log in at ' . url($t['login']) . ' with ' . $email . '.')];
    }

    public static function toggle(string $type, int $id, array $actor): array
    {
        $t = self::TYPES[$type] ?? null;
        if (!$t || !self::h($t['table'], 'status')) return ['ok' => false, 'message' => 'Unknown staff type.'];
        $old = DB::table($t['table'])->where('id', $id)->first();
        if (!$old) return ['ok' => false, 'message' => 'That user no longer exists.'];
        [$on, $off] = AdminRecords::statusValues($t['table']);
        $to = AdminRecords::isOn($old->status) ? $off : $on;
        DB::table($t['table'])->where('id', $id)->update(['status' => $to]);
        AdminAudit::log($actor, AdminRecords::isOn($to) ? 'switch on' : 'switch off', 'Staff', $t['table'], $id, (AdminRecords::isOn($to) ? 'Allowed login for ' : 'Blocked login for ') . strtolower($t['one']) . ' ' . $old->email, $old, DB::table($t['table'])->where('id', $id)->first());
        return ['ok' => true, 'on' => AdminRecords::isOn($to), 'message' => $old->name . (AdminRecords::isOn($to) ? ' can log in again.' : ' can no longer log in.')];
    }

    /* ------------------------------------------------------------------ roles & permissions (tables roles + role_user, same format as the old admin) */

    public const ROLE_TYPES = ['Account Manager', 'Area Manager', 'Country Manager', 'Customer care Manager', 'HR Manager', 'Marketing Manager', 'Planning Manager', 'Operation Manager', 'Production Manager'];
    public const DASHBOARDS = ['dashboard' => 'Dashboard 1', 'dashboard2' => 'Dashboard 2', 'dashboard3' => 'Area dashboard', 'dashboard4' => 'Hub dashboard'];
    /** group flag column => [label, [permission key => label]] (exact keys of the old admin) */
    public const GROUPS = [
        'inventory' => ['Inventory management', ['pending_inward' => 'Pending inward', 'hub_inventory' => 'Hub inventory', 'interhub_orders' => 'InterHub orders', 'interhub_moments_view' => 'InterHub moments view', 'locked_inventory' => 'Locked inventory', 'scheduled_orders' => 'Scheduled orders', 'sku_sales_country' => 'SKU sales', 'sku_sales_hub' => 'SKU sales (all hubs)', 'sku_sales_city' => 'SKU sales (all cities)', 'add_inventory' => 'Add inventory', 'edit_inventory' => 'Edit inventory', 'delete_inventory' => 'Delete inventory']],
        'customer' => ['Customer management', ['all_customers' => 'Customer information', 'customer_order' => 'Customer order history', 'create_cust' => 'Create customer', 'edit_cust' => 'Edit customer', 'delete_cust' => 'Delete customer', 'block_cust' => 'Block customer']],
        'orders' => ['Orders management', ['all_orders' => 'All orders', 'all_merchant_orders' => 'All merchant orders', 'edit_order' => 'Edit order', 'delete_order' => 'Delete order', 'orders_report' => 'Orders report']],
        'cash_deposit' => ['Cash deposit', ['deposit_receipt' => 'Add deposit receipt', 'cash_deposit' => 'View cash deposits', 'add_cash_deposit' => 'Add cash deposit']],
        'communicator' => ['Communicator', ['inward_outward' => 'Hub inward / outward transactions', 'accept_inward_stocks' => 'Accept inward stocks', 'request_outward_stocks' => 'Request outward stocks', 'add_communicator_deposit' => 'InterHub orders and moments view']],
        'production' => ['Production', ['upload_inventory_excel' => 'Upload inventory by Excel & manual', 'download_inventory_report' => 'Download live inventory report', 'products' => 'Products', 'add_product' => 'Add products', 'edit_product' => 'Edit products', 'delete_product' => 'Delete products', 'wastage_reports' => 'Wastage reports', 'production' => 'Production']],
        'marketing' => ['Marketing management', ['news_letters' => 'Newsletters', 'banner' => 'Banner image', 'home_offers' => 'Home offers', 'promotions' => 'Home promotions', 'certificate' => 'Certificates', 'rating_reviews' => 'Rating & reviews', 'promo_code' => 'Promo code', 'create_merchant_orders' => 'Create merchant orders', 'push_notifications' => 'Push notifications', 'flash_sale' => 'Flash sale', 'upload_image' => 'Upload image', 'sms_blast' => 'SMS blast']],
        'customer_care' => ['Customer care management', ['grievance' => 'View grievance', 'add_grievance' => 'Create grievance', 'edit_grievance' => 'Edit grievance', 'delete_grievance' => 'Delete grievance', 'grievances_category' => 'Grievance category', 'add_grievance_category' => 'Add grievance category', 'customer_grade' => 'Customer grade', 'add_wallet_money' => 'Add wallet money', 'withdraw_money' => 'Withdraw money', 'create_sample_order' => 'Create sample order', 'express_order_time_slot' => 'Express orders time slot edit', 'scheduled_order_time_slot' => 'Scheduled order time slot change']],
        'account' => ['Account management', ['view_cash_deposits_hub' => 'View cash deposit hub', 'view_cash_deposits_all_hub' => 'View cash deposit all hubs', 'pull_sales_report' => 'Pull sales report', 'sales_report' => 'Sales report', 'view_wastage_report' => 'View wastage report', 'pull_wastage_report' => 'Pull wastage report', 'payment_history' => 'Payment history']],
        'planning' => ['Planning manager', ['add_inventory_sku_hub_wise' => 'Add inventory SKU wise to hub', 'sku_in_out_transaction' => 'SKU inward / outward transactions', 'edit_inventory_qty' => 'Edit inventory qty', 'delete_inventory_qty' => 'Delete inventory qty']],
        'kml' => ['KML management', ['hub_kml_list' => 'KML list hub wise', 'kml_create' => 'Create KML', 'kml_edit' => 'Edit KML', 'kml_delete' => 'Delete KML', 'kml_upload' => 'Upload KML']],
        'delivery_boy' => ['Delivery boy', ['add_delivery_boy' => 'Add delivery boy', 'delivery_boy' => 'All delivery boys', 'update_delivery_boy' => 'Edit delivery boy', 'delete_delivery_boy' => 'Delete delivery boy', 'block_delivery_boy' => 'Block delivery boy']],
        'operation' => ['Operation management', ['view_danger_stock' => 'View danger stock']],
        'wastage_report' => ['Wastage reports', ['sku_wastage' => 'SKU wastage', 'add_wastage_stock' => 'Add wastage SKU wise', 'edit_wastage_stock' => 'Edit wastage SKU qty', 'delete_wastage_stock' => 'Delete wastage SKU qty', 'submit_wastage_report' => 'Submit wastage report']],
        'danger_stock' => ['Danger stock', ['add_danger_stock' => 'Add danger stock inventory', 'danger_stock_inventory' => 'View danger stock inventory', 'transfer_danger_stock' => 'Transfer danger stock inventory', 'edit_danger_stock' => 'Edit danger stock inventory', 'delete_danger_stock_inventory' => 'Delete danger stock inventory']],
    ];

    public static function roles(): array
    {
        if (!self::h('roles') || !self::h('role_user')) return ['ok' => false, 'message' => 'The roles tables are missing.'];
        $rows = [];
        $ru = DB::table('role_user')->get()->keyBy('role_id');
        foreach (DB::table('roles')->orderBy('id')->get() as $r) {
            $u = $ru[$r->id] ?? null;
            $perms = $u ? array_values(array_filter(array_map('trim', explode(',', (string) $u->permission)))) : [];
            $flags = [];
            foreach (array_keys(self::GROUPS) as $g) if ($u && self::h('role_user', $g)) $flags[$g] = (string) ($u->$g ?? '0') === '1';
            $rows[] = ['id' => (int) $r->id, 'type' => (string) $r->type, 'dashboard' => (string) ($u->dashboard_style ?? ''), 'permissions' => $perms, 'groups' => $flags, 'updated' => (string) ($u->updated_at ?? $r->updated_at ?? '')];
        }
        $all = []; foreach (self::GROUPS as $g => [$label, $perms]) $all[] = ['key' => $g, 'label' => $label, 'perms' => array_map(fn ($k, $l) => ['key' => $k, 'label' => $l], array_keys($perms), $perms), 'has_flag' => self::h('role_user', $g)];
        return ['ok' => true, 'rows' => $rows, 'groups' => $all, 'types' => self::ROLE_TYPES, 'dashboards' => self::DASHBOARDS];
    }

    public static function roleSave(?int $id, array $in, array $actor): array
    {
        if (!self::h('roles') || !self::h('role_user')) return ['ok' => false, 'message' => 'The roles tables are missing.'];
        $type = trim((string) ($in['type'] ?? ''));
        if (!in_array($type, self::ROLE_TYPES, true)) return ['ok' => false, 'message' => 'Choose the role.'];
        $dup = DB::table('roles')->where('type', $type); if ($id) $dup->where('id', '!=', $id);
        if ($dup->exists()) return ['ok' => false, 'message' => 'The role "' . $type . '" already exists. Edit it instead.'];
        $dash = (string) ($in['dashboard'] ?? 'dashboard');
        if (!isset(self::DASHBOARDS[$dash])) $dash = 'dashboard';
        $valid = []; foreach (self::GROUPS as $g => [, $perms]) foreach ($perms as $k => $l) $valid[$k] = $g;
        $perms = array_values(array_unique(array_filter((array) ($in['permissions'] ?? []), fn ($p) => isset($valid[$p]))));
        $vals = ['dashboard_style' => $dash, 'permission' => implode(',', $perms)];
        foreach (array_keys(self::GROUPS) as $g) if (self::h('role_user', $g)) $vals[$g] = in_array($g, array_values(array_intersect_key($valid, array_flip($perms))), true) ? '1' : '0';
        $now = date('Y-m-d H:i:s');
        $oldRole = $id ? DB::table('roles')->where('id', $id)->first() : null;
        if ($id && !$oldRole) return ['ok' => false, 'message' => 'That role no longer exists.'];
        $oldRu = $id ? DB::table('role_user')->where('role_id', $id)->first() : null;
        DB::transaction(function () use (&$id, $type, $vals, $now, $oldRole, $oldRu) {
            $rv = ['type' => $type];
            if (self::h('roles', 'updated_at')) $rv['updated_at'] = $now;
            if ($oldRole) DB::table('roles')->where('id', $id)->update($rv);
            else { if (self::h('roles', 'created_at')) $rv['created_at'] = $now; $id = (int) DB::table('roles')->insertGetId($rv); }
            if (self::h('role_user', 'updated_at')) $vals['updated_at'] = $now;
            if ($oldRu) DB::table('role_user')->where('role_id', $id)->update($vals);
            else { $vals['role_id'] = $id; if (self::h('role_user', 'created_at')) $vals['created_at'] = $now; DB::table('role_user')->insert($vals); }
        });
        AdminAudit::log($actor, $oldRole ? 'edit' : 'add', 'Staff', 'role_user', $id, ($oldRole ? 'Changed permissions of ' : 'Added role ') . $type . ' (' . count($perms) . ' permissions)', $oldRu, DB::table('role_user')->where('role_id', $id)->first());
        return ['ok' => true, 'id' => $id, 'message' => 'Permissions of ' . $type . ' saved. The person sees the change the next time they log in.'];
    }
}
