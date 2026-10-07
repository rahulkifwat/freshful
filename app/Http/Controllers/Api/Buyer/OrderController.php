<?php

namespace App\Http\Controllers\Api\Buyer;

use App\Exceptions\ApiException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends BuyerController
{
    /**
     * /api/add_order — was api/add_order.php
     * product_id and quantity are comma separated; one orders row per product, all sharing one order_id.
     */
    public function store(Request $request): JsonResponse
    {
        $this->requireFields(
            $request,
            ['hub_id', 'product_id', 'address_id', 'total_amount', 'quantity'],
            'hub_id,product_id(comma saperate),address_id,total_amount,quantity(comma saperate),{payment_type(o) as Card,Cash,Wallet},{delivery_type(o) as Scheduled,Express},schedule_date(o),schedule_time(o) is required'
        );

        $productIds = explode(',', rtrim((string) $request->input('product_id'), ', '));
        $quantities = explode(',', (string) $request->input('quantity'));
        $buyerId = $this->ownBuyerId($request);
        if (! DB::table('buyer_address')->where('id', $request->input('address_id'))->where('buyer_id', $buyerId)->exists()) {
            throw new ApiException('Address not found');
        }

        // Server-controlled fields are never taken from the request.
        $base = ['buyer_id' => $buyerId] + $this->onlyColumns('orders', $request->all(), [
            'id', 'order_id', 'buyer_id', 'rider_id', 'payment_status', 'payment_id', 'txn_id', 'order_status',
            'amount_to_return', 'order_complete_time', 'processing_complete_time', 'shipping_complete_time', 'delivery_complete_time',
        ]);

        DB::transaction(function () use ($productIds, $quantities, $base) {
            // Lock the latest order so two checkouts can't get the same order_id.
            $last = DB::table('orders')->orderByDesc('id')->lockForUpdate()->value('order_id');
            $orderId = $last ? $this->increment($last) : 'ORD200000';

            foreach ($productIds as $i => $productId) {
                DB::table('orders')->insert(array_merge($base, [
                    'order_id'   => $orderId,
                    'product_id' => $productId,
                    'quantity'   => $quantities[$i] ?? null,
                ]));
            }
        });

        return $this->respond(['result' => true, 'message' => 'order added successfully']);
    }

    /** /api/get_my_orders — was api/get_my_orders.php (one summary row per order) */
    public function index(Request $request): JsonResponse
    {
        $orderIds = DB::table('orders')
            ->where('buyer_id', $this->ownBuyerId($request))
            ->groupBy('order_id')
            ->orderByRaw('MAX(id) DESC')
            ->pluck('order_id');

        $orders = [];
        foreach ($orderIds as $orderId) {
            $lines = $this->orderLines($orderId)->select('t1.*', 't3.image as item_image', 't3.item as title');

            $first = $lines->clone()->orderByDesc('t1.id')->first();
            if (! $first) {
                continue;
            }

            $row = (array) $first;
            $row['image'] = $this->uploadsUrl('images/products', $row['item_image']);
            unset($row['item_image']);
            $row['product_count'] = $lines->clone()->count();
            $orders[] = $row;
        }

        return $this->respond($orders
            ? ['result' => true, 'message' => 'success', 'orders' => $orders]
            : ['result' => false, 'message' => 'No order found', 'orders' => []]);
    }

    /** /api/get_order_details — was api/get_order_details.php */
    public function show(Request $request): JsonResponse
    {
        $this->requireFields($request, ['order_id'], 'order_id required');

        // Same column order as legacy (t1.*, then t2.*): product columns win on name clashes.
        $lines = $this->orderLines($request->input('order_id'))
            ->where('t1.buyer_id', $this->ownBuyerId($request))
            ->select('t1.id as main_id', 't1.*', 't2.*', 't3.image as item_image', 't3.item as title')
            ->orderByDesc('t1.id')
            ->get();

        if ($lines->isEmpty()) {
            return $this->respond(['result' => false, 'message' => 'No order found', 'orders' => []]);
        }

        $first = (array) $lines->first();
        $totalMrp = 0;
        $products = [];

        foreach ($lines as $line) {
            $line = (array) $line;
            $line['image'] = $this->uploadsUrl('images/products', $line['item_image']);
            unset($line['item_image'], $line['order_id'], $line['address_id']);
            $products[] = $line;
            $totalMrp += (float) $line['MRP'] * (float) $line['quantity'];
        }

        $data = [
            'product'        => $products,
            'address'        => $this->rows(DB::table('buyer_address')->where('id', $first['address_id'])->get()),
            'mrp_amount'     => (string) $totalMrp,
            'total_amount'   => $first['total_amount'],
            'order_status'   => $first['order_status'],
            'payment_status' => $first['payment_status'],
            'payment_type'   => $first['payment_type'],
        ];

        return $this->respond(['result' => true, 'message' => 'success', 'orders' => $data]);
    }

    private function orderLines(string $orderId)
    {
        return DB::table('orders as t1')
            ->join('products as t2', 't2.id', '=', 't1.product_id')
            ->join('items as t3', 't3.id', '=', 't2.item_id')
            ->where('t1.order_id', $orderId);
    }

    /** Legacy strplusone(): ORD200009 -> ORD200010 (keeps zero padding). */
    private function increment(string $code): string
    {
        return preg_replace_callback('/\d+/', fn ($m) => sprintf('%0'.strlen($m[0]).'d', $m[0] + 1), $code);
    }
}
