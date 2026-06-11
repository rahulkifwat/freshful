<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class HrManagerController extends Controller
{
    private function user()
    {
        return Auth::guard('hr_managers')->user();
    }

    // ─── Dashboard ───────────────────────────────────────────────────────────────

    public function dashboard()
    {
        $total_buyers     = DB::table('buyers')->count();
        $total_orders     = Schema::hasTable('orders')    ? DB::table('orders')->distinct('order_id')->count('order_id') : 0;
        $today_orders     = Schema::hasTable('orders')
            ? DB::table('orders')->whereDate('created_at', today())->distinct('order_id')->count('order_id') : 0;
        $total_products   = DB::table('products')->count();

        return view('hr_manager.dashboard', compact(
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

        return view('hr_manager.all_customers', compact('customers', 'search'));
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

        return view('hr_manager.customer_order', compact('buyer', 'orders', 'buyer_id'));
    }

    // ─── Orders ──────────────────────────────────────────────────────────────────

    public function allOrders(Request $request)
    {
        if (! Schema::hasTable('orders')) {
            return view('hr_manager.all_orders', ['orders' => collect(), 'search' => '', 'status' => '']);
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

        return view('hr_manager.all_orders', compact('orders', 'search', 'status'));
    }

    public function viewOrder(Request $request)
    {
        $order_id = $request->input('order_id');
        $items    = collect();
        $buyer    = null;

        if ($order_id && Schema::hasTable('orders')) {
            $items = DB::table('orders')->where('order_id', $order_id)->get();
            if ($items->isNotEmpty() && Schema::hasTable('buyers')) {
                $buyer = DB::table('buyers')->where('id', $items->first()->buyer_id ?? 0)->first();
            }
        }
        return view('hr_manager.view_order', compact('items', 'buyer', 'order_id'));
    }

    public function scheduledOrders(Request $request)
    {
        if (! Schema::hasTable('orders')) {
            return view('hr_manager.scheduled_orders', ['orders' => collect()]);
        }
        $orders = DB::table('orders')
            ->select('order_id', DB::raw('MAX(id) as id'), DB::raw('MAX(buyer_name) as buyer_name'),
                DB::raw('MAX(buyer_phone) as buyer_phone'), DB::raw('SUM(total_price) as total_price'),
                DB::raw('MAX(order_status) as order_status'), DB::raw('MAX(created_at) as created_at'))
            ->groupBy('order_id')
            ->where('order_status', 'Order Placed')
            ->orderByDesc(DB::raw('MAX(id)'))
            ->paginate(25)->withQueryString();
        return view('hr_manager.scheduled_orders', compact('orders'));
    }

    public function interhubOrders(Request $request)
    {
        $rows = collect();
        if (Schema::hasTable('interhub_orders')) {
            $rows = DB::table('interhub_orders')
                ->leftJoin('hubs as fh', 'interhub_orders.from_hub_id', '=', 'fh.id')
                ->leftJoin('hubs as th', 'interhub_orders.to_hub_id', '=', 'th.id')
                ->select('interhub_orders.*',
                    DB::raw('IFNULL(fh.hub, "—") as from_hub'),
                    DB::raw('IFNULL(th.hub, "—") as to_hub')
                )
                ->orderByDesc('interhub_orders.id')
                ->paginate(25)->withQueryString();
        }
        return view('hr_manager.interhub_orders', compact('rows'));
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
        return view('hr_manager.products', compact('products', 'search'));
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
        return view('hr_manager.delivery_boy', compact('rows'));
    }

    // ─── Grievance ───────────────────────────────────────────────────────────────

    public function grievance(Request $request)
    {
        $search     = $request->input('search');
        $rows       = collect();

        if (Schema::hasTable('grievance')) {
            $query = DB::table('grievance')
                ->leftJoin('buyers', 'grievance.buyer_id', '=', 'buyers.id')
                ->leftJoin('grievance_category', 'grievance.category_id', '=', 'grievance_category.id')
                ->select('grievance.*',
                    DB::raw('IFNULL(buyers.name, "—") as buyer_name'),
                    DB::raw('IFNULL(grievance_category.category, "—") as category_name')
                );
            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('buyers.name', 'like', "%{$search}%")
                      ->orWhere('grievance.order_id', 'like', "%{$search}%");
                });
            }
            $rows = $query->orderByDesc('grievance.id')->paginate(25)->withQueryString();
        }
        return view('hr_manager.grievance', compact('rows', 'search'));
    }

    public function grievanceCategories(Request $request)
    {
        $rows = collect();
        if (Schema::hasTable('grievance_category')) {
            $rows = DB::table('grievance_category')->orderByDesc('id')->paginate(25)->withQueryString();
        }
        return view('hr_manager.grievance_categories', compact('rows'));
    }

    public function grievanceCategoryStore(Request $request)
    {
        $request->validate(['category' => 'required|string|max:255']);

        if (! Schema::hasTable('grievance_category')) {
            return redirect()->back()->with('error', 'Table not found.');
        }

        if ($request->filled('id')) {
            DB::table('grievance_category')->where('id', $request->id)->update(['category' => $request->category]);
            return redirect()->route('hr_manager.grievance_categories')->with('success', 'Category updated.');
        }

        DB::table('grievance_category')->insert(['category' => $request->category]);
        return redirect()->route('hr_manager.grievance_categories')->with('success', 'Category added.');
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
        return view('hr_manager.hub_inventory', compact('rows'));
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
        return view('hr_manager.pending_inward', compact('rows'));
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
        return view('hr_manager.inward_outward', compact('rows'));
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
        return view('hr_manager.locked_inventory', compact('rows'));
    }

    // ─── Danger / Wastage ────────────────────────────────────────────────────────

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
        return view('hr_manager.danger_stock', compact('rows'));
    }

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
        return view('hr_manager.wastage_reports', compact('rows'));
    }

    public function skuReport(Request $request)
    {
        $rows = collect();
        if (Schema::hasTable('wastage_stock')) {
            $rows = DB::table('wastage_stock')
                ->leftJoin('products', 'wastage_stock.product_id', '=', 'products.id')
                ->leftJoin('hubs', 'wastage_stock.hub_id', '=', 'hubs.id')
                ->select(
                    'products.product_name',
                    'hubs.hub as hub_name',
                    DB::raw('SUM(wastage_stock.qty) as total_wastage')
                )
                ->groupBy('wastage_stock.product_id', 'wastage_stock.hub_id', 'products.product_name', 'hubs.hub')
                ->orderByDesc(DB::raw('SUM(wastage_stock.qty)'))
                ->paginate(25)->withQueryString();
        }
        return view('hr_manager.sku_report', compact('rows'));
    }

    // ─── Wallet & Finance ────────────────────────────────────────────────────────

    public function walletHistory(Request $request)
    {
        $table = Schema::hasTable('wallet_transaction_history') ? 'wallet_transaction_history'
               : (Schema::hasTable('wallet_history') ? 'wallet_history' : null);
        $rows  = $table
            ? DB::table($table)->orderByDesc('id')->paginate(25)->withQueryString()
            : collect();
        return view('hr_manager.wallet_history', compact('rows'));
    }

    public function addWalletMoney(Request $request)
    {
        $buyer    = null;
        $buyer_id = $request->input('buyer_id');
        if ($buyer_id) {
            $buyer = DB::table('buyers')->where('id', $buyer_id)->first();
        }
        return view('hr_manager.add_wallet_money', compact('buyer', 'buyer_id'));
    }

    public function addWalletMoneySubmit(Request $request)
    {
        $request->validate([
            'buyer_id' => 'required|integer',
            'amount'   => 'required|numeric|min:1',
        ]);

        $table = Schema::hasTable('wallet_transaction_history') ? 'wallet_transaction_history'
               : (Schema::hasTable('wallet_history') ? 'wallet_history' : null);

        if ($table) {
            DB::table($table)->insert([
                'buyer_id'         => $request->buyer_id,
                'amount'           => $request->amount,
                'transaction_type' => 'credit',
                'note'             => $request->input('note', 'Added by HR Manager'),
                'created_at'       => now(),
            ]);
        }

        if (Schema::hasTable('buyers') && Schema::hasColumn('buyers', 'wallet_amount')) {
            DB::table('buyers')->where('id', $request->buyer_id)
                ->increment('wallet_amount', $request->amount);
        }

        return redirect()->route('hr_manager.wallet_history')->with('success', 'Wallet amount added successfully.');
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
        return view('hr_manager.withdraw_money', compact('rows'));
    }

    public function cashDeposit(Request $request)
    {
        $table = Schema::hasTable('cash_deposit') ? 'cash_deposit' : null;
        $rows  = $table
            ? DB::table($table)->orderByDesc('id')->paginate(25)->withQueryString()
            : collect();
        return view('hr_manager.cash_deposit', compact('rows'));
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
        return view('hr_manager.online_payment_history', compact('rows'));
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
        return view('hr_manager.sales_report', compact('rows', 'totals', 'from', 'to'));
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
        return view('hr_manager.rating_reviews', compact('rows'));
    }

    public function newsLetters(Request $request)
    {
        $rows = DB::table('news_letter')->orderByDesc('id')->paginate(25)->withQueryString();
        return view('hr_manager.news_letters', compact('rows'));
    }

    // ─── Promotions & Banners ────────────────────────────────────────────────────

    public function banner(Request $request)
    {
        $rows = collect();
        if (Schema::hasTable('banner')) {
            $rows = DB::table('banner')->orderByDesc('id')->paginate(25)->withQueryString();
        }
        return view('hr_manager.banner', compact('rows'));
    }

    public function homeOffers(Request $request)
    {
        $rows = collect();
        if (Schema::hasTable('home_offers')) {
            $rows = DB::table('home_offers')->orderByDesc('id')->paginate(25)->withQueryString();
        } elseif (Schema::hasTable('offers')) {
            $rows = DB::table('offers')->orderByDesc('id')->paginate(25)->withQueryString();
        }
        return view('hr_manager.home_offers', compact('rows'));
    }

    public function promotions(Request $request)
    {
        $rows = collect();
        if (Schema::hasTable('promotions')) {
            $rows = DB::table('promotions')->orderByDesc('id')->paginate(25)->withQueryString();
        } elseif (Schema::hasTable('promo_codes')) {
            $rows = DB::table('promo_codes')->orderByDesc('id')->paginate(25)->withQueryString();
        }
        return view('hr_manager.promotions', compact('rows'));
    }

    // ─── Profile ─────────────────────────────────────────────────────────────────

    public function profile()
    {
        $user = $this->user();
        return view('hr_manager.profile', compact('user'));
    }

    public function editProfile()
    {
        $user = $this->user();
        return view('hr_manager.edit_profile', compact('user'));
    }

    public function updateProfile(Request $request)
    {
        $request->validate(['name' => 'required|string|max:255']);
        $data = ['name' => $request->name, 'address' => $request->input('address')];
        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }
        DB::table('hr_managers')->where('id', $this->user()->id)->update($data);
        return redirect()->route('hr_manager.profile')->with('success', 'Profile updated.');
    }
}
