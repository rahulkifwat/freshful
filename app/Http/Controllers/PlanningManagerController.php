<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PlanningManagerController extends Controller
{
    private function guard()
    {
        return Auth::guard('planning_managers');
    }

    public function dashboard()
    {
        $buyers    = Schema::hasTable('buyers') ? DB::table('buyers')->count() : 0;
        $orders    = Schema::hasTable('orders') ? DB::table('orders')->count() : 0;
        $today_orders = Schema::hasTable('orders') ? DB::table('orders')->whereDate('created_at', today())->count() : 0;
        $products  = Schema::hasTable('products') ? DB::table('products')->count() : 0;
        return view('planning_manager.dashboard', compact('buyers', 'orders', 'today_orders', 'products'));
    }

    // Orders
    public function allOrders()
    {
        $orders = collect();
        if (Schema::hasTable('orders')) {
            $orders = DB::table('orders')
                ->leftJoin('buyers', 'buyers.id', '=', 'orders.buyer_id')
                ->select('orders.*', 'buyers.name as customer_name', 'buyers.phone')
                ->orderByDesc('orders.id')
                ->paginate(25);
        }
        return view('planning_manager.all_orders', compact('orders'));
    }

    public function viewOrder($id)
    {
        $order = null; $items = collect();
        if (Schema::hasTable('orders')) {
            $order = DB::table('orders')
                ->leftJoin('buyers', 'buyers.id', '=', 'orders.buyer_id')
                ->leftJoin('hubs', 'hubs.id', '=', 'orders.hub_id')
                ->select('orders.*', 'buyers.name as customer_name', 'buyers.phone', 'hubs.name as hub_name')
                ->where('orders.id', $id)->first();
        }
        if (Schema::hasTable('order_items')) {
            $items = DB::table('order_items')
                ->leftJoin('products', 'products.id', '=', 'order_items.product_id')
                ->leftJoin('items', 'items.id', '=', 'products.item_id')
                ->select('order_items.*', 'items.item as product_name')
                ->where('order_items.order_id', $id)->get();
        }
        return view('planning_manager.view_order', compact('order', 'items'));
    }

    public function viewOperationOrder($id)
    {
        $order = null; $items = collect();
        if (Schema::hasTable('orders')) {
            $order = DB::table('orders')
                ->leftJoin('buyers', 'buyers.id', '=', 'orders.buyer_id')
                ->leftJoin('hubs', 'hubs.id', '=', 'orders.hub_id')
                ->select('orders.*', 'buyers.name as customer_name', 'buyers.phone', 'hubs.name as hub_name')
                ->where('orders.id', $id)->first();
        }
        if (Schema::hasTable('order_items')) {
            $items = DB::table('order_items')
                ->leftJoin('products', 'products.id', '=', 'order_items.product_id')
                ->leftJoin('items', 'items.id', '=', 'products.item_id')
                ->select('order_items.*', 'items.item as product_name')
                ->where('order_items.order_id', $id)->get();
        }
        return view('planning_manager.view_operation_order', compact('order', 'items'));
    }

    public function scheduledOrders()
    {
        $orders = collect();
        if (Schema::hasTable('orders')) {
            $orders = DB::table('orders')
                ->leftJoin('buyers', 'buyers.id', '=', 'orders.buyer_id')
                ->where('orders.order_type', 'scheduled')
                ->select('orders.*', 'buyers.name as customer_name')
                ->orderByDesc('orders.id')->paginate(25);
        }
        return view('planning_manager.scheduled_orders', compact('orders'));
    }

    public function pendingOrders()
    {
        $orders = collect();
        if (Schema::hasTable('orders')) {
            $orders = DB::table('orders')
                ->leftJoin('buyers', 'buyers.id', '=', 'orders.buyer_id')
                ->leftJoin('hubs', 'hubs.id', '=', 'orders.hub_id')
                ->where('orders.order_status', 'placed')
                ->select('orders.*', 'buyers.name as customer_name', 'hubs.name as hub_name')
                ->orderByDesc('orders.id')->paginate(25);
        }
        return view('planning_manager.pending_orders', compact('orders'));
    }

    public function orderStatus()
    {
        $orders = collect();
        if (Schema::hasTable('orders')) {
            $q = DB::table('orders')->leftJoin('buyers', 'buyers.id', '=', 'orders.buyer_id')
                ->select('orders.*', 'buyers.name as customer_name');
            if (request('status')) $q->where('orders.order_status', request('status'));
            if (request('date'))   $q->whereDate('orders.created_at', request('date'));
            $orders = $q->orderByDesc('orders.id')->paginate(25);
        }
        return view('planning_manager.order_status', compact('orders'));
    }

    // Customers
    public function allCustomers()
    {
        $customers = collect();
        if (Schema::hasTable('buyers')) {
            $q = DB::table('buyers');
            if (request('search')) $q->where(function($w){ $w->where('name','like','%'.request('search').'%')->orWhere('phone','like','%'.request('search').'%'); });
            $customers = $q->orderByDesc('id')->paginate(25);
        }
        return view('planning_manager.all_customers', compact('customers'));
    }

    public function customerOrder()
    {
        $orders = collect();
        if (Schema::hasTable('orders')) {
            $orders = DB::table('orders')
                ->leftJoin('buyers', 'buyers.id', '=', 'orders.buyer_id')
                ->select('orders.*', 'buyers.name as customer_name', 'buyers.phone')
                ->orderByDesc('orders.id')->paginate(25);
        }
        return view('planning_manager.customer_order', compact('orders'));
    }

    // Inventory
    public function hubInventory()
    {
        $inventory = collect(); $hubs = collect();
        if (Schema::hasTable('hubs')) $hubs = DB::table('hubs')->get();
        if (Schema::hasTable('inventory')) {
            $q = DB::table('inventory')
                ->leftJoin('products', 'products.product_id', '=', 'inventory.product_id')
                ->leftJoin('items', 'items.id', '=', 'products.item_id')
                ->leftJoin('hubs', 'hubs.id', '=', 'inventory.hub_id')
                ->select('inventory.*', 'items.item as product_name', 'hubs.name as hub_name');
            if (request('hub_id')) $q->where('inventory.hub_id', request('hub_id'));
            $inventory = $q->paginate(25);
        }
        return view('planning_manager.hub_inventory', compact('inventory', 'hubs'));
    }

    public function pendingInward()
    {
        $rows = collect();
        if (Schema::hasTable('inward_stocks')) {
            $rows = DB::table('inward_stocks')
                ->leftJoin('hubs', 'hubs.id', '=', 'inward_stocks.hub_id')
                ->select('inward_stocks.*', 'hubs.name as hub_name')
                ->where('inward_stocks.status', 'pending')
                ->orderByDesc('inward_stocks.id')->paginate(25);
        }
        return view('planning_manager.pending_inward', compact('rows'));
    }

    public function lockedInventory()
    {
        $rows = collect();
        if (Schema::hasTable('inventory')) {
            $rows = DB::table('inventory')
                ->leftJoin('products', 'products.product_id', '=', 'inventory.product_id')
                ->leftJoin('items', 'items.id', '=', 'products.item_id')
                ->leftJoin('hubs', 'hubs.id', '=', 'inventory.hub_id')
                ->select('inventory.*', 'items.item as product_name', 'hubs.name as hub_name')
                ->where('inventory.locked', 1)->paginate(25);
        }
        return view('planning_manager.locked_inventory', compact('rows'));
    }

    public function interhubOrders()
    {
        $rows = collect(); $hubs = collect();
        if (Schema::hasTable('hubs')) $hubs = DB::table('hubs')->get();
        if (Schema::hasTable('interhub_orders')) {
            $q = DB::table('interhub_orders')
                ->leftJoin('hubs as fh', 'fh.id', '=', 'interhub_orders.from_hub_id')
                ->leftJoin('hubs as th', 'th.id', '=', 'interhub_orders.to_hub_id')
                ->select('interhub_orders.*', 'fh.name as from_hub_name', 'th.name as to_hub_name');
            $rows = $q->orderByDesc('interhub_orders.id')->paginate(25);
        }
        return view('planning_manager.interhub_orders', compact('rows', 'hubs'));
    }

    public function interhubMomentsView()
    {
        $rows = collect(); $hubs = collect();
        if (Schema::hasTable('hubs')) $hubs = DB::table('hubs')->get();
        if (Schema::hasTable('interhub_moments')) {
            $q = DB::table('interhub_moments')
                ->leftJoin('hubs as fh', 'fh.id', '=', 'interhub_moments.from_hub_id')
                ->leftJoin('hubs as th', 'th.id', '=', 'interhub_moments.to_hub_id')
                ->select('interhub_moments.*', 'fh.name as from_hub_name', 'th.name as to_hub_name');
            if (request('from_hub')) $q->where('interhub_moments.from_hub_id', request('from_hub'));
            if (request('to_hub'))   $q->where('interhub_moments.to_hub_id', request('to_hub'));
            if (request('status'))   $q->where('interhub_moments.status', request('status'));
            $rows = $q->orderByDesc('interhub_moments.id')->paginate(25);
        }
        return view('planning_manager.interhub_moments_view', compact('rows', 'hubs'));
    }

    public function inwardOutward()
    {
        $rows = collect(); $hubs = collect();
        if (Schema::hasTable('hubs')) $hubs = DB::table('hubs')->get();
        if (Schema::hasTable('products') && Schema::hasTable('items') && Schema::hasTable('inventory')) {
            $q = DB::table('products')
                ->join('items', 'items.id', '=', 'products.item_id')
                ->join('category', 'category.id', '=', 'items.cat_id')
                ->leftJoin('inventory', function($join) {
                    $join->on('inventory.product_id', '=', 'products.product_id')
                         ->when(request('hub_id'), fn($j) => $j->where('inventory.hub_id', request('hub_id')));
                })
                ->select('products.*', 'items.item as product_name', 'category.name as cat_name', 'inventory.stock');
            if (request('hub_id')) $q->where('inventory.hub_id', request('hub_id'));
            $rows = $q->paginate(25);
        }
        return view('planning_manager.inward_outward', compact('rows', 'hubs'));
    }

    public function acceptInwardStocks()
    {
        $stocks = collect();
        if (Schema::hasTable('inward_stocks')) {
            $stocks = DB::table('inward_stocks')->where('status', 'pending')->orderByDesc('id')->paginate(25);
        }
        return view('planning_manager.accept_inward_stocks', compact('stocks'));
    }

    public function requestOutwardStocks(Request $request)
    {
        $products = Schema::hasTable('products') ? DB::table('products')->leftJoin('items','items.id','=','products.item_id')->select('products.*','items.item as name')->get() : collect();
        $hubs     = Schema::hasTable('hubs') ? DB::table('hubs')->get() : collect();
        $requests = collect();
        if (Schema::hasTable('outward_stock_requests')) {
            $requests = DB::table('outward_stock_requests')->orderByDesc('id')->paginate(25);
        }
        if ($request->isMethod('post')) {
            if (Schema::hasTable('outward_stock_requests')) {
                DB::table('outward_stock_requests')->insert([
                    'product_id' => $request->product_id,
                    'to_hub'     => $request->to_hub,
                    'quantity'   => $request->quantity,
                    'status'     => 'pending',
                ]);
            }
            return back()->with('success', 'Outward stock request submitted.');
        }
        return view('planning_manager.request_outward_stocks', compact('products', 'hubs', 'requests'));
    }

    // Danger Stock
    public function dangerStock()
    {
        $rows = collect(); $hubs = collect();
        if (Schema::hasTable('hubs')) $hubs = DB::table('hubs')->get();
        if (Schema::hasTable('danger_stocks')) {
            $rows = DB::table('danger_stocks')
                ->leftJoin('products', 'products.product_id', '=', 'danger_stocks.product_id')
                ->leftJoin('items', 'items.id', '=', 'products.item_id')
                ->leftJoin('hubs', 'hubs.id', '=', 'danger_stocks.hub_id')
                ->select('danger_stocks.*', 'items.item as product_name', 'hubs.name as hub_name')
                ->paginate(25);
        }
        return view('planning_manager.danger_stock', compact('rows', 'hubs'));
    }

    public function transferDangerStock(Request $request)
    {
        $products  = Schema::hasTable('products') ? DB::table('products')->leftJoin('items','items.id','=','products.item_id')->select('products.*','items.item as name')->get() : collect();
        $hubs      = Schema::hasTable('hubs') ? DB::table('hubs')->get() : collect();
        $transfers = collect();
        if (Schema::hasTable('danger_stock_transfers')) {
            $transfers = DB::table('danger_stock_transfers')->orderByDesc('id')->paginate(25);
        }
        if ($request->isMethod('post')) {
            if (Schema::hasTable('danger_stock_transfers')) {
                DB::table('danger_stock_transfers')->insert([
                    'product_id' => $request->product_id,
                    'from_hub'   => $request->from_hub,
                    'to_hub'     => $request->to_hub,
                    'quantity'   => $request->quantity,
                    'remarks'    => $request->remarks,
                ]);
            }
            return back()->with('success', 'Danger stock transferred.');
        }
        return view('planning_manager.transfer_danger_stock', compact('products', 'hubs', 'transfers'));
    }

    // Wastage
    public function skuWastage()
    {
        $rows = collect(); $hubs = collect();
        if (Schema::hasTable('hubs')) $hubs = DB::table('hubs')->get();
        if (Schema::hasTable('sku_wastage')) {
            $q = DB::table('sku_wastage')
                ->leftJoin('products', 'products.product_id', '=', 'sku_wastage.product_id')
                ->leftJoin('items', 'items.id', '=', 'products.item_id')
                ->leftJoin('hubs', 'hubs.id', '=', 'sku_wastage.hub_id')
                ->select('sku_wastage.*', 'items.item as product_name', 'hubs.name as hub_name');
            if (request('hub_id'))    $q->where('sku_wastage.hub_id', request('hub_id'));
            if (request('from_date')) $q->whereDate('sku_wastage.created_at', '>=', request('from_date'));
            if (request('to_date'))   $q->whereDate('sku_wastage.created_at', '<=', request('to_date'));
            $rows = $q->orderByDesc('sku_wastage.id')->paginate(25);
        }
        return view('planning_manager.sku_wastage', compact('rows', 'hubs'));
    }

    public function submitWastageReport(Request $request)
    {
        $hubs     = Schema::hasTable('hubs') ? DB::table('hubs')->get() : collect();
        $products = Schema::hasTable('products') ? DB::table('products')->leftJoin('items','items.id','=','products.item_id')->select('products.*','items.item as name')->get() : collect();
        if ($request->isMethod('post')) {
            if (Schema::hasTable('wastage_reports')) {
                DB::table('wastage_reports')->insert([
                    'hub_id'       => $request->hub_id,
                    'product_id'   => $request->product_id,
                    'quantity'     => $request->quantity,
                    'reason'       => $request->reason,
                    'wastage_date' => $request->wastage_date,
                ]);
            }
            return back()->with('success', 'Wastage report submitted.');
        }
        return view('planning_manager.submit_wastage_report', compact('hubs', 'products'));
    }

    public function wastageReports()
    {
        $rows = collect();
        if (Schema::hasTable('wastage_reports')) {
            $rows = DB::table('wastage_reports')
                ->leftJoin('hubs', 'hubs.id', '=', 'wastage_reports.hub_id')
                ->leftJoin('products', 'products.product_id', '=', 'wastage_reports.product_id')
                ->leftJoin('items', 'items.id', '=', 'products.item_id')
                ->select('wastage_reports.*', 'hubs.name as hub_name', 'items.item as product_name')
                ->orderByDesc('wastage_reports.id')->paginate(25);
        }
        return view('planning_manager.wastage_reports', compact('rows'));
    }

    public function viewWastageReport()
    {
        $rows = collect();
        if (Schema::hasTable('wastage_reports')) {
            $q = DB::table('wastage_reports')
                ->leftJoin('hubs', 'hubs.id', '=', 'wastage_reports.hub_id')
                ->leftJoin('products', 'products.product_id', '=', 'wastage_reports.product_id')
                ->leftJoin('items', 'items.id', '=', 'products.item_id')
                ->select('wastage_reports.*', 'hubs.name as hub_name', 'items.item as product_name');
            if (request('from_date')) $q->whereDate('wastage_reports.created_at', '>=', request('from_date'));
            if (request('to_date'))   $q->whereDate('wastage_reports.created_at', '<=', request('to_date'));
            $rows = $q->orderByDesc('wastage_reports.id')->paginate(25);
        }
        return view('planning_manager.view_wastage_report', compact('rows'));
    }

    public function viewWastageReportHubWise()
    {
        $hubs = Schema::hasTable('hubs') ? DB::table('hubs')->get() : collect();
        $hubs_data = [];
        if (Schema::hasTable('wastage_reports')) {
            foreach ($hubs as $hub) {
                $q = DB::table('wastage_reports')
                    ->leftJoin('products', 'products.product_id', '=', 'wastage_reports.product_id')
                    ->leftJoin('items', 'items.id', '=', 'products.item_id')
                    ->select('wastage_reports.*', 'items.item as product_name')
                    ->where('wastage_reports.hub_id', $hub->id);
                if (request('from_date')) $q->whereDate('wastage_reports.created_at', '>=', request('from_date'));
                if (request('to_date'))   $q->whereDate('wastage_reports.created_at', '<=', request('to_date'));
                $items = $q->get();
                if ($items->count()) {
                    $hub->hub_name     = $hub->name;
                    $hub->items        = $items;
                    $hub->total_wastage = $items->sum('quantity');
                    $hubs_data[] = $hub;
                }
            }
        }
        return view('planning_manager.view_wastage_report_hub_wise', compact('hubs', 'hubs_data'));
    }

    // Production
    public function products()
    {
        $rows = collect(); $hubs = collect();
        if (Schema::hasTable('hubs')) $hubs = DB::table('hubs')->get();
        if (Schema::hasTable('products')) {
            $rows = DB::table('products')
                ->leftJoin('items', 'items.id', '=', 'products.item_id')
                ->leftJoin('category', 'category.id', '=', 'items.cat_id')
                ->select('products.*', 'items.item as product_name', 'items.image', 'category.name as cat_name')
                ->orderByDesc('products.id')->paginate(25);
        }
        return view('planning_manager.products', compact('rows', 'hubs'));
    }

    public function production()
    {
        $rows = collect(); $hubs = collect();
        if (Schema::hasTable('hubs')) $hubs = DB::table('hubs')->get();
        if (Schema::hasTable('production')) {
            $q = DB::table('production')
                ->leftJoin('products', 'products.product_id', '=', 'production.product_id')
                ->leftJoin('items', 'items.id', '=', 'products.item_id')
                ->leftJoin('hubs', 'hubs.id', '=', 'production.hub_id')
                ->select('production.*', 'items.item as product_name', 'hubs.name as hub_name');
            if (request('hub_id'))    $q->where('production.hub_id', request('hub_id'));
            if (request('from_date')) $q->whereDate('production.created_at', '>=', request('from_date'));
            if (request('to_date'))   $q->whereDate('production.created_at', '<=', request('to_date'));
            $rows = $q->orderByDesc('production.id')->paginate(25);
        }
        return view('planning_manager.production', compact('rows', 'hubs'));
    }

    public function skuReport()
    {
        $rows = collect();
        if (Schema::hasTable('products')) {
            $rows = DB::table('products')
                ->leftJoin('items', 'items.id', '=', 'products.item_id')
                ->leftJoin('category', 'category.id', '=', 'items.cat_id')
                ->select('products.*', 'items.item as product_name', 'category.name as cat_name')
                ->paginate(25);
        }
        return view('planning_manager.sku_report', compact('rows'));
    }

    // Upload Inventory (planning_manager unique)
    public function uploadInventory(Request $request)
    {
        $hubs = Schema::hasTable('hubs') ? DB::table('hubs')->get() : collect();
        $hub_id = request('hub_id', 0);
        $rows = collect();
        if (Schema::hasTable('products')) {
            $q = DB::table('products')
                ->join('items', 'items.id', '=', 'products.item_id')
                ->join('category', 'category.id', '=', 'items.cat_id')
                ->leftJoin('inventory', function($join) use ($hub_id) {
                    $join->on('inventory.product_id', '=', 'products.product_id');
                    if ($hub_id) $join->where('inventory.hub_id', $hub_id);
                })
                ->select('products.*', 'items.item as title', 'items.image', 'category.name as cat_name', 'inventory.stock as inventory_stock')
                ->orderByDesc('products.id');
            $rows = $q->paginate(25);
        }
        return view('planning_manager.upload_inventory', compact('hubs', 'hub_id', 'rows'));
    }

    // Certificate (planning_manager unique)
    public function certificate()
    {
        $certificates = collect();
        if (Schema::hasTable('certificates')) {
            $certificates = DB::table('certificates')->orderByDesc('id')->get();
        }
        return view('planning_manager.certificate', compact('certificates'));
    }

    // Setting / Change Password (planning_manager unique)
    public function setting()
    {
        return view('planning_manager.setting');
    }

    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'new_password'     => 'required|min:6|same:confirm_password',
            'confirm_password' => 'required',
        ]);

        $manager = $this->guard()->user();
        $stored  = $manager->password;

        $currentOk = preg_match('/^\$2[aby]\$/', $stored)
            ? \Illuminate\Support\Facades\Hash::check($request->current_password, $stored)
            : hash_equals($stored, $request->current_password);

        if (!$currentOk) {
            return back()->with('error', 'Current password is incorrect.');
        }

        $manager->password = Hash::make($request->new_password);
        $manager->save();

        return back()->with('success', 'Password changed successfully.');
    }

    // Delivery & Hub
    public function deliveryBoy()
    {
        $rows = collect();
        if (Schema::hasTable('delivery_boys')) {
            $rows = DB::table('delivery_boys')
                ->leftJoin('hubs', 'hubs.id', '=', 'delivery_boys.hub_id')
                ->select('delivery_boys.*', 'hubs.name as hub_name')
                ->orderByDesc('delivery_boys.id')->paginate(25);
        }
        return view('planning_manager.delivery_boy', compact('rows'));
    }

    public function hubKmlList()
    {
        $hubs = collect();
        if (Schema::hasTable('hubs')) {
            $hubs = DB::table('hubs')
                ->leftJoin('cities', 'cities.id', '=', 'hubs.city_id')
                ->select('hubs.*', 'cities.name as city_name')
                ->orderByDesc('hubs.id')->paginate(25);
        }
        return view('planning_manager.hub_kml_list', compact('hubs'));
    }

    // Marketing
    public function banner()
    {
        $rows = collect();
        if (Schema::hasTable('banners')) $rows = DB::table('banners')->orderByDesc('id')->paginate(25);
        return view('planning_manager.banner', compact('rows'));
    }

    public function homeOffers()
    {
        $rows = collect();
        if (Schema::hasTable('home_offers')) $rows = DB::table('home_offers')->orderByDesc('id')->paginate(25);
        return view('planning_manager.home_offers', compact('rows'));
    }

    public function promotions()
    {
        $rows = collect();
        if (Schema::hasTable('promotions')) $rows = DB::table('promotions')->orderByDesc('id')->paginate(25);
        return view('planning_manager.promotions', compact('rows'));
    }

    public function newsLetters()
    {
        $rows = collect();
        if (Schema::hasTable('news_letters')) $rows = DB::table('news_letters')->orderByDesc('id')->paginate(25);
        return view('planning_manager.news_letters', compact('rows'));
    }

    public function ratingReviews()
    {
        $rows = collect();
        if (Schema::hasTable('rating_reviews')) {
            $rows = DB::table('rating_reviews')
                ->leftJoin('buyers', 'buyers.id', '=', 'rating_reviews.buyer_id')
                ->select('rating_reviews.*', 'buyers.name as customer_name')
                ->orderByDesc('rating_reviews.id')->paginate(25);
        }
        return view('planning_manager.rating_reviews', compact('rows'));
    }

    // Grievance
    public function grievance()
    {
        $rows = collect();
        if (Schema::hasTable('grievances')) {
            $rows = DB::table('grievances')
                ->leftJoin('buyers', 'buyers.id', '=', 'grievances.buyer_id')
                ->select('grievances.*', 'buyers.name as customer_name')
                ->orderByDesc('grievances.id')->paginate(25);
        }
        return view('planning_manager.grievance', compact('rows'));
    }

    public function grievanceCategories()
    {
        $rows = collect();
        if (Schema::hasTable('grievance_categories')) {
            $rows = DB::table('grievance_categories')->orderByDesc('id')->paginate(25);
        }
        return view('planning_manager.grievance_categories', compact('rows'));
    }

    // Finance / Wallet
    public function walletHistory()
    {
        $rows = collect();
        if (Schema::hasTable('wallet_history')) {
            $rows = DB::table('wallet_history')
                ->leftJoin('buyers', 'buyers.id', '=', 'wallet_history.buyer_id')
                ->select('wallet_history.*', 'buyers.name as customer_name', 'buyers.phone')
                ->orderByDesc('wallet_history.id')->paginate(25);
        }
        return view('planning_manager.wallet_history', compact('rows'));
    }

    public function walletPaymentHistory()
    {
        $rows = collect();
        if (Schema::hasTable('wallet_transactions')) {
            $q = DB::table('wallet_transactions')
                ->leftJoin('buyers', 'buyers.id', '=', 'wallet_transactions.buyer_id')
                ->select('wallet_transactions.*', 'buyers.name as customer_name', 'buyers.phone');
            if (request('search')) $q->where(function($w){ $w->where('buyers.name','like','%'.request('search').'%')->orWhere('buyers.phone','like','%'.request('search').'%'); });
            if (request('from_date')) $q->whereDate('wallet_transactions.created_at', '>=', request('from_date'));
            if (request('to_date'))   $q->whereDate('wallet_transactions.created_at', '<=', request('to_date'));
            $rows = $q->orderByDesc('wallet_transactions.id')->paginate(25);
        }
        return view('planning_manager.wallet_payment_history', compact('rows'));
    }

    public function addWalletMoney()
    {
        $customers = collect();
        if (Schema::hasTable('buyers')) $customers = DB::table('buyers')->orderBy('name')->get();
        return view('planning_manager.add_wallet_money', compact('customers'));
    }

    public function addWalletMoneySubmit(Request $request)
    {
        if (Schema::hasTable('wallet_history')) {
            DB::table('wallet_history')->insert([
                'buyer_id' => $request->buyer_id,
                'amount'   => $request->amount,
                'type'     => 'credit',
                'description' => $request->description ?? 'Added by planning manager',
            ]);
        }
        return back()->with('success', 'Wallet money added.');
    }

    public function withdrawMoney()
    {
        $customers = collect();
        if (Schema::hasTable('buyers')) $customers = DB::table('buyers')->orderBy('name')->get();
        return view('planning_manager.withdraw_money', compact('customers'));
    }

    public function onlinePaymentHistory()
    {
        $rows = collect();
        if (Schema::hasTable('payment_history')) {
            $q = DB::table('payment_history')
                ->leftJoin('buyers', 'buyers.id', '=', 'payment_history.buyer_id')
                ->select('payment_history.*', 'buyers.name as customer_name', 'buyers.phone');
            if (request('search')) $q->where(function($w){ $w->where('buyers.name','like','%'.request('search').'%')->orWhere('buyers.phone','like','%'.request('search').'%'); });
            $rows = $q->orderByDesc('payment_history.id')->paginate(25);
        }
        return view('planning_manager.online_payment_history', compact('rows'));
    }

    public function cashDeposit()
    {
        $deposits = collect(); $hubs = collect();
        if (Schema::hasTable('hubs')) $hubs = DB::table('hubs')->get();
        if (Schema::hasTable('cash_deposits')) {
            $deposits = DB::table('cash_deposits')
                ->leftJoin('hubs', 'hubs.id', '=', 'cash_deposits.hub_id')
                ->select('cash_deposits.*', 'hubs.name as hub_name')
                ->orderByDesc('cash_deposits.id')->paginate(25);
        }
        return view('planning_manager.cash_deposit', compact('deposits', 'hubs'));
    }

    public function depositReceipt(Request $request)
    {
        $hubs = Schema::hasTable('hubs') ? DB::table('hubs')->get() : collect();
        if ($request->isMethod('post')) {
            $path = null;
            if ($request->hasFile('receipt_image')) {
                $path = $request->file('receipt_image')->store('receipts', 'public');
            }
            if (Schema::hasTable('cash_deposits')) {
                DB::table('cash_deposits')->insert([
                    'hub_id'         => $request->hub_id,
                    'amount'         => $request->amount,
                    'bank_name'      => $request->bank_name,
                    'transaction_no' => $request->transaction_no,
                    'deposit_date'   => $request->deposit_date,
                    'receipt_image'  => $path ? basename($path) : null,
                    'remarks'        => $request->remarks,
                    'status'         => 'pending',
                ]);
            }
            return back()->with('success', 'Deposit receipt submitted.');
        }
        return view('planning_manager.deposit_receipt', compact('hubs'));
    }

    public function viewCashDepositsHub()
    {
        $deposits = collect(); $hubs = collect();
        if (Schema::hasTable('hubs')) $hubs = DB::table('hubs')->get();
        if (Schema::hasTable('cash_deposits')) {
            $q = DB::table('cash_deposits')
                ->leftJoin('hubs', 'hubs.id', '=', 'cash_deposits.hub_id')
                ->select('cash_deposits.*', 'hubs.name as hub_name');
            if (request('hub_id'))    $q->where('cash_deposits.hub_id', request('hub_id'));
            if (request('from_date')) $q->whereDate('cash_deposits.deposit_date', '>=', request('from_date'));
            if (request('to_date'))   $q->whereDate('cash_deposits.deposit_date', '<=', request('to_date'));
            $deposits = $q->orderByDesc('cash_deposits.id')->paginate(25);
        }
        return view('planning_manager.view_cash_deposits_hub', compact('deposits', 'hubs'));
    }

    public function viewCashDepositsAllHub()
    {
        $hubs = Schema::hasTable('hubs') ? DB::table('hubs')->get() : collect();
        $hubs_data = [];
        if (Schema::hasTable('cash_deposits')) {
            foreach ($hubs as $hub) {
                $q = DB::table('cash_deposits')->where('hub_id', $hub->id);
                if (request('from_date')) $q->whereDate('deposit_date', '>=', request('from_date'));
                if (request('to_date'))   $q->whereDate('deposit_date', '<=', request('to_date'));
                $deposits = $q->get();
                if ($deposits->count()) {
                    $hub->deposits     = $deposits;
                    $hub->total_amount = $deposits->sum('amount');
                    $hubs_data[] = $hub;
                }
            }
        }
        return view('planning_manager.view_cash_deposits_all_hub', compact('hubs', 'hubs_data'));
    }

    // Reports
    public function salesReport()
    {
        $rows = collect();
        if (Schema::hasTable('orders')) {
            $q = DB::table('orders')
                ->leftJoin('hubs', 'hubs.id', '=', 'orders.hub_id')
                ->select('orders.*', 'hubs.name as hub_name');
            if (request('from_date')) $q->whereDate('orders.created_at', '>=', request('from_date'));
            if (request('to_date'))   $q->whereDate('orders.created_at', '<=', request('to_date'));
            if (request('hub_id'))    $q->where('orders.hub_id', request('hub_id'));
            $rows = $q->orderByDesc('orders.id')->paginate(25);
        }
        $hubs = Schema::hasTable('hubs') ? DB::table('hubs')->get() : collect();
        return view('planning_manager.sales_report', compact('rows', 'hubs'));
    }

    // Time Slots
    public function expressOrderTimeSlot(Request $request)
    {
        $slots = Schema::hasTable('express_time_slots') ? DB::table('express_time_slots')->get() : collect();
        if ($request->isMethod('post')) {
            if (Schema::hasTable('express_time_slots')) {
                DB::table('express_time_slots')->insert([
                    'start_time' => $request->start_time,
                    'end_time'   => $request->end_time,
                    'max_orders' => $request->max_orders,
                    'status'     => 1,
                ]);
            }
            return back()->with('success', 'Time slot added.');
        }
        return view('planning_manager.express_order_time_slot', compact('slots'));
    }

    public function scheduledOrderTimeSlot(Request $request)
    {
        $slots = Schema::hasTable('scheduled_time_slots') ? DB::table('scheduled_time_slots')->get() : collect();
        if ($request->isMethod('post')) {
            if (Schema::hasTable('scheduled_time_slots')) {
                DB::table('scheduled_time_slots')->insert([
                    'day'        => $request->day,
                    'start_time' => $request->start_time,
                    'end_time'   => $request->end_time,
                    'max_orders' => $request->max_orders,
                    'status'     => 1,
                ]);
            }
            return back()->with('success', 'Time slot added.');
        }
        return view('planning_manager.scheduled_order_time_slot', compact('slots'));
    }

    // Profile
    public function profile()
    {
        $manager = $this->guard()->user();
        return view('planning_manager.profile', compact('manager'));
    }

    public function editProfile()
    {
        $manager = $this->guard()->user();
        return view('planning_manager.edit_profile', compact('manager'));
    }

    public function updateProfile(Request $request)
    {
        $manager = $this->guard()->user();
        $data = $request->only(['name', 'email', 'phone']);
        if (Schema::hasTable('planning_managers')) {
            DB::table('planning_managers')->where('id', $manager->id)->update($data);
        }
        return redirect()->route('planning_manager.profile')->with('success', 'Profile updated.');
    }

    // AJAX
    public function inwardStockAction(Request $request)
    {
        if (Schema::hasTable('inward_stocks')) {
            DB::table('inward_stocks')->where('id', $request->id)->update(['status' => $request->action === 'accept' ? 'accepted' : 'rejected']);
        }
        return response()->json(['result' => true, 'message' => 'Stock ' . $request->action . 'ed successfully.']);
    }
}
