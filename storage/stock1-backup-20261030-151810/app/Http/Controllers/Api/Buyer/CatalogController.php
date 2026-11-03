<?php

namespace App\Http\Controllers\Api\Buyer;

use App\Exceptions\ApiException;
use App\Support\Pricing;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class CatalogController extends BuyerController
{
    /** /api/get_banner_images — was api/get_banner_images.php */
    public function banners(): JsonResponse
    {
        return $this->bannerResponse('banner_images');
    }

    /** /api/get_app_banner_images — was api/get_app_banner_images.php */
    public function appBanners(): JsonResponse
    {
        return $this->bannerResponse('app_banner_images');
    }

    /** /api/product_tags — was api/product_tags.php */
    public function tags(): JsonResponse
    {
        return $this->listResponse($this->rows(DB::table('product_tags')->get()));
    }

    /** /api/category — was api/category.php */
    public function categories(): JsonResponse
    {
        $rows = DB::table('categories')->where('status', 'Active')->get()->map(function ($row) {
            $row = (array) $row;
            $row['image'] = $this->uploadsUrl('images', $row['image']);

            return $row;
        })->all();

        return $this->listResponse($rows);
    }

    /** /api/get_cities — was api/get_cities.php */
    public function cities(): JsonResponse
    {
        return $this->listResponse($this->rows(DB::table('cities')->where('status', 1)->get()));
    }

    /** /api/get_nearest_hub — was api/get_nearest_hub.php (distance in miles) */
    public function nearestHub(Request $request): JsonResponse
    {
        $this->requireFields($request, ['lat', 'lng'], 'lat,lng required');
        $lat = (float) $request->input('lat');
        $lng = (float) $request->input('lng');

        $hub = DB::select(
            'SELECT t2.city, t1.id, t1.hub,
                (3959 * acos(cos(radians(?)) * cos(radians(hub_lat)) * cos(radians(hub_lng) - radians(?))
                    + sin(radians(?)) * sin(radians(hub_lat)))) AS distance
             FROM hubs t1
             INNER JOIN cities t2 ON t2.id = t1.city_id
             WHERE t1.status = 1
             ORDER BY distance LIMIT 1',
            [$lat, $lng, $lat]
        );

        return $this->listResponse($this->rows($hub));
    }

    /** /api/get_products_by_tag — was api/get_products_by_tag.php (10 per page) */
    public function productsByTag(Request $request): JsonResponse
    {
        $this->requireFields($request, ['tag'], 'tag,page required');

        $perPage = 10;
        $page = max(1, (int) $request->query('page', 1));

        $query = $this->productQuery('t2.image')->where('t1.tag', $request->input('tag'));

        // Public route: hide products already in the cart only for a logged-in buyer.
        if ($buyer = $this->tokenBuyer()) {
            $inCart = DB::table('cart')->where('buyer_id', $buyer->id)->pluck('product_id');
            if ($inCart->isNotEmpty()) {
                $query->whereNotIn('t1.id', $inCart);
            }
        }

        $products = $query->offset(($page - 1) * $perPage)->limit($perPage)->get()
            ->map(function ($row) {
                $row = (array) $row;
                $row['image'] = $this->uploadsUrl('images/products', $row['image']);
                $row['percent_off'] = Pricing::priceRatio($row);

                return $row;
            })->all();

        return $this->respond($products
            ? ['result' => true, 'message' => 'success', 'products' => $products]
            : ['result' => false, 'message' => 'No product found', 'products' => []]);
    }

    /** /api/home_products — was api/home_products.php (products grouped by tag) */
    public function homeProducts(Request $request): JsonResponse
    {
        $this->requireFields($request, ['hub_id'], 'hub_id required');
        $buyerId = $this->ownBuyerId($request);
        $hubId = $request->input('hub_id');

        $tags = DB::table('product_tags')->orderByDesc('tag')->get();
        if ($tags->isEmpty()) {
            return $this->respond(['result' => false, 'message' => 'No product found', 'products' => []]);
        }

        $context = $this->buyerContext($buyerId, $hubId);
        $groups = [];

        foreach ($tags as $tag) {
            $products = $this->productQuery('t1.product_image')
                ->whereRaw('FIND_IN_SET(?, t1.tag)', [$tag->tag])
                ->get()
                ->map(function ($row) use ($context) {
                    $row = (array) $row;
                    $row['percent_off'] = Pricing::discount($row);

                    return $this->decorate($row, $context, withTimes: true);
                })->all();

            $groups[] = ['tag' => $tag->tag, 'tag_id' => $tag->id, 'xyz' => $products];
        }

        return $this->respond(['result' => true, 'message' => 'success', 'products' => $groups]);
    }

    /** /api/get_products_by_category — was api/get_products_by_category.php */
    public function productsByCategory(Request $request): JsonResponse
    {
        $this->requireFields($request, ['category_id'], 'category_id required');

        $cart = $this->cartQuantities($this->ownBuyerId($request));

        $products = $this->productQuery('t2.image')
            ->where('t2.cat_id', $request->input('category_id'))
            ->get()
            ->map(function ($row) use ($cart) {
                $row = (array) $row;
                $row['image'] = $this->uploadsUrl('images/products', $row['image']);
                $row['percent_off'] = Pricing::priceRatio($row);
                $row['quantity'] = $cart[$row['id']] ?? 0;

                return $row;
            })->all();

        return $this->respond($products
            ? ['result' => true, 'message' => 'success', 'products' => $products]
            : ['result' => false, 'message' => 'No product found', 'products' => []]);
    }

    /** /api/search_products — was api/search_products.php */
    public function search(Request $request): JsonResponse
    {
        $this->requireFields($request, ['search', 'hub_id'], 'search,hub_id required');

        $context = $this->buyerContext($this->ownBuyerId($request), $request->input('hub_id'));

        $products = $this->productQuery('t1.product_image')
            ->where('t2.item', 'like', '%'.$request->input('search').'%')
            ->get()
            ->map(function ($row) use ($context) {
                $row = (array) $row;
                $row['percent_off'] = Pricing::priceRatio($row);

                return $this->decorate($row, $context);
            })->all();

        if (! $products) {
            throw new ApiException('No search found');
        }

        return $this->respond(['result' => true, 'message' => 'success', 'search' => $products]);
    }

    private function bannerResponse(string $table): JsonResponse
    {
        $rows = DB::table($table)->select('id', 'title', 'image')->get()->map(fn ($row) => [
            'id'    => $row->id,
            'title' => $row->title,
            'image' => $this->uploadsUrl('images/banners', $row->image),
        ])->all();

        return $this->listResponse($rows);
    }

    private function listResponse(array $rows): JsonResponse
    {
        return $this->respond($rows
            ? ['result' => true, 'message' => 'data found', 'data' => $rows]
            : ['result' => false, 'message' => 'Data not found', 'data' => '']);
    }

    /**
     * products + item title + category name; "image" is built from $imageColumn
     * (some endpoints used the item image, others the product image).
     */
    private function productQuery(string $imageColumn): Builder
    {
        return DB::table('products as t1')
            ->join('items as t2', 't2.id', '=', 't1.item_id')
            ->join('category as t3', 't3.id', '=', 't2.cat_id')
            ->select('t1.*', "$imageColumn as image", 't2.item as title', 't3.name as cat_name');
    }

    /** Per-buyer lookups, loaded once instead of three queries per product. */
    private function buyerContext($buyerId, $hubId): array
    {
        return [
            'cart'     => $this->cartQuantities($buyerId),
            // Legacy matched wishlist and inventory on products.product_id, cart on products.id.
            'wishlist' => DB::table('wishlist')->where('buyer_id', $buyerId)
                ->selectRaw('product_id, COUNT(*) as total')->groupBy('product_id')->pluck('total', 'product_id'),
            'stock'    => DB::table('inventory')->where('hub_id', $hubId)->orderBy('id')
                ->get(['product_id', 'live_stock'])->unique('product_id')->pluck('live_stock', 'product_id'),
        ];
    }

    private function cartQuantities($buyerId)
    {
        return DB::table('cart')->where('buyer_id', $buyerId)->orderBy('id')
            ->get(['product_id', 'quantity'])->unique('product_id')->pluck('quantity', 'product_id');
    }

    private function decorate(array $row, array $context, bool $withTimes = false): array
    {
        $row['image'] = $this->uploadsUrl('images/products', $row['image']);
        $row['quantity'] = $context['cart'][$row['id']] ?? 0;
        $row['favorite_status'] = (int) ($context['wishlist'][$row['product_id']] ?? 0);
        $row['cart_staus'] = isset($context['cart'][$row['id']]) ? '1' : '0';
        $row['product_stock'] = ($context['stock'][$row['product_id']] ?? 0) == 0 ? 'Out of Stock' : 'Add to cart';

        if ($withTimes) {
            $now = Carbon::now(self::TZ);
            $row['current_time'] = $now->format('h:i a');
            $row['after_time'] = $now->copy()->addHour()->format('h:i a');
        }

        return $row;
    }
}
