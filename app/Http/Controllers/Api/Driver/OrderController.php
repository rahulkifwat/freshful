<?php

namespace App\Http\Controllers\Api\Driver;

use App\Exceptions\ApiException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class OrderController extends DriverController
{
    private const STATUSES = ['Order Pending', 'Order Cancel', 'Order Placed', 'Order Processed', 'Order Shipped', 'Order Delivered', 'Order Dispatched'];

    /** /api/driver/orders/driver-orders — rider's orders grouped into today / weekly / monthly */
    public function index(Request $request): JsonResponse
    {
        $orders = DB::table('orders')->where('rider_id', $this->driverId($request))->orderByDesc('date_added')->get();

        $now = Carbon::now(self::TZ);
        $today = $now->toDateString();
        $weekStart = $now->copy()->startOfWeek(Carbon::MONDAY)->toDateString();
        $monthStart = $now->copy()->startOfMonth()->toDateString();

        $grouped = ['today' => [], 'weekly' => [], 'monthly' => []];
        foreach ($orders as $order) {
            if (! $order->date_added) {
                continue;
            }
            $date = Carbon::parse($order->date_added)->toDateString();
            if ($date === $today) {
                $grouped['today'][] = (array) $order;
            }
            if ($date >= $weekStart) {
                $grouped['weekly'][] = (array) $order;
            }
            if ($date >= $monthStart) {
                $grouped['monthly'][] = (array) $order;
            }
        }

        return $this->respond(['result' => true, 'message' => 'success', 'orders' => $grouped]);
    }

    /** /api/driver/orders/order-details?order_id= */
    public function show(Request $request): JsonResponse
    {
        $orderId = $this->requireFilled($request, 'order_id');

        $order = $this->ordersWithDetails()
            ->leftJoin('products as p', 'p.id', '=', 'o.product_id')
            ->addSelect('p.product_name', 'p.product_image', 'p.product_unit', 'p.main_price', 'p.MRP')
            ->where('o.order_id', $orderId)
            ->first();

        if (! $order) {
            throw new ApiException('Order not found');
        }

        return $this->respond([
            'result'  => true,
            'message' => 'success',
            'order'   => [
                'order_id'          => $order->order_id,
                'total_amount'      => $order->total_amount,
                'amount_to_collect' => $order->amount_to_collect,
                'payment_type'      => $order->payment_type,
                'payment_status'    => $order->payment_status,
                'order_status'      => $order->order_status,
                'schedule_date'     => $order->schedule_date,
                'schedule_time'     => $order->schedule_time,
                'quantity'          => $order->quantity,
                'date_added'        => $order->date_added,
                'buyer'             => ['name' => $order->buyer_name, 'phone' => $order->buyer_phone],
                'address'           => [
                    'name'        => $order->address_name,
                    'phone'       => $order->address_phone,
                    'street_name' => $order->street_name,
                    'locality'    => $order->locality,
                    'landmark'    => $order->landmark,
                    'city'        => $order->city,
                    'user_lat'    => $order->user_lat,
                    'user_lng'    => $order->user_lng,
                ],
                'hub'     => ['hub' => $order->hub_name, 'hub_lat' => $order->hub_lat, 'hub_lng' => $order->hub_lng],
                'product' => [
                    'product_name'  => $order->product_name,
                    'product_image' => $this->uploadsUrl('images/products', $order->product_image),
                    'product_unit'  => $order->product_unit,
                    'main_price'    => $order->main_price,
                    'MRP'           => $order->MRP,
                ],
            ],
        ]);
    }

    /** /api/driver/orders/orders-by-status?status= */
    public function byStatus(Request $request): JsonResponse
    {
        $status = $this->requireFilled($request, 'status');
        if (! in_array($status, self::STATUSES, true)) {
            throw new ApiException('Invalid status. Allowed: '.implode(', ', self::STATUSES));
        }

        $orders = $this->rows($this->ordersWithDetails()
            ->where('o.rider_id', $this->driverId($request))
            ->where('o.order_status', $status)
            ->orderByDesc('o.date_added')
            ->get());

        return $this->respond(['result' => true, 'message' => 'success', 'orders' => $orders, 'count' => count($orders)]);
    }

    private function ordersWithDetails()
    {
        return DB::table('orders as o')
            ->leftJoin('buyers as b', 'b.id', '=', 'o.buyer_id')
            ->leftJoin('buyer_address as ba', 'ba.id', '=', 'o.address_id')
            ->leftJoin('hubs as h', 'h.id', '=', 'o.hub_id')
            ->select(
                'o.*',
                'b.name as buyer_name', 'b.phone as buyer_phone',
                'ba.name as address_name', 'ba.phone as address_phone', 'ba.street_name', 'ba.locality',
                'ba.landmark', 'ba.city', 'ba.user_lat', 'ba.user_lng',
                'h.hub as hub_name', 'h.hub_lat', 'h.hub_lng'
            );
    }
}
