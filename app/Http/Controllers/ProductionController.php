<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class ProductionController extends Controller
{
    private function guard() { return Auth::guard('productions'); }
    private function user()  { return $this->guard()->user(); }

    // ── Dashboard ────────────────────────────────────────────────────────────

    public function dashboard()
    {
        $total_buyers   = Schema::hasTable('buyers')      ? DB::table('buyers')->count()      : 0;
        $total_orders   = Schema::hasTable('orders')      ? DB::table('orders')->count()      : 0;
        $today_orders   = Schema::hasTable('orders')      ? DB::table('orders')->whereDate('date_added', today())->count() : 0;
        $total_products = Schema::hasTable('products')    ? DB::table('products')->count()    : 0;
        $news_count     = Schema::hasTable('news_letters')? DB::table('news_letters')->count(): 0;

        return view('production.dashboard', compact(
            'total_buyers','total_orders','today_orders','total_products','news_count'
        ));
    }

    // ── Orders ───────────────────────────────────────────────────────────────

    public function allOrders(Request $request)
    {
        $rows = collect();
        if (Schema::hasTable('orders')) {
            $q = DB::table('orders')->orderByDesc('id');
            if ($s = $request->search) $q->where('order_id', 'like', "%$s%");
            $rows = $q->paginate(25)->withQueryString();
        }
        return view('production.all_orders', compact('rows'));
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
        return view('production.view_order', compact('order','items'));
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
        return view('production.view_operation_order', compact('order','items'));
    }

    public function scheduledOrders(Request $request)
    {
        $rows = collect();
        if (Schema::hasTable('orders')) {
            $rows = DB::table('orders')
                ->where('order_type', 'Scheduled')
                ->orderByDesc('id')
                ->paginate(25)->withQueryString();
        }
        return view('production.scheduled_orders', compact('rows'));
    }

    public function pendingOrders(Request $request)
    {
        $rows = collect();
        if (Schema::hasTable('orders')) {
            $rows = DB::table('orders')
                ->where('order_status', 'Order Pending')
                ->orderByDesc('id')
                ->paginate(25)->withQueryString();
        }
        return view('production.pending_orders', compact('rows'));
    }

    public function orderStatus(Request $request)
    {
        $rows = collect();
        if (Schema::hasTable('orders')) {
            $q = DB::table('orders')->orderByDesc('id');
            if ($s = $request->status) $q->where('order_status', $s);
            $rows = $q->paginate(25)->withQueryString();
        }
        $status = $request->status ?? '';
        return view('production.order_status', compact('rows','status'));
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
        return view('production.all_customers', compact('rows'));
    }

    public function customerOrder(Request $request)
    {
        $rows  = collect();
        $buyer = null;
        if (Schema::hasTable('orders') && $request->buyer_id) {
            $buyer = Schema::hasTable('buyers') ? DB::table('buyers')->find($request->buyer_id) : null;
            $rows  = DB::table('orders')->where('buyer_id', $request->buyer_id)->orderByDesc('id')->paginate(25)->withQueryString();
        }
        return view('production.customer_order', compact('rows','buyer'));
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
        return view('production.hub_inventory', compact('rows','hubs'));
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
        return view('production.pending_inward', compact('rows'));
    }

    public function lockedInventory(Request $request)
    {
        $rows = collect();
        if (Schema::hasTable('locked_inventory')) {
            $rows = DB::table('locked_inventory')->orderByDesc('id')->paginate(25)->withQueryString();
        }
        return view('production.locked_inventory', compact('rows'));
    }

    public function interhubOrders(Request $request)
    {
        $rows = collect();
        if (Schema::hasTable('interhub_orders')) {
            $rows = DB::table('interhub_orders')->orderByDesc('id')->paginate(25)->withQueryString();
        }
        return view('production.interhub_orders', compact('rows'));
    }

    public function interhubMomentsView(Request $request)
    {
        $rows = collect();
        if (Schema::hasTable('interhub_orders')) {
            $rows = DB::table('interhub_orders')->orderByDesc('id')->paginate(25)->withQueryString();
        }
        return view('production.interhub_moments_view', compact('rows'));
    }

    public function inwardOutward(Request $request)
    {
        $hub_id = $request->hub_id ?? 0;
        $rows   = collect();
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
        return view('production.inward_outward', compact('rows','hubs','hub_id'));
    }

    public function acceptInwardStocks(Request $request)
    {
        $rows = collect();
        if (Schema::hasTable('inward_stocks')) {
            $rows = DB::table('inward_stocks')->orderByDesc('id')->paginate(25)->withQueryString();
        }
        return view('production.accept_inward_stocks', compact('rows'));
    }

    public function inwardStockAction(Request $request)
    {
        $request->validate(['id' => 'required', 'action' => 'required|in:accept,reject']);
        if (Schema::hasTable('inward_stocks')) {
            DB::table('inward_stocks')->where('id', $request->id)
                ->update(['status' => $request->action === 'accept' ? 'accepted' : 'rejected']);
        }
        return response()->json(['status' => 'success', 'message' => 'Stock status updated.']);
    }

    public function requestOutwardStocks(Request $request)
    {
        $rows = collect();
        if (Schema::hasTable('outward_stocks')) {
            $rows = DB::table('outward_stocks')->orderByDesc('id')->paginate(25)->withQueryString();
        }
        return view('production.request_outward_stocks', compact('rows'));
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
        return view('production.danger_stock', compact('rows'));
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
        return view('production.transfer_danger_stock', compact('rows','hubs'));
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
        return view('production.sku_wastage', compact('rows'));
    }

    public function submitWastageReport(Request $request)
    {
        $rows     = collect();
        $hubs     = Schema::hasTable('hubs')    ? DB::table('hubs')->get()    : collect();
        $products = Schema::hasTable('products') ? DB::table('products')->get() : collect();
        if (Schema::hasTable('wastage_report')) {
            $rows = DB::table('wastage_report')->orderByDesc('id')->paginate(25)->withQueryString();
        }
        return view('production.submit_wastage_report', compact('rows','hubs','products'));
    }

    public function wastageReports(Request $request)
    {
        $rows = collect();
        if (Schema::hasTable('wastage_report')) {
            $rows = DB::table('wastage_report')->orderByDesc('id')->paginate(25)->withQueryString();
        }
        return view('production.wastage_reports', compact('rows'));
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
        return view('production.view_wastage_report', compact('rows','hubs'));
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
        return view('production.view_wastage_report_hub_wise', compact('rows','hubs'));
    }

    // ── Production Management ─────────────────────────────────────────────────

    public function products(Request $request)
    {
        $search = $request->search ?? '';
        $rows   = collect();
        if (Schema::hasTable('products')) {
            $q = DB::table('products')
                ->leftJoin('items', 'items.id', '=', 'products.item_id')
                ->leftJoin('category', 'category.id', '=', 'items.cat_id')
                ->select('products.*', 'items.item', 'category.name as cat_name')
                ->orderByDesc('products.id');
            if ($search) $q->where('items.item', 'like', "%$search%");
            $rows = $q->paginate(25)->withQueryString();
        }
        return view('production.products', compact('rows','search'));
    }

    public function productionRecords(Request $request)
    {
        $rows = collect();
        if (Schema::hasTable('production_records')) {
            $rows = DB::table('production_records')->orderByDesc('id')->paginate(25)->withQueryString();
        }
        return view('production.production', compact('rows'));
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
        return view('production.sku_report', compact('rows'));
    }

    // ── Delivery / Hub ───────────────────────────────────────────────────────

    public function deliveryBoy(Request $request)
    {
        $rows = collect();
        if (Schema::hasTable('delivery_boy')) {
            $rows = DB::table('delivery_boy')
                ->leftJoin('hubs', 'hubs.id', '=', 'delivery_boy.hub_id')
                ->select('delivery_boy.*', 'hubs.hub as hub_name')
                ->orderByDesc('delivery_boy.id')
                ->paginate(25)->withQueryString();
        }
        return view('production.delivery_boy', compact('rows'));
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
        return view('production.hub_kml_list', compact('rows'));
    }

    // ── Marketing ────────────────────────────────────────────────────────────

    public function banner(Request $request)
    {
        $rows = collect();
        if (Schema::hasTable('banners')) {
            $rows = DB::table('banners')->orderByDesc('id')->paginate(25)->withQueryString();
        }
        return view('production.banner', compact('rows'));
    }

    public function homeOffers(Request $request)
    {
        $rows = collect();
        if (Schema::hasTable('home_offers')) {
            $rows = DB::table('home_offers')->orderByDesc('id')->paginate(25)->withQueryString();
        }
        return view('production.home_offers', compact('rows'));
    }

    public function promotions(Request $request)
    {
        $rows = collect();
        if (Schema::hasTable('promotions')) {
            $rows = DB::table('promotions')->orderByDesc('id')->paginate(25)->withQueryString();
        }
        return view('production.promotions', compact('rows'));
    }

    public function newsLetters(Request $request)
    {
        $rows = collect();
        if (Schema::hasTable('news_letters')) {
            $rows = DB::table('news_letters')->orderByDesc('id')->paginate(25)->withQueryString();
        }
        return view('production.news_letters', compact('rows'));
    }

    public function certificate(Request $request)
    {
        $rows = collect();
        $edit = null;
        if (Schema::hasTable('certificates')) {
            if ($request->isMethod('post')) {
                if ($request->cert_id) {
                    DB::table('certificates')->where('id', $request->cert_id)
                        ->update(['title' => $request->title, 'description' => $request->description ?? '']);
                } else {
                    DB::table('certificates')->insert([
                        'title'       => $request->title,
                        'description' => $request->description ?? '',
                    ]);
                }
                return back()->with('success', 'Certificate saved.');
            }
            if ($request->edit_id) {
                $edit = DB::table('certificates')->find($request->edit_id);
            }
            $rows = DB::table('certificates')->orderByDesc('id')->get();
        }
        return view('production.certificate', compact('rows','edit'));
    }

    public function ratingReviews(Request $request)
    {
        $rows = collect();
        if (Schema::hasTable('rating_reviews')) {
            $rows = DB::table('rating_reviews')->orderByDesc('id')->paginate(25)->withQueryString();
        }
        return view('production.rating_reviews', compact('rows'));
    }

    // ── Grievance ────────────────────────────────────────────────────────────

    public function grievance(Request $request)
    {
        $rows = collect();
        if (Schema::hasTable('grievances')) {
            $rows = DB::table('grievances')->orderByDesc('id')->paginate(25)->withQueryString();
        }
        return view('production.grievance', compact('rows'));
    }

    public function grievanceCategories(Request $request)
    {
        $rows = collect();
        if (Schema::hasTable('grievance_categories')) {
            $rows = DB::table('grievance_categories')->orderByDesc('id')->paginate(25)->withQueryString();
        }
        return view('production.grievance_categories', compact('rows'));
    }

    // ── Wallet / Finance ─────────────────────────────────────────────────────

    public function walletHistory(Request $request)
    {
        $rows = collect();
        if (Schema::hasTable('wallet_transaction_history')) {
            $rows = DB::table('wallet_transaction_history')->orderByDesc('id')->paginate(25)->withQueryString();
        }
        return view('production.wallet_history', compact('rows'));
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
        return view('production.wallet_payment_history', compact('rows'));
    }

    public function addWalletMoney(Request $request)
    {
        $buyers = Schema::hasTable('buyers') ? DB::table('buyers')->get() : collect();
        return view('production.add_wallet_money', compact('buyers'));
    }

    public function addWalletMoneySubmit(Request $request)
    {
        $request->validate(['buyer_id' => 'required', 'amount' => 'required|numeric|min:1']);
        if (Schema::hasTable('wallet_transaction_history') && Schema::hasTable('buyers')) {
            DB::table('wallet_transaction_history')->insert([
                'buyer_id'   => $request->buyer_id,
                'amount'     => $request->amount,
                'type'       => 'credit',
                'note'       => $request->note ?? 'Added by Production Manager',
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
        return view('production.withdraw_money', compact('rows'));
    }

    public function onlinePaymentHistory(Request $request)
    {
        $rows = collect();
        if (Schema::hasTable('payment_history')) {
            $rows = DB::table('payment_history')->orderByDesc('id')->paginate(25)->withQueryString();
        }
        return view('production.online_payment_history', compact('rows'));
    }

    public function cashDeposit(Request $request)
    {
        $rows = collect();
        if (Schema::hasTable('cash_deposits')) {
            $rows = DB::table('cash_deposits')->orderByDesc('id')->paginate(25)->withQueryString();
        }
        return view('production.cash_deposit', compact('rows'));
    }

    public function depositReceipt(Request $request)
    {
        $rows = collect();
        if (Schema::hasTable('cash_deposits')) {
            $rows = DB::table('cash_deposits')->orderByDesc('id')->paginate(25)->withQueryString();
        }
        $hubs = Schema::hasTable('hubs') ? DB::table('hubs')->get() : collect();
        return view('production.deposit_receipt', compact('rows','hubs'));
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
        return view('production.view_cash_deposits_hub', compact('rows','hubs'));
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
        return view('production.view_cash_deposits_all_hub', compact('rows'));
    }

    // ── Reports ──────────────────────────────────────────────────────────────

    public function salesReport(Request $request)
    {
        $rows  = collect();
        $total = 0;
        if (Schema::hasTable('orders')) {
            $q = DB::table('orders')
                ->leftJoin('buyers', 'buyers.id', '=', 'orders.buyer_id')
                ->leftJoin('hubs', 'hubs.id', '=', 'orders.hub_id')
                ->leftJoin('cities', 'cities.id', '=', 'hubs.city_id')
                ->leftJoin('buyer_address', 'buyer_address.id', '=', 'orders.address_id')
                ->select('orders.*', 'buyers.name', 'hubs.hub', 'cities.name as city')
                ->orderByDesc('orders.id');
            if ($f = $request->date_from) $q->whereDate('orders.date_added', '>=', $f);
            if ($t = $request->date_to)   $q->whereDate('orders.date_added', '<=', $t);
            if ($s = $request->search)    $q->where('buyers.name', 'like', "%$s%");
            $rows  = $q->paginate(25)->withQueryString();
            $total = $q->sum('orders.total_amount');
        }
        return view('production.sales_report', compact('rows','total'));
    }

    // ── Time Slots ───────────────────────────────────────────────────────────

    public function expressOrderTimeSlot(Request $request)
    {
        $rows = collect();
        if (Schema::hasTable('express_time_slots')) {
            $rows = DB::table('express_time_slots')->orderByDesc('id')->paginate(25)->withQueryString();
        }
        return view('production.express_order_time_slot', compact('rows'));
    }

    public function scheduledOrderTimeSlot(Request $request)
    {
        $rows = collect();
        if (Schema::hasTable('scheduled_time_slots')) {
            $rows = DB::table('scheduled_time_slots')->orderByDesc('id')->paginate(25)->withQueryString();
        }
        return view('production.scheduled_order_time_slot', compact('rows'));
    }

    // ── Profile / Settings ───────────────────────────────────────────────────

    public function profile()
    {
        $manager = $this->user();
        return view('production.profile', compact('manager'));
    }

    public function editProfile()
    {
        $manager = $this->user();
        return view('production.edit_profile', compact('manager'));
    }

    public function updateProfile(Request $request)
    {
        $request->validate(['name' => 'required', 'email' => 'required|email']);
        $data = ['name' => $request->name, 'email' => $request->email];
        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }
        DB::table('production')->where('id', $this->user()->id)->update($data);
        return back()->with('success', 'Profile updated successfully.');
    }

    public function setting()
    {
        $manager = $this->user();
        return view('production.setting', compact('manager'));
    }

    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'new_password'     => 'required|min:6',
            'confirm_password' => 'required|same:new_password',
        ]);

        $user   = $this->user();
        $stored = $user->password;

        if (preg_match('/^\$2[aby]\$/', $stored)) {
            $valid = Hash::check($request->current_password, $stored);
        } else {
            $valid = hash_equals($stored, $request->current_password);
        }

        if (!$valid) {
            return back()->with('error', 'Current password is incorrect.');
        }

        DB::table('production')->where('id', $user->id)
            ->update(['password' => Hash::make($request->new_password)]);

        return back()->with('success', 'Password changed successfully.');
    }
}
