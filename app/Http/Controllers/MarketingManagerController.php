<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class MarketingManagerController extends Controller
{
    private function user()
    {
        return Auth::guard('marketing_managers')->user();
    }

    // ─── Dashboard ───────────────────────────────────────────────────────────────

    public function dashboard()
    {
        $total_buyers   = DB::table('buyers')->count();
        $total_orders   = Schema::hasTable('orders')   ? DB::table('orders')->distinct('order_id')->count('order_id') : 0;
        $today_orders   = Schema::hasTable('orders')
            ? DB::table('orders')->whereDate('created_at', today())->distinct('order_id')->count('order_id') : 0;
        $total_products = DB::table('products')->count();

        return view('marketing_manager.dashboard', compact(
            'total_buyers', 'total_orders', 'today_orders', 'total_products'
        ));
    }

    // ─── Customers ───────────────────────────────────────────────────────────────

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
        return view('marketing_manager.all_customers', compact('customers', 'search'));
    }

    public function customerOrder(Request $request)
    {
        $buyer_id = $request->input('buyer_id');
        $buyer    = null;
        $orders   = collect();

        if ($buyer_id && Schema::hasTable('orders')) {
            $buyer  = DB::table('buyers')->where('id', $buyer_id)->first();
            $orders = DB::table('orders')
                ->where('buyer_id', $buyer_id)
                ->select('order_id',
                    DB::raw('MAX(id) as id'),
                    DB::raw('SUM(total_price) as total_price'),
                    DB::raw('MAX(order_status) as order_status'),
                    DB::raw('MAX(created_at) as created_at')
                )
                ->groupBy('order_id')
                ->orderByDesc(DB::raw('MAX(id)'))
                ->paginate(25)->withQueryString();
        }
        return view('marketing_manager.customer_order', compact('buyer', 'orders', 'buyer_id'));
    }

    // ─── Orders ──────────────────────────────────────────────────────────────────

    public function allOrders(Request $request)
    {
        if (! Schema::hasTable('orders')) {
            return view('marketing_manager.all_orders', ['orders' => collect(), 'search' => '', 'status' => '']);
        }
        $search = $request->input('search');
        $status = $request->input('status');

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
            ->when($status, fn ($q) => $q->where('order_status', $status))
            ->orderByDesc(DB::raw('MAX(id)'))
            ->paginate(25)->withQueryString();
        return view('marketing_manager.all_orders', compact('orders', 'search', 'status'));
    }

    public function viewOrder(Request $request)
    {
        $order_id = $request->input('order_id');
        $items    = collect();
        $buyer    = null;

        if ($order_id && Schema::hasTable('orders')) {
            $items = DB::table('orders')->where('order_id', $order_id)->get();
            if ($items->isNotEmpty()) {
                $buyer = DB::table('buyers')->where('id', $items->first()->buyer_id ?? 0)->first();
            }
        }
        return view('marketing_manager.view_order', compact('items', 'buyer', 'order_id'));
    }

    public function scheduledOrders(Request $request)
    {
        $orders = collect();
        if (Schema::hasTable('orders')) {
            $orders = DB::table('orders')
                ->select('order_id', DB::raw('MAX(id) as id'), DB::raw('MAX(buyer_name) as buyer_name'),
                    DB::raw('MAX(buyer_phone) as buyer_phone'), DB::raw('SUM(total_price) as total_price'),
                    DB::raw('MAX(order_status) as order_status'), DB::raw('MAX(created_at) as created_at'))
                ->groupBy('order_id')
                ->where('order_status', 'Order Placed')
                ->orderByDesc(DB::raw('MAX(id)'))
                ->paginate(25)->withQueryString();
        }
        return view('marketing_manager.scheduled_orders', compact('orders'));
    }

    // ─── Products ────────────────────────────────────────────────────────────────

    public function products(Request $request)
    {
        $search   = $request->input('search');
        $products = DB::table('products')
            ->leftJoin('category as cat', 'products.cat_id', '=', 'cat.id')
            ->select('products.*', 'cat.name as cat_name')
            ->when($search, fn ($q) => $q->where('products.product_name', 'like', "%{$search}%"))
            ->orderByDesc('products.id')
            ->paginate(25)->withQueryString();
        return view('marketing_manager.products', compact('products', 'search'));
    }

    // ─── Categories & Items ───────────────────────────────────────────────────────

    public function category(Request $request)
    {
        $rows = DB::table('category')->orderByDesc('id')->paginate(25)->withQueryString();
        return view('marketing_manager.category', compact('rows'));
    }

    public function categoryStore(Request $request)
    {
        $request->validate(['name' => 'required|string|max:255']);
        if ($request->filled('id')) {
            DB::table('category')->where('id', $request->id)->update([
                'name'       => $request->name,
                'group_type' => $request->input('group_type'),
            ]);
            return redirect()->route('marketing_manager.category')->with('success', 'Category updated.');
        }
        DB::table('category')->insert(['name' => $request->name, 'group_type' => $request->input('group_type')]);
        return redirect()->route('marketing_manager.category')->with('success', 'Category added.');
    }

    public function items(Request $request)
    {
        $search = $request->input('search');
        $rows   = DB::table('items')
            ->leftJoin('category', 'items.cat_id', '=', 'category.id')
            ->select('items.*', DB::raw('IFNULL(category.name, "—") as cat_name'))
            ->when($search, fn ($q) => $q->where('items.item', 'like', "%{$search}%"))
            ->orderByDesc('items.id')
            ->paginate(25)->withQueryString();
        return view('marketing_manager.items', compact('rows', 'search'));
    }

    // ─── Hubs ────────────────────────────────────────────────────────────────────

    public function hubs(Request $request)
    {
        $rows = DB::table('hubs')
            ->leftJoin('cities', 'hubs.city_id', '=', 'cities.id')
            ->select('hubs.*', DB::raw('IFNULL(cities.city, "—") as city_name'))
            ->orderByDesc('hubs.id')
            ->paginate(25)->withQueryString();
        return view('marketing_manager.hubs', compact('rows'));
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
        return view('marketing_manager.delivery_boy', compact('rows'));
    }

    // ─── Grievance ───────────────────────────────────────────────────────────────

    public function grievance(Request $request)
    {
        $search = $request->input('search');
        $rows   = collect();
        if (Schema::hasTable('grievance')) {
            $query = DB::table('grievance')
                ->leftJoin('buyers', 'grievance.buyer_id', '=', 'buyers.id')
                ->leftJoin('grievance_category', 'grievance.category_id', '=', 'grievance_category.id')
                ->select('grievance.*',
                    DB::raw('IFNULL(buyers.name, "—") as buyer_name'),
                    DB::raw('IFNULL(grievance_category.category, "—") as category_name')
                );
            if ($search) {
                $query->where(fn ($q) => $q->where('buyers.name', 'like', "%{$search}%")
                    ->orWhere('grievance.order_id', 'like', "%{$search}%"));
            }
            $rows = $query->orderByDesc('grievance.id')->paginate(25)->withQueryString();
        }
        return view('marketing_manager.grievance', compact('rows', 'search'));
    }

    public function grievanceCategories(Request $request)
    {
        $rows = collect();
        if (Schema::hasTable('grievance_category')) {
            $rows = DB::table('grievance_category')->orderByDesc('id')->paginate(25)->withQueryString();
        }
        return view('marketing_manager.grievance_categories', compact('rows'));
    }

    public function grievanceCategoryStore(Request $request)
    {
        $request->validate(['category' => 'required|string|max:255']);
        if (! Schema::hasTable('grievance_category')) {
            return redirect()->back()->with('error', 'Table not found.');
        }
        if ($request->filled('id')) {
            DB::table('grievance_category')->where('id', $request->id)->update(['category' => $request->category]);
            return redirect()->route('marketing_manager.grievance_categories')->with('success', 'Category updated.');
        }
        DB::table('grievance_category')->insert(['category' => $request->category]);
        return redirect()->route('marketing_manager.grievance_categories')->with('success', 'Category added.');
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
        return view('marketing_manager.hub_inventory', compact('rows'));
    }

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
        return view('marketing_manager.danger_stock', compact('rows'));
    }

    public function lockedInventory(Request $request)
    {
        $rows = collect();
        if (Schema::hasTable('inventory') && Schema::hasColumn('inventory', 'is_locked')) {
            $rows = DB::table('inventory')
                ->leftJoin('products', 'inventory.product_id', '=', 'products.id')
                ->leftJoin('hubs', 'inventory.hub_id', '=', 'hubs.id')
                ->select('inventory.*',
                    DB::raw('IFNULL(products.product_name, "—") as product_name'),
                    DB::raw('IFNULL(hubs.hub, "—") as hub_name')
                )
                ->where('inventory.is_locked', 1)
                ->orderByDesc('inventory.id')
                ->paginate(25)->withQueryString();
        }
        return view('marketing_manager.locked_inventory', compact('rows'));
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
        return view('marketing_manager.wastage_reports', compact('rows'));
    }

    public function skuReport(Request $request)
    {
         $rows = DB::table('products as t1')
        ->join('items as t2', 't2.id', '=', 't1.item_id')
        ->join('category as t3', 't3.id', '=', 't2.cat_id')
        ->leftJoin('wastage_stock as t4', 't4.product_id', '=', 't1.product_id')
        ->join('hubs as t5', 't4.hub_id', '=', 't5.id')
        ->select(
            't1.*',
            't4.waste_stock',
            't4.id as wastage_id',
            't4.date_added',
            't2.item as title',
            't5.hub',
            't2.image',
            't3.name as cat_name'
        );

    if ($request->filled('date')) {
        $startDate = date('Y-m-d', strtotime($request->date));
        $rows->whereDate('t4.date_added', $startDate);
    }

    $rows = $rows->orderByDesc('t4.id')
                 ->paginate(25)
                 ->withQueryString();

    return view('marketing_manager.sku_report', compact('rows'));
    }

    // ─── Banners & Promotions ────────────────────────────────────────────────────

    public function banner(Request $request)
    {
        $table = Schema::hasTable('banner_images') ? 'banner_images'
               : (Schema::hasTable('banner') ? 'banner' : null);
        $rows  = $table ? DB::table($table)->orderByDesc('id')->paginate(25)->withQueryString() : collect();
        return view('marketing_manager.banner', compact('rows'));
    }

    public function homeOffers(Request $request)
    {
        $table = Schema::hasTable('home_offers') ? 'home_offers'
               : (Schema::hasTable('offers') ? 'offers' : null);
        $rows  = $table ? DB::table($table)->orderByDesc('id')->paginate(25)->withQueryString() : collect();
        return view('marketing_manager.home_offers', compact('rows'));
    }

    public function promotions(Request $request)
    {
        $table = Schema::hasTable('promotions') ? 'promotions'
               : (Schema::hasTable('promo_codes') ? 'promo_codes' : null);
        $rows  = $table ? DB::table($table)->orderByDesc('id')->paginate(25)->withQueryString() : collect();
        return view('marketing_manager.promotions', compact('rows'));
    }

    // ─── Wallet & Finance ────────────────────────────────────────────────────────

    public function walletHistory(Request $request)
    {
        $table = Schema::hasTable('wallet_transaction_history') ? 'wallet_transaction_history'
               : (Schema::hasTable('wallet_history') ? 'wallet_history' : null);
        $rows  = $table ? DB::table($table)->orderByDesc('id')->paginate(25)->withQueryString() : collect();
        return view('marketing_manager.wallet_history', compact('rows'));
    }

    public function addWalletMoney(Request $request)
    {
        $buyer_id = $request->input('buyer_id');
        $buyer    = $buyer_id ? DB::table('buyers')->where('id', $buyer_id)->first() : null;
        return view('marketing_manager.add_wallet_money', compact('buyer', 'buyer_id'));
    }

    public function addWalletMoneySubmit(Request $request)
    {
        $request->validate(['buyer_id' => 'required|integer', 'amount' => 'required|numeric|min:1']);
        $table = Schema::hasTable('wallet_transaction_history') ? 'wallet_transaction_history'
               : (Schema::hasTable('wallet_history') ? 'wallet_history' : null);
        if ($table) {
            DB::table($table)->insert([
                'buyer_id' => $request->buyer_id, 'amount' => $request->amount,
                'transaction_type' => 'credit', 'note' => $request->input('note', 'Added by Marketing Manager'),
                'created_at' => now(),
            ]);
        }
        if (Schema::hasTable('buyers') && Schema::hasColumn('buyers', 'wallet_amount')) {
            DB::table('buyers')->where('id', $request->buyer_id)->increment('wallet_amount', $request->amount);
        }
        return redirect()->route('marketing_manager.wallet_history')->with('success', 'Wallet amount added.');
    }

    public function withdrawMoney(Request $request)
    {
        $rows = collect();
        if (Schema::hasTable('withdraw_amount')) {
            $rows = DB::table('withdraw_amount')
                ->leftJoin('buyers', 'withdraw_amount.buyer_id', '=', 'buyers.id')
                ->select('withdraw_amount.*', DB::raw('IFNULL(buyers.name, "—") as buyer_name'))
                ->orderByDesc('withdraw_amount.id')
                ->paginate(25)->withQueryString();
        }
        return view('marketing_manager.withdraw_money', compact('rows'));
    }

    public function onlinePaymentHistory(Request $request)
    {
        $rows = collect();
        if (Schema::hasTable('orders')) {
            $rows = DB::table('orders')
                ->whereNotNull('payment_type')->where('payment_type', '!=', 'cod')
                ->select('order_id', DB::raw('MAX(id) as id'), DB::raw('MAX(buyer_name) as buyer_name'),
                    DB::raw('MAX(payment_type) as payment_type'), DB::raw('SUM(total_price) as total_price'),
                    DB::raw('MAX(created_at) as created_at'))
                ->groupBy('order_id')
                ->orderByDesc(DB::raw('MAX(id)'))
                ->paginate(25)->withQueryString();
        }
        return view('marketing_manager.online_payment_history', compact('rows'));
    }

    public function cashDeposit(Request $request)
    {
        $rows = collect();
        if (Schema::hasTable('cash_deposit')) {
            $rows = DB::table('cash_deposit')->orderByDesc('id')->paginate(25)->withQueryString();
        }
        return view('marketing_manager.cash_deposit', compact('rows'));
    }

    // ─── Reports ─────────────────────────────────────────────────────────────────

    public function salesReport(Request $request)
    {
        $from = $request->input('from'); $to = $request->input('to');
        $rows = collect(); $totals = null;
        if (Schema::hasTable('orders')) {
            $rows = DB::table('orders')
                ->select(DB::raw('DATE(created_at) as sale_date'),
                    DB::raw('COUNT(DISTINCT order_id) as total_orders'),
                    DB::raw('SUM(total_price) as total_revenue'))
                ->groupBy(DB::raw('DATE(created_at)'))
                ->when($from, fn ($q) => $q->whereDate('created_at', '>=', $from))
                ->when($to,   fn ($q) => $q->whereDate('created_at', '<=', $to))
                ->orderByDesc('sale_date')
                ->paginate(25)->withQueryString();
            $totals = DB::table('orders')
                ->when($from, fn ($q) => $q->whereDate('created_at', '>=', $from))
                ->when($to,   fn ($q) => $q->whereDate('created_at', '<=', $to))
                ->selectRaw('COUNT(DISTINCT order_id) as total_orders, SUM(total_price) as total_revenue')
                ->first();
        }
        return view('marketing_manager.sales_report', compact('rows', 'totals', 'from', 'to'));
    }

    public function ratingReviews(Request $request)
    {
        $rows = collect();
        if (Schema::hasTable('reviews')) {
            $rows = DB::table('reviews')
                ->leftJoin('buyers', 'reviews.buyer_id', '=', 'buyers.id')
                ->leftJoin('products', 'reviews.product_id', '=', 'products.id')
                ->select('reviews.*',
                    DB::raw('IFNULL(buyers.name, "—") as buyer_name'),
                    DB::raw('IFNULL(products.product_name, "—") as product_name')
                )
                ->orderByDesc('reviews.id')
                ->paginate(25)->withQueryString();
        }
        return view('marketing_manager.rating_reviews', compact('rows'));
    }

    public function newsLetters(Request $request)
    {
        $rows = DB::table('news_letter')->orderByDesc('id')->paginate(25)->withQueryString();
        return view('marketing_manager.news_letters', compact('rows'));
    }

    // ─── Profile ─────────────────────────────────────────────────────────────────

    public function profile()
    {
        $user = $this->user();
        return view('marketing_manager.profile', compact('user'));
    }

    public function editProfile()
    {
        $user = $this->user();
        return view('marketing_manager.edit_profile', compact('user'));
    }

    public function updateProfile(Request $request)
    {
        $request->validate(['name' => 'required|string|max:255']);
        $data = ['name' => $request->name, 'address' => $request->input('address')];
        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }
        DB::table('marketing_managers')->where('id', $this->user()->id)->update($data);
        return redirect()->route('marketing_manager.profile')->with('success', 'Profile updated.');
    }
}
