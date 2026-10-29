<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class HubController extends Controller
{
    private function user()
    {
        return Auth::guard('hub_users')->user();
    }

    private function hubId()
    {
        return $this->user()->hub_id ?? null;
    }

    // ─── Dashboard ───────────────────────────────────────────────────────────────

    public function dashboard()
    {
        $hub_id = $this->hubId();

        $stats = [
            'today_orders'     => 0,
            'scheduled_orders' => 0,
            'delivered'        => 0,
            'cancelled'        => 0,
            'pending'          => 0,
            'dispatched'       => 0,
        ];

        if (Schema::hasTable('orders')) {
            $base = DB::table('orders')->when($hub_id, fn ($q) => $q->where('hub_id', $hub_id));

            $stats['today_orders']     = (clone $base)->whereDate('created_at', today())->distinct('order_id')->count('order_id');
            $stats['scheduled_orders'] = (clone $base)->where('order_status', 'Order Placed')->distinct('order_id')->count('order_id');
            $stats['delivered']        = (clone $base)->where('order_status', 'Delivered')->distinct('order_id')->count('order_id');
            $stats['cancelled']        = (clone $base)->where('order_status', 'Cancelled')->distinct('order_id')->count('order_id');
            $stats['pending']          = (clone $base)->where('order_status', 'Order Pending')->distinct('order_id')->count('order_id');
            $stats['dispatched']       = (clone $base)->where('order_status', 'Dispatched')->distinct('order_id')->count('order_id');
        }

        $hub = $hub_id ? DB::table('hubs')->where('id', $hub_id)->first() : null;

        return view('hub.dashboard', compact('stats', 'hub'));
    }

    // ─── Orders ──────────────────────────────────────────────────────────────────

    public function myTodayOrder(Request $request)
    {
        $hub_id = $this->hubId();
        $orders = collect();

        if (Schema::hasTable('orders')) {
            $orders = DB::table('orders')
                ->select('order_id',
                    DB::raw('MAX(id) as id'),
                    DB::raw('MAX(buyer_name) as buyer_name'),
                    DB::raw('MAX(buyer_phone) as buyer_phone'),
                    DB::raw('SUM(total_price) as total_price'),
                    DB::raw('MAX(order_status) as order_status'),
                    DB::raw('MAX(created_at) as created_at')
                )
                ->when($hub_id, fn ($q) => $q->where('hub_id', $hub_id))
                ->whereDate('created_at', today())
                ->groupBy('order_id')
                ->orderByDesc(DB::raw('MAX(id)'))
                ->paginate(25)->withQueryString();
        }

        return view('hub.my_today_order', compact('orders'));
    }

    public function scheduledOrders(Request $request)
    {
        $hub_id = $this->hubId();
        $orders = collect();

        if (Schema::hasTable('orders')) {
            $orders = DB::table('orders')
                ->select('order_id',
                    DB::raw('MAX(id) as id'),
                    DB::raw('MAX(buyer_name) as buyer_name'),
                    DB::raw('MAX(buyer_phone) as buyer_phone'),
                    DB::raw('SUM(total_price) as total_price'),
                    DB::raw('MAX(order_status) as order_status'),
                    DB::raw('MAX(created_at) as created_at'),
                    DB::raw('MAX(schedule_date) as schedule_date')
                )
                ->when($hub_id, fn ($q) => $q->where('hub_id', $hub_id))
                ->where('order_status', 'Order Placed')
                ->groupBy('order_id')
                ->orderByDesc(DB::raw('MAX(id)'))
                ->paginate(25)->withQueryString();
        }

        return view('hub.scheduled_orders', compact('orders'));
    }

    public function todayCancelOrders(Request $request)
    {
        $hub_id = $this->hubId();
        $orders = collect();

        if (Schema::hasTable('orders')) {
            $orders = DB::table('orders')
                ->select('order_id',
                    DB::raw('MAX(id) as id'),
                    DB::raw('MAX(buyer_name) as buyer_name'),
                    DB::raw('MAX(buyer_phone) as buyer_phone'),
                    DB::raw('SUM(total_price) as total_price'),
                    DB::raw('MAX(order_status) as order_status'),
                    DB::raw('MAX(created_at) as created_at')
                )
                ->when($hub_id, fn ($q) => $q->where('hub_id', $hub_id))
                ->where('order_status', 'Cancelled')
                ->whereDate('created_at', today())
                ->groupBy('order_id')
                ->orderByDesc(DB::raw('MAX(id)'))
                ->paginate(25)->withQueryString();
        }

        return view('hub.today_cancel_orders', compact('orders'));
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

        return view('hub.view_order', compact('items', 'buyer', 'order_id'));
    }

    public function interhubOrders(Request $request)
    {
        $hub_id = $this->hubId();
        $rows   = collect();

        if (Schema::hasTable('interhub_orders')) {
            $rows = DB::table('interhub_orders')
                ->leftJoin('hubs as fh', 'interhub_orders.from_hub_id', '=', 'fh.id')
                ->leftJoin('hubs as th', 'interhub_orders.to_hub_id', '=', 'th.id')
                ->select('interhub_orders.*',
                    DB::raw('IFNULL(fh.hub, "—") as from_hub'),
                    DB::raw('IFNULL(th.hub, "—") as to_hub')
                )
                ->where(function ($q) use ($hub_id) {
                    if ($hub_id) {
                        $q->where('interhub_orders.from_hub_id', $hub_id)
                          ->orWhere('interhub_orders.to_hub_id', $hub_id);
                    }
                })
                ->orderByDesc('interhub_orders.id')
                ->paginate(25)->withQueryString();
        }

        return view('hub.interhub_orders', compact('rows'));
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

        return view('hub.all_customers', compact('customers', 'search'));
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

        return view('hub.customer_order', compact('buyer', 'orders', 'buyer_id'));
    }

    public function walletHistory(Request $request)
    {
        $table = Schema::hasTable('wallet_transaction_history') ? 'wallet_transaction_history'
               : (Schema::hasTable('wallet_history') ? 'wallet_history' : null);

        $rows = $table
            ? DB::table($table)->orderByDesc('id')->paginate(25)->withQueryString()
            : collect();

        return view('hub.wallet_history', compact('rows'));
    }

    // ─── Inventory ───────────────────────────────────────────────────────────────

    public function hubInventory(Request $request)
    {
        $hub_id = $this->hubId();
        $rows   = collect();

        if (Schema::hasTable('inventory')) {
            $rows = DB::table('inventory')
                ->leftJoin('products', 'inventory.product_id', '=', 'products.id')
                ->leftJoin('hubs', 'inventory.hub_id', '=', 'hubs.id')
                ->select('inventory.*',
                    DB::raw('IFNULL(products.product_name, "—") as product_name'),
                    DB::raw('IFNULL(hubs.hub, "—") as hub_name')
                )
                ->when($hub_id, fn ($q) => $q->where('inventory.hub_id', $hub_id))
                ->orderByDesc('inventory.id')
                ->paginate(25)->withQueryString();
        }

        return view('hub.hub_inventory', compact('rows'));
    }

    public function pendingInward(Request $request)
    {
        $hub_id = $this->hubId();
        $rows   = collect();

        if (Schema::hasTable('inventory')) {
            $rows = DB::table('inventory')
                ->leftJoin('products', 'inventory.product_id', '=', 'products.id')
                ->leftJoin('hubs', 'inventory.hub_id', '=', 'hubs.id')
                ->select('inventory.*',
                    DB::raw('IFNULL(products.product_name, "—") as product_name'),
                    DB::raw('IFNULL(hubs.hub, "—") as hub_name')
                )
                ->when($hub_id, fn ($q) => $q->where('inventory.hub_id', $hub_id))
                ->where('inventory.stock', '>', 0)
                ->orderByDesc('inventory.id')
                ->paginate(25)->withQueryString();
        }

        return view('hub.pending_inward', compact('rows'));
    }

    public function lockedInventory(Request $request)
    {
        $hub_id = $this->hubId();
        $rows   = collect();

        if (Schema::hasTable('inventory') && Schema::hasColumn('inventory', 'is_locked')) {
            $rows = DB::table('inventory')
                ->leftJoin('products', 'inventory.product_id', '=', 'products.id')
                ->leftJoin('hubs', 'inventory.hub_id', '=', 'hubs.id')
                ->select('inventory.*',
                    DB::raw('IFNULL(products.product_name, "—") as product_name'),
                    DB::raw('IFNULL(hubs.hub, "—") as hub_name')
                )
                ->when($hub_id, fn ($q) => $q->where('inventory.hub_id', $hub_id))
                ->where('inventory.is_locked', 1)
                ->orderByDesc('inventory.id')
                ->paginate(25)->withQueryString();
        }

        return view('hub.locked_inventory', compact('rows'));
    }

    public function acceptInwardStocks(Request $request)
    {
        $hub_id = $this->hubId();
        $rows   = collect();

        if (Schema::hasTable('inventory')) {
            $rows = DB::table('inventory')
                ->leftJoin('products', 'inventory.product_id', '=', 'products.id')
                ->leftJoin('hubs', 'inventory.hub_id', '=', 'hubs.id')
                ->select('inventory.*',
                    DB::raw('IFNULL(products.product_name, "—") as product_name'),
                    DB::raw('IFNULL(hubs.hub, "—") as hub_name')
                )
                ->when($hub_id, fn ($q) => $q->where('inventory.hub_id', $hub_id))
                ->orderByDesc('inventory.id')
                ->paginate(25)->withQueryString();
        }

        return view('hub.accept_inward_stocks', compact('rows'));
    }

    // ─── Hub Transactions (Inward/Outward Log) ───────────────────────────────────

    public function hubTransactions(Request $request)
    {
        $hub_id = $this->hubId();
        $rows   = collect();
        $table  = Schema::hasTable('hub_transactions') ? 'hub_transactions'
                : (Schema::hasTable('interhub_moments') ? 'interhub_moments' : null);

        if ($table) {
            $rows = DB::table($table)
                ->when($hub_id, function ($q) use ($hub_id, $table) {
                    $q->where(function ($q) use ($hub_id, $table) {
                        if (Schema::hasColumn($table, 'from_hub_id')) {
                            $q->where("{$table}.from_hub_id", $hub_id)
                              ->orWhere("{$table}.to_hub_id", $hub_id);
                        } elseif (Schema::hasColumn($table, 'hub_id')) {
                            $q->where("{$table}.hub_id", $hub_id);
                        }
                    });
                })
                ->orderByDesc("{$table}.id")
                ->paginate(25)->withQueryString();
        }

        return view('hub.hub_transactions', compact('rows'));
    }

    // ─── Delivery Boy ────────────────────────────────────────────────────────────

    public function deliveryBoy(Request $request)
    {
        $hub_id = $this->hubId();
        $rows   = collect();

        if (Schema::hasTable('delivery_boy')) {
            $rows = DB::table('delivery_boy')
                ->leftJoin('hubs', 'delivery_boy.hub_id', '=', 'hubs.id')
                ->select('delivery_boy.*', DB::raw('IFNULL(hubs.hub, "—") as hub_name'))
                ->when($hub_id, fn ($q) => $q->where('delivery_boy.hub_id', $hub_id))
                ->orderByDesc('delivery_boy.id')
                ->paginate(25)->withQueryString();
        }

        return view('hub.delivery_boy', compact('rows'));
    }

    // ─── Cash Deposit ────────────────────────────────────────────────────────────

    public function cashDeposit(Request $request)
    {
        $hub_id = $this->hubId();
        $rows   = collect();

        if (Schema::hasTable('cash_deposit')) {
            $query = DB::table('cash_deposit');
            if ($hub_id && Schema::hasColumn('cash_deposit', 'hub_id')) {
                $query->where('hub_id', $hub_id);
            }
            $rows = $query->orderByDesc('id')->paginate(25)->withQueryString();
        }

        return view('hub.cash_deposit', compact('rows'));
    }

    // ─── Profile ─────────────────────────────────────────────────────────────────

    public function profile()
    {
        $user = $this->user();
        return view('hub.profile', compact('user'));
    }

    public function editProfile()
    {
        $user = $this->user();
        return view('hub.edit_profile', compact('user'));
    }

    public function updateProfile(Request $request)
    {
        $request->validate(['name' => 'required|string|max:255']);
        $data = ['name' => $request->name, 'address' => $request->input('address')];
        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }
        DB::table('hub_user')->where('id', $this->user()->id)->update($data);
        return redirect()->route('hub.profile')->with('success', 'Profile updated.');
    }
}
