<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class AreaManagerController extends Controller
{
    private function user()
    {
        return Auth::guard('area_managers')->user();
    }

    // ─── Dashboard ──────────────────────────────────────────────────────────────

    public function dashboard()
    {
        $total_orders   = Schema::hasTable('orders')       ? DB::table('orders')->distinct('order_id')->count('order_id') : 0;
        $total_products = Schema::hasTable('products')     ? DB::table('products')->count() : 0;
        $total_buyers   = DB::table('buyers')->count();
        $danger_count   = Schema::hasTable('danger_stock') ? DB::table('danger_stock')->count() : 0;
        $wastage_count  = Schema::hasTable('wastage_stock')? DB::table('wastage_stock')->count() : 0;
        $today_orders   = Schema::hasTable('orders')
            ? DB::table('orders')->whereDate('created_at', today())->distinct('order_id')->count('order_id')
            : 0;

        return view('area_manager.dashboard', compact(
            'total_orders', 'total_products', 'total_buyers',
            'danger_count', 'wastage_count', 'today_orders'
        ));
    }

    // ─── Customers ──────────────────────────────────────────────────────────────

    public function allCustomers(Request $request)
    {
        $search    = $request->input('search');
        $customers = DB::table('buyers')
            ->when($search, fn ($q) => $q->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            }))
            ->orderByDesc('id')
            ->paginate(25)->withQueryString();

        return view('area_manager.all_customers', compact('customers', 'search'));
    }

    // ─── Orders ─────────────────────────────────────────────────────────────────

    public function allOrders(Request $request)
    {
        if (! Schema::hasTable('orders')) {
            return view('area_manager.all_orders', ['orders' => collect(), 'search' => '', 'status' => '']);
        }

        $search = $request->input('search');
        $status = $request->input('status');

        $query = DB::table('orders')
            ->select('order_id',
                DB::raw('MAX(id) as id'),
                DB::raw('MAX(buyer_name) as buyer_name'),
                DB::raw('MAX(buyer_phone) as buyer_phone'),
                DB::raw('SUM(total_price) as total_price'),
                DB::raw('MAX(order_status) as order_status'),
                DB::raw('MAX(created_at) as created_at')
            )
            ->groupBy('order_id')
            ->when($status, fn ($q) => $q->where('order_status', $status))
            ->orderByDesc(DB::raw('MAX(id)'));

        $orders = $query->paginate(25)->withQueryString();

        return view('area_manager.all_orders', compact('orders', 'search', 'status'));
    }

    public function viewOrder(Request $request)
    {
        $order_id = $request->input('order_id');
        $items    = [];
        $buyer    = null;

        if ($order_id && Schema::hasTable('orders')) {
            $items = DB::table('orders')
                ->where('order_id', $order_id)
                ->get();

            if ($items->isNotEmpty() && Schema::hasTable('buyers')) {
                $buyer = DB::table('buyers')->where('id', $items->first()->buyer_id ?? 0)->first();
            }
        }

        return view('area_manager.view_order', compact('items', 'buyer', 'order_id'));
    }

    public function scheduledOrders(Request $request)
    {
        if (! Schema::hasTable('orders')) {
            return view('area_manager.scheduled_orders', ['orders' => collect()]);
        }

        $orders = DB::table('orders')
            ->select('order_id',
                DB::raw('MAX(id) as id'),
                DB::raw('MAX(buyer_name) as buyer_name'),
                DB::raw('MAX(buyer_phone) as buyer_phone'),
                DB::raw('SUM(total_price) as total_price'),
                DB::raw('MAX(order_status) as order_status'),
                DB::raw('MAX(created_at) as created_at')
            )
            ->groupBy('order_id')
            ->where('order_status', '!=', 'Order Delivered')
            ->orderByDesc(DB::raw('MAX(id)'))
            ->paginate(25)->withQueryString();

        return view('area_manager.scheduled_orders', compact('orders'));
    }

    public function interhubOrders(Request $request)
    {
        $rows = collect();
        if (Schema::hasTable('orders') && Schema::hasTable('hubs')) {
            $rows = DB::table('orders')
                ->leftJoin('hubs', 'orders.hub_id', '=', 'hubs.id')
                ->select('orders.order_id',
                    DB::raw('MAX(orders.id) as id'),
                    DB::raw('MAX(orders.buyer_name) as buyer_name'),
                    DB::raw('MAX(orders.buyer_phone) as buyer_phone'),
                    DB::raw('MAX(hubs.hub) as hub_name'),
                    DB::raw('MAX(orders.order_status) as order_status'),
                    DB::raw('MAX(orders.created_at) as created_at')
                )
                ->groupBy('orders.order_id')
                ->orderByDesc(DB::raw('MAX(orders.id)'))
                ->paginate(25)->withQueryString();
        }

        return view('area_manager.interhub_orders', compact('rows'));
    }

    // ─── Products ───────────────────────────────────────────────────────────────

    public function products(Request $request)
    {
        $search = $request->input('search');

        $products = DB::table('products')
            ->leftJoin('category as cat', 'products.cat_id', '=', 'cat.id')
            ->select('products.*', 'cat.name as cat_name')
            ->when($search, fn ($q) => $q->where('products.product_name', 'like', "%{$search}%"))
            ->orderByDesc('products.id')
            ->paginate(25)->withQueryString();

        return view('area_manager.products', compact('products', 'search'));
    }

    // ─── Delivery Boy ────────────────────────────────────────────────────────────

    public function deliveryBoy(Request $request)
    {
        $rows = collect();
        if (Schema::hasTable('delivery_boy')) {
            $rows = DB::table('delivery_boy')
                ->leftJoin('hubs', 'delivery_boy.hub_id', '=', 'hubs.id')
                ->select('delivery_boy.*', DB::raw('IFNULL(hubs.hub, "—") as hub_name'))
                ->orderByDesc('delivery_boy.id')
                ->paginate(25)->withQueryString();
        }

        return view('area_manager.delivery_boy', compact('rows'));
    }

    // ─── Grievance ───────────────────────────────────────────────────────────────

    public function grievance(Request $request)
    {
        $rows       = collect();
        $categories = collect();

        if (Schema::hasTable('grievance')) {
            $rows = DB::table('grievance')->orderByDesc('id')->paginate(25)->withQueryString();
        }
        if (Schema::hasTable('grievance_category')) {
            $categories = DB::table('grievance_category')->get();
        }

        return view('area_manager.grievance', compact('rows', 'categories'));
    }

    // ─── Inventory ───────────────────────────────────────────────────────────────

    public function hubInventory(Request $request)
    {
        $rows = collect();
        if (Schema::hasTable('inventory')) {
            $rows = DB::table('inventory')
                ->leftJoin('products', 'inventory.product_id', '=', 'products.id')
                ->leftJoin('hubs', 'inventory.hub_id', '=', 'hubs.id')
                ->select('inventory.*',
                    DB::raw('IFNULL(products.product_name, "—") as product_name'),
                    DB::raw('IFNULL(hubs.hub, "—") as hub_name')
                )
                ->orderByDesc('inventory.id')
                ->paginate(25)->withQueryString();
        }

        return view('area_manager.hub_inventory', compact('rows'));
    }

    public function pendingInward(Request $request)
    {
        $rows = collect();
        if (Schema::hasTable('inventory')) {
            $rows = DB::table('inventory')
                ->leftJoin('products', 'inventory.product_id', '=', 'products.id')
                ->leftJoin('hubs', 'inventory.hub_id', '=', 'hubs.id')
                ->select('inventory.*',
                    DB::raw('IFNULL(products.product_name, "—") as product_name'),
                    DB::raw('IFNULL(hubs.hub, "—") as hub_name')
                )
                ->where('inventory.stock', '>', 0)
                ->orderByDesc('inventory.id')
                ->paginate(25)->withQueryString();
        }

        return view('area_manager.pending_inward', compact('rows'));
    }

    public function inwardOutward(Request $request)
    {
        $rows = collect();
        if (Schema::hasTable('inventory')) {
            $rows = DB::table('inventory')
                ->leftJoin('products', 'inventory.product_id', '=', 'products.id')
                ->leftJoin('hubs', 'inventory.hub_id', '=', 'hubs.id')
                ->select('inventory.*',
                    DB::raw('IFNULL(products.product_name, "—") as product_name'),
                    DB::raw('IFNULL(hubs.hub, "—") as hub_name')
                )
                ->orderByDesc('inventory.id')
                ->paginate(25)->withQueryString();
        }

        return view('area_manager.inward_outward', compact('rows'));
    }

    // ─── Danger Stock ────────────────────────────────────────────────────────────

    public function dangerStock(Request $request)
    {
        $rows = collect();
        if (Schema::hasTable('danger_stock')) {
            $rows = DB::table('danger_stock')
                ->leftJoin('products', 'danger_stock.product_id', '=', 'products.id')
                ->leftJoin('hubs', 'danger_stock.hub_id', '=', 'hubs.id')
                ->select('danger_stock.*',
                    DB::raw('IFNULL(products.product_name, "—") as product_name'),
                    DB::raw('IFNULL(hubs.hub, "—") as hub_name')
                )
                ->orderByDesc('danger_stock.id')
                ->paginate(25)->withQueryString();
        }

        return view('area_manager.danger_stock', compact('rows'));
    }

    // ─── Wastage ─────────────────────────────────────────────────────────────────

    public function wastageReports(Request $request)
    {
        $rows = collect();
        if (Schema::hasTable('wastage_stock')) {
            $rows = DB::table('wastage_stock')
                ->leftJoin('products', 'wastage_stock.product_id', '=', 'products.id')
                ->leftJoin('hubs', 'wastage_stock.hub_id', '=', 'hubs.id')
                ->select('wastage_stock.*',
                    DB::raw('IFNULL(products.product_name, "—") as product_name'),
                    DB::raw('IFNULL(hubs.hub, "—") as hub_name')
                )
                ->orderByDesc('wastage_stock.id')
                ->paginate(25)->withQueryString();
        }

        return view('area_manager.wastage_reports', compact('rows'));
    }

    // ─── Reports ─────────────────────────────────────────────────────────────────

    public function salesReport(Request $request)
    {
        $from   = $request->input('from');
        $to     = $request->input('to');
        $rows   = collect();
        $totals = null;

        if (Schema::hasTable('orders')) {
            $query = DB::table('orders')
                ->select(
                    DB::raw('DATE(created_at) as sale_date'),
                    DB::raw('COUNT(DISTINCT order_id) as total_orders'),
                    DB::raw('SUM(total_price) as total_revenue')
                )
                ->groupBy(DB::raw('DATE(created_at)'))
                ->when($from, fn ($q) => $q->whereDate('created_at', '>=', $from))
                ->when($to,   fn ($q) => $q->whereDate('created_at', '<=', $to));

            $rows = $query->orderByDesc('sale_date')->paginate(25)->withQueryString();

            $totals = DB::table('orders')
                ->when($from, fn ($q) => $q->whereDate('created_at', '>=', $from))
                ->when($to,   fn ($q) => $q->whereDate('created_at', '<=', $to))
                ->selectRaw('COUNT(DISTINCT order_id) as total_orders, SUM(total_price) as total_revenue')
                ->first();
        }

        return view('area_manager.sales_report', compact('rows', 'totals', 'from', 'to'));
    }

    public function walletHistory(Request $request)
    {
        $table = Schema::hasTable('wallet_transaction_history') ? 'wallet_transaction_history'
               : (Schema::hasTable('wallet_history') ? 'wallet_history' : null);

        $rows = $table
            ? DB::table($table)->orderByDesc('id')->paginate(25)->withQueryString()
            : collect();

        return view('area_manager.wallet_history', compact('rows'));
    }

    public function onlinePaymentHistory(Request $request)
    {
        $rows = collect();
        if (Schema::hasTable('orders')) {
            $rows = DB::table('orders')
                ->where('payment_type', '!=', 'cod')
                ->select('order_id',
                    DB::raw('MAX(id) as id'),
                    DB::raw('MAX(buyer_name) as buyer_name'),
                    DB::raw('MAX(payment_type) as payment_type'),
                    DB::raw('SUM(total_price) as total_price'),
                    DB::raw('MAX(created_at) as created_at')
                )
                ->groupBy('order_id')
                ->orderByDesc(DB::raw('MAX(id)'))
                ->paginate(25)->withQueryString();
        }

        return view('area_manager.online_payment_history', compact('rows'));
    }

    public function ratingReviews(Request $request)
    {
        $rows = collect();
        if (Schema::hasTable('reviews')) {
            $rows = DB::table('reviews')->orderByDesc('id')->paginate(25)->withQueryString();
        }

        return view('area_manager.rating_reviews', compact('rows'));
    }

    public function newsLetters(Request $request)
    {
        $rows = DB::table('news_letter')->orderByDesc('id')->paginate(25)->withQueryString();

        return view('area_manager.news_letters', compact('rows'));
    }

    // ─── Profile ─────────────────────────────────────────────────────────────────

    public function profile()
    {
        $user = $this->user();
        return view('area_manager.profile', compact('user'));
    }

    public function editProfile()
    {
        $user = $this->user();
        return view('area_manager.edit_profile', compact('user'));
    }

    public function updateProfile(Request $request)
    {
        $request->validate([
            'name'    => 'required|string|max:255',
            'address' => 'nullable|string|max:500',
        ]);

        $data = ['name' => $request->name, 'address' => $request->address];

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        DB::table('area_managers')->where('id', $this->user()->id)->update($data);

        return redirect()->route('area_manager.profile')->with('success', 'Profile updated.');
    }
}
