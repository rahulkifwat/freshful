<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AdminAjaxController extends Controller
{
    // Tables the admin panel is allowed to mutate via these generic endpoints.
    // Keep this allow-list tight — the table name comes from a request body.
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
        'category',
        'delivery_boy',
        'grievance',
    ];

    public function changeStatus(Request $request)
    {
        $data = $request->validate([
            'table' => 'required|string',
            'id'    => 'required|integer',
            'value' => 'nullable|string',
        ]);

        if (! in_array($data['table'], $this->allowedTables, true)) {
            return response()->json(['result' => false, 'message' => 'Table not allowed.'], 422);
        }

        if (! Schema::hasColumn($data['table'], 'status')) {
            return response()->json(['result' => false, 'message' => 'No status column on this table.'], 422);
        }

        // Toggle: if currently active → disabled, else → active. Legacy schema
        // uses ENUM('active','disabled') on most tables — sending 'inactive'
        // truncates. Trust the posted current value; fall back to a DB read.
        $current = $data['value'] ?? DB::table($data['table'])->where('id', $data['id'])->value('status');
        $next    = $current === 'active' ? 'disabled' : 'active';

        $updated = DB::table($data['table'])
            ->where('id', $data['id'])
            ->update(['status' => $next]);

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

        if (! in_array($data['table'], $this->allowedTables, true)) {
            return response()->json(['result' => false, 'message' => 'Table not allowed.'], 422);
        }

        $deleted = DB::table($data['table'])->where('id', $data['id'])->delete();

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

        return response()->json([
            'result'  => (bool) $updated,
            'message' => $updated ? 'Order status updated.' : 'Nothing changed.',
        ]);
    }
}
