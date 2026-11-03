<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Services\Storefront\HomeV2Data;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

// "Notify me" on an out-of-stock product: saves one row per customer, product and store.
class StockAlertController extends Controller
{
    public function store(Request $request, HomeV2Data $data)
    {
        if (! Auth::check()) {
            return response()->json(['result' => false, 'login' => true, 'message' => 'Please log in to get notified']);
        }

        $productId = (int) $request->input('product_id');
        if ($productId <= 0 || ! DB::table('products')->where('id', $productId)->exists()) {
            return response()->json(['result' => false, 'message' => 'Product not found'], 422);
        }

        if (! HomeV2Data::hasStockAlertsTable()) {
            return response()->json(['result' => false, 'message' => 'Please try again later'], 503);
        }

        try {
            DB::table('storefront_stock_alerts')->insertOrIgnore([
                'buyer_id' => (int) Auth::id(),
                'product_id' => $productId,
                'hub_id' => $data->hubId($request),
                'created_at' => now(),
            ]);
        } catch (Throwable $e) {
            Log::warning('[storefront] stock alert failed: '.$e->getMessage());

            return response()->json(['result' => false, 'message' => 'Please try again later'], 500);
        }

        return response()->json(['result' => true, 'message' => 'We will let you know when it is back']);
    }
}
