<?php

namespace App\Services\BuyerApp\Catalog;

use App\Services\Stock\StockGuard;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * What a customer may see and buy, shaped for the app. Read-only.
 *
 * Rules (from the live data, 3 Nov 2026):
 *  - Only products with status active, whose item and category are active, are on sale. About 125 of 2,153 items are.
 *  - One products row is ONE PACK (e.g. 500 g). An item groups its packs, so the app shows one card per item with pack chips.
 *  - Categories with nothing on sale are hidden. The category colour (bg_color) is optional.
 *  - cost_price and other internal columns are NEVER returned.
 *  - Stock per hub = inventory.live_stock minus packs held by open orders (StockGuard), by product code. Without a hub, stock is unknown (null).
 *  - Sections come from product_tags. products.tag is a comma list typed by hand ("Todays Deals", "Fresh Dairy "), so tags are
 *    compared after trimming, lower-casing and removing a trailing "s".
 *  - All counts are small (about 125 sellable products), so everything is shaped in PHP after one query.
 */
final class Catalog
{
    /** display order of the sections on the home screen (normalised names); others follow in product_tags order */
    private const PREFERRED = ['meat & seafood', 'best seller', 'todays deal', 'fruits & vegetables', 'ready to cook', 'fresh dairy'];
    private const UNITS = ['gram' => 'g', 'grams' => 'g', 'gm' => 'g', 'kg' => 'kg', 'kilogram' => 'kg', 'kilograms' => 'kg', 'ml' => 'ml', 'millilitre' => 'ml', 'litre' => 'L', 'liter' => 'L', 'l' => 'L',
        'piece' => 'pc', 'pieces' => 'pc', 'pcs' => 'pc', 'pc' => 'pc', 'box' => 'box', 'pack' => 'pack', 'dozen' => 'dozen'];

    public static function norm(string $s): string
    {
        return preg_replace('/s$/', '', strtolower(trim((string) preg_replace('/\s+/', ' ', $s))));
    }

    public static function packLabel($qty, $unit): string
    {
        $q = trim((string) $qty);
        $u = trim((string) $unit);
        $mapped = self::UNITS[strtolower($u)] ?? $u;
        if (is_numeric($q)) {
            $q = rtrim(rtrim(number_format((float) $q, 3, '.', ''), '0'), '.');
        }

        return trim($q . ' ' . $mapped);
    }

    private static function file($f): ?string
    {
        $f = trim((string) $f);

        return ($f === '' || strtolower($f) === 'noimage.jpg' || strtolower($f) === 'none.png') ? null : $f;
    }

    /**
     * All sellable items, shaped as cards, plus category rows.
     * @return array{items: array<int, array>, categories: array<int, array>}
     */
    public static function snapshot(?int $hubId, ?int $buyerId, callable $url): array
    {
        $cols = ['p.id', 'p.product_id as code', 'p.item_id', 'p.product_name', 'p.product_unit', 'p.unit_quantity', 'p.MRP', 'p.main_price', 'p.product_image', 'p.description',
            'p.property', 'p.tag', 'p.pieces', 'p.serves', 'p.cooking_time', 'p.ranking', 'i.item as item_name', 'i.image as item_image', 'i.cat_id',
            'c.name as cat_name', 'c.group_type', 'c.ranking as cat_rank', 'c.image as cat_image', 'c.circle_image as cat_circle'];
        $hasColor = Schema::hasColumn('category', 'bg_color');
        if ($hasColor) {
            $cols[] = 'c.bg_color';
        }
        $rows = DB::table('products as p')->join('items as i', 'i.id', '=', 'p.item_id')->join('category as c', 'c.id', '=', 'i.cat_id')
            ->where('p.status', 'active')->where('i.status', 'active')->where('c.status', 'active')
            ->orderBy('p.ranking')->orderBy('p.id')->select(...$cols)->get()->all();

        $live = [];
        if ($hubId) {
            $raw = [];
            foreach (DB::table('inventory')->where('hub_id', $hubId)->orderBy('id')->get(['product_id', 'live_stock'])->all() as $r) {
                if (! isset($raw[(string) $r->product_id])) {
                    $raw[(string) $r->product_id] = (float) $r->live_stock;
                }
            }
            $live = StockGuard::net($hubId, $raw);
        }
        $cart = [];
        $fav = [];
        if ($buyerId) {
            foreach (DB::table('cart')->where('buyer_id', $buyerId)->get(['product_id', 'quantity'])->all() as $r) {
                $cart[(int) $r->product_id] = (int) $r->quantity;
            }
            foreach (DB::table('wishlist')->where('buyer_id', $buyerId)->get(['product_id'])->all() as $r) {
                $fav[(string) $r->product_id] = true;
            }
        }

        $items = [];
        $cats = [];
        foreach ($rows as $r) {
            $price = (float) $r->main_price > 0 ? (float) $r->main_price : (float) $r->MRP;
            if ($price <= 0) {
                continue; // a pack with no price cannot be sold
            }
            $mrp = max((float) $r->MRP, $price);
            $code = (string) $r->code;
            $avail = $hubId ? (float) ($live[$code] ?? 0) : null;
            $iid = (int) $r->item_id;
            if (! isset($items[$iid])) {
                $items[$iid] = [
                    'item_id' => $iid, 'name' => trim((string) $r->item_name), 'category_id' => (int) $r->cat_id, 'category_name' => trim((string) $r->cat_name),
                    'group_type' => trim((string) $r->group_type), 'description' => '', 'properties' => [], 'images' => [], 'tags' => [],
                    'favorite' => false, 'packs' => [], '_item_image' => self::file($r->item_image), '_tags' => [],
                ];
            }
            $it = &$items[$iid];
            $img = self::file($r->product_image) ?? $it['_item_image'];
            $it['packs'][] = [
                'product_id' => (int) $r->id, 'code' => $code, 'label' => self::packLabel($r->unit_quantity, $r->product_unit),
                'price' => round($price, 2), 'mrp' => round($mrp, 2), 'off_percent' => $mrp > $price ? (int) round(($mrp - $price) / $mrp * 100) : 0,
                'in_stock' => $avail === null ? null : $avail > 0, 'available' => $avail === null ? null : (int) floor($avail),
                'in_cart_qty' => $cart[(int) $r->id] ?? 0,
                'pieces' => trim((string) $r->pieces), 'serves' => trim((string) $r->serves), 'cooking_time' => trim((string) $r->cooking_time),
                'image' => $img ? $url('images/products', $img) : null,
            ];
            if ($it['description'] === '' && trim((string) $r->description) !== '') {
                $it['description'] = trim(strip_tags((string) $r->description));
            }
            foreach (explode(',', (string) $r->property) as $pr) {
                if (trim($pr) !== '' && ! in_array(trim($pr), $it['properties'], true)) {
                    $it['properties'][] = trim($pr);
                }
            }
            if ($img && ! in_array($url('images/products', $img), $it['images'], true)) {
                $it['images'][] = $url('images/products', $img);
            }
            foreach (explode(',', (string) $r->tag) as $tg) {
                if (trim($tg) !== '') {
                    $it['_tags'][self::norm($tg)] = trim($tg);
                }
            }
            $it['favorite'] = $it['favorite'] || isset($fav[$code]);
            unset($it);

            $cid = (int) $r->cat_id;
            if (! isset($cats[$cid])) {
                $cats[$cid] = [
                    'id' => $cid, 'name' => trim((string) $r->cat_name), 'group_type' => trim((string) $r->group_type), 'ranking' => (int) $r->cat_rank,
                    'image' => ($f = self::file($r->cat_image)) ? $url('images', $f) : null,
                    'circle_image' => ($f = self::file($r->cat_circle)) ? $url('images', $f) : null,
                    'bg_color' => $hasColor && preg_match('/^#[0-9a-fA-F]{6}([0-9a-fA-F]{2})?$/', trim((string) ($r->bg_color ?? ''))) ? trim((string) $r->bg_color) : null,
                    'item_count' => 0, '_items' => [],
                ];
            }
            $cats[$cid]['_items'][$iid] = true;
        }
        foreach ($cats as &$c) {
            $c['item_count'] = count($c['_items']);
            unset($c['_items']);
        }
        unset($c);

        foreach ($items as &$it) {
            // the pack the card opens on: the first one in stock, else the first
            $it['default_pack'] = 0;
            foreach ($it['packs'] as $k => $p) {
                if ($p['in_stock'] !== false) {
                    $it['default_pack'] = $k;
                    break;
                }
            }
            $d = $it['packs'][$it['default_pack']];
            $it['image'] = $d['image'] ?? ($it['_item_image'] ? $url('images/products', $it['_item_image']) : null);
            $it['price_from'] = min(array_column($it['packs'], 'price'));
            $it['in_stock'] = $hubId ? (bool) array_filter($it['packs'], fn ($p) => $p['in_stock']) : null;
            $it['tags'] = array_values($it['_tags']);
            unset($it['_item_image']);
        }
        unset($it);

        uasort($cats, fn ($a, $b) => [$a['ranking'], $a['name']] <=> [$b['ranking'], $b['name']]);

        return ['items' => $items, 'categories' => $cats];
    }

    /** A card for lists: the same shape without the long fields. */
    public static function card(array $it): array
    {
        unset($it['description'], $it['images'], $it['properties'], $it['_tags']);

        return $it;
    }

    /** Sections for the home screen (and "see all"): [key, title, normalised tag, items]. */
    public static function sections(array $items): array
    {
        $tags = [];
        foreach (DB::table('product_tags')->where('status', 'active')->orderBy('id')->get(['id', 'tag'])->all() as $t) {
            $tags[self::norm((string) $t->tag)] = trim((string) $t->tag);
        }
        $tags['meat & seafood'] = $tags['meat & seafood'] ?? 'Meat & Seafood';  // used on products but not in the tag list

        $out = [];
        foreach ($tags as $n => $title) {
            $list = [];
            foreach ($items as $it) {
                if (isset($it['_tags'][$n])) {
                    $list[] = $it;
                }
            }
            if ($list) {
                $out[$n] = ['key' => trim((string) preg_replace('/[^a-z0-9]+/', '-', strtolower($title)), '-'), 'title' => $title, 'norm' => $n, 'items' => $list];
            }
        }
        $order = [];
        foreach (self::PREFERRED as $k => $n) {
            $order[self::norm($n)] = $k;
        }
        $keys = array_keys($out);
        usort($keys, fn ($a, $b) => ($order[$a] ?? 100 + array_search($a, array_keys($out))) <=> ($order[$b] ?? 100 + array_search($b, array_keys($out))));

        return array_map(fn ($k) => $out[$k], $keys);
    }

    public static function banners(callable $url): array
    {
        $out = [];
        foreach (DB::table('app_banner_images')->where('status', 'active')->orderBy('id')->get(['id', 'title', 'image', 'url'])->all() as $b) {
            if (self::file($b->image) === null) {
                continue;
            }
            $out[] = ['id' => (int) $b->id, 'title' => trim((string) $b->title), 'image' => $url('images/banners', $b->image)];
        }

        return $out;
    }

    /** Items the customer ordered before (newest first), still on sale. */
    public static function buyAgain(int $buyerId, array $items, int $limit = 12): array
    {
        $byProduct = [];
        foreach ($items as $it) {
            foreach ($it['packs'] as $p) {
                $byProduct[$p['product_id']] = $it['item_id'];
            }
        }
        $seen = [];
        $out = [];
        foreach (DB::table('orders')->where('buyer_id', $buyerId)->where('order_status', '!=', 'Order Cancel')->orderByDesc('id')->get(['product_id'])->all() as $o) {
            $iid = $byProduct[(int) $o->product_id] ?? null;
            if ($iid !== null && ! isset($seen[$iid])) {
                $seen[$iid] = true;
                $out[] = $items[$iid];
                if (count($out) >= $limit) {
                    break;
                }
            }
        }

        return $out;
    }

    /** Items matching a search: names that start with the text first, then names that contain it. */
    public static function search(string $q, array $items, int $limit = 40): array
    {
        $q = strtolower(trim($q));
        if (mb_strlen($q) < 2) {
            return [];
        }
        $starts = [];
        $has = [];
        foreach ($items as $it) {
            $name = strtolower($it['name']);
            if (str_starts_with($name, $q)) {
                $starts[] = $it;
            } elseif (str_contains($name, $q) || str_contains(strtolower($it['category_name']), $q)) {
                $has[] = $it;
            } else {
                foreach ($it['packs'] as $p) {
                    if (str_contains(strtolower($p['code']), $q)) {
                        $has[] = $it;
                        break;
                    }
                }
            }
        }

        return array_slice(array_merge($starts, $has), 0, $limit);
    }
}
