<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class InventoryController extends Controller
{
    public function inventory(Request $request)
    {
        $query = DB::table('products as p')
            ->leftJoin('category as c', 'c.id', '=', 'p.category_id')
            ->select(
                'p.id', 'p.product_id', 'p.product_name', 'p.product_image',
                'p.MRP as mrp', 'p.main_price', 'p.cost_price',
                'p.stock', 'p.product_unit', 'p.unit_quantity', 'p.status',
                'c.name as category_name'
            );

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('p.product_name', 'like', "%{$s}%")
                  ->orWhere('p.product_id', 'like', "%{$s}%");
            });
        }

        $products = $query->orderByDesc('p.id')->paginate(25)->withQueryString();
        return view('admin.inventory', compact('products'));
    }

    public function inventory_report_detail(Request $request)
    {
        $cities = Schema::hasTable('cities')
            ? DB::table('cities')->where('status', 'active')->orderBy('city')->get(['id', 'city'])
            : collect();

        $hubs = collect();
        if ($request->filled('city_id')) {
            $hubs = DB::table('hubs')->where('city_id', $request->city_id)->orderBy('hub')->get(['id', 'hub']);
        }

        $rows = DB::table('products as p')
            ->leftJoin('category as c', 'c.id', '=', 'p.category_id')
            ->select(
                'p.id', 'p.product_id', 'p.product_name',
                'c.name as category',
                DB::raw("CONCAT(COALESCE(p.unit_quantity, ''), '', COALESCE(p.product_unit, '')) as uom"),
                'p.cost_price as cost', 'p.MRP', 'p.stock'
            )
            ->orderByDesc('p.id')
            ->paginate(25)
            ->withQueryString();

        return view('admin.inventory_report_detail', compact('rows', 'cities', 'hubs'));
    }

    public function sale_summary(Request $request)
    {
        $query = DB::table('orders')
            ->select(
                'order_id',
                DB::raw('MAX(date_added) as date_added'),
                DB::raw('MAX(total_amount) as total_amount'),
                DB::raw('COUNT(*) as line_items'),
                DB::raw('MAX(buyer_id) as buyer_id')
            )
            ->where('order_status', 'Order Delivered')
            ->groupBy('order_id');

        if ($request->filled('date_from')) {
            $query->whereDate('date_added', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('date_added', '<=', $request->date_to);
        }

        $orders = $query->orderByDesc(DB::raw('MAX(date_added)'))->paginate(25)->withQueryString();

        $totals = DB::table('orders')
            ->select(
                DB::raw('COUNT(DISTINCT order_id) as total_orders'),
                DB::raw('SUM(total_amount) as total_revenue_raw')
            )
            ->where('order_status', 'Order Delivered')
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('date_added', '>=', $request->date_from))
            ->when($request->filled('date_to'),   fn ($q) => $q->whereDate('date_added', '<=', $request->date_to))
            ->first();

        // Revenue: sum of distinct (order_id, total_amount) pairs (orders has
        // one row per line item, so we de-dup before summing).
        $totalRevenue = DB::query()
            ->fromSub(
                DB::table('orders')->select('order_id', 'total_amount')
                    ->where('order_status', 'Order Delivered')
                    ->when($request->filled('date_from'), fn ($q) => $q->whereDate('date_added', '>=', $request->date_from))
                    ->when($request->filled('date_to'),   fn ($q) => $q->whereDate('date_added', '<=', $request->date_to))
                    ->distinct(),
                'sub'
            )
            ->sum('total_amount');

        return view('admin.sale_summary', compact('orders', 'totals', 'totalRevenue'));
    }
}
