<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\bannerImage;
use App\Models\category;
use App\Models\product;
use App\Models\coupons;
use App\Models\Cart;
use App\Models\Buyer;
use App\Models\BuyersAddress;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;



class HomeController extends Controller
{
    public function index()
    {
        $banners = bannerImage::where('status', '=', 'active')->get();
        $categories = category::where('status', '=', 'active')
            ->orderBy('ranking', 'asc')
            ->get()
            ->filter(fn ($category) => filled($category->name))
            ->unique(fn ($category) => \Illuminate\Support\Str::lower(trim((string) $category->name)))
            ->values();
        $bestSellers = product::with('category')->where('status', '=', 'active')->where('tag', 'like', 'Best Sellers')->orderBy('ranking', 'desc')->limit(10)->get();
        // dd($bestSellers);
        $meatAndSeafoods = product::with('category')->where('status', '=', 'active')->where('tag', 'like', 'Meat & Seafoods')->orderBy('ranking', 'desc')->limit(6)->get();
        $coupons = coupons::where('status', '=', 'active')->where('type', '=', 'normal')->orderBy('created_at', 'desc')->get();
        $discount = coupons::where('status', '=', 'active')->where('type', '=', 'discount')->orderBy('created_at', 'asc')->limit(3)->get();
        $promotions = DB::table('home_promotions')
            ->where(function ($query) {
                $query->whereNull('expiry')->orWhere('expiry', '=', '0')->orWhereDate('expiry', '>=', now()->toDateString());
            })
            ->orderByDesc('id')
            ->limit(8)
            ->get();
        $shopByCategory = category::where('status', '=', 'active')->orderBy('ranking', 'asc')->limit(10)->get();
        // dd($bestSellers);
        return view('home', compact('banners', 'categories', 'bestSellers', 'meatAndSeafoods', 'coupons', 'discount', 'promotions', 'shopByCategory'));
    }
    public function view_product($category = null, $type = null)
    {
        $categories = Category::where('status', 'active')
            ->orderBy('ranking', 'asc')
            ->get();

        if ($category && $category !== 'all') {

            $cat = Category::where('id', $category)->firstOrFail();

            $products = Product::where('category_id', $category)
                ->where('status', 'active')
                ->orderBy('ranking', 'asc')
                ->get();
        } elseif ($type) {

            $cat = null;

            $products = Product::where('tag', 'LIKE', "%$type%")
                ->where('status', 'active')
                ->orderBy('ranking', 'asc')
                ->get();
        } else {

            $cat = null;

            $products = Product::where('status', 'active')
                ->orderBy('ranking', 'asc')
                ->get();
        }
        $cartQuery = Cart::whereIn('product_id', $products->pluck('id'));
        $cartQuery = Auth::check()
            ? $cartQuery->where('buyer_id', Auth::id())
            : $cartQuery->where('session_id', session()->getId());
        $cartQuantities = $cartQuery->pluck('quantity', 'product_id');
        // dd($products);
        return view('view_product', compact(
            'products',
            'cat',
            'categories',
            'type',
            'cartQuantities'
        ));
    }

    public function categories()
    {
        $categories = Category::where('status', 'active')
            ->orderBy('ranking', 'asc')
            ->get()
            ->filter(fn ($category) => filled($category->name))
            ->unique(fn ($category) => \Illuminate\Support\Str::lower(trim((string) $category->name)))
            ->values();

        return view('categories', compact('categories'));
    }
    public function product($category = null, $type = null)
    {
        $categories = Category::where('status', 'active')
            ->orderBy('ranking', 'asc')
            ->get();

        if ($category && $category !== 'all') {

            $cat = Category::where('id', $category)->firstOrFail();

            $products = Product::where('category_id', $category)
                ->where('status', 'active')
                ->orderBy('ranking', 'asc')
                ->get();
        } elseif ($type) {

            $cat = null;

            $products = Product::where('tag', 'LIKE', "%$type%")
                ->where('status', 'active')
                ->orderBy('ranking', 'asc')
                ->get();
        } else {

            $cat = null;

            $products = Product::where('status', 'active')
                ->orderBy('ranking', 'asc')
                ->get();
        }
        // dd($products);
        return view('product', compact(
            'products',
            'cat',
            'categories',
            'type'
        ));
    }

    public function product_detail(Request $request)
    {
        // Security fix (21 Oct 2026): product detail - every value goes to the database as a bound
        // parameter (no SQL injection), the product id must be a number, and an unknown product shows 404.
        $product_id = (int) $request->query('id');
        $product = DB::table('products as t1')
            ->select(
                't1.*',
                't4.live_stock',
                't2.item as title',
                't2.image as image',
                't3.name as category'
            )
            ->join('items as t2', 't2.id', '=', 't1.item_id')
            ->join('category as t3', 't3.id', '=', 't2.cat_id')
            ->leftJoin('inventory as t4', 't4.product_id', '=', 't1.product_id')
            ->where('t1.id', $product_id)
            ->where('t1.status', 'active')
            ->first();
        if (!$product) {
            abort(404);
        }
        $categories = category::where('status', '=', 'active')->orderBy('ranking', 'asc')->get();

        $remove = ['Todays Deal', 'Best Sellers', 'May Offers'];
        $session_id = session()->getId();
        $tags = array_values(array_filter(array_map('trim', explode(',', (string) $product->tag)), function ($t) use ($remove) {
            return $t !== '' && !in_array($t, $remove, true);
        }));
        $hub_id = isset($_COOKIE['hub_id']) ? (int) $_COOKIE['hub_id'] : 3;
        if ($hub_id <= 0) {
            $hub_id = 3;
        }

        $sql = "SELECT t1.*, t4.live_stock, t2.item as title, t3.name as category, IF(t5.id IS NULL, FALSE, TRUE) as cart, t5.quantity, t5.id as cart_id
                FROM products t1
                INNER JOIN items t2 ON t2.id = t1.item_id
                INNER JOIN category t3 ON t3.id = t2.cat_id
                LEFT JOIN cart t5 ON t5.product_id = t1.id AND t5.session_id = ?
                LEFT JOIN inventory t4 ON t4.product_id = t1.product_id AND t4.hub_id = ?
                WHERE t1.status = 'active' AND t1.id != ?";
        $bindings = [$session_id, $hub_id, $product_id];

        if (!empty($tags)) {
            $likes = [];
            foreach ($tags as $tag) {
                $likes[] = 't1.tag LIKE ?';
                $bindings[] = '%' . $tag . '%';
            }
            $sql .= ' AND (' . implode(' OR ', $likes) . ')';
        }

        $moreProduct = DB::select($sql . ' ORDER BY t1.id DESC', $bindings);
        return view('product_detail', compact('product', 'categories', 'moreProduct'));
    }

    /** Security fix (21 Oct 2026): checkout - the same delivery charge rule the checkout page shows. */
    private function serverDeliveryCharge()
    {
        $city = isset($_COOKIE['city']) ? $_COOKIE['city'] : 'Lucknow';
        $delivery_charge = 0;
        $city_data = DB::table('cities')->where('city', 'like', "%$city%")->first();
        if ($city_data) {
            $delivery_price = DB::table('delivery_price')->where('city', $city_data->id)->first();
            if ($delivery_price) {
                $delivery_charge = $delivery_price->price;
            }
        } else {
            $delivery_charge = 30; // Default
        }
        return $delivery_charge;
    }

    private function cartWhereClause($query)
    {
        if (Auth::check()) {
            return $query->where('t1.buyer_id', Auth::id());
        }
        return $query->where('t1.session_id', session()->getId());
    }

    private function cartCount(): int
    {
        if (Auth::check()) {
            return Cart::where('buyer_id', Auth::id())->count();
        }
        return Cart::where('session_id', session()->getId())->count();
    }

    public function cart(Request $request)
    {
        try {
            $baseUrl = url('/uploads/images/products');

            $query = DB::table('cart as t1')
                ->join('products as t2', 't2.id', '=', 't1.product_id')
                ->join('items as t3', 't3.id', '=', 't2.item_id')
                ->select(
                    't1.id as cart_id',
                    't1.quantity',
                    DB::raw('t1.product_id as cart_pid'),
                    't3.item as title',
                    't2.*',
                    DB::raw("CONCAT('$baseUrl/', t3.image) as image")
                );

            $products = $this->cartWhereClause($query)->get();

            if ($products->isEmpty()) {
                return response()->json(['result' => false, 'message' => 'Cart is empty', 'products' => []]);
            }

            $products = $products->map(function ($item) {
                $MRP = $item->MRP ?? 0;
                $main_price = $item->main_price ?? 0;
                $item->percent_off = $MRP > 0 ? round((($MRP - $main_price) * 100) / $MRP) : 0;
                return $item;
            });

            return response()->json(['result' => true, 'message' => 'success', 'products' => $products]);
        } catch (\Exception $e) {
            return response()->json(['result' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function addCart(Request $request)
    {
        try {
            $request->validate([
                'product_id' => 'required|numeric',
                'quantity'   => 'required|numeric|min:0',
            ]);

            $product_id = (int) $request->product_id;
            $quantity   = (int) $request->quantity;

            $isGuest  = !Auth::check();
            $buyerId  = $isGuest ? null : Auth::id();
            $sessId   = $isGuest ? session()->getId() : null;

            $cartWhere = function ($q) use ($isGuest, $buyerId, $sessId, $product_id) {
                $q->where('product_id', $product_id);
                return $isGuest ? $q->where('session_id', $sessId) : $q->where('buyer_id', $buyerId);
            };

            DB::beginTransaction();

            $action = 'none';

            if ($quantity == 0) {
                $deleted = Cart::where($cartWhere)->delete();
                if ($deleted) $action = 'deleted';
            } else {
                $product    = product::find($product_id);
                $price      = ($product && floatval($product->main_price) > 0)
                                ? floatval($product->main_price)
                                : ($product ? floatval($product->MRP) : 0);
                $total_price = $price * $quantity;

                $cart = Cart::where($cartWhere)->first();

                if ($cart) {
                    $cart->update(['quantity' => $quantity, 'total_price' => $total_price]);
                    $action = 'updated';
                } else {
                    $data = ['product_id' => $product_id, 'quantity' => $quantity, 'total_price' => $total_price];
                    $isGuest ? $data['session_id'] = $sessId : $data['buyer_id'] = $buyerId;
                    Cart::create($data);
                    $action = 'inserted';
                }
            }

            $cart_count = $this->cartCount();
            DB::commit();

            $messages = ['inserted' => 'Added successfully', 'updated' => 'Updated successfully', 'deleted' => 'Removed from cart', 'none' => 'No change'];
            return response()->json(['result' => true, 'message' => $messages[$action], 'cart_count' => $cart_count]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['result' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function myAccount()
    {
        if (!Auth::check()) {
            return redirect()->route('home')->with('error', 'Please login first');
        }

        $user_id = Auth::id();
        $buyer = Buyer::find($user_id);
        if (!$buyer) return redirect()->route('home')->with('error', 'Please login first');

        $referralCode = app(\App\Services\BuyerReferralService::class)->ensureCode((int) $user_id);
        $orders = DB::table('orders')->where('buyer_id', $user_id)
            ->select('order_id', DB::raw('MAX(id) as id'), DB::raw('MAX(date_added) as date_added'),
                DB::raw('MAX(order_status) as order_status'), DB::raw('MAX(delivery_type) as delivery_type'),
                DB::raw('MAX(schedule_time) as schedule_time'), DB::raw('MAX(total_amount) as total_amount'),
                DB::raw('MAX(delivery_charge) as delivery_charge'), DB::raw('COUNT(*) as order_count'))
            ->groupBy('order_id')->orderByDesc('id')->get();
        $addresses = BuyersAddress::where('buyer_id', $user_id)->get();
        $walletTransactions = DB::table('buyer_wallet_ledger')->where('buyer_id', $user_id)->orderByDesc('id')->limit(30)->get();
        if ($walletTransactions->isEmpty()) {
            foreach (['wallet_transaction_history', 'wallet_history'] as $legacyTable) {
                if (!\Illuminate\Support\Facades\Schema::hasTable($legacyTable) || !\Illuminate\Support\Facades\Schema::hasColumn($legacyTable, 'buyer_id')) continue;
                $legacyQuery = DB::table($legacyTable)->where('buyer_id', $user_id);
                if (\Illuminate\Support\Facades\Schema::hasColumn($legacyTable, 'id')) $legacyQuery->orderByDesc('id');
                $walletTransactions = $legacyQuery->limit(30)->get();
                break;
            }
        }

        return view('myaccount', compact('buyer', 'orders', 'addresses', 'walletTransactions', 'referralCode'));
    }
    public function trackOrder($order_id)
    {
        if (!Auth::check()) {
            return redirect()->route('home')->with('error', 'Please login first');
        }

        $user_id = Auth::id();

        $order_items = DB::table('orders as t1')
            ->join('products as t2', 't2.id', '=', 't1.product_id')
            ->join('items as t3', 't3.id', '=', 't2.item_id')
            ->where('t1.order_id', $order_id)
            ->where('t1.buyer_id', $user_id)
            ->select('t1.*', 't2.main_price', 't2.product_unit', 't2.unit_quantity', 't2.product_image', 't3.item as item_name')
            ->get();

        if ($order_items->isEmpty()) {
            return redirect()->route('myaccount')->with('error', 'Order not found');
        }

        $product_ids = $order_items->pluck('product_id');
        $reviews = DB::table('reviews')
            ->where('buyer_id', $user_id)
            ->whereIn('product_id', $product_ids)
            ->get()
            ->keyBy('product_id');

        return view('track_order', compact('order_items', 'order_id', 'reviews'));
    }

    public function cancelOrderPage($order_id)
    {
        if (!Auth::check()) {
            return redirect()->route('home')->with('error', 'Please login first');
        }

        $user_id = Auth::id();
        $order = DB::table('orders')->where('order_id', $order_id)->where('buyer_id', $user_id)->first();

        if (!$order || $order->order_status === 'Order Cancel') {
            return redirect()->route('myaccount')->with('error', 'Order not found or already cancelled');
        }

        return view('cancel_order', compact('order_id'));
    }

    public function cancelOrder(Request $request)
    {
        if (!Auth::check()) {
            return response()->json(['result' => false, 'message' => 'Please login first']);
        }

        $user_id = Auth::id();
        $order_id = $request->order_id;
        $reason   = $request->reason_for_order_cancel;

        if (!$reason) {
            return response()->json(['result' => false, 'message' => 'Please select a reason']);
        }

        $exists = DB::table('orders')->where('order_id', $order_id)->where('buyer_id', $user_id)->exists();
        if (!$exists) {
            return response()->json(['result' => false, 'message' => 'Order not found']);
        }

        DB::table('orders')
            ->where('order_id', $order_id)
            ->where('buyer_id', $user_id)
            ->update(['order_status' => 'Order Cancel']);

        return response()->json(['result' => true, 'message' => 'Order cancelled successfully']);
    }

    public function addReview(Request $request)
    {
        if (!Auth::check()) {
            return response()->json(['result' => false, 'message' => 'Please login first']);
        }

        $user_id    = Auth::id();
        $product_id = $request->product_id;
        $rating     = $request->rating;
        $feedback   = $request->feedback;

        if (!$product_id) {
            return response()->json(['result' => false, 'message' => 'Please define product id']);
        }

        $existing = DB::table('reviews')
            ->where('buyer_id', $user_id)
            ->where('product_id', $product_id)
            ->first();

        if ($existing) {
            DB::table('reviews')->where('id', $existing->id)->update([
                'rating'   => $rating,
                'feedback' => $feedback,
            ]);
            return response()->json(['result' => true, 'message' => 'Rating Updated Successfully']);
        }

        DB::table('reviews')->insert([
            'buyer_id'   => $user_id,
            'product_id' => $product_id,
            'rating'     => $rating,
            'feedback'   => $feedback,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(['result' => true, 'message' => 'Rating Added Successfully']);
    }

    /** Address type as the buyer_address.type column accepts it (enum or short text). Fix 21 Oct 2026. */
    private function fitAddressType(string $type): string
    {
        $type = trim($type) !== '' ? trim($type) : 'Home';
        try {
            $col = DB::selectOne("SELECT DATA_TYPE AS dt, COLUMN_TYPE AS ct, CHARACTER_MAXIMUM_LENGTH AS len FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'buyer_address' AND column_name = 'type'");
        } catch (\Throwable $e) {
            return mb_substr($type, 0, 20);
        }
        if (!$col) return mb_substr($type, 0, 20);
        if (strtolower((string) $col->dt) === 'enum' && preg_match_all("/'((?:[^']|'')*)'/", (string) $col->ct, $m)) {
            $allowed = array_map(fn ($v) => str_replace("''", "'", $v), $m[1]);
            foreach ($allowed as $a) if (strcasecmp($a, $type) === 0) return $a;
            $same = ['work' => ['office', 'work'], 'office' => ['work', 'office'], 'home' => ['home', 'house'], 'house' => ['home'], 'other' => ['other', 'others']];
            foreach ($same[strtolower($type)] ?? [] as $want) foreach ($allowed as $a) if (strcasecmp($a, $want) === 0) return $a;
            foreach ($allowed as $a) if (in_array(strtolower($a), ['other', 'others'], true)) return $a;
            return $allowed ? end($allowed) : $type;
        }
        return $col->len ? mb_substr($type, 0, (int) $col->len) : $type;
    }

    public function storeAddress(Request $request)
    {
        $data = $request->validate([
            'address_id' => 'nullable|integer',
            'name' => 'required|string|max:150',
            'phone' => 'required|string|max:30',
            'locality' => 'required|string|max:255',
            'street_name' => 'required|string|max:500',
            'landmark' => 'nullable|string|max:255',
            'city' => 'required|string|max:150',
            'type' => 'required|string|max:50',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
        ]);

        $addressId = $data['address_id'] ?? null;
        unset($data['address_id']);
        $data['type'] = $this->fitAddressType((string) $data['type']);   // Fix 21 Oct 2026: match what the database column accepts
        foreach (['created_at', 'updated_at'] as $timestamp) {
            if (Schema::hasColumn('buyer_address', $timestamp)) {
                $data[$timestamp] = now();
            }
        }
        foreach (['latitude', 'longitude'] as $coordinate) {
            if (!Schema::hasColumn('buyer_address', $coordinate)) {
                unset($data[$coordinate]);
            }
        }

        if ($addressId) {
            $address = BuyersAddress::where('buyer_id', Auth::id())->findOrFail($addressId);
            $address->update($data);
            $message = 'Address updated successfully';
        } else {
            $data['buyer_id'] = Auth::id();
            $address = BuyersAddress::create($data);
            $message = 'Address added successfully';
        }
        // Fix 21 Oct 2026: remember where this address is on the map and which hub delivers there.
        try {
            $geo = [];
            $lat = $data['latitude'] ?? null; $lng = $data['longitude'] ?? null;
            $ckCity = trim((string) ($_COOKIE['city'] ?? ''));
            $sameCity = $ckCity !== '' && (stripos((string) $data['city'], $ckCity) !== false || stripos($ckCity, (string) $data['city']) !== false);
            if ((!is_numeric($lat) || !is_numeric($lng)) && $sameCity && is_numeric($_COOKIE['lat'] ?? null) && is_numeric($_COOKIE['lng'] ?? null)) {
                $lat = (float) $_COOKIE['lat']; $lng = (float) $_COOKIE['lng'];
            }
            if (is_numeric($lat) && is_numeric($lng)) {
                if (Schema::hasColumn('buyer_address', 'latitude')) { $geo['latitude'] = $lat; $geo['longitude'] = $lng; }
                $hubForAddress = \App\Services\HubLocator::locate((float) $lat, (float) $lng);
                if (Schema::hasColumn('buyer_address', 'hub_id')) $geo['hub_id'] = $hubForAddress->id ?? null;
            }
            if ($geo && !empty($address->id)) DB::table('buyer_address')->where('id', $address->id)->where('buyer_id', Auth::id())->update($geo);
        } catch (\Throwable $e) {
            report($e);
        }

        return back()->with('success', $message);
    }

    public function deleteAddress($id)
    {
        BuyersAddress::where('id', $id)
            ->where('buyer_id', Auth::id())
            ->delete();

        return back()->with('success', 'Address deleted');
    }

    public function updateAccount(Request $request){
        try {
            $validator = Validator::make($request->all(), [
                'name'   => 'required|string|max:255',
                'gender' => 'required|in:Male,Female',
                'email'  => 'required|email|unique:buyers,email,' . Auth::id(),
            ]);

            if ($validator->fails()) {
                return back()->withErrors($validator)->withInput();
            }
            $user = Auth::user();

            $user->name   = $request->name;
            $user->gender = $request->gender;
            $user->email  = $request->email;

            $user->save();

            return back()->with('success', 'Account updated successfully');

        } catch (\Exception $e) {
            dd($e->getMessage());
            return back()->with('error', 'Something went wrong: ' . $e->getMessage());
        }
    }

    public function changePassword(Request $request){
        try {
            $user = Auth::user();
            if (empty($user->password)) {

                $validator = Validator::make($request->all(), [
                    'new_password' => 'required|min:6|confirmed',
                ]);

                if ($validator->fails()) {
                    return back()->withErrors($validator)->withInput();
                }

                $user->password = Hash::make($request->new_password);
                $user->save();

                return back()->with('success', 'Password set successfully');
            }
            $validator = Validator::make($request->all(), [
                'old_password' => 'required',
                'new_password' => 'required|min:6|confirmed',
            ]);

            if ($validator->fails()) {
                return back()->withErrors($validator)->withInput();
            }


            if (!Hash::check($request->old_password, $user->password)) {
                return back()->with('error', 'Old password is incorrect');
            }

            if (Hash::check($request->new_password, $user->password)) {
                return back()->with('error', 'New password cannot be same as old password');
            }

            $user->password = Hash::make($request->new_password);
            $user->save();

            return back()->with('success', 'Password changed successfully');

        } catch (\Exception $e) {
            return back()->with('error', 'Something went wrong: ' . $e->getMessage());
        }
    }

    public function checkout()
    {
        if (!Auth::check()) {
            return redirect()->route('home')->with('error', 'Please login first');
        }

        $user_id = Auth::id();
        $hub_id = isset($_COOKIE['hub_id']) ? $_COOKIE['hub_id'] : 3;
        $city = isset($_COOKIE['city']) ? $_COOKIE['city'] : 'Lucknow';

        $buyer = Buyer::find($user_id);
        $addresses = DB::table('buyer_address')->where('buyer_id', $user_id)->get();
        $promotions = DB::table('home_promotions')
            ->where(function ($query) {
                $query->whereNull('expiry')->orWhere('expiry', '=', '0')->orWhereDate('expiry', '>=', now()->toDateString());
            })
            ->orderByDesc('id')
            ->get();

        $cart_items = DB::table('cart')
            ->join('products', 'cart.product_id', '=', 'products.id')
            ->join('items', 'products.item_id', '=', 'items.id')
            ->where('cart.buyer_id', $user_id)
            ->select('cart.*', 'products.main_price', 'products.product_image', 'items.item as title')
            ->get();

        $cart_total = $cart_items->sum(function ($item) {
            return $item->quantity * $item->main_price;
        });

        // Simplified delivery charge logic
        $delivery_charge = 0;
        $city_data = DB::table('cities')->where('city', 'like', "%$city%")->first();
        if ($city_data) {
            $delivery_price = DB::table('delivery_price')->where('city', $city_data->id)->first();
            if ($delivery_price) {
                $delivery_charge = $delivery_price->price;
            }
        } else {
             $delivery_charge = 30; // Default
        }

        return view('checkout', compact('buyer', 'addresses', 'cart_items', 'cart_total', 'delivery_charge', 'hub_id', 'promotions'));
    }

    public function checkHub(Request $request)
    {
        $lat     = (float) $request->lat;
        $lng     = (float) $request->lng;
        $address = $request->address ?? '';
        $city    = $request->city    ?? '';

        $hub = \App\Services\HubLocator::locate($lat, $lng);   // Fix 21 Oct 2026: hub whose delivery area contains the point
        if ($request->boolean('preview')) {   // Fix 21 Oct 2026: let the picker check a point without saving it
            return response()->json(['result' => (bool) $hub, 'hub' => $hub->hub ?? null, 'message' => $hub ? 'We deliver here' : "Sorry, we don't deliver to this location yet."]);
        }

        if ($hub) {
            $expire = time() + 60 * 60 * 24 * 365;
            setcookie('hub_id',  $hub->id, $expire, '/');
            setcookie('lat',     $lat,     $expire, '/');
            setcookie('lng',     $lng,     $expire, '/');
            setcookie('address', $address, $expire, '/');
            setcookie('city',    $city,    $expire, '/');
            session(['hub_session_id' => $hub->id]);

            return response()->json(['result' => true, 'message' => 'Location set successfully', 'hub_id' => $hub->id]);
        }

        return response()->json(['result' => false, 'message' => "Sorry, we don't serve your chosen location currently. But we wish to, soon!"]);
    }

    public function searchProducts(Request $request)
    {
        $q = trim($request->input('query', $request->input('q', '')));

        if (strlen($q) < 2) {
            return response()->json([]);
        }

        $baseUrl = rtrim(config('app.url'), '/');

        $results = DB::table('products as p')
            ->join('items as i', 'i.id', '=', 'p.item_id')
            ->leftJoin('category as c', 'c.id', '=', 'p.category_id')
            ->where('p.status', 'active')
            ->where(function ($q2) use ($q) {
                $q2->where('i.item', 'like', "%$q%")
                   ->orWhere('c.name', 'like', "%$q%");
            })
            ->select(
                'p.id',
                'i.item as product_name',
                'p.product_image',
                'p.main_price'
            )
            ->orderBy('i.item')
            ->limit(10)
            ->get()
            ->map(function ($row) use ($baseUrl) {
                $row->image_url = $baseUrl . '/uploads/images/products/' . $row->product_image;
                $row->detail_url = url('/product_detail?id=' . $row->id);
                return $row;
            });

        return response()->json($results);
    }

    public function certificate()
    {
        return view('certificate');
    }

    public function compareProduct(Request $request)
    {
        $ids = $request->input('ids', []);
        if (is_string($ids)) {
            $ids = explode(',', $ids);
        }
        $ids = array_filter(array_map('intval', $ids));

        $products = [];
        if (!empty($ids)) {
            $products = DB::table('products as p')
                ->join('items as i', 'i.id', '=', 'p.item_id')
                ->whereIn('p.id', $ids)
                ->select('p.*', 'i.item')
                ->get();
        }

        return view('compare_product', compact('products'));
    }

    public function maps()
    {
        $hubs = DB::table('hubs')
            ->select('id', 'hub as hub_name', 'hub_lat as lat', 'hub_lng as lng')
            ->get();

        return view('maps', compact('hubs'));
    }

    public function addOrder(Request $request)
    {
        if (!Auth::check()) {
            return response()->json(['result' => false, 'message' => 'Please login first']);
        }

        $user_id = Auth::id();
        $buyer_id = $user_id;

        $address_id = $request->address_id;
        if (empty($address_id)) {
            return response()->json(['result' => false, 'message' => 'Please select address']);
        }

        // Security fix (21 Oct 2026): checkout - hub, delivery charge, address and payment type
        // are decided by the server, never taken from the browser.
        $address_id = (int) $address_id;
        if (!DB::table('buyer_address')->where('id', $address_id)->where('buyer_id', $user_id)->exists()) {
            return response()->json(['result' => false, 'message' => 'Please select one of your saved addresses']);
        }
        // Fix 21 Oct 2026: the order goes to the hub that delivers to the chosen ADDRESS (not whatever the browser remembered).
        $addr = DB::table('buyer_address')->where('id', $address_id)->first();
        $addrLat = $addr->latitude ?? null; $addrLng = $addr->longitude ?? null;
        if (is_numeric($addrLat) && is_numeric($addrLng) && ((float) $addrLat != 0.0 || (float) $addrLng != 0.0)) {
            $addrHub = \App\Services\HubLocator::locate((float) $addrLat, (float) $addrLng);
            if (!$addrHub) {
                return response()->json(['result' => false, 'message' => "Sorry, we don't deliver to this address yet. Please choose another address."]);
            }
            $hubId = (int) $addrHub->id;
        } else {
            $hubId = (int) (session('hub_session_id') ?: (isset($_COOKIE['hub_id']) ? $_COOKIE['hub_id'] : 3));
            // Older address without a map position: it must at least be in the city of the chosen hub.
            $hubCity = (string) DB::table('hubs')->leftJoin('cities', 'cities.id', '=', 'hubs.city_id')->where('hubs.id', $hubId)->value('cities.city');
            $addrCity = trim((string) ($addr->city ?? ''));
            if ($hubCity !== '' && $addrCity !== '' && stripos($addrCity, $hubCity) === false && stripos($hubCity, $addrCity) === false) {
                return response()->json(['result' => false, 'message' => 'Your delivery location is set to ' . $hubCity . ', but this address is in ' . $addrCity . '. Please choose your location (top of the page) for this address and try again.']);
            }
        }
        if ($hubId <= 0 || !DB::table('hubs')->where('id', $hubId)->whereRaw("LOWER(CAST(status AS CHAR)) IN ('active', '1')")->exists()) {
            return response()->json(['result' => false, 'message' => 'Please choose your delivery location again']);
        }
        $deliveryCharge = $this->serverDeliveryCharge();
        $paymentType = (string) $request->payment_type;
        if (!in_array($paymentType, ['Cash', 'Online', 'Wallet'], true)) {
            return response()->json(['result' => false, 'message' => 'Please choose a payment method']);
        }

        $cart_items = DB::table('cart')
            ->join('products', 'cart.product_id', '=', 'products.id')
            ->where('cart.buyer_id', $user_id)
            ->select('cart.*', 'products.main_price')
            ->get();

        if ($cart_items->isEmpty()) {
            return response()->json(['result' => false, 'message' => 'Cart is empty']);
        }

        $total_amount = $cart_items->sum(function ($item) {
            return $item->quantity * $item->main_price;
        });

        // Promocode logic (simplified)
        $discount = 0;
        // ... promo logic ...

        // Generate Order ID
        $last_order = DB::table('orders')->orderBy('id', 'desc')->first();
        if (!$last_order) {
            $order_id = 'ORD200000';
        } else {
            // Simple increment for demo
            $last_num = (int) preg_replace('/[^0-9]/', '', $last_order->order_id);
            $order_id = 'ORD' . ($last_num + 1);
        }

        DB::beginTransaction();
        try {
            $ordersHasCreatedAt = \Illuminate\Support\Facades\Schema::hasColumn('orders', 'created_at');
            $ordersHasUpdatedAt = \Illuminate\Support\Facades\Schema::hasColumn('orders', 'updated_at');
            $ordersHasCancelReason = \Illuminate\Support\Facades\Schema::hasColumn('orders', 'reason_for_order_cancel');
            foreach ($cart_items as $item) {
                $orderRow = [
                    'order_id' => $order_id,
                    'buyer_id' => $buyer_id,
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                    'address_id' => $address_id,
                    'payment_type' => $paymentType,
                    'delivery_type' => $request->delivery_type,
                    'delivery_charge' => $deliveryCharge,
                    'schedule_date' => $request->schedule_date,
                    'schedule_time' => $request->schedule_time,
                    'hub_id' => $hubId,
                    'total_amount' => $total_amount,
                    'order_status' => 'Order Pending',
                    'date_added' => now(),
                ];
                if ($ordersHasCreatedAt) $orderRow['created_at'] = now();
                if ($ordersHasUpdatedAt) $orderRow['updated_at'] = now();
                if ($ordersHasCancelReason) $orderRow['reason_for_order_cancel'] = '';
                DB::table('orders')->insert($orderRow);

                // Update stock
                DB::table('inventory')
                    ->where('product_id', $item->product_id)
                    ->where('hub_id', $hubId)
                    ->decrement('live_stock', $item->quantity);
            }

            // Clear cart
            DB::table('cart')->where('buyer_id', $user_id)->delete();
            // Update buyer cart count
            DB::table('buyers')->where('id', $user_id)->update(['cart_count' => 0]);

            DB::commit();
            return response()->json(['result' => true, 'message' => 'Order placed successfully', 'order_id' => $order_id]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['result' => false, 'message' => 'Something went wrong: ' . $e->getMessage()]);
        }
    }
}
