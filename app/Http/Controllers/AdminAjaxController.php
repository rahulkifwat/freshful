<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Shared AJAX actions for the admin and the manager panels.
 *
 * Security fix (21 Oct 2026): access by panel
 *  - Delete: main admin only, and never admin accounts.
 *  - On/off switch: the admin keeps the full list; each manager panel may only switch
 *    the record types it works with (never staff, customers or admin accounts).
 *  - Order status: unchanged.
 *  - Every change is written to the Laravel log with who did it; a deleted row is logged in full.
 */
class AdminAjaxController extends Controller
{
    // Tables the ADMIN panel may change through these generic endpoints.
    private array $allowedTables = [
        'buyers',
        'products',
        'admins',
        'hub_user',
        'hubs',
        'cities',
        'account_managers',
        'area_managers',
        'country_managers',
        'customer_care_managers',
        'hr_managers',
        'marketing_managers',
        'operation_managers',
        'planning_managers',
        'production_user',
        'pos_users',
        'banner_images',
        'app_banner_images',
        'home_offers',
        'home_promotions',
        'coupons_and_deals',
        'categories',
        'category',
        'delivery_price',
        'news_letter',
        'contact_us',
        'frenchisee_enquiry',
        'certificates',
        'push_noti',
        'policies',
        'role_user',
        'items',
        'product_units',
        'product_tags',
        'product_filter',
        'delivery_boy',
        'grievance',
    ];

    // On/off switches each MANAGER panel may use (route name prefix => tables).
    private array $panelStatusTables = [
        'account_manager'       => ['delivery_boy', 'grievance'],
        'area_manager'          => ['delivery_boy', 'grievance'],
        'country_manager'       => ['delivery_boy', 'grievance'],
        'customer_care_manager' => ['delivery_boy', 'grievance'],
        'hr_manager'            => ['delivery_boy', 'grievance', 'home_offers'],
        'hub'                   => ['delivery_boy'],
        'marketing_manager'     => ['delivery_boy', 'grievance', 'home_offers', 'items', 'category'],
        'operation_manager'     => ['delivery_boy', 'grievance', 'home_offers', 'hubs'],
        'planning_manager'      => ['delivery_boy', 'grievance', 'home_offers', 'certificates'],
        'production'            => ['delivery_boy', 'grievance', 'home_offers', 'certificates'],
    ];

    public function changeStatus(Request $request)
    {
        $data = $request->validate([
            'table' => 'required|string',
            'id'    => 'required|integer',
            'value' => 'nullable|string',
        ]);

        $panel = $this->panel($request);
        $allowed = $this->isAdmin($panel) ? $this->allowedTables : ($this->panelStatusTables[$panel] ?? []);

        if (! in_array($data['table'], $allowed, true)) {
            $this->audit('status-refused', $panel, $data['table'], $data['id']);
            return response()->json(['result' => false, 'message' => 'You do not have permission to change this.'], 403);
        }

        if ($data['table'] === 'admins' && (int) $data['id'] === (int) Auth::guard('admin')->id()) {
            return response()->json(['result' => false, 'message' => 'You cannot switch off your own admin account.'], 422);
        }

        if (! Schema::hasColumn($data['table'], 'status')) {
            return response()->json(['result' => false, 'message' => 'No status column on this table.'], 422);
        }

        // Toggle: if currently active -> disabled, else -> active. Legacy schema
        // uses ENUM('active','disabled') on most tables.
        $current = $data['value'] ?? DB::table($data['table'])->where('id', $data['id'])->value('status');
        $next    = $current === 'active' ? 'disabled' : 'active';

        $updated = DB::table($data['table'])
            ->where('id', $data['id'])
            ->update(['status' => $next]);

        $this->audit('status', $panel, $data['table'], $data['id'], ['to' => $next, 'updated' => $updated]);

        return response()->json([
            'result'  => (bool) $updated,
            'status'  => $next,
            'message' => $updated ? 'Status updated.' : 'Nothing changed.',
        ]);
    }

    public function deleteRecord(Request $request)
    {
        $data = $request->validate([
            'table' => 'required|string',
            'id'    => 'required|integer',
        ]);

        $panel = $this->panel($request);

        if (! $this->isAdmin($panel)) {
            $this->audit('delete-refused', $panel, $data['table'], $data['id']);
            return response()->json(['result' => false, 'message' => 'Only the admin can delete records. Switch it off instead.'], 403);
        }

        if ($data['table'] === 'admins') {
            $this->audit('delete-refused', $panel, $data['table'], $data['id']);
            return response()->json(['result' => false, 'message' => 'Admin accounts cannot be deleted here.'], 403);
        }

        if (! in_array($data['table'], $this->allowedTables, true)) {
            return response()->json(['result' => false, 'message' => 'Table not allowed.'], 422);
        }

        // Keep a full copy of the row in the log so it can be put back if deleted by mistake.
        $row = DB::table($data['table'])->where('id', $data['id'])->first();
        $deleted = DB::table($data['table'])->where('id', $data['id'])->delete();

        $this->audit('delete', $panel, $data['table'], $data['id'], ['deleted' => $deleted, 'row' => $row ? (array) $row : null]);

        return response()->json([
            'result'  => (bool) $deleted,
            'message' => $deleted ? 'Deleted.' : 'Record not found.',
        ]);
    }

    public function changeOrderStatus(Request $request)
    {
        $data = $request->validate([
            'id'           => 'required|integer',
            'order_id'     => 'nullable|string',
            'order_status' => 'required|in:Order Pending,Order Cancel,Order Placed,Order Processed,Order Shipped,Order Delivered',
        ]);

        // Update every row sharing this order_id (orders table has one row per
        // line item). Fall back to id-only update when order_id wasn't sent.
        $query = DB::table('orders');
        if (! empty($data['order_id'])) {
            $query->where('order_id', $data['order_id']);
        } else {
            $query->where('id', $data['id']);
        }

        $updated = $query->update(['order_status' => $data['order_status']]);
        if ($updated && $data['order_status'] === 'Order Delivered') {
            $deliveredOrderId = !empty($data['order_id']) ? $data['order_id'] : DB::table('orders')->where('id', $data['id'])->value('order_id');
            if ($deliveredOrderId) { try { app(\App\Services\BuyerReferralService::class)->awardForDeliveredOrder((string) $deliveredOrderId); } catch (\Throwable $e) { report($e); } }
        }

        $this->audit('order-status', $this->panel($request), 'orders', ($data['order_id'] ?? null) ?: $data['id'], ['to' => $data['order_status'], 'updated' => $updated]);

        return response()->json([
            'result'  => (bool) $updated,
            'message' => $updated ? 'Order status updated.' : 'Nothing changed.',
        ]);
    }

    /** Which panel sent the request: the route name prefix, e.g. "admin", "hub", "operation_manager". */
    private function panel(Request $request): string
    {
        $name = (string) optional($request->route())->getName();
        $pos = strpos($name, '.ajax.');
        return $pos === false ? 'unknown' : substr($name, 0, $pos);
    }

    /** Full admin powers only on the admin routes AND with a logged-in admin. */
    private function isAdmin(string $panel): bool
    {
        try {
            return $panel === 'admin' && Auth::guard('admin')->check();
        } catch (\Throwable $e) {
            return false;
        }
    }

    /** Who is logged in (first guard that has a user), for the change log. */
    private function actor(): string
    {
        foreach (array_keys((array) config('auth.guards', [])) as $guard) {
            try {
                if (Auth::guard($guard)->check()) {
                    return $guard . '#' . Auth::guard($guard)->id();
                }
            } catch (\Throwable $e) {
                // guard not usable here
            }
        }
        return 'unknown';
    }

    private function audit(string $action, string $panel, string $table, $id, array $extra = []): void
    {
        try {
            Log::info('[freshful-audit] ' . $action, array_merge([
                'panel' => $panel,
                'by'    => $this->actor(),
                'table' => $table,
                'id'    => $id,
                'ip'    => request()->ip(),
            ], $extra));
        } catch (\Throwable $e) {
            // logging must never block the action
        }
    }
}
