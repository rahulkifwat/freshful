<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AccountManagerController extends Controller
{
    public function dashboard()
    {
        $total_buyers   = DB::table('buyers')->count();
        $total_orders   = Schema::hasTable('orders')   ? DB::table('orders')->distinct('order_id')->count('order_id') : 0;
        $total_products = Schema::hasTable('products') ? DB::table('products')->count() : 0;
        $today_orders   = Schema::hasTable('orders')
            ? DB::table('orders')->whereDate('created_at', today())->distinct('order_id')->count('order_id')
            : 0;

        return view('account_manager.dashboard', compact(
            'total_buyers', 'total_orders', 'total_products', 'today_orders'
        ));
    }

    public function allCustomers(Request $request)
    {
        $search = $request->input('search');
        $query  = DB::table('buyers');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $customers = $query->orderByDesc('id')->paginate(25)->withQueryString();

        return view('account_manager.all_customers', compact('customers', 'search'));
    }

    public function allOrders(Request $request)
    {
        if (! Schema::hasTable('orders')) {
            return view('account_manager.all_orders', ['orders' => collect(), 'search' => '']);
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
            ->groupBy('order_id');

        if ($search) {
            $query->having(DB::raw('MAX(buyer_name)'), 'like', "%{$search}%")
                  ->orHaving(DB::raw('MAX(buyer_phone)'), 'like', "%{$search}%")
                  ->orHaving('order_id', 'like', "%{$search}%");
        }

        if ($status) {
            $query->where('order_status', $status);
        }

        $orders = $query->orderByDesc('id')->paginate(25)->withQueryString();

        return view('account_manager.all_orders', compact('orders', 'search', 'status'));
    }

    public function products(Request $request)
    {
        $search = $request->input('search');
        $query  = DB::table('products');

        if ($search) {
            $query->where('product_name', 'like', "%{$search}%");
        }

        $products = $query->orderByDesc('id')->paginate(25)->withQueryString();

        return view('account_manager.products', compact('products', 'search'));
    }

    public function grievance(Request $request)
    {
        $rows = collect();
        if (Schema::hasTable('grievance')) {
            $rows = DB::table('grievance')->orderByDesc('id')->paginate(25)->withQueryString();
        }

        return view('account_manager.grievance', compact('rows'));
    }

    public function deliveryBoy(Request $request)
    {
        $table = Schema::hasTable('delivery_boy') ? 'delivery_boy' : null;
        $rows  = $table
            ? DB::table($table)->orderByDesc('id')->paginate(25)->withQueryString()
            : collect();

        return view('account_manager.delivery_boy', compact('rows'));
    }

    public function walletHistory(Request $request)
    {
        $table = Schema::hasTable('wallet_transaction_history') ? 'wallet_transaction_history'
               : (Schema::hasTable('wallet_history') ? 'wallet_history' : null);

        $rows = $table
            ? DB::table($table)->orderByDesc('id')->paginate(25)->withQueryString()
            : collect();

        return view('account_manager.wallet_history', compact('rows'));
    }

    public function hubInventory(Request $request)
    {
        $table = Schema::hasTable('hub_inventory') ? 'hub_inventory'
               : (Schema::hasTable('inward_stock') ? 'inward_stock' : null);

        $rows = $table
            ? DB::table($table)->orderByDesc('id')->paginate(25)->withQueryString()
            : collect();

        return view('account_manager.hub_inventory', compact('rows'));
    }

    public function dangerStock(Request $request)
    {
        $rows = collect();
        if (Schema::hasTable('products')) {
            // Products with stock at or below a safety threshold (column: stock_qty or quantity)
            $col = Schema::hasColumn('products', 'stock_qty') ? 'stock_qty'
                 : (Schema::hasColumn('products', 'quantity') ? 'quantity' : null);
            $query = DB::table('products');
            if ($col) {
                $query->where($col, '<=', 10);
            }
            $rows = $query->orderBy('id')->paginate(25)->withQueryString();
        }

        return view('account_manager.danger_stock', compact('rows'));
    }

    public function wastageReports(Request $request)
    {
        $table = Schema::hasTable('wastage_stock') ? 'wastage_stock'
               : (Schema::hasTable('wastage') ? 'wastage' : null);

        $rows = $table
            ? DB::table($table)->orderByDesc('id')->paginate(25)->withQueryString()
            : collect();

        return view('account_manager.wastage_reports', compact('rows'));
    }

    public function pendingInward(Request $request)
    {
        $table = Schema::hasTable('pending_inward') ? 'pending_inward'
               : (Schema::hasTable('inward_request') ? 'inward_request' : null);

        $rows = $table
            ? DB::table($table)->orderByDesc('id')->paginate(25)->withQueryString()
            : collect();

        return view('account_manager.pending_inward', compact('rows'));
    }

    public function cashDeposit(Request $request)
    {
        $table = Schema::hasTable('cash_deposit') ? 'cash_deposit' : null;

        $rows = $table
            ? DB::table($table)->orderByDesc('id')->paginate(25)->withQueryString()
            : collect();

        return view('account_manager.cash_deposit', compact('rows'));
    }

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
                ->groupBy(DB::raw('DATE(created_at)'));

            if ($from) {
                $query->whereDate('created_at', '>=', $from);
            }
            if ($to) {
                $query->whereDate('created_at', '<=', $to);
            }

            $rows = $query->orderByDesc('sale_date')->paginate(25)->withQueryString();

            $totals = DB::table('orders')
                ->when($from, fn ($q) => $q->whereDate('created_at', '>=', $from))
                ->when($to,   fn ($q) => $q->whereDate('created_at', '<=', $to))
                ->selectRaw('COUNT(DISTINCT order_id) as total_orders, SUM(total_price) as total_revenue')
                ->first();
        }

        return view('account_manager.sales_report', compact('rows', 'totals', 'from', 'to'));
    }
}
