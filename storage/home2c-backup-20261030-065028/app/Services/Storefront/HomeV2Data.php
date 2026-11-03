<?php

namespace App\Services\Storefront;

use App\Models\bannerImage;
use App\Models\category;
use App\Models\coupons;
use App\Models\product;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Throwable;

/**
 * Data for the new home page (home v2).
 *
 * Read-only. The product, banner, category, tag and promotion rules are the same
 * as HomeController::index so the new page shows exactly what the old one shows;
 * this class only adds store stock signals and "Buy again".
 */
class HomeV2Data
{
    /** Labels shown on the photo instead of as a chip (the old card did the same). */
    public const PHOTO_LABELS = ['antibiotic free'];

    public function build(Request $request): array
    {
        $banners = bannerImage::where('status', '=', 'active')->get();

        $categories = category::where('status', '=', 'active')
            ->orderBy('ranking', 'asc')
            ->get()
            ->filter(fn ($c) => filled($c->name))
            ->unique(fn ($c) => Str::lower(trim((string) $c->name)))
            ->values();

        $limit = max(4, (int) config('storefront.section_limit', 8));

        // Same rule as the old page: every tag on an active product becomes a section.
        $tagNames = product::where('status', '=', 'active')
            ->pluck('tag')
            ->flatMap(fn ($s) => array_map('trim', explode(',', (string) $s)))
            ->filter()
            ->unique(fn ($t) => Str::lower($t))
            ->values();

        $sections = $tagNames->map(function ($tagName) use ($limit) {
            $items = product::with('category')
                ->where('status', '=', 'active')
                ->whereRaw("FIND_IN_SET(?, REPLACE(tag, ', ', ',')) > 0", [$tagName])
                ->orderBy('ranking', 'asc')
                ->limit($limit)
                ->get();

            return ['name' => $tagName, 'products' => $items];
        })->filter(fn ($s) => $s['products']->isNotEmpty())->values();

        $bestSellers = collect();
        if ($sections->isEmpty()) {
            $bestSellers = product::with('category')->where('status', '=', 'active')
                ->orderBy('ranking', 'desc')->limit($limit)->get();
        }

        $promotions = DB::table('home_promotions')
            ->where(function ($q) {
                $q->whereNull('expiry')->orWhere('expiry', '=', '0')->orWhereDate('expiry', '>=', now()->toDateString());
            })
            ->orderByDesc('id')
            ->limit(8)
            ->get();

        $coupons = $promotions->isEmpty()
            ? coupons::where('status', '=', 'active')->where('type', '=', 'normal')->orderBy('created_at', 'desc')->limit(6)->get()
            : collect();

        $hubId = $this->hubId($request);

        $buyAgain = $this->buyAgain();

        // Featured products for the hero (tag from config, default "Best Sellers").
        $heroTag = (string) config('storefront.hero_tag', 'Best Sellers');
        $heroPool = product::with('category')->where('status', '=', 'active')
            ->whereRaw("FIND_IN_SET(?, REPLACE(tag, ', ', ',')) > 0", [$heroTag])
            ->orderBy('ranking', 'asc')->limit(12)->get();
        if ($heroPool->isEmpty()) {
            $heroPool = product::with('category')->where('status', '=', 'active')->orderBy('ranking', 'asc')->limit(12)->get();
        }

        // Guests (or customers with no orders yet) see popular picks in the "Buy again" strip.
        $favourites = $buyAgain->isEmpty() ? $heroPool->take(4)->values() : collect();

        $allProducts = $sections->pluck('products')->flatten(1)->concat($bestSellers)->concat($buyAgain)->concat($heroPool);
        $stock = $this->stockMap($allProducts, $hubId);

        // Hero: in-stock products first, then the rest; up to 4 slides.
        $heroProducts = $heroPool->sortBy(fn ($p) => ($stock[$p->id]['out'] ?? false) ? 1 : 0)->take(4)->values();

        $imageMap = [];
        foreach ($allProducts as $p) {
            if (filled($p->product_image)) {
                $imageMap[$p->id] = asset('uploads/images/products/'.$p->product_image);
            }
        }

        return [
            'banners' => $banners,
            'categories' => $categories,
            'categoryGroups' => $this->categoryGroups($categories),
            'homeTagSections' => $sections,
            'bestSellers' => $bestSellers,
            'promotions' => $promotions,
            'coupons' => $coupons,
            'buyAgain' => $buyAgain,
            'favourites' => $favourites,
            'heroProducts' => $heroProducts,
            'stock' => $stock,
            'hubId' => $hubId,
            'storeCount' => $this->storeCount(),
            'imageMap' => $imageMap,
            'firstOrderOffer' => $promotions->first(fn ($p) => ($p->user_eligible ?? '') === 'new user') ?? $promotions->first(),
        ];
    }

    /**
     * Tabs for "Shop by category": the main categories (table `categories`) and the
     * categories that belong to each one (category.group_id holds the main category id,
     * possibly several separated by commas). Returns [] when the data is not there.
     */
    public function categoryGroups(Collection $categories): array
    {
        try {
            if (! Schema::hasTable('categories') || ! Schema::hasColumn('category', 'group_id')) {
                return [];
            }
            $mains = DB::table('categories')->where('status', 'active')->orderBy('id')->get(['id', 'name']);
        } catch (Throwable $e) {
            return [];
        }

        $groups = [];
        foreach ($mains as $m) {
            $ids = $categories->filter(function ($c) use ($m) {
                $parts = array_map('trim', explode(',', (string) ($c->group_id ?? '')));

                return in_array((string) $m->id, $parts, true);
            })->pluck('id')->map(fn ($v) => (int) $v)->values()->all();
            if ($ids) {
                $groups[] = ['id' => (int) $m->id, 'name' => (string) $m->name, 'ids' => $ids];
            }
        }

        return count($groups) >= 2 ? $groups : [];
    }

    public function storeCount(): int
    {
        try {
            return (int) DB::table('hubs')->whereRaw("CAST(status AS CHAR) IN ('active','1')")->count();
        } catch (Throwable $e) {
            return 0;
        }
    }

    /** Colour for the tag ribbon on the photo; the same tag always gets the same colour. */
    public static function ribbonColor(string $tag): string
    {
        $palette = ['#1d4ea8', '#5b21a6', '#a8174f', '#0b6b5f', '#2f3a8f', '#8a4b00'];

        return $palette[abs(crc32(Str::lower(trim($tag)))) % count($palette)];
    }

    /** Same order the checkout uses: session, then the location cookie, then the default store. */
    public function hubId(Request $request): int
    {
        $hub = (int) (session('hub_session_id') ?: ($_COOKIE['hub_id'] ?? 0));

        return $hub > 0 ? $hub : (int) config('storefront.default_hub_id', 3);
    }

    /**
     * Stock signal per product for one store, keyed by products.id.
     * inventory.product_id holds the product CODE (products.product_id).
     * A product with no inventory row for the store gets no signal (it is not shown as out of stock).
     */
    public function stockMap(Collection $products, int $hubId): array
    {
        if (! config('storefront.stock_badges', true) || $products->isEmpty()) {
            return [];
        }

        $codes = $products->pluck('product_id')->filter()->unique()->values()->all();
        if (! $codes) {
            return [];
        }

        try {
            $rows = DB::table('inventory')
                ->where('hub_id', $hubId)
                ->whereIn('product_id', $codes)
                ->get(['product_id', 'live_stock', 'fresh_stock'])
                ->keyBy('product_id');
        } catch (Throwable $e) {
            Log::warning('[storefront] stock lookup failed: '.$e->getMessage());

            return [];
        }

        $lowAt = max(1, (int) config('storefront.low_stock_at', 5));
        $cutWords = (array) config('storefront.cut_words', []);
        $map = [];

        foreach ($products as $p) {
            $row = $rows->get($p->product_id);
            if (! $row) {
                continue;
            }
            $live = (int) $row->live_stock;
            $fresh = (int) $row->fresh_stock;
            $catName = Str::lower((string) (optional($p->category)->name ?? ''));
            $isCut = $catName !== '' && Str::contains($catName.' '.Str::lower((string) $p->product_name), $cutWords);

            $map[$p->id] = [
                'out' => $live <= 0,
                'left' => $live,
                'low' => $live > 0 && $live <= $lowAt,
                'fresh' => $live > 0 && $fresh > 0,
                'freshLabel' => $isCut ? 'Cut today' : 'Fresh today',
            ];
        }

        return $map;
    }

    /** Products this customer ordered before, newest first (logged-in customers only). */
    public function buyAgain(): Collection
    {
        if (! Auth::check()) {
            return collect();
        }

        try {
            $ids = DB::table('orders')
                ->where('buyer_id', Auth::id())
                ->orderByDesc('id')
                ->limit(80)
                ->pluck('product_id')
                ->filter()
                ->unique()
                ->take(12)
                ->values();
            if ($ids->isEmpty()) {
                return collect();
            }

            $found = product::with('category')
                ->where('status', '=', 'active')
                ->where(function ($q) use ($ids) {
                    $q->whereIn('id', $ids->map(fn ($v) => (int) $v)->filter()->all())
                      ->orWhereIn('product_id', $ids->map(fn ($v) => (string) $v)->all());
                })
                ->get();

            // Keep the "most recent first" order.
            return $ids->map(fn ($v) => $found->first(fn ($p) => (string) $p->id === (string) $v || (string) $p->product_id === (string) $v))
                ->filter()->unique('id')->take(4)->values();
        } catch (Throwable $e) {
            Log::warning('[storefront] buy again failed: '.$e->getMessage());

            return collect();
        }
    }

    /** Tags of one product, split for display. */
    public static function tagsOf($product): array
    {
        $all = array_values(array_filter(array_map('trim', explode(',', (string) ($product->tag ?? '')))));
        $photo = [];
        $chips = [];
        foreach ($all as $t) {
            if (in_array(Str::lower($t), self::PHOTO_LABELS, true)) {
                $photo[] = $t;
            } else {
                $chips[] = $t;
            }
        }

        return ['photo' => $photo, 'chips' => $chips];
    }

    /** Soft colour pair for a tag name; the same tag always gets the same colour. */
    public static function tagStyle(string $tag): string
    {
        $palette = [
            ['#e3eefc', '#1d4ea8'],
            ['#efe7fb', '#5b21a6'],
            ['#fde7ef', '#a8174f'],
            ['#e0f4f1', '#0b6b5f'],
            ['#e8eaf6', '#2f3a8f'],
            ['#f3ece4', '#6b4423'],
        ];
        $pick = $palette[abs(crc32(Str::lower(trim($tag)))) % count($palette)];

        return 'background:'.$pick[0].';color:'.$pick[1];
    }

    public static function money($value): string
    {
        $n = (float) $value;

        return '₹'.(fmod($n, 1.0) == 0.0 ? number_format($n, 0) : number_format($n, 2));
    }

    public static function hasStockAlertsTable(): bool
    {
        static $has = null;
        if ($has === null) {
            try {
                $has = Schema::hasTable('storefront_stock_alerts');
            } catch (Throwable $e) {
                $has = false;
            }
        }

        return $has;
    }
}
