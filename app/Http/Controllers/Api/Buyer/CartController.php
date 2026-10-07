<?php

namespace App\Http\Controllers\Api\Buyer;

use App\Exceptions\ApiException;
use App\Support\Pricing;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CartController extends BuyerController
{
    /** /api/add_cart — was api/add_cart.php (quantity 0 removes the product) */
    public function store(Request $request): JsonResponse
    {
        $this->requireFields($request, ['product_id', 'quantity'], 'product_id,quantity required');

        $productId = $request->input('product_id');
        $buyerId   = $this->ownBuyerId($request);
        $quantity  = $request->input('quantity');

        $message = DB::transaction(function () use ($productId, $buyerId, $quantity) {
            $line = DB::table('cart')->where('product_id', $productId)->where('buyer_id', $buyerId);

            if ($quantity == 0) {
                if (! $line->delete()) {
                    throw new ApiException('something went wrong');
                }
                DB::table('buyers')->where('id', $buyerId)->decrement('cart_count');

                return 'Deleted successfull';
            }

            // Same rule as the web cart (HomeController): main_price, falling back to MRP.
            $product = DB::table('products')->where('id', $productId)->first(['main_price', 'MRP']);
            $price = $product ? ((float) $product->main_price > 0 ? (float) $product->main_price : (float) $product->MRP) : 0;
            $totalPrice = $price * $quantity;

            if ($line->exists()) {
                if (! $line->update(['quantity' => $quantity, 'total_price' => $totalPrice])) {
                    throw new ApiException('something went wrong');
                }

                return 'updated successfull';
            }

            DB::table('cart')->insert(['product_id' => $productId, 'buyer_id' => $buyerId, 'quantity' => $quantity, 'total_price' => $totalPrice]);
            DB::table('buyers')->where('id', $buyerId)->increment('cart_count');

            return 'Added successfull';
        });

        $cartCount = DB::table('buyers')->where('id', $buyerId)->value('cart_count') ?? '';

        return $this->respond(['result' => true, 'message' => $message, 'cart_count' => $cartCount]);
    }

    /** /api/cart — was api/cart.php */
    public function index(Request $request): JsonResponse
    {
        $products = DB::table('cart as t1')
            ->join('products as t2', 't2.id', '=', 't1.product_id')
            ->join('items as t3', 't3.id', '=', 't2.item_id')
            ->where('t1.buyer_id', $this->ownBuyerId($request))
            ->select('t1.quantity', 't3.item as title', 't2.*', 't3.image as item_image')
            ->get();

        if ($products->isEmpty()) {
            throw new ApiException('No product found');
        }

        $rows = $products->map(function ($row) {
            $row = (array) $row;
            $row['image'] = $this->uploadsUrl('images/products', $row['item_image']);
            unset($row['item_image']);
            $row['percent_off'] = Pricing::priceRatio($row);

            return $row;
        })->all();

        return $this->respond(['result' => true, 'message' => 'success', 'products' => $rows]);
    }

    /** /api/empty_cart — was api/empty_cart.php */
    public function destroy(Request $request): JsonResponse
    {
        $buyerId = $this->ownBuyerId($request);

        DB::transaction(function () use ($buyerId) {
            DB::table('cart')->where('buyer_id', $buyerId)->delete();
            DB::table('buyers')->where('id', $buyerId)->update(['cart_count' => 0]);
        });

        return $this->respond(['result' => true, 'message' => 'Cart empty successfully']);
    }
}
