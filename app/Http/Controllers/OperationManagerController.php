<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class OperationManagerController extends Controller
{
    private function guard() { return Auth::guard('operation_managers'); }
    private function user()  { return $this->guard()->user(); }

    // ── Dashboard ────────────────────────────────────────────────────────────

    public function dashboard()
    {
        $total_buyers   = Schema::hasTable('buyers')      ? DB::table('buyers')->count()      : 0;
        $total_orders   = Schema::hasTable('orders')      ? DB::table('orders')->count()      : 0;
        $today_orders   = Schema::hasTable('orders')      ? DB::table('orders')->whereDate('created_at', today())->count() : 0;
        $total_products = Schema::hasTable('products')    ? DB::table('products')->count()    : 0;
        $news_count     = Schema::hasTable('news_letters')? DB::table('news_letters')->count(): 0;

        return view('operation_manager.dashboard', compact(
            'total_buyers','total_orders','today_orders','total_products','news_count'
        ));
    }

    // ── Orders ───────────────────────────────────────────────────────────────

    public function allOrders(Request $request)
    {
        $rows = collect();
        if (Schema::hasTable('orders')) {
            $q = DB::table('orders')->orderByDesc('id');
            if ($s = $request->search) $q->where('id', 'like', "%$s%");
            $rows = $q->paginate(25)->withQueryString();
        }
        return view('operation_manager.all_orders', compact('rows'));
    }

    public function viewOrder(Request $request)
    {
        $order = null;
        $items = collect();
        if (Schema::hasTable('orders') && $request->id) {
            $order = DB::table('orders')->where('id', $request->id)->first();
            if (Schema::hasTable('order_products')) {
                $items = DB::table('order_products')->where('order_id', $request->id)->get();
            }
        }
        return view('operation_manager.view_order', compact('order','items'));
    }

    public function viewOperationOrder(Request $request)
    {
        $order = null;
        $items = collect();
        if (Schema::hasTable('orders') && $request->id) {
            $order = DB::table('orders')->where('id', $request->id)->first();
            if (Schema::hasTable('order_products')) {
                $items = DB::table('order_products')->where('order_id', $request->id)->get();
            }
        }
        return view('operation_manager.view_operation_order', compact('order','items'));
    }

    public function scheduledOrders(Request $request)
    {
        $rows = collect();
        if (Schema::hasTable('orders')) {
            $rows = DB::table('orders')
                ->where('order_type', 'scheduled')
                ->orderByDesc('id')
                ->paginate(25)->withQueryString();
        }
        return view('operation_manager.scheduled_orders', compact('rows'));
    }

    public function pendingOrders(Request $request)
    {
        $rows = collect();
        if (Schema::hasTable('orders')) {
            $rows = DB::table('orders')
                ->where('status', 'pending')
                ->orderByDesc('id')
                ->paginate(25)->withQueryString();
        }
        return view('operation_manager.pending_orders', compact('rows'));
    }

    public function orderStatus(Request $request)
    {
        $rows = collect();
        if (Schema::hasTable('orders')) {
            $q = DB::table('orders')->orderByDesc('id');
            if ($s = $request->status) $q->where('status', $s);
            $rows = $q->paginate(25)->withQueryString();
        }
        $status = $request->status ?? '';
        return view('operation_manager.order_status', compact('rows','status'));
    }

    // ── Customers ────────────────────────────────────────────────────────────

    public function allCustomers(Request $request)
    {
        $rows = collect();
        if (Schema::hasTable('buyers')) {
            $q = DB::table('buyers')->orderByDesc('id');
            if ($s = $request->search) $q->where('name', 'like', "%$s%")->orWhere('phone', 'like', "%$s%");
            $rows = $q->paginate(25)->withQueryString();
        }
        return view('operation_manager.all_customers', compact('rows'));
    }

    public function customerOrder(Request $request)
    {
        $rows = collect();
        $buyer = null;
        if (Schema::hasTable('orders') && $request->buyer_id) {
            $buyer = Schema::hasTable('buyers') ? DB::table('buyers')->find($request->buyer_id) : null;
            $rows  = DB::table('orders')->where('buyer_id', $request->buyer_id)->orderByDesc('id')->paginate(25)->withQueryString();
        }
        return view('operation_manager.customer_order', compact('rows','buyer'));
    }

    // ── Inventory ────────────────────────────────────────────────────────────

    public function hubInventory(Request $request)
    {
        $rows = collect();
        if (Schema::hasTable('inventory')) {
            $q = DB::table('inventory')
                ->leftJoin('products', 'products.product_id', '=', 'inventory.product_id')
                ->leftJoin('hubs', 'hubs.id', '=', 'inventory.hub_id')
                ->select('inventory.*', 'products.title', 'hubs.hub as hub_name')
                ->orderByDesc('inventory.id');
            if ($h = $request->hub_id) $q->where('inventory.hub_id', $h);
            $rows = $q->paginate(25)->withQueryString();
        }
        $hubs = Schema::hasTable('hubs') ? DB::table('hubs')->get() : collect();
        return view('operation_manager.hub_inventory', compact('rows','hubs'));
    }

    public function pendingInward(Request $request)
    {
        $rows = collect();
        if (Schema::hasTable('inward_stocks')) {
            $rows = DB::table('inward_stocks')
                ->where('status', 'pending')
                ->orderByDesc('id')
                ->paginate(25)->withQueryString();
        }
        return view('operation_manager.pending_inward', compact('rows'));
    }

    public function lockedInventory(Request $request)
    {
        $rows = collect();
        if (Schema::hasTable('locked_inventory')) {
            $rows = DB::table('locked_inventory')->orderByDesc('id')->paginate(25)->withQueryString();
        }
        return view('operation_manager.locked_inventory', compact('rows'));
    }

    public function interhubOrders(Request $request)
    {
        $rows = collect();
        if (Schema::hasTable('interhub_orders')) {
            $rows = DB::table('interhub_orders')->orderByDesc('id')->paginate(25)->withQueryString();
        }
        return view('operation_manager.interhub_orders', compact('rows'));
    }

    public function interhubMomentsView(Request $request)
    {
        $rows = collect();
        if (Schema::hasTable('interhub_orders')) {
            $rows = DB::table('interhub_orders')->orderByDesc('id')->paginate(25)->withQueryString();
        }
        return view('operation_manager.interhub_moments_view', compact('rows'));
    }

    public function inwardOutward(Request $request)
    {
        $hub_id  = $request->hub_id ?? 0;
        $rows    = collect();
        if (Schema::hasTable('products')) {
            $q = DB::table('products as p')
                ->leftJoin('items as i', 'i.id', '=', 'p.item_id')
                ->leftJoin('category as c', 'c.id', '=', 'i.cat_id')
                ->leftJoin('inventory as inv', function ($j) use ($hub_id) {
                    $j->on('inv.product_id', '=', 'p.product_id')->where('inv.hub_id', $hub_id);
                })
                ->select('p.*','i.item as title','i.image','c.name as cat_name',
                    'inv.stock as inventory_stock','inv.live_stock','inv.fresh_stock','inv.variance')
                ->orderByDesc('p.id');
            if ($s = $request->search) $q->where('i.item', 'like', "%$s%");
            $rows = $q->paginate(25)->withQueryString();
        }
        $hubs = Schema::hasTable('hubs') ? DB::table('hubs')->get() : collect();
        return view('operation_manager.inward_outward', compact('rows','hubs','hub_id'));
    }

    public function acceptInwardStocks(Request $request)
    {
        $rows = collect();
        if (Schema::hasTable('inward_stocks')) {
            $rows = DB::table('inward_stocks')->orderByDesc('id')->paginate(25)->withQueryString();
        }
        return view('operation_manager.accept_inward_stocks', compact('rows'));
    }

    public function requestOutwardStocks(Request $request)
    {
        $rows = collect();
        if (Schema::hasTable('outward_stocks')) {
            $rows = DB::table('outward_stocks')->orderByDesc('id')->paginate(25)->withQueryString();
        }
        return view('operation_manager.request_outward_stocks', compact('rows'));
    }

    // ── Danger Stock ─────────────────────────────────────────────────────────

    public function dangerStock(Request $request)
    {
        $rows = collect();
        if (Schema::hasTable('danger_stock')) {
            $rows = DB::table('danger_stock')
                ->leftJoin('products', 'products.product_id', '=', 'danger_stock.product_id')
                ->leftJoin('hubs', 'hubs.id', '=', 'danger_stock.hub_id')
                ->select('danger_stock.*', 'products.title', 'hubs.hub as hub_name')
                ->orderByDesc('danger_stock.id')
                ->paginate(25)->withQueryString();
        }
        return view('operation_manager.danger_stock', compact('rows'));
    }

    public function transferDangerStock(Request $request)
    {
        $rows = collect();
        if (Schema::hasTable('danger_stock')) {
            $rows = DB::table('danger_stock')
                ->leftJoin('hubs', 'hubs.id', '=', 'danger_stock.hub_id')
                ->select('danger_stock.*', 'hubs.hub as hub_name')
                ->orderByDesc('danger_stock.id')
                ->paginate(25)->withQueryString();
        }
        $hubs = Schema::hasTable('hubs') ? DB::table('hubs')->get() : collect();
        return view('operation_manager.transfer_danger_stock', compact('rows','hubs'));
    }

    // ── Wastage ──────────────────────────────────────────────────────────────

    public function skuWastage(Request $request)
    {
        $rows = collect();
        if (Schema::hasTable('wastage_report')) {
            $rows = DB::table('wastage_report')
                ->leftJoin('products', 'products.product_id', '=', 'wastage_report.product_id')
                ->select('wastage_report.*', 'products.title')
                ->orderByDesc('wastage_report.id')
                ->paginate(25)->withQueryString();
        }
        return view('operation_manager.sku_wastage', compact('rows'));
    }

    public function submitWastageReport(Request $request)
    {
        $rows = collect();
        if (Schema::hasTable('wastage_report')) {
            $rows = DB::table('wastage_report')->orderByDesc('id')->paginate(25)->withQueryString();
        }
        $hubs     = Schema::hasTable('hubs')     ? DB::table('hubs')->get()     : collect();
        $products = Schema::hasTable('products')  ? DB::table('products')->get() : collect();
        return view('operation_manager.submit_wastage_report', compact('rows','hubs','products'));
    }

    public function wastageReports(Request $request)
    {
        $rows = collect();
        if (Schema::hasTable('wastage_report')) {
            $rows = DB::table('wastage_report')->orderByDesc('id')->paginate(25)->withQueryString();
        }
        return view('operation_manager.wastage_reports', compact('rows'));
    }

    public function viewWastageReport(Request $request)
    {
        $rows = collect();
        if (Schema::hasTable('wastage_report')) {
            $q = DB::table('wastage_report')
                ->leftJoin('hubs', 'hubs.id', '=', 'wastage_report.hub_id')
                ->select('wastage_report.*', 'hubs.hub as hub_name')
                ->orderByDesc('wastage_report.id');
            if ($h = $request->hub_id) $q->where('wastage_report.hub_id', $h);
            $rows = $q->paginate(25)->withQueryString();
        }
        $hubs = Schema::hasTable('hubs') ? DB::table('hubs')->get() : collect();
        return view('operation_manager.view_wastage_report', compact('rows','hubs'));
    }

    public function viewWastageReportHubWise(Request $request)
    {
        $rows = collect();
        if (Schema::hasTable('wastage_report')) {
            $q = DB::table('wastage_report')
                ->leftJoin('hubs', 'hubs.id', '=', 'wastage_report.hub_id')
                ->leftJoin('products', 'products.product_id', '=', 'wastage_report.product_id')
                ->select('wastage_report.*', 'hubs.hub as hub_name', 'products.title')
                ->orderByDesc('wastage_report.id');
            if ($h = $request->hub_id) $q->where('wastage_report.hub_id', $h);
            $rows = $q->paginate(25)->withQueryString();
        }
        $hubs = Schema::hasTable('hubs') ? DB::table('hubs')->get() : collect();
        return view('operation_manager.view_wastage_report_hub_wise', compact('rows','hubs'));
    }

    // ── Production ───────────────────────────────────────────────────────────

    public function products(Request $request)
    {
        $rows = collect();
        $search = $request->search ?? '';
        if (Schema::hasTable('products')) {
            $q = DB::table('products')
                ->leftJoin('items', 'items.id', '=', 'products.item_id')
                ->leftJoin('category', 'category.id', '=', 'items.cat_id')
                ->select('products.*', 'items.item', 'category.name as cat_name')
                ->orderByDesc('products.id');
            if ($search) $q->where('items.item', 'like', "%$search%");
            $rows = $q->paginate(25)->withQueryString();
        }
        return view('operation_manager.products', compact('rows','search'));
    }

    public function production(Request $request)
    {
        $rows = collect();
        if (Schema::hasTable('production')) {
            $rows = DB::table('production')->orderByDesc('id')->paginate(25)->withQueryString();
        }
        return view('operation_manager.production', compact('rows'));
    }

    public function skuReport(Request $request)
    {
        $rows = collect();
        if (Schema::hasTable('products')) {
            $rows = DB::table('products')
                ->leftJoin('items', 'items.id', '=', 'products.item_id')
                ->select('products.*', 'items.item')
                ->orderByDesc('products.id')
                ->paginate(25)->withQueryString();
        }
        return view('operation_manager.sku_report', compact('rows'));
    }

    // ── Delivery / Hub ───────────────────────────────────────────────────────

    public function deliveryBoy(Request $request)
    {
        $rows = collect();
        if (Schema::hasTable('delivery_boys')) {
            $rows = DB::table('delivery_boys')->orderByDesc('id')->paginate(25)->withQueryString();
        }
        return view('operation_manager.delivery_boy', compact('rows'));
    }

    public function hubKmlList(Request $request)
    {
        $rows = collect();
        if (Schema::hasTable('hubs')) {
            $rows = DB::table('hubs')
                ->leftJoin('cities', 'cities.id', '=', 'hubs.city_id')
                ->select('hubs.*', 'cities.name as city_name')
                ->orderByDesc('hubs.id')
                ->paginate(25)->withQueryString();
        }
        return view('operation_manager.hub_kml_list', compact('rows'));
    }

    // ── Marketing ────────────────────────────────────────────────────────────

    public function banner(Request $request)
    {
        $rows = collect();
        if (Schema::hasTable('banners')) {
            $rows = DB::table('banners')->orderByDesc('id')->paginate(25)->withQueryString();
        }
        return view('operation_manager.banner', compact('rows'));
    }

    public function homeOffers(Request $request)
    {
        $rows = collect();
        if (Schema::hasTable('home_offers')) {
            $rows = DB::table('home_offers')->orderByDesc('id')->paginate(25)->withQueryString();
        }
        return view('operation_manager.home_offers', compact('rows'));
    }

    public function promotions(Request $request)
    {
        $rows = collect();
        if (Schema::hasTable('promotions')) {
            $rows = DB::table('promotions')->orderByDesc('id')->paginate(25)->withQueryString();
        }
        return view('operation_manager.promotions', compact('rows'));
    }

    public function newsLetters(Request $request)
    {
        $rows = collect();
        if (Schema::hasTable('news_letters')) {
            $rows = DB::table('news_letters')->orderByDesc('id')->paginate(25)->withQueryString();
        }
        return view('operation_manager.news_letters', compact('rows'));
    }

    public function ratingReviews(Request $request)
    {
        $rows = collect();
        if (Schema::hasTable('rating_reviews')) {
            $rows = DB::table('rating_reviews')->orderByDesc('id')->paginate(25)->withQueryString();
        }
        return view('operation_manager.rating_reviews', compact('rows'));
    }

    // ── Grievance ────────────────────────────────────────────────────────────

    public function grievance(Request $request)
    {
        $rows = collect();
        if (Schema::hasTable('grievances')) {
            $rows = DB::table('grievances')->orderByDesc('id')->paginate(25)->withQueryString();
        }
        return view('operation_manager.grievance', compact('rows'));
    }

    public function grievanceCategories(Request $request)
    {
        $rows = collect();
        if (Schema::hasTable('grievance_categories')) {
            $rows = DB::table('grievance_categories')->orderByDesc('id')->paginate(25)->withQueryString();
        }
        return view('operation_manager.grievance_categories', compact('rows'));
    }

    // ── Wallet / Finance ─────────────────────────────────────────────────────

    public function walletHistory(Request $request)
    {
        $rows = collect();
        if (Schema::hasTable('wallet_transaction_history')) {
            $rows = DB::table('wallet_transaction_history')->orderByDesc('id')->paginate(25)->withQueryString();
        }
        return view('operation_manager.wallet_history', compact('rows'));
    }

    public function walletPaymentHistory(Request $request)
    {
        $rows = collect();
        if (Schema::hasTable('wallet_transaction_history')) {
            $rows = DB::table('wallet_transaction_history')
                ->where('type', 'payment')
                ->orderByDesc('id')
                ->paginate(25)->withQueryString();
        }
        return view('operation_manager.wallet_payment_history', compact('rows'));
    }

    public function addWalletMoney(Request $request)
    {
        $buyers = Schema::hasTable('buyers') ? DB::table('buyers')->get() : collect();
        return view('operation_manager.add_wallet_money', compact('buyers'));
    }

    public function addWalletMoneySubmit(Request $request)
    {
        $request->validate(['buyer_id' => 'required', 'amount' => 'required|numeric|min:1']);
        if (Schema::hasTable('wallet_transaction_history') && Schema::hasTable('buyers')) {
            DB::table('wallet_transaction_history')->insert([
                'buyer_id'   => $request->buyer_id,
                'amount'     => $request->amount,
                'type'       => 'credit',
                'note'       => $request->note ?? 'Added by Operation Manager',
                'created_at' => now(),
            ]);
            DB::table('buyers')->where('id', $request->buyer_id)
                ->increment('wallet_amount', $request->amount);
        }
        return back()->with('success', 'Wallet money added successfully.');
    }

    public function withdrawMoney(Request $request)
    {
        $rows = collect();
        if (Schema::hasTable('withdraw_requests')) {
            $rows = DB::table('withdraw_requests')->orderByDesc('id')->paginate(25)->withQueryString();
        }
        return view('operation_manager.withdraw_money', compact('rows'));
    }

    public function onlinePaymentHistory(Request $request)
    {
        $rows = collect();
        if (Schema::hasTable('payment_history')) {
            $rows = DB::table('payment_history')->orderByDesc('id')->paginate(25)->withQueryString();
        }
        return view('operation_manager.online_payment_history', compact('rows'));
    }

    public function cashDeposit(Request $request)
    {
        $rows = collect();
        if (Schema::hasTable('cash_deposits')) {
            $rows = DB::table('cash_deposits')->orderByDesc('id')->paginate(25)->withQueryString();
        }
        return view('operation_manager.cash_deposit', compact('rows'));
    }

    public function depositReceipt(Request $request)
    {
        $rows = collect();
        if (Schema::hasTable('cash_deposits')) {
            $rows = DB::table('cash_deposits')->orderByDesc('id')->paginate(25)->withQueryString();
        }
        $hubs = Schema::hasTable('hubs') ? DB::table('hubs')->get() : collect();
        return view('operation_manager.deposit_receipt', compact('rows','hubs'));
    }

    public function viewCashDepositsHub(Request $request)
    {
        $rows = collect();
        if (Schema::hasTable('cash_deposits')) {
            $q = DB::table('cash_deposits')
                ->leftJoin('hubs', 'hubs.id', '=', 'cash_deposits.hub_id')
                ->select('cash_deposits.*', 'hubs.hub as hub_name')
                ->orderByDesc('cash_deposits.id');
            if ($h = $request->hub_id) $q->where('cash_deposits.hub_id', $h);
            $rows = $q->paginate(25)->withQueryString();
        }
        $hubs = Schema::hasTable('hubs') ? DB::table('hubs')->get() : collect();
        return view('operation_manager.view_cash_deposits_hub', compact('rows','hubs'));
    }

    public function viewCashDepositsAllHub(Request $request)
    {
        $rows = collect();
        if (Schema::hasTable('cash_deposits')) {
            $rows = DB::table('cash_deposits')
                ->leftJoin('hubs', 'hubs.id', '=', 'cash_deposits.hub_id')
                ->select('cash_deposits.*', 'hubs.hub as hub_name')
                ->orderByDesc('cash_deposits.id')
                ->paginate(25)->withQueryString();
        }
        return view('operation_manager.view_cash_deposits_all_hub', compact('rows'));
    }

    // ── Reports ──────────────────────────────────────────────────────────────

    public function salesReport(Request $request)
    {
        $rows = collect();
        if (Schema::hasTable('orders')) {
            $q = DB::table('orders')->orderByDesc('id');
            if ($f = $request->date_from) $q->whereDate('created_at', '>=', $f);
            if ($t = $request->date_to)   $q->whereDate('created_at', '<=', $t);
            $rows = $q->paginate(25)->withQueryString();
        }
        return view('operation_manager.sales_report', compact('rows'));
    }

    // ── Time Slots ───────────────────────────────────────────────────────────

    public function expressOrderTimeSlot(Request $request)
    {
        $rows = collect();
        if (Schema::hasTable('express_time_slots')) {
            $rows = DB::table('express_time_slots')->orderByDesc('id')->paginate(25)->withQueryString();
        }
        return view('operation_manager.express_order_time_slot', compact('rows'));
    }

    public function scheduledOrderTimeSlot(Request $request)
    {
        $rows = collect();
        if (Schema::hasTable('scheduled_time_slots')) {
            $rows = DB::table('scheduled_time_slots')->orderByDesc('id')->paginate(25)->withQueryString();
        }
        return view('operation_manager.scheduled_order_time_slot', compact('rows'));
    }

    // ── Profile ──────────────────────────────────────────────────────────────

    public function profile()
    {
        $manager = $this->user();
        return view('operation_manager.profile', compact('manager'));
    }

    public function editProfile()
    {
        $manager = $this->user();
        return view('operation_manager.edit_profile', compact('manager'));
    }

    public function updateProfile(Request $request)
    {
        $request->validate(['name' => 'required', 'email' => 'required|email']);
        $data = ['name' => $request->name, 'email' => $request->email];
        if ($request->filled('password')) {
            $data['password'] = \Illuminate\Support\Facades\Hash::make($request->password);
        }
        DB::table('operation_managers')->where('id', $this->user()->id)->update($data);
        return back()->with('success', 'Profile updated successfully.');
    }
}
