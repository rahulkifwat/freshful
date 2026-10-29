<?php

namespace App\Services\AdminConsole;

use App\Services\CountryConsole\CountryScope;
use App\Services\CountryConsole\CountryUnits;
use App\Services\CountryConsole\SimpleXlsx;
use Illuminate\Support\Facades\DB;

/**
 * Freshful admin console (admin update 1, 22 Oct 2026): products.
 * Same table and columns as the old admin (products: one row per pack size of an item). Rules that are new:
 *   - a product is never deleted (the old admin deleted products and left their hub stock behind: the "unknown codes");
 *   - product codes are never reused: the next code is one more than the highest code in products AND in hub inventory;
 *   - price checks: price above MRP, cost above price, zero price and impossible pack sizes are flagged;
 *   - stock without a product can be reconnected by recreating the product with the same code (switched off until checked);
 *   - Excel: export, bulk update of MRP / price / cost by product code, and import of new products, each with a preview first.
 */
class AdminCatalog
{
    private static function h(string $t, ?string $c = null): bool { return CountryScope::has($t, $c); }

    /** live / incoming stock per product code (case and spaces ignored, like MySQL). */
    private static function stockByCode(): array
    {
        if (!self::h('inventory')) return [];
        $o = [];
        foreach (DB::table('inventory')->select(DB::raw('UPPER(TRIM(product_id)) AS c'), DB::raw('SUM(live_stock) AS live'), DB::raw('SUM(stock) AS inc'), DB::raw('SUM(CASE WHEN live_stock > 0 THEN 1 ELSE 0 END) AS hubs'), DB::raw('COUNT(*) AS n'), DB::raw('MAX(updated_at) AS upd'))->groupBy(DB::raw('UPPER(TRIM(product_id))'))->get() as $r) {
            $o[(string) $r->c] = ['live' => (float) $r->live, 'incoming' => (float) $r->inc, 'hubs' => (int) $r->hubs, 'rows' => (int) $r->n, 'updated' => CountryScope::ts($r->upd)];
        }
        return $o;
    }

    public static function meta(): array
    {
        $cats = [];
        foreach (DB::table('category')->orderBy('group_type')->orderBy('ranking')->get(['id', 'name', 'group_type', 'status']) as $c) $cats[] = ['id' => (int) $c->id, 'name' => (string) $c->name, 'main' => (string) $c->group_type, 'active' => AdminRecords::isOn($c->status)];
        $filters = [];
        if (self::h('product_filter')) foreach (DB::table('product_filter')->get() as $f) $filters[] = ['id' => (int) $f->id, 'cat' => (int) $f->subcat_id, 'name' => (string) $f->property_name, 'values' => array_values(array_filter(array_map('trim', explode(',', (string) $f->parameter))))];
        return ['ok' => true, 'mains' => DB::table('categories')->orderBy('name')->pluck('name')->all(), 'categories' => $cats,
            'units' => self::h('product_units') ? DB::table('product_units')->orderBy('unit')->pluck('unit')->all() : [],
            'tags' => self::h('product_tags') ? DB::table('product_tags')->orderBy('tag')->pluck('tag')->all() : [], 'filters' => $filters, 'next_code' => self::nextCode()];
    }

    public static function items(int $catId): array
    {
        $o = [];
        foreach (DB::table('items')->where('cat_id', $catId)->orderBy('item')->get(['id', 'item', 'image', 'status']) as $i) $o[] = ['id' => (int) $i->id, 'name' => (string) $i->item, 'image' => AdminUploads::url('products', $i->image), 'active' => AdminRecords::isOn($i->status)];
        return ['ok' => true, 'items' => $o];
    }

    public static function nextCode(): string
    {
        $max = 0;
        foreach (['products', 'inventory'] as $t) {
            if (!self::h($t, 'product_id')) continue;
            foreach (DB::table($t)->select('product_id')->distinct()->pluck('product_id') as $c) if (preg_match('/^\s*PRD0*(\d+)\s*$/i', (string) $c, $m)) $max = max($max, (int) $m[1]);
        }
        return 'PRD' . str_pad((string) ($max + 1), 5, '0', STR_PAD_LEFT);
    }

    public static function list(): array
    {
        $q = DB::table('products AS p')->leftJoin('items AS i', 'i.id', '=', 'p.item_id')->leftJoin('category AS c', 'c.id', '=', 'p.category_id')->leftJoin('category AS ci', 'ci.id', '=', 'i.cat_id')
            ->select('p.*', 'i.item AS item_name', 'i.image AS item_image', 'i.cat_id AS item_cat', DB::raw('COALESCE(c.name, ci.name) AS cat_name'), DB::raw('COALESCE(c.group_type, ci.group_type) AS main_name'));
        $stock = self::stockByCode();
        [$on] = AdminRecords::statusValues('products');
        $rows = [];
        foreach ($q->orderBy('p.ranking')->orderBy('p.id')->get() as $p) {
            $u = CountryUnits::of($p->unit_quantity, $p->product_unit);
            $mrp = (float) $p->MRP; $price = (float) $p->main_price; $cost = self::h('products', 'cost_price') ? (float) $p->cost_price : 0.0;
            $st = $stock[strtoupper(trim((string) $p->product_id))] ?? ['live' => 0, 'incoming' => 0, 'hubs' => 0];
            $issues = [];
            if (!$p->item_name) $issues[] = 'Item missing';
            if ($price <= 0) $issues[] = 'No price';
            if ($mrp > 0 && $price > $mrp) $issues[] = 'Price above MRP';
            if ($cost > 0 && $cost > $price) $issues[] = 'Cost above price';
            if ($u['suspect']) $issues[] = 'Check pack size';
            $img = trim((string) $p->product_image) !== '' && $p->product_image !== 'false' ? AdminUploads::url('products', $p->product_image) : AdminUploads::url('products', $p->item_image);
            if ($img === '') $issues[] = 'No photo';
            $rows[] = ['id' => (int) $p->id, 'rank' => (int) $p->ranking, 'code' => (string) $p->product_id, 'name' => trim((string) ($p->item_name ?: $p->product_name)), 'saved_name' => (string) $p->product_name,
                'pack' => trim(CountryScope::q($p->unit_quantity) . ' ' . $p->product_unit), 'unit' => (string) $p->product_unit, 'unit_quantity' => (string) $p->unit_quantity,
                'category' => (string) ($p->cat_name ?? ''), 'main' => (string) ($p->main_name ?? ''), 'category_id' => (int) $p->category_id, 'item_id' => (int) $p->item_id,
                'mrp' => $mrp, 'price' => $price, 'cost' => $cost, 'margin' => $price > 0 && $cost > 0 ? round(($price - $cost) * 100 / $price, 1) : null, 'off' => $mrp > 0 && $price < $mrp ? (int) round(($mrp - $price) * 100 / $mrp) : 0,
                'live' => $st['live'], 'incoming' => $st['incoming'], 'hubs' => $st['hubs'], 'kind' => $u['kind'], 'per' => $u['per'], 'live_text' => $st['live'] ? CountryUnits::text($st['live'], $u) : '0',
                'image' => $img, 'active' => AdminRecords::isOn($p->status), 'tags' => (string) $p->tag, 'issues' => $issues];
        }
        return ['ok' => true, 'rows' => $rows, 'next_code' => self::nextCode()];
    }

    public static function get(int $id): array
    {
        $p = DB::table('products')->where('id', $id)->first();
        if (!$p) return ['ok' => false, 'message' => 'Product not found.'];
        $a = (array) $p;
        $item = DB::table('items')->where('id', $p->item_id)->first();
        $cat = DB::table('category')->where('id', $p->category_id ?: ($item->cat_id ?? 0))->first();
        $a['main'] = (string) ($cat->group_type ?? '');
        $a['category_id'] = (int) ($p->category_id ?: ($item->cat_id ?? 0));
        $a['image_url'] = AdminUploads::url('products', $p->product_image);
        $a['item_image_url'] = $item ? AdminUploads::url('products', $item->image) : '';
        $a['active'] = AdminRecords::isOn($p->status);
        $st = self::stockByCode()[strtoupper(trim((string) $p->product_id))] ?? null;
        $a['stock'] = $a['stock'] ?? '';
        $a['live'] = $st['live'] ?? 0;
        $hubs = [];
        if (self::h('inventory')) {
            $hn = CountryScope::hubs();
            foreach (DB::table('inventory')->whereRaw('UPPER(TRIM(product_id)) = ?', [strtoupper(trim((string) $p->product_id))])->get(['hub_id', 'live_stock', 'stock']) as $r) {
                $hubs[] = ['hub' => $hn[(int) $r->hub_id]['name'] ?? ('Hub ' . $r->hub_id), 'live' => (float) $r->live_stock, 'incoming' => (float) $r->stock];
            }
        }
        unset($a['password']);
        return ['ok' => true, 'product' => $a, 'hubs' => $hubs] + self::items($a['category_id']);
    }

    /** Check typed values. */
    private static function clean(array $in, ?object $old): array
    {
        $v = [];
        $cat = (int) ($in['category_id'] ?? 0);
        $catRow = $cat ? DB::table('category')->where('id', $cat)->first() : null;
        if (!$catRow) return [null, 'Choose the category.'];
        $item = DB::table('items')->where('id', (int) ($in['item_id'] ?? 0))->first();
        if (!$item) return [null, 'Choose the item.'];
        if ((int) $item->cat_id !== $cat) return [null, 'That item belongs to another category. Choose the item again.'];
        $v['category_id'] = $cat; $v['item_id'] = (int) $item->id; $v['product_name'] = (string) $item->item;
        $unit = trim((string) ($in['product_unit'] ?? ''));
        if ($unit === '') return [null, 'Choose the unit.'];
        if (self::h('product_units') && !DB::table('product_units')->where('unit', $unit)->exists()) return [null, 'Choose the unit from the list.'];
        $v['product_unit'] = $unit;
        foreach (['unit_quantity' => 'Pack size', 'MRP' => 'MRP', 'main_price' => 'Selling price'] as $k => $lab) {
            $x = trim((string) ($in[$k] ?? ''));
            if ($x === '' || !is_numeric($x) || (float) $x <= 0) return [null, $lab . ' must be a number above 0.'];
            $v[$k] = $k === 'unit_quantity' ? CountryScope::q($x) : (string) round((float) $x, 2);
        }
        if ((float) $v['main_price'] > (float) $v['MRP']) return [null, 'Selling price cannot be more than the MRP.'];
        $u = CountryUnits::of($v['unit_quantity'], $unit);
        if ($u['suspect'] && empty($in['confirm_pack'])) return [null, 'A pack of ' . $v['unit_quantity'] . ' ' . $unit . ' looks wrong (under 50 g or over 25 kg). Check the pack size, or tick "this pack size is correct".'];
        foreach (['gross_quantity' => 'Gross quantity', 'cost_price' => 'Cost price', 'stock' => 'Stock', 'pieces' => 'Pieces', 'serves' => 'Serves', 'cooking_time' => 'Cooking time'] as $k => $lab) {
            if (!self::h('products', $k) || !array_key_exists($k, $in)) continue;
            $x = trim((string) $in[$k]);
            if ($x === '') { $v[$k] = $k === 'gross_quantity' ? '' : '0'; continue; }
            if (in_array($k, ['gross_quantity', 'serves', 'cooking_time', 'pieces'], true) && !is_numeric($x)) { $v[$k] = mb_substr($x, 0, 40); continue; }
            if (!is_numeric($x) || (float) $x < 0) return [null, $lab . ' must be a number (0 or more).'];
            $v[$k] = $k === 'cost_price' ? (string) round((float) $x, 2) : CountryScope::q($x);
        }
        if (isset($v['cost_price']) && (float) $v['cost_price'] > (float) $v['main_price'] && empty($in['confirm_cost'])) return [null, 'Cost price is more than the selling price (you would lose money). Check it, or tick "sell below cost".'];
        $tags = array_values(array_filter(array_map('trim', is_array($in['tag'] ?? null) ? $in['tag'] : explode(',', (string) ($in['tag'] ?? '')))));
        if ($tags && self::h('product_tags')) { $known = DB::table('product_tags')->pluck('tag')->all(); $tags = array_values(array_intersect($tags, $known)); }
        $v['tag'] = implode(',', $tags);
        $props = array_values(array_filter(array_map('trim', is_array($in['property'] ?? null) ? $in['property'] : explode(',', (string) ($in['property'] ?? '')))));
        $v['property'] = mb_substr(implode(',', $props), 0, 1000);
        $v['description'] = mb_substr(trim((string) ($in['description'] ?? '')), 0, 5000);
        $img = trim((string) ($in['product_image'] ?? ''));
        if ($img !== '' && !preg_match('/^[A-Za-z0-9_\-\.]{1,120}$/', $img)) return [null, 'Upload the photo again.'];
        if (array_key_exists('product_image', $in)) $v['product_image'] = $img;
        if (array_key_exists('ranking', $in) && trim((string) $in['ranking']) !== '') {
            if (!ctype_digit(trim((string) $in['ranking']))) return [null, 'Rank must be a whole number.'];
            $v['ranking'] = (int) $in['ranking'];
        }
        foreach (array_keys($v) as $k) if (!self::h('products', $k)) unset($v[$k]);
        return [$v, null];
    }

    public static function save(?int $id, array $in, array $actor): array
    {
        $old = $id ? DB::table('products')->where('id', $id)->first() : null;
        if ($id && !$old) return ['ok' => false, 'message' => 'Product not found.'];
        [$v, $err] = self::clean($in, $old);
        if ($err) return ['ok' => false, 'message' => $err];
        if (array_key_exists('active', $in) && self::h('products', 'status')) { [$on, $off] = AdminRecords::statusValues('products'); $v['status'] = filter_var($in['active'], FILTER_VALIDATE_BOOLEAN) ? $on : $off; }
        $lock = DB::getDriverName() === 'mysql';
        try {
            if ($old) {
                DB::table('products')->where('id', $id)->update($v);
            } else {
                if ($lock) DB::select("SELECT GET_LOCK('freshful_product_code', 10)");
                try {
                    $code = strtoupper(trim((string) ($in['restore_code'] ?? '')));
                    if ($code !== '') {
                        if (!preg_match('/^[A-Za-z0-9_\-]{2,30}$/', $code)) return ['ok' => false, 'message' => 'That product code is not valid.'];
                        if (DB::table('products')->whereRaw('UPPER(TRIM(product_id)) = ?', [strtoupper($code)])->exists()) return ['ok' => false, 'message' => 'A product with code ' . $code . ' already exists.'];
                    } else $code = self::nextCode();
                    $v['product_id'] = $code;
                    if (!isset($v['ranking'])) $v['ranking'] = (int) DB::table('products')->max('ranking') + 1;
                    if (!isset($v['status'])) $v['status'] = AdminRecords::statusValues('products')[0];
                    if (self::h('products', 'date_added')) $v['date_added'] = date('Y-m-d H:i:s');
                    foreach (['stock' => '0', 'gross_quantity' => '', 'pieces' => '0', 'serves' => '0', 'cooking_time' => '0'] as $k => $def) if (self::h('products', $k) && !isset($v[$k])) $v[$k] = $def;
                    $id = (int) DB::table('products')->insertGetId($v);
                } finally {
                    if ($lock) DB::select("SELECT RELEASE_LOCK('freshful_product_code')");
                }
            }
        } catch (\Throwable $e) {
            return ['ok' => false, 'message' => 'Could not save: ' . AdminRecords::dbError($e)];
        }
        $new = DB::table('products')->where('id', $id)->first();
        $what = trim($new->product_name . ' ' . CountryScope::q($new->unit_quantity) . ' ' . $new->product_unit);
        AdminAudit::log($actor, $old ? 'edit' : (!empty($in['restore_code']) ? 'restore' : 'add'), 'Catalogue', 'products', $id, ($old ? 'Edited product ' : (!empty($in['restore_code']) ? 'Recreated product for stock code ' : 'Added product ')) . $new->product_id . ' "' . $what . '"', $old, $new);
        return ['ok' => true, 'id' => $id, 'code' => (string) $new->product_id, 'message' => ($old ? 'Product saved.' : 'Product ' . $new->product_id . ' added.') . (!AdminRecords::isOn($new->status) ? ' It is switched off; switch it on to sell it.' : '')];
    }

    public static function toggle(int $id, array $actor): array
    {
        $old = DB::table('products')->where('id', $id)->first();
        if (!$old) return ['ok' => false, 'message' => 'Product not found.'];
        [$on, $off] = AdminRecords::statusValues('products');
        $to = AdminRecords::isOn($old->status) ? $off : $on;
        if (AdminRecords::isOn($to) && (float) $old->main_price <= 0) return ['ok' => false, 'message' => 'Set a selling price before switching this product on.'];
        DB::table('products')->where('id', $id)->update(['status' => $to]);
        AdminAudit::log($actor, AdminRecords::isOn($to) ? 'switch on' : 'switch off', 'Catalogue', 'products', $id, (AdminRecords::isOn($to) ? 'Switched on' : 'Switched off') . ' product ' . $old->product_id . ' "' . $old->product_name . '"', $old, DB::table('products')->where('id', $id)->first());
        return ['ok' => true, 'on' => AdminRecords::isOn($to), 'message' => $old->product_name . ' is now ' . (AdminRecords::isOn($to) ? 'on sale.' : 'off (hidden from customers).')];
    }

    /** Several products at once: on / off. */
    public static function bulkStatus(array $ids, bool $onSale, array $actor): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
        if (!$ids) return ['ok' => false, 'message' => 'Select at least one product.'];
        if (count($ids) > 500) return ['ok' => false, 'message' => 'At most 500 products at a time.'];
        [$on, $off] = AdminRecords::statusValues('products');
        $n = 0; $skipped = 0;
        foreach (DB::table('products')->whereIn('id', $ids)->get() as $p) {
            if ($onSale && (float) $p->main_price <= 0) { $skipped++; continue; }
            if (AdminRecords::isOn($p->status) === $onSale) continue;
            DB::table('products')->where('id', $p->id)->update(['status' => $onSale ? $on : $off]); $n++;
            AdminAudit::log($actor, $onSale ? 'switch on' : 'switch off', 'Catalogue', 'products', $p->id, ($onSale ? 'Switched on' : 'Switched off') . ' product ' . $p->product_id . ' (bulk)', $p, DB::table('products')->where('id', $p->id)->first());
        }
        return ['ok' => true, 'message' => $n . ' product(s) switched ' . ($onSale ? 'on' : 'off') . '.' . ($skipped ? ' ' . $skipped . ' skipped: no selling price.' : '')];
    }

    /** Stock rows whose code is not in products (the old admin deleted the product but kept the stock). */
    public static function missing(): array
    {
        if (!self::h('inventory')) return ['ok' => true, 'rows' => []];
        $codes = [];
        foreach (DB::table('products')->pluck('product_id') as $c) $codes[strtoupper(trim((string) $c))] = true;
        $hn = CountryScope::hubs();
        $rows = [];
        foreach (DB::table('inventory')->select('product_id', 'hub_id', 'live_stock', 'stock', 'updated_at')->get() as $r) {
            $k = strtoupper(trim((string) $r->product_id));
            if ($k === '' || isset($codes[$k])) continue;
            if (!isset($rows[$k])) $rows[$k] = ['code' => trim((string) $r->product_id), 'live' => 0.0, 'incoming' => 0.0, 'hubs' => 0, 'rows' => 0, 'updated' => null, 'by_hub' => []];
            $rows[$k]['live'] += (float) $r->live_stock; $rows[$k]['incoming'] += (float) $r->stock; $rows[$k]['rows']++;
            if ((float) $r->live_stock > 0) $rows[$k]['hubs']++;
            $ts = CountryScope::ts($r->updated_at); if ($ts && $ts > (int) $rows[$k]['updated']) $rows[$k]['updated'] = $ts;
            if ((float) $r->live_stock || (float) $r->stock) $rows[$k]['by_hub'][] = ['hub' => $hn[(int) $r->hub_id]['name'] ?? ('Hub ' . $r->hub_id), 'live' => (float) $r->live_stock, 'incoming' => (float) $r->stock];
        }
        // What was ordered under these codes before (orders keep products.id, not the code), and old moves that name the code.
        $out = array_values($rows);
        usort($out, fn ($a, $b) => [$b['live'], $a['code']] <=> [$a['live'], $b['code']]);
        $tot = ['codes' => count($out), 'live' => array_sum(array_column($out, 'live')), 'incoming' => array_sum(array_column($out, 'incoming'))];
        return ['ok' => true, 'rows' => $out, 'total' => $tot];
    }

    /* ------------------------------------------------------------------ Excel */

    public const UPDATE_HEAD = ['S.No', 'Product ID', 'Product name', 'Pack', 'MRP', 'Selling price', 'Cost price', 'Category', 'Status'];
    public const IMPORT_HEAD = ['Main category', 'Category', 'Item', 'Tag', 'Unit', 'Unit quantity', 'Gross quantity', 'MRP', 'Selling price', 'Stock', 'Pieces', 'Serves', 'Cooking time', 'Property', 'Description', 'Cost price'];

    public static function exportXlsx(): string
    {
        $x = new SimpleXlsx();
        $x->row(self::UPDATE_HEAD, 'head');
        $i = 0;
        foreach (self::list()['rows'] as $r) $x->row([++$i, $r['code'], $r['name'], $r['pack'], $r['mrp'], $r['price'], $r['cost'], $r['category'], $r['active'] ? 'On' : 'Off']);
        $x->widths([7, 13, 36, 14, 10, 12, 11, 24, 8]);
        $x->freeze(0, 1);
        return $x->build('Products');
    }

    public static function templateXlsx(): string
    {
        $x = new SimpleXlsx();
        $x->row(self::IMPORT_HEAD, 'head');
        $x->row(['Meat', 'Chicken', 'Chicken Curry Cut', 'Bestseller', 'Gram', 500, 550, 260, 239, 0, 12, 3, 20, 'Skinless', 'Fresh chicken curry cut, cleaned', 180]);
        $x->widths([14, 16, 26, 14, 9, 12, 13, 9, 12, 8, 8, 8, 12, 14, 34, 10]);
        $x->freeze(0, 1);
        return $x->build('New products');
    }

    private static function norm(string $s): string { return strtolower(preg_replace('/[^a-z0-9]+/i', '', $s)); }

    /** Find the header row and map our column names to positions. */
    private static function columns(array $rows, array $want): array
    {
        foreach (array_slice($rows, 0, 5, true) as $ri => $r) {
            $map = [];
            foreach ($r as $ci => $h) { $n = self::norm((string) $h); foreach ($want as $w) if ($n !== '' && $n === self::norm($w)) $map[$w] = $ci; }
            if (count($map) >= 2) return [$ri, $map];
        }
        return [null, []];
    }

    /** Bulk price update: MRP / selling price / cost by Product ID. $apply false = preview only. */
    public static function bulkUpdate(array $rows, bool $apply, array $actor): array
    {
        $alias = ['Selling price' => ['Selling price', 'Main price', 'Price', 'SP', 'S.P.'], 'MRP' => ['MRP', 'M.P.'], 'Cost price' => ['Cost price', 'Cost'], 'Product ID' => ['Product ID', 'Product code', 'Code', 'Ids']];
        $want = []; foreach ($alias as $vs) foreach ($vs as $v) $want[] = $v;
        [$hr, $map] = self::columns($rows, $want);
        $col = function (string $k) use ($alias, $map) { foreach ($alias[$k] as $a) if (isset($map[$a])) return $map[$a]; return null; };
        if ($hr === null || $col('Product ID') === null) return ['ok' => false, 'message' => 'The file needs a "Product ID" column (download the product list first and edit that file).'];
        $cMrp = $col('MRP'); $cPrice = $col('Selling price'); $cCost = self::h('products', 'cost_price') ? $col('Cost price') : null;
        if ($cMrp === null && $cPrice === null && $cCost === null) return ['ok' => false, 'message' => 'The file needs at least one of: MRP, Selling price, Cost price.'];
        $products = [];
        foreach (DB::table('products')->get() as $p) $products[strtoupper(trim((string) $p->product_id))] = $p;
        $changes = []; $errors = []; $same = 0; $seen = [];
        foreach ($rows as $i => $r) {
            if ($i <= $hr) continue;
            $code = strtoupper(trim((string) ($r[$col('Product ID')] ?? '')));
            if ($code === '') continue;
            $line = $i + 1;
            if (isset($seen[$code])) { $errors[] = 'Row ' . $line . ': ' . $code . ' appears twice; only the first row is used.'; continue; }
            $seen[$code] = true;
            $p = $products[$code] ?? null;
            if (!$p) { $errors[] = 'Row ' . $line . ': product ' . $code . ' not found.'; continue; }
            $new = [];
            foreach (['MRP' => $cMrp, 'main_price' => $cPrice, 'cost_price' => $cCost] as $k => $c) {
                if ($c === null) continue;
                $v = str_replace([',', '₹', 'Rs.', 'Rs'], '', trim((string) ($r[$c] ?? '')));
                if ($v === '') continue;
                if (!is_numeric($v) || (float) $v < 0) { $errors[] = 'Row ' . $line . ': ' . $code . ' ' . $k . ' "' . $v . '" is not a number.'; continue 2; }
                if (round((float) $v, 2) !== round((float) $p->$k, 2)) $new[$k] = round((float) $v, 2);
            }
            if (!$new) { $same++; continue; }
            $mrp = $new['MRP'] ?? (float) $p->MRP; $price = $new['main_price'] ?? (float) $p->main_price;
            if ($price <= 0 && isset($new['main_price'])) { $errors[] = 'Row ' . $line . ': ' . $code . ' selling price cannot be 0.'; continue; }
            if ($mrp > 0 && $price > $mrp) { $errors[] = 'Row ' . $line . ': ' . $code . ' selling price ' . $price . ' is above MRP ' . $mrp . '.'; continue; }
            if (!$new) { $same++; continue; }
            $changes[] = ['id' => (int) $p->id, 'code' => (string) $p->product_id, 'name' => (string) $p->product_name . ' ' . CountryScope::q($p->unit_quantity) . ' ' . $p->product_unit,
                'from' => ['MRP' => (float) $p->MRP, 'main_price' => (float) $p->main_price, 'cost_price' => (float) ($p->cost_price ?? 0)], 'to' => $new];
        }
        if ($apply) {
            if (!$changes) return ['ok' => false, 'message' => 'Nothing to change.'];
            DB::transaction(function () use ($changes, $actor) {
                foreach ($changes as $c) {
                    $old = DB::table('products')->where('id', $c['id'])->first();
                    DB::table('products')->where('id', $c['id'])->update(array_map(fn ($v) => (string) $v, $c['to']));
                    AdminAudit::log($actor, 'price update', 'Catalogue', 'products', $c['id'], 'Excel price update ' . $c['code'], $old, DB::table('products')->where('id', $c['id'])->first());
                }
            });
            return ['ok' => true, 'applied' => count($changes), 'message' => count($changes) . ' product(s) updated.' . ($errors ? ' ' . count($errors) . ' row(s) skipped.' : ''), 'errors' => $errors];
        }
        return ['ok' => true, 'preview' => true, 'changes' => $changes, 'errors' => $errors, 'same' => $same, 'message' => count($changes) . ' product(s) will change, ' . $same . ' unchanged, ' . count($errors) . ' problem row(s).'];
    }

    /** Import new products (old template columns). Items missing in the category are created. */
    public static function import(array $rows, bool $apply, array $actor): array
    {
        [$hr, $map] = self::columns($rows, array_merge(self::IMPORT_HEAD, ['Main price', 'Price']));
        if ($hr === null || !isset($map['Category'], $map['Item'], $map['Unit'])) return ['ok' => false, 'message' => 'Use the template: it needs at least the columns Category, Item, Unit, Unit quantity, MRP and Selling price.'];
        $g = fn ($r, $k) => isset($map[$k]) ? trim((string) ($r[$map[$k]] ?? '')) : '';
        $cats = [];
        foreach (DB::table('category')->get(['id', 'name', 'group_type']) as $c) $cats[self::norm($c->name) . '|' . self::norm((string) $c->group_type)] = $c;
        $catByName = []; foreach ($cats as $c) $catByName[self::norm($c->name)][] = $c;
        $units = self::h('product_units') ? array_change_key_case(DB::table('product_units')->pluck('unit', 'unit')->all(), CASE_LOWER) : [];
        $plan = []; $errors = []; $newItems = [];
        $existing = [];
        foreach (DB::table('products')->get(['item_id', 'unit_quantity', 'product_unit']) as $p) $existing[$p->item_id . '|' . (float) $p->unit_quantity . '|' . strtolower((string) $p->product_unit)] = true;
        foreach ($rows as $i => $r) {
            if ($i <= $hr) continue;
            $line = $i + 1;
            $catName = $g($r, 'Category'); $itemName = $g($r, 'Item');
            if ($catName === '' && $itemName === '') continue;
            $main = $g($r, 'Main category');
            $c = $cats[self::norm($catName) . '|' . self::norm($main)] ?? (count($catByName[self::norm($catName)] ?? []) === 1 ? $catByName[self::norm($catName)][0] : null);
            if (!$c) { $errors[] = 'Row ' . $line . ': category "' . $catName . '"' . ($main ? ' in "' . $main . '"' : '') . ' not found (add it under Categories first).'; continue; }
            if ($itemName === '') { $errors[] = 'Row ' . $line . ': item name is empty.'; continue; }
            $unit = $g($r, 'Unit');
            if ($unit === '' || ($units && !isset($units[strtolower($unit)]))) { $errors[] = 'Row ' . $line . ': unit "' . $unit . '" is not in Product units.'; continue; }
            if ($units) $unit = $units[strtolower($unit)];
            $uq = $g($r, 'Unit quantity'); $mrp = $g($r, 'MRP'); $price = $g($r, 'Selling price') ?: ($g($r, 'Main price') ?: $g($r, 'Price'));
            if (!is_numeric($uq) || (float) $uq <= 0) { $errors[] = 'Row ' . $line . ': unit quantity must be a number.'; continue; }
            if (!is_numeric($mrp) || !is_numeric($price) || (float) $price <= 0) { $errors[] = 'Row ' . $line . ': MRP and selling price must be numbers.'; continue; }
            if ((float) $price > (float) $mrp) { $errors[] = 'Row ' . $line . ': selling price is above MRP.'; continue; }
            $item = DB::table('items')->where('cat_id', $c->id)->whereRaw('LOWER(TRIM(item)) = ?', [strtolower($itemName)])->first();
            $key = ($item->id ?? 'new:' . strtolower($itemName) . ':' . $c->id) . '|' . (float) $uq . '|' . strtolower($unit);
            if (isset($existing[$key])) { $errors[] = 'Row ' . $line . ': "' . $itemName . ' ' . $uq . ' ' . $unit . '" already exists.'; continue; }
            $existing[$key] = true;
            if (!$item) $newItems[strtolower($itemName) . ':' . $c->id] = $itemName . ' (' . $c->name . ')';
            $u = CountryUnits::of($uq, $unit);
            $plan[] = ['line' => $line, 'category' => $c, 'item' => $item, 'item_name' => $itemName, 'unit' => $unit, 'uq' => (float) $uq, 'mrp' => (float) $mrp, 'price' => (float) $price,
                'cost' => is_numeric($g($r, 'Cost price')) ? (float) $g($r, 'Cost price') : 0, 'gross' => $g($r, 'Gross quantity'), 'stock' => is_numeric($g($r, 'Stock')) ? $g($r, 'Stock') : '0',
                'pieces' => $g($r, 'Pieces'), 'serves' => $g($r, 'Serves'), 'cook' => $g($r, 'Cooking time'), 'tag' => $g($r, 'Tag'), 'prop' => $g($r, 'Property'), 'desc' => $g($r, 'Description'), 'suspect' => $u['suspect']];
        }
        $preview = array_map(fn ($p) => ['line' => $p['line'], 'category' => $p['category']->name, 'item' => $p['item_name'], 'new_item' => !$p['item'], 'pack' => CountryScope::q($p['uq']) . ' ' . $p['unit'], 'mrp' => $p['mrp'], 'price' => $p['price'], 'suspect' => $p['suspect']], $plan);
        if (!$apply) return ['ok' => true, 'preview' => true, 'rows' => $preview, 'new_items' => array_values($newItems), 'errors' => $errors, 'message' => count($plan) . ' new product(s) ready, ' . count($newItems) . ' new item(s), ' . count($errors) . ' problem row(s).'];
        if (!$plan) return ['ok' => false, 'message' => 'Nothing to import.', 'errors' => $errors];
        $added = [];
        [$on, $off] = AdminRecords::statusValues('products');
        DB::transaction(function () use ($plan, $actor, &$added, $off) {
            $itemIds = [];
            foreach ($plan as $p) {
                $ik = strtolower($p['item_name']) . ':' . $p['category']->id;
                $itemId = $p['item']->id ?? ($itemIds[$ik] ?? null);
                if (!$itemId) {
                    $iv = ['cat_id' => $p['category']->id, 'item' => $p['item_name']];
                    if (CountryScope::has('items', 'status')) $iv['status'] = AdminRecords::statusValues('items')[0];
                    if (CountryScope::has('items', 'image')) $iv['image'] = '';
                    $itemId = $itemIds[$ik] = (int) DB::table('items')->insertGetId($iv);
                    AdminAudit::log($actor, 'add', 'Catalogue', 'items', $itemId, 'Added item "' . $p['item_name'] . '" (Excel import)', null, DB::table('items')->where('id', $itemId)->first());
                }
                $tags = $p['tag'] !== '' ? implode(',', array_map('trim', explode(',', $p['tag']))) : '';
                $r = self::save(null, ['category_id' => $p['category']->id, 'item_id' => $itemId, 'product_unit' => $p['unit'], 'unit_quantity' => $p['uq'], 'MRP' => $p['mrp'], 'main_price' => $p['price'],
                    'cost_price' => $p['cost'], 'gross_quantity' => $p['gross'], 'stock' => $p['stock'], 'pieces' => $p['pieces'], 'serves' => $p['serves'], 'cooking_time' => $p['cook'],
                    'tag' => $tags, 'property' => $p['prop'], 'description' => $p['desc'], 'confirm_pack' => 1, 'confirm_cost' => 1, 'active' => false], $actor);
                if (!$r['ok']) throw new \RuntimeException('Row ' . $p['line'] . ': ' . $r['message']);
                $added[] = $r['code'];
            }
        });
        return ['ok' => true, 'added' => $added, 'errors' => $errors, 'message' => count($added) . ' product(s) added (switched off: add photos, check them, then switch them on).'];
    }
}
