<?php

namespace __NS__;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** "Notify me" on an out-of-stock item: saves one request per customer and product. */
class StockAlertController extends __PARENT__
{
    public function store(Request $request)
    {
        try {
            $buyer = null;
            foreach ([
                fn () => $request->attributes->get('buyer_id'),
                fn () => ($b = $request->attributes->get('buyer')) ? (is_object($b) ? ($b->id ?? null) : (is_array($b) ? ($b['id'] ?? null) : $b)) : null,
                fn () => $request->user() ? ($request->user()->id ?? null) : null,
                fn () => auth()->id(),
                fn () => $request->get('buyer_id'),
            ] as $try) {
                try { $buyer = $try(); } catch (\Throwable $e) { $buyer = null; }
                if ($buyer) { break; }
            }
            if (! $buyer) {
                return response()->json(['result' => false, 'message' => 'Please sign in again.', 'code' => 'unauthorized']);
            }
            $pid = (int) $request->input('product_id');
            if ($pid < 1 || ! Schema::hasTable('app_stock_alerts')) {
                return response()->json(['result' => false, 'message' => 'Could not save this right now.']);
            }
            $hub = $request->input('hub_id');
            DB::table('app_stock_alerts')->updateOrInsert(
                ['buyer_id' => (int) $buyer, 'product_id' => $pid],
                ['hub_id' => $hub ? (int) $hub : null, 'notified_at' => null, 'updated_at' => now(), 'created_at' => now()]
            );

            return response()->json(['result' => true, 'message' => 'Done. We will tell you when it is back in stock.']);
        } catch (\Throwable $e) {
            return response()->json(['result' => false, 'message' => 'Could not save this right now. Please try again.']);
        }
    }
}
