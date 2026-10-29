<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PosController extends Controller
{
    private function posUser()
    {
        return Auth::guard('pos_users')->user();
    }

    private function hubId()
    {
        return $this->posUser()->hub_id ?? 0;
    }

    private function orderCounts()
    {
        if (!Schema::hasTable('orders')) return [];
        $hub = $this->hubId();
        return [
            'new_count'        => DB::table('orders')->where('hub_id', $hub)->where('order_status', 'Order Pending')->count(),
            'accepted_count'   => DB::table('orders')->where('hub_id', $hub)->where('order_status', 'Order Accepted')->count(),
            'ongoing_count'    => DB::table('orders')->where('hub_id', $hub)->whereIn('order_status', ['Order Shipped', 'Order Processed'])->count(),
            'completed_count'  => DB::table('orders')->where('hub_id', $hub)->where('order_status', 'Order Delivered')->count(),
            'cancelled_count'  => DB::table('orders')->where('hub_id', $hub)->where('order_status', 'Order Cancel')->count(),
            'unsettled_count'  => DB::table('orders')->where('hub_id', $hub)->where('payment_status', 'pending')->where('order_status', 'Order Delivered')->count(),
        ];
    }

    public function dashboard()
    {
        $pos_user = $this->posUser();
        $hub_id   = $this->hubId();
        $hub = null; $city = null;
        if (Schema::hasTable('hubs')) {
            $hub = DB::table('hubs')->leftJoin('cities', 'cities.id', '=', 'hubs.city_id')
                ->select('hubs.*', 'cities.city as city_name')
                ->where('hubs.id', $hub_id)->first();
        }
        $counts = $this->orderCounts();
        return view('pos.dashboard', compact('pos_user', 'hub', 'counts'));
    }

    public function newOrders()
    {
        $hub_id = $this->hubId();
        $orders = collect();
        if (Schema::hasTable('orders')) {
            $q = DB::table('orders')
                ->join('buyers', 'buyers.id', '=', 'orders.buyer_id')
                ->join('buyer_address', 'buyer_address.id', '=', 'orders.address_id')
                ->join('hubs', 'hubs.id', '=', 'orders.hub_id')
                ->select('orders.*', 'buyers.name', 'buyer_address.phone', 'hubs.hub')
                ->where('orders.order_status', 'Order Pending')
                ->where('orders.hub_id', $hub_id);
            if (request('date_from')) $q->whereDate('orders.date_added', '>=', request('date_from'));
            if (request('date_to'))   $q->whereDate('orders.date_added', '<=', request('date_to'));
            $orders = $q->orderByDesc('orders.date_added')->get();
        }
        $counts = $this->orderCounts();
        return view('pos.new_orders', compact('orders', 'counts'));
    }

    public function acceptedOrders()
    {
        $hub_id = $this->hubId();
        $orders = collect();
        if (Schema::hasTable('orders')) {
            $q = DB::table('orders')
                ->join('buyers', 'buyers.id', '=', 'orders.buyer_id')
                ->join('buyer_address', 'buyer_address.id', '=', 'orders.address_id')
                ->join('hubs', 'hubs.id', '=', 'orders.hub_id')
                ->select('orders.*', 'buyers.name', 'buyer_address.phone', 'hubs.hub')
                ->where('orders.order_status', 'Order Accepted')
                ->where('orders.hub_id', $hub_id);
            if (request('date_from')) $q->whereDate('orders.date_added', '>=', request('date_from'));
            if (request('date_to'))   $q->whereDate('orders.date_added', '<=', request('date_to'));
            $orders = $q->orderByDesc('orders.date_added')->get();
        }
        $counts = $this->orderCounts();
        return view('pos.accepted_orders', compact('orders', 'counts'));
    }

    public function ongoingOrders()
    {
        $hub_id = $this->hubId();
        $sla_breach = collect(); $on_time = collect();
        if (Schema::hasTable('orders')) {
            $base = DB::table('orders')
                ->join('buyers', 'buyers.id', '=', 'orders.buyer_id')
                ->join('buyer_address', 'buyer_address.id', '=', 'orders.address_id')
                ->join('hubs', 'hubs.id', '=', 'orders.hub_id')
                ->select('orders.*', 'buyers.name', 'buyer_address.phone', 'hubs.hub')
                ->whereIn('orders.order_status', ['Order Shipped', 'Order Processed'])
                ->where('orders.hub_id', $hub_id);

            $all_ongoing = $base->get();
            $now = now();
            foreach ($all_ongoing as $order) {
                $added = \Carbon\Carbon::parse($order->date_added);
                $diff  = $added->diffInMinutes($now);
                if ($diff > 90) {
                    $sla_breach->push($order);
                } else {
                    $on_time->push($order);
                }
            }
        }
        $counts = $this->orderCounts();
        return view('pos.ongoing_orders', compact('sla_breach', 'on_time', 'counts'));
    }

    public function completedOrders()
    {
        $hub_id = $this->hubId();
        $orders = collect();
        if (Schema::hasTable('orders')) {
            $q = DB::table('orders')
                ->join('buyers', 'buyers.id', '=', 'orders.buyer_id')
                ->join('buyer_address', 'buyer_address.id', '=', 'orders.address_id')
                ->join('hubs', 'hubs.id', '=', 'orders.hub_id')
                ->select('orders.*', 'buyers.name', 'buyer_address.phone', 'hubs.hub')
                ->where('orders.order_status', 'Order Delivered')
                ->where('orders.hub_id', $hub_id);
            if (request('date_from')) $q->whereDate('orders.date_added', '>=', request('date_from'));
            if (request('date_to'))   $q->whereDate('orders.date_added', '<=', request('date_to'));
            $orders = $q->orderByDesc('orders.date_added')->get();
        }
        $counts = $this->orderCounts();
        return view('pos.completed_orders', compact('orders', 'counts'));
    }

    public function cancelledOrders()
    {
        $hub_id = $this->hubId();
        $orders = collect();
        if (Schema::hasTable('orders')) {
            $q = DB::table('orders')
                ->join('buyers', 'buyers.id', '=', 'orders.buyer_id')
                ->join('buyer_address', 'buyer_address.id', '=', 'orders.address_id')
                ->join('hubs', 'hubs.id', '=', 'orders.hub_id')
                ->select('orders.*', 'buyers.name', 'buyer_address.phone', 'hubs.hub')
                ->where('orders.order_status', 'Order Cancel')
                ->where('orders.hub_id', $hub_id);
            if (request('date_from')) $q->whereDate('orders.date_added', '>=', request('date_from'));
            if (request('date_to'))   $q->whereDate('orders.date_added', '<=', request('date_to'));
            $orders = $q->orderByDesc('orders.date_added')->get();
        }
        $counts = $this->orderCounts();
        return view('pos.cancelled_orders', compact('orders', 'counts'));
    }

    public function unsettledInvoices()
    {
        $hub_id = $this->hubId();
        $orders = collect();
        if (Schema::hasTable('orders')) {
            $orders = DB::table('orders')
                ->join('buyers', 'buyers.id', '=', 'orders.buyer_id')
                ->join('buyer_address', 'buyer_address.id', '=', 'orders.address_id')
                ->join('hubs', 'hubs.id', '=', 'orders.hub_id')
                ->select('orders.*', 'buyers.name', 'buyer_address.phone', 'hubs.hub')
                ->where('orders.payment_status', 'pending')
                ->where('orders.order_status', 'Order Delivered')
                ->where('orders.hub_id', $hub_id)
                ->orderByDesc('orders.updated_at')->get();
        }
        $counts = $this->orderCounts();
        return view('pos.unsettled_invoices', compact('orders', 'counts'));
    }

    public function processingOrderView($order_id)
    {
        $hub_id = $this->hubId();
        $order_items = collect(); $buyer = null; $address = null;
        $order_status = $total_amount = $payment_status = $payment_type = '';
        $delivery_charge = $delivery_type = $date_added = '';
        $promo_code = 'No coupons applied'; $discount = 0;

        if (Schema::hasTable('orders')) {
            $order_items = DB::table('orders')
                ->join('products', 'products.id', '=', 'orders.product_id')
                ->join('items', 'items.id', '=', 'products.item_id')
                ->select('orders.*', 'products.product_unit', 'products.unit_quantity', 'products.main_price', 'products.MRP', 'items.item')
                ->where('orders.order_id', $order_id)
                ->where('orders.hub_id', $hub_id)
                ->get();

            if ($order_items->isNotEmpty()) {
                $first = $order_items->first();
                $order_status    = $first->order_status;
                $total_amount    = $first->total_amount;
                $delivery_charge = $first->delivery_charge;
                $payment_status  = $first->payment_status;
                $payment_type    = $first->payment_type;
                $date_added      = $first->date_added;
                $delivery_type   = $first->delivery_type;

                $buyer   = DB::table('buyers')->where('id', $first->buyer_id)->first();
                $address = DB::table('buyer_address')->where('id', $first->address_id)->first();

                $promo = DB::table('home_promotions')
                    ->join('buyer_coupon_relation', 'buyer_coupon_relation.promo_id', '=', 'home_promotions.id')
                    ->select('home_promotions.title', 'home_promotions.percentage', 'buyer_coupon_relation.discount')
                    ->where('buyer_coupon_relation.user_id', $first->buyer_id)
                    ->where('buyer_coupon_relation.order_id', $order_id)
                    ->first();

                if ($promo) {
                    $promo_code = $promo->title;
                    $discount   = round($promo->discount, 2);
                }
            }
        }

        $counts = $this->orderCounts();
        return view('pos.processing_order_view', compact('order_id', 'order_items', 'buyer', 'address', 'order_status', 'total_amount', 'delivery_charge', 'payment_status', 'payment_type', 'date_added', 'delivery_type', 'promo_code', 'discount', 'counts'));
    }

    public function settleOrder($order_id)
    {
        $hub_id = $this->hubId();
        $order_items = collect(); $buyer = null; $address = null;
        $order_status = $total_amount = $payment_status = $payment_type = '';
        $delivery_charge = $delivery_type = $date_added = '';
        $amount_to_collect = $amount_to_return = 0;
        $promo_code = 'No coupons applied'; $discount = 0;

        if (Schema::hasTable('orders')) {
            $order_items = DB::table('orders')
                ->join('products', 'products.id', '=', 'orders.product_id')
                ->join('items', 'items.id', '=', 'products.item_id')
                ->select('orders.*', 'products.product_unit', 'products.unit_quantity', 'products.main_price', 'products.MRP', 'items.item')
                ->where('orders.order_id', $order_id)
                ->where('orders.hub_id', $hub_id)
                ->get();

            if ($order_items->isNotEmpty()) {
                $first = $order_items->first();
                $order_status      = $first->order_status;
                $total_amount      = $first->total_amount;
                $delivery_charge   = $first->delivery_charge;
                $payment_status    = $first->payment_status;
                $payment_type      = $first->payment_type;
                $date_added        = $first->date_added;
                $delivery_type     = $first->delivery_type;
                $amount_to_collect = $first->amount_to_collect ?? 0;
                $amount_to_return  = $first->amount_to_return ?? 0;

                $buyer   = DB::table('buyers')->where('id', $first->buyer_id)->first();
                $address = DB::table('buyer_address')->where('id', $first->address_id)->first();

                $promo = DB::table('home_promotions')
                    ->join('buyer_coupon_relation', 'buyer_coupon_relation.promo_id', '=', 'home_promotions.id')
                    ->select('home_promotions.title', 'home_promotions.percentage', 'buyer_coupon_relation.discount')
                    ->where('buyer_coupon_relation.user_id', $first->buyer_id)
                    ->where('buyer_coupon_relation.order_id', $order_id)
                    ->first();

                if ($promo) {
                    $promo_code = $promo->title;
                    $discount   = round($promo->discount, 2);
                }
            }
        }

        $counts = $this->orderCounts();
        return view('pos.settle_order', compact('order_id', 'order_items', 'buyer', 'address', 'order_status', 'total_amount', 'delivery_charge', 'payment_status', 'payment_type', 'date_added', 'delivery_type', 'amount_to_collect', 'amount_to_return', 'promo_code', 'discount', 'counts'));
    }

    public function invoice($order_id)
    {
        $hub_id = $this->hubId();
        $order_items = collect(); $buyer = null; $address = null;
        $total_amount = $delivery_charge = $payment_type = $date_added = $delivery_type = '';
        $promo_code = 'No coupons applied'; $discount = 0;

        if (Schema::hasTable('orders')) {
            $order_items = DB::table('orders')
                ->join('products', 'products.id', '=', 'orders.product_id')
                ->join('items', 'items.id', '=', 'products.item_id')
                ->select('orders.*', 'products.product_unit', 'products.unit_quantity', 'products.main_price', 'products.MRP', 'items.item')
                ->where('orders.order_id', $order_id)
                ->where('orders.hub_id', $hub_id)
                ->get();

            if ($order_items->isNotEmpty()) {
                $first = $order_items->first();
                $total_amount    = $first->total_amount;
                $delivery_charge = $first->delivery_charge;
                $payment_type    = $first->payment_type;
                $date_added      = $first->date_added;
                $delivery_type   = $first->delivery_type;

                $buyer   = DB::table('buyers')->where('id', $first->buyer_id)->first();
                $address = DB::table('buyer_address')->where('id', $first->address_id)->first();

                $promo = DB::table('home_promotions')
                    ->join('buyer_coupon_relation', 'buyer_coupon_relation.promo_id', '=', 'home_promotions.id')
                    ->select('home_promotions.title', 'home_promotions.percentage', 'buyer_coupon_relation.discount')
                    ->where('buyer_coupon_relation.user_id', $first->buyer_id)
                    ->where('buyer_coupon_relation.order_id', $order_id)
                    ->first();

                if ($promo) { $promo_code = $promo->title; $discount = round($promo->discount, 2); }
            }
        }

        $counts = $this->orderCounts();
        return view('pos.invoice', compact('order_id', 'order_items', 'buyer', 'address', 'total_amount', 'delivery_charge', 'payment_type', 'date_added', 'delivery_type', 'promo_code', 'discount', 'counts'));
    }

    public function printInvoice($order_id)
    {
        $hub_id = $this->hubId();
        $order_items = collect(); $buyer = null; $address = null;
        $total_amount = $delivery_charge = $payment_type = $date_added = '';
        $promo_code = 'No coupons applied'; $discount = 0;

        if (Schema::hasTable('orders')) {
            $order_items = DB::table('orders')
                ->join('products', 'products.id', '=', 'orders.product_id')
                ->join('items', 'items.id', '=', 'products.item_id')
                ->select('orders.*', 'products.product_unit', 'products.unit_quantity', 'products.main_price', 'products.MRP', 'items.item')
                ->where('orders.order_id', $order_id)
                ->where('orders.hub_id', $hub_id)
                ->get();

            if ($order_items->isNotEmpty()) {
                $first = $order_items->first();
                $total_amount    = $first->total_amount;
                $delivery_charge = $first->delivery_charge;
                $payment_type    = $first->payment_type;
                $date_added      = $first->date_added;

                $buyer   = DB::table('buyers')->where('id', $first->buyer_id)->first();
                $address = DB::table('buyer_address')->where('id', $first->address_id)->first();

                $promo = DB::table('home_promotions')
                    ->join('buyer_coupon_relation', 'buyer_coupon_relation.promo_id', '=', 'home_promotions.id')
                    ->select('home_promotions.title', 'home_promotions.percentage', 'buyer_coupon_relation.discount')
                    ->where('buyer_coupon_relation.user_id', $first->buyer_id)
                    ->where('buyer_coupon_relation.order_id', $order_id)
                    ->first();

                if ($promo) { $promo_code = $promo->title; $discount = round($promo->discount, 2); }
            }
        }

        return view('pos.print_invoice', compact('order_id', 'order_items', 'buyer', 'address', 'total_amount', 'delivery_charge', 'payment_type', 'date_added', 'promo_code', 'discount'));
    }

    public function dayEndReport()
    {
        $hub_id    = $this->hubId();
        $hub       = Schema::hasTable('hubs') ? DB::table('hubs')->where('id', $hub_id)->first() : null;
        $date_from = request('date_from', date('Y-m-d'));
        $date_to   = request('date_to', date('Y-m-d'));

        $express_opening  = $express_received  = $express_pending  = $express_rejected  = 0;
        $scheduled_opening = $scheduled_received = $scheduled_pending = $scheduled_rejected = 0;

        if (Schema::hasTable('orders')) {
            $q = fn($type, $statuses) => DB::table('orders')
                ->where('hub_id', $hub_id)
                ->where('delivery_type', $type)
                ->whereIn('order_status', (array)$statuses)
                ->whereBetween(DB::raw('DATE(date_added)'), [$date_from, $date_to]);

            $express_opening   = $q('Express', ['Order Pending', 'Order Placed'])->count();
            $express_received  = $q('Express', 'Order Delivered')->count();
            $express_pending   = $q('Express', ['Order Shipped', 'Order Processed'])->count();
            $express_rejected  = $q('Express', 'Order Cancel')->count();

            $scheduled_opening  = $q('Scheduled', ['Order Pending', 'Order Placed'])->count();
            $scheduled_received = $q('Scheduled', 'Order Delivered')->count();
            $scheduled_pending  = $q('Scheduled', ['Order Shipped', 'Order Processed'])->count();
            $scheduled_rejected = $q('Scheduled', 'Order Cancel')->count();
        }

        $counts = $this->orderCounts();
        return view('pos.day_end_report', compact(
            'hub', 'date_from', 'date_to',
            'express_opening', 'express_received', 'express_pending', 'express_rejected',
            'scheduled_opening', 'scheduled_received', 'scheduled_pending', 'scheduled_rejected',
            'counts'
        ));
    }

    // AJAX: update order status
    public function updateOrderStatus(Request $request)
    {
        if (!Schema::hasTable('orders')) {
            return response()->json(['result' => false, 'message' => 'Orders table not found.']);
        }

        $updated = DB::table('orders')
            ->where('order_id', $request->order_id)
            ->update([
                'order_status'      => $request->order_status,
                'updated_at'        => now(),
                'amount_to_collect' => $request->amount_to_collect ?? null,
                'amount_to_return'  => $request->amount_to_return ?? null,
            ]);

        if ($updated) {
            return response()->json(['result' => true, 'message' => 'Status updated successfully.']);
        }
        return response()->json(['result' => false, 'message' => 'Something went wrong.']);
    }
}
