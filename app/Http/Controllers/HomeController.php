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

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;



class HomeController extends Controller
{
    public function index()
    {
        $banners = bannerImage::where('status', '=', 'active')->get();
        $categories = category::where('status', '=', 'active')->orderBy('ranking', 'asc')->get();
        $bestSellers = product::where('status', '=', 'active')->where('tag', 'like', 'Best Sellers')->orderBy('ranking', 'desc')->limit(10)->get();
        // dd($bestSellers);
        $meatAndSeafoods = product::where('status', '=', 'active')->where('tag', 'like', 'Meat & Seafoods')->orderBy('ranking', 'desc')->limit(6)->get();
        $coupons = coupons::where('status', '=', 'active')->where('type', '=', 'normal')->orderBy('created_at', 'desc')->get();
        $discount = coupons::where('status', '=', 'active')->where('type', '=', 'discount')->orderBy('created_at', 'asc')->limit(3)->get();
        $shopByCategory = category::where('status', '=', 'active')->orderBy('ranking', 'asc')->limit(10)->get();
        // dd($bestSellers);
        return view('home', compact('banners', 'categories', 'bestSellers', 'meatAndSeafoods', 'coupons', 'discount', 'shopByCategory'));
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
        // dd($products);
        return view('view_product', compact(
            'products',
            'cat',
            'categories',
            'type'
        ));
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
        $product_id = $request->query('id');
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
        $categories = category::where('status', '=', 'active')->orderBy('ranking', 'asc')->get();
        $tags = explode(',', $product->tag);

        $remove = ['Todays Deal', 'Best Sellers', 'May Offers'];
        $session_id = session()->getId();
        $tags = array_filter($tags, function ($t) use ($remove) {
            return !in_array(trim($t), $remove);
        });
        $hub_id = isset($_COOKIE['hub_id']) ? $_COOKIE['hub_id'] : 3;

        $sql = "SELECT t1.*,t4.live_stock,t2.item as title,t3.name as category,IF(t5.id IS NULL, FALSE, TRUE) as cart,t5.quantity,t5.id as cart_id  
                FROM products t1 
                INNER JOIN items t2 ON t2.id = t1.item_id 
                INNER JOIN category t3 ON t3.id = t2.cat_id 
                LEFT JOIN cart t5 ON (t5.product_id = t1.id) and t5.session_id = '$session_id' 
                LEFT JOIN inventory t4 ON t4.product_id = t1.product_id and t4.hub_id = $hub_id 
                WHERE t1.status = 'active' AND t1.id != $product_id";

        if (!empty($tags) && isset($tags)) {
            foreach ($tags as $k => $tag) {
                if ($k == 0) {
                    $sql .= " AND t1.tag LIKE '%$tag%'";
                } else {
                    $sql .= " OR t1.tag LIKE '%$tag%'";
                }
            }
        }

        $moreProduct = DB::select($sql . " ORDER BY t1.id DESC");
        return view('product_detail', compact('product', 'categories', 'moreProduct'));
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

        // Orders with count
        $orders = DB::table('orders as o')
            ->join(DB::raw('
                (SELECT order_id, COUNT(*) as order_count 
                 FROM orders 
                 GROUP BY order_id) as t
            '), 'o.order_id', '=', 't.order_id')
            ->where('o.buyer_id', $user_id)
            ->orderBy('o.id', 'desc')
            ->select('o.*', 't.order_count')
            ->get();

        // Addresses
        $addresses = BuyersAddress::where('buyer_id', $user_id)->get();

        return view('myaccount', compact('buyer', 'orders', 'addresses'));
    }
    public function storeAddress(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'phone' => 'required',
            'locality' => 'required',
            'street_name' => 'required',
            'city' => 'required',
            'landmark' => 'required',
            'type' => 'required'
        ]);

        BuyersAddress::create([
            'buyer_id' => Auth::id(),
            'name' => $request->name,
            'phone' => $request->phone,
            'locality' => $request->locality,
            'street_name' => $request->street_name,
            'landmark' => $request->landmark,
            'city' => $request->city,
            'type' => $request->type
        ]);

        return back()->with('success', 'Address added successfully');
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

        return view('checkout', compact('buyer', 'addresses', 'cart_items', 'cart_total', 'delivery_charge', 'hub_id'));
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
            foreach ($cart_items as $item) {
                DB::table('orders')->insert([
                    'order_id' => $order_id,
                    'buyer_id' => $buyer_id,
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                    'address_id' => $address_id,
                    'payment_type' => $request->payment_type,
                    'delivery_type' => $request->delivery_type,
                    'delivery_charge' => $request->delivery_charge,
                    'schedule_date' => $request->schedule_date,
                    'schedule_time' => $request->schedule_time,
                    'hub_id' => $request->hub_id,
                    'total_amount' => $total_amount,
                    'order_status' => 'Order Pending',
                    'date_added' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                // Update stock
                DB::table('inventory')
                    ->where('product_id', $item->product_id)
                    ->where('hub_id', $request->hub_id)
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
