<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    public function index()
    {
        return view('admin.dashboard');
    }

    public function all_orders(Request $request)
    {
        $query = DB::table('orders as t1')
            ->join('buyers as t2', 't2.id', '=', 't1.buyer_id')
            ->join('buyer_address as t3', 't3.id', '=', 't1.address_id')
            ->join('hubs as t4', 't4.id', '=', 't1.hub_id')
            ->select(
                't1.*',
                't2.name',
                't3.phone',
                't4.hub'
            );

        // 🔎 Filters
        if ($request->date) {
            $query->whereDate('t1.date_added', $request->date);
        }

        if ($request->date_from) {
            $query->whereDate('t1.date_added', '>=', $request->date_from);
        }

        if ($request->date_to) {
            $query->whereDate('t1.date_added', '<=', $request->date_to);
        }

        if ($request->city_id) {
            $query->where('t4.city_id', $request->city_id);
        }

        if ($request->hub_id) {
            $query->where('t4.id', $request->hub_id);
        }

        if ($request->customer) {
            $query->where('t2.name', 'like', '%' . $request->customer . '%');
        }

        if ($request->customer_mobile) {
            $query->where('t2.phone', $request->customer_mobile);
        }

        if ($request->order_status) {
            $query->where('t1.order_status', $request->order_status);
        }

        if ($request->delivery_type) {
            $query->where('t1.delivery_type', $request->delivery_type);
        }

        $orders = $query
                ->latest('t1.date_added')
                ->get();

        return view('admin.all_orders', compact('orders'));
    }
    public function order(Request $request){
        $query = DB::table('orders as t1')
        ->join('buyers as t2', 't2.id', '=', 't1.buyer_id')
        ->join('buyer_address as t3', 't3.id', '=', 't1.address_id')
        ->join('hubs as t4', 't4.id', '=', 't1.hub_id')
        ->select(
            't1.id',
            't1.order_id',
            't1.date_added',
            't1.total_amount',
            't1.order_status',
            't2.name as customer_name'
        );

    // 🔎 Optional filters (safe)
    if ($request->search) {
        $query->where('t1.order_id', 'like', '%' . $request->search . '%');
    }

    $orders = $query
        ->orderByDesc('t1.date_added')
        ->paginate(10);

    return view('admin.order', compact('orders'));
    }
    public function products(Request $request)
    {
        $query = DB::table('products as p')
            ->leftJoin('categories as c', 'c.id', '=', 'p.category_id')
            ->leftJoin('main_categories as mc', 'mc.id', '=', 'p.main_category_id')
            ->select(
                'p.id',
                'p.product_id',
                'p.product_name',
                'p.mrp',
                'p.main_price',
                'p.image',
                'p.status',
                'p.rank',
                'c.category_name',
                'mc.main_category_name'
            );

        if ($request->main_cat) {
            $query->where('p.main_category_id', $request->main_cat);
        }

        if ($request->category) {
            $query->where('p.category_id', $request->category);
        }

        if ($request->product) {
            $query->where('p.id', $request->product);
        }

        if ($request->search) {
            $query->where('p.product_name', 'like', '%' . $request->search . '%');
        }

        $products = $query
            ->orderByDesc('p.id')
            ->paginate(10);

        return view('admin.products', compact('products'));
    }
}
