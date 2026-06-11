<?php
namespace App\Http\Controllers;
use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Buyer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class AdminController extends Controller
{
    public function index()
    {
        $buyer_count   = DB::table('buyers')->count();
        $product_count = DB::table('products')->where('status', 'active')->count();

        $total_orders = DB::table('orders')->distinct()->count('order_id');

        $pending_orders  = DB::table('orders')->where('order_status', 'Order Pending')->distinct()->count('order_id');
        $complete_orders = DB::table('orders')->where('order_status', 'Order Delivered')->distinct()->count('order_id');
        $cancel_orders   = DB::table('orders')->where('order_status', 'Order Cancel')->distinct()->count('order_id');
        $ongoing_orders  = DB::table('orders')
            ->whereIn('order_status', ['Order Placed', 'Order Processed', 'Order Shipped', 'Order Dispatched'])
            ->distinct()
            ->count('order_id');

        // SUM over DISTINCT (order_id,total_amount) — orders table has one row
        // per line item, so revenue needs de-duplication per order.
        $total_revenue = DB::query()
            ->fromSub(
                DB::table('orders')
                    ->select('order_id', 'total_amount')
                    ->where('order_status', 'Order Delivered')
                    ->distinct(),
                'sub'
            )
            ->sum('total_amount');

        return view('admin.dashboard', compact(
            'buyer_count', 'product_count', 'total_orders',
            'pending_orders', 'complete_orders', 'cancel_orders', 'ongoing_orders',
            'total_revenue'
        ));
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

        $cities = Schema::hasTable('cities')
            ? DB::table('cities')->orderBy('city')->get(['id', 'city'])
            : collect();
        $hubs = DB::table('hubs')->orderBy('hub')->get(['id', 'hub', 'city_id']);

        return view('admin.all_orders', compact('orders', 'cities', 'hubs'));
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
            ->leftJoin('category as c', 'c.id', '=', 'p.category_id')
            ->select(
                'p.id',
                'p.product_id',
                'p.product_name',
                'p.MRP as mrp',
                'p.main_price',
                'p.product_image',
                'p.status',
                'p.ranking',
                'c.name as category_name',
                'c.group_type as main_category_name'
            );

        // Filter by main category. The dropdown sends the main category NAME
        // (matches category.group_type) for direct comparison.
        if ($request->main_cat) {
            $query->where('c.group_type', $request->main_cat);
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
            ->paginate(10)
            ->withQueryString();

        // `categories` (plural) is the MAIN category table per legacy schema;
        // `category` (singular) holds sub-categories linked via group_type=name.
        $main_categories = DB::table('categories')->orderBy('name')->get(['id', 'name as main_category_name']);
        $categories      = DB::table('category')->orderBy('name')
            ->get(['id', 'name as category_name', 'group_type as main_category_name']);

        return view('admin.products', compact('products', 'main_categories', 'categories'));
    }

    public function buyers(Request $request)
    {
        $query = Buyer::query()->whereNotNull('email')->where('email', '!=', '');

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                  ->orWhere('email', 'like', "%{$s}%")
                  ->orWhere('phone', 'like', "%{$s}%");
            });
        }

        $buyers = $query->orderByDesc('id')->paginate(20)->withQueryString();

        return view('admin.buyers', compact('buyers'));
    }

    public function view_buyer(Request $request)
    {
        $id    = (int) $request->query('id');
        $buyer = $id ? DB::table('buyers')->where('id', $id)->first() : null;
        if (! $buyer) {
            return redirect()->route('admin.buyers')->with('error', 'Buyer not found.');
        }

        $addresses = Schema::hasTable('buyer_address')
            ? DB::table('buyer_address')->where('buyer_id', $id)->orderByDesc('id')->get()
            : collect();

        $orders = DB::table('orders')
            ->where('buyer_id', $id)
            ->select('id', 'order_id', 'date_added', 'order_status', 'total_amount', 'delivery_type')
            ->groupBy('order_id', 'id', 'date_added', 'order_status', 'total_amount', 'delivery_type')
            ->orderByDesc('date_added')
            ->limit(50)
            ->get();

        return view('admin.view_buyer', compact('buyer', 'addresses', 'orders'));
    }

    public function profile()
    {
        $admin = Auth::guard('admin')->user();
        return view('admin.profile', compact('admin'));
    }

    public function edit_profile()
    {
        $admin = Auth::guard('admin')->user();
        return view('admin.edit_profile', compact('admin'));
    }

    public function hub(Request $request)
    {
        $query = DB::table('hubs as h');
        if (Schema::hasTable('cities')) {
            $query->leftJoin('cities as c', 'c.id', '=', 'h.city_id')
                  ->select('h.*', 'c.city as city_name');
        } else {
            $query->select('h.*');
        }

        if ($request->filled('search')) {
            $query->where('h.hub', 'like', '%'.$request->search.'%');
        }

        $hubs = $query->orderBy('h.hub')->paginate(20)->withQueryString();
        return view('admin.hub', compact('hubs'));
    }

    public function city(Request $request)
    {
        $query = DB::table('cities as c');
        if (Schema::hasTable('states')) {
            $query->leftJoin('states as s', 's.id', '=', 'c.state_id')
                  ->select('c.*', 's.name as state_name');
        } else {
            $query->select('c.*');
        }

        if ($request->filled('search')) {
            $query->where('c.city', 'like', '%'.$request->search.'%');
        }

        $cities = $query->orderBy('c.city')->paginate(50)->withQueryString();
        return view('admin.city', compact('cities'));
    }

    // ----- Hub CRUD -------------------------------------------------------
    public function hub_form(Request $request)
    {
        $id  = (int) $request->query('id');
        $row = $id ? DB::table('hubs')->where('id', $id)->first() : null;

        $cities = DB::table('cities')
            ->when(Schema::hasTable('states'), function ($q) {
                $q->leftJoin('states', 'states.id', '=', 'cities.state_id')
                  ->where(function ($qq) { $qq->where('states.country_id', 101)->orWhereNull('states.country_id'); });
            })
            ->where('cities.status', 'active')
            ->orderBy('cities.city')
            ->get(['cities.id', 'cities.city']);

        return view('admin.hub_form', compact('row', 'cities'));
    }

    public function hub_store(Request $request)
    {
        $request->validate([
            'hub'     => 'required|string|max:191',
            'city_id' => 'required|integer',
            'type'    => 'nullable|string|max:50',
            'hub_lat' => 'nullable|string|max:50',
            'hub_lng' => 'nullable|string|max:50',
        ]);

        $row = [
            'hub'     => $request->hub,
            'city_id' => $request->city_id,
            'type'    => $request->type,
            'hub_lat' => $request->hub_lat,
            'hub_lng' => $request->hub_lng,
            'status'  => 'active',
        ];

        if ($request->filled('id')) {
            DB::table('hubs')->where('id', $request->id)->update($row);
            $msg = 'Hub updated.';
        } else {
            DB::table('hubs')->insert($row);
            $msg = 'Hub added.';
        }

        return redirect()->route('admin.hub')->with('success', $msg);
    }

    // ----- City CRUD ------------------------------------------------------
    public function city_form(Request $request)
    {
        $id  = (int) $request->query('id');
        $row = $id ? DB::table('cities')->where('id', $id)->first() : null;

        $states = Schema::hasTable('states')
            ? DB::table('states')->where('country_id', 101)->orderBy('name')->get(['id', 'name'])
            : collect();

        return view('admin.city_form', compact('row', 'states'));
    }

    public function city_store(Request $request)
    {
        $request->validate([
            'city'     => 'required|string|max:191',
            'state_id' => 'nullable|integer',
        ]);

        $row = [
            'city'     => $request->city,
            'state_id' => $request->state_id,
            'status'   => 'active',
        ];

        if ($request->filled('id')) {
            DB::table('cities')->where('id', $request->id)->update($row);
            $msg = 'City updated.';
        } else {
            DB::table('cities')->insert($row);
            $msg = 'City added.';
        }

        return redirect()->route('admin.city')->with('success', $msg);
    }

    // ----- Order detail ---------------------------------------------------
    public function view_operation_order(Request $request)
    {
        $orderId = (string) $request->query('order_id', '');
        if ($orderId === '') {
            return redirect()->route('admin.all_orders')->with('error', 'No order specified.');
        }

        $items = DB::table('orders as t1')
            ->leftJoin('products as t2', 't2.id', '=', 't1.product_id')
            ->leftJoin('items as t3', 't3.id', '=', 't2.item_id')
            ->where('t1.order_id', $orderId)
            ->select(
                't1.*',
                't2.product_unit', 't2.unit_quantity', 't2.main_price as product_main_price', 't2.MRP as product_mrp',
                't3.item'
            )
            ->get();

        if ($items->isEmpty()) {
            return redirect()->route('admin.all_orders')->with('error', 'Order not found.');
        }

        $order   = $items->first();
        $buyer   = DB::table('buyers')->where('id', $order->buyer_id)->first();
        $address = Schema::hasTable('buyer_address')
            ? DB::table('buyer_address')->where('id', $order->address_id)->first()
            : null;
        $hub     = DB::table('hubs')->where('id', $order->hub_id)->first();

        return view('admin.view_operation_order', compact('order', 'items', 'buyer', 'address', 'hub'));
    }

    public function update_profile(Request $request)
    {
        /** @var \App\Models\Admin $admin */
        $admin = Auth::guard('admin')->user();

        $request->validate([
            'name'             => 'required|string|max:191',
            'email'            => 'required|email|max:191',
            'phone'            => 'nullable|string|max:20',
            'password'         => 'nullable|string|min:6|confirmed',
        ]);

        $admin->name  = $request->name;
        $admin->email = $request->email;
        if (Schema::hasColumn('admins', 'phone')) {
            $admin->phone = $request->phone;
        }
        if ($request->filled('password')) {
            $admin->password = Hash::make($request->password);
        }
        $admin->save();

        return redirect()->route('admin.profile')->with('success', 'Profile updated.');
    }
}
