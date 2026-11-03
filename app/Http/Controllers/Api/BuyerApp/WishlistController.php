<?php

namespace App\Http\Controllers\Api\BuyerApp;

use App\Services\Stock\StockGuard;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Favourites. The wishlist table keeps the PRODUCT CODE (products.product_id, e.g. PRD00161), exactly as the old app
 * and the catalog's favorite_status do, so the 79 existing favourites keep working.
 */
class WishlistController extends AppController
{
    /** GET /api/app/wishlist?hub_id=  (hub_id is optional: with it each product also says whether it is in stock) */
    public function index(Request $request): JsonResponse
    {
        $buyerId = $this->ownBuyerId($request);
        $hubId = (int) $request->input('hub_id', 0);

        // Newest favourite first, one entry per product even if it was saved twice.
        $codes = DB::table('wishlist')->where('buyer_id', $buyerId)
            ->select('product_id', DB::raw('MAX(id) as last_id'))
            ->groupBy('product_id')->orderByDesc('last_id')
            ->pluck('product_id')->map(fn ($c) => (string) $c)->all();

        if (! $codes) {
            return $this->ok(['count' => 0, 'products' => []]);
        }

        $rows = DB::table('products as p')
            ->join('items as i', 'i.id', '=', 'p.item_id')
            ->whereIn('p.product_id', $codes)
            ->where('p.status', 'active')
            ->select('p.id', 'p.product_id', 'p.product_name', 'p.product_unit', 'p.unit_quantity', 'p.MRP', 'p.main_price', 'p.off', 'i.item as title', 'i.image as item_image')
            ->orderBy('p.id')
            ->get()
            ->unique('product_id')
            ->keyBy('product_id');

        $stock = $hubId > 0 ? $this->stockByCode($hubId) : null;

        $products = [];
        foreach ($codes as $code) {
            $r = $rows[$code] ?? null;
            if (! $r) {
                continue; // product was switched off or removed
            }
            $item = [
                'id'              => (int) $r->id,
                'product_id'      => (string) $r->product_id,
                'title'           => (string) $r->title,
                'name'            => (string) $r->product_name,
                'unit'            => trim($r->unit_quantity . ' ' . $r->product_unit),
                'mrp'             => (float) $r->MRP,
                'price'           => (float) $r->main_price,
                'off'             => $r->off,
                'image'           => $this->uploadsUrl('images/products', $r->item_image),
                'favorite_status' => 1,
            ];
            if ($stock !== null) {
                $inStock = ((float) ($stock[$code] ?? 0)) > 0;
                $item['in_stock'] = $inStock;
                $item['product_stock'] = $inStock ? 'Add to cart' : 'Out of Stock';
            }
            $products[] = $item;
        }

        return $this->ok(['count' => count($products), 'products' => $products]);
    }

    /**
     * POST /api/app/wishlist/toggle {product_id, favorite?}
     * product_id is the product code (PRD00161); the numeric products.id is accepted too.
     * favorite=1 forces "saved", favorite=0 forces "not saved" (safe to retry); without it the state flips.
     */
    public function toggle(Request $request): JsonResponse
    {
        $buyerId = $this->ownBuyerId($request);
        $raw = trim((string) $request->input('product_id', ''));
        if ($raw === '' || strlen($raw) > 100) {
            return $this->fail('Product is required', 'product_required');
        }

        $product = DB::table('products')->where('product_id', $raw)->orderBy('id')->first();
        if (! $product && ctype_digit($raw)) {
            $product = DB::table('products')->where('id', (int) $raw)->first();
        }
        if (! $product || (string) $product->product_id === '') {
            return $this->fail('Product not found', 'product_not_found');
        }
        $code = (string) $product->product_id;

        $want = $request->has('favorite') ? filter_var($request->input('favorite'), FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE) : null;

        $state = DB::transaction(function () use ($buyerId, $code, $product, $want) {
            $ids = DB::table('wishlist')->where('buyer_id', $buyerId)->where('product_id', $code)->lockForUpdate()->pluck('id');
            $saved = $ids->isNotEmpty();
            $target = $want === null ? ! $saved : $want;

            if ($saved && ! $target) {
                DB::table('wishlist')->whereIn('id', $ids->all())->delete();
            } elseif (! $saved && $target) {
                DB::table('wishlist')->insert([
                    'buyer_id'     => $buyerId,
                    'product_id'   => $code,
                    'session_id'   => '',
                    'item_id'      => (string) $product->item_id,
                    'created_date' => date('Y-m-d H:i:s'),
                    'status'       => '1',
                ]);
            }

            return $target ? 1 : 0;
        });

        $count = DB::table('wishlist')->where('buyer_id', $buyerId)->distinct()->count('product_id');

        return $this->ok(['product_id' => $code, 'favorite_status' => $state, 'count' => $count],
            $state ? 'Added to favourites' : 'Removed from favourites');
    }

    /** [product code => live stock] for one hub, after open-order holds (same number the catalog shows). */
    private function stockByCode(int $hubId)
    {
        $live = DB::table('inventory')->where('hub_id', $hubId)->orderBy('id')
            ->get(['product_id', 'live_stock'])->unique('product_id')->pluck('live_stock', 'product_id');

        if (class_exists(StockGuard::class)) {
            $live = StockGuard::net($hubId, $live);
        }

        return $live instanceof \Illuminate\Support\Collection ? $live->all() : (array) $live;
    }
}
