<?php

namespace App\Http\Controllers\Api\BuyerApp;

use App\Services\BuyerApp\Catalog\Catalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Catalog for the app (read-only). Every call may carry ?hub_id= (the hub that serves the customer's address, from
 * service-area or the saved address) so stock is shown for that hub; without it stock is "unknown" (null).
 * Never returns cost_price.
 */
class CatalogController extends AppController
{
    private function hub(Request $request): ?int
    {
        $h = (int) $request->input('hub_id', 0);
        if ($h <= 0) {
            return null;
        }

        return DB::table('hubs')->where('id', $h)->whereRaw("LOWER(CAST(status AS CHAR)) IN ('active', '1')")->exists() ? $h : null;
    }

    private function snap(Request $request): array
    {
        $buyerId = $this->ownBuyerId($request);
        $hub = $this->hub($request);

        return [$buyerId, $hub, Catalog::snapshot($hub, $buyerId, fn ($f, $x) => $this->uploadsUrl($f, $x))];
    }

    private function cards(array $items): array
    {
        return array_map([Catalog::class, 'card'], array_values($items));
    }

    /** GET catalog/home */
    public function home(Request $request): JsonResponse
    {
        [$buyerId, $hub, $s] = $this->snap($request);
        $sections = [];
        $buyAgain = Catalog::buyAgain($buyerId, $s['items']);
        if ($buyAgain) {
            $sections[] = ['key' => 'buy-again', 'title' => 'Buy again', 'count' => count($buyAgain), 'items' => $this->cards($buyAgain)];
        }
        foreach (Catalog::sections($s['items']) as $sec) {
            $sections[] = ['key' => $sec['key'], 'title' => $sec['title'], 'count' => count($sec['items']), 'items' => $this->cards(array_slice($sec['items'], 0, 12))];
        }

        return $this->ok([
            'stock_known' => $hub !== null, 'hub_id' => $hub,
            'banners' => Catalog::banners(fn ($f, $x) => $this->uploadsUrl($f, $x)),
            'categories' => array_values($s['categories']),
            'sections' => $sections,
            'theme' => \App\Services\BuyerApp\AppHomeConfig::theme(),
            'blocks' => \App\Services\BuyerApp\AppHomeConfig::blocks(),
            'card' => \App\Services\BuyerApp\AppCardStyle::get(),
        ]);
    }

    /** GET catalog/categories */
    public function categories(Request $request): JsonResponse
    {
        [, $hub, $s] = $this->snap($request);

        return $this->ok(['stock_known' => $hub !== null, 'categories' => array_values($s['categories'])]);
    }

    /** GET catalog/categories/{id}/items */
    public function categoryItems(Request $request, string $id): JsonResponse
    {
        [, $hub, $s] = $this->snap($request);
        $cat = $s['categories'][(int) $id] ?? null;
        if (! $cat) {
            return $this->fail('Category not found', 'category_not_found');
        }
        $items = array_filter($s['items'], fn ($it) => $it['category_id'] === (int) $id);

        return $this->ok(['stock_known' => $hub !== null, 'category' => $cat, 'count' => count($items), 'items' => $this->cards($items)]);
    }

    /** GET catalog/items/{id} - one item with all packs, description, and "you may also like" from the same category */
    public function item(Request $request, string $id): JsonResponse
    {
        [, $hub, $s] = $this->snap($request);
        $it = $s['items'][(int) $id] ?? null;
        if (! $it) {
            return $this->fail('This item is not available right now.', 'item_not_found');
        }
        $related = array_filter($s['items'], fn ($o) => $o['category_id'] === $it['category_id'] && $o['item_id'] !== $it['item_id']);
        unset($it['_tags']);

        return $this->ok(['stock_known' => $hub !== null, 'item' => $it, 'related' => $this->cards(array_slice($related, 0, 12))]);
    }

    /** GET catalog/search?q= */
    public function search(Request $request): JsonResponse
    {
        [, $hub, $s] = $this->snap($request);
        $q = trim((string) $request->input('q', ''));
        if (mb_strlen($q) < 2) {
            return $this->fail('Type at least 2 letters to search', 'query_too_short');
        }
        $found = Catalog::search($q, $s['items']);

        return $this->ok(['stock_known' => $hub !== null, 'query' => $q, 'count' => count($found), 'items' => $this->cards($found)]);
    }

    /** GET catalog/sections/{key} - the full list behind a "See all" */
    public function section(Request $request, string $key): JsonResponse
    {
        [$buyerId, $hub, $s] = $this->snap($request);
        if ($key === 'buy-again') {
            $items = Catalog::buyAgain($buyerId, $s['items'], 60);

            return $this->ok(['stock_known' => $hub !== null, 'key' => $key, 'title' => 'Buy again', 'count' => count($items), 'items' => $this->cards($items)]);
        }
        foreach (Catalog::sections($s['items']) as $sec) {
            if ($sec['key'] === $key) {
                return $this->ok(['stock_known' => $hub !== null, 'key' => $key, 'title' => $sec['title'], 'count' => count($sec['items']), 'items' => $this->cards($sec['items'])]);
            }
        }

        return $this->fail('Nothing to show here right now.', 'section_not_found');
    }
}
