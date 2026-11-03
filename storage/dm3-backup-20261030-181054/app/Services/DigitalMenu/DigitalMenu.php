<?php

namespace App\Services\DigitalMenu;

use App\Services\Stock\StockGuard;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Digital menu boards (Admin sidebar > Marketing > Digital Menu).
 * One screen = one TV. A screen keeps a DRAFT and a LIVE copy of its set-up (layout, look, playlist, offers).
 * Prices and names of linked products are always read live, so a price change in Products reaches the TV within a minute.
 */
class DigitalMenu
{
    public const MAX_SCREENS = 10;

    public const LAYOUTS = [
        'cinema'   => 'Cinematic (one dish at a time)',
        'showcase' => 'Showcase (one item)',
        'duo'      => 'Two cards',
        'trio'     => 'Three cards',
        'board'    => 'Price board',
        'offers'   => 'Offers poster',
    ];

    public const PER_SLIDE = ['cinema' => 1, 'showcase' => 1, 'duo' => 2, 'trio' => 3];

    public const FONTS = ['Outfit', 'Poppins', 'Inter', 'Montserrat', 'Archivo Black', 'Playfair Display'];

    public const PRESETS = [
        'studio' => ['label' => 'Studio red', 'bg1' => '#2A0604', 'bg2' => '#6B0F06', 'angle' => 150, 'text' => '#FFFFFF', 'card' => '#2F0B07', 'cardText' => '#FFFFFF', 'accent' => '#F70202', 'accent2' => '#FF5500'],
        'fresh' => ['label' => 'Fresh light', 'bg1' => '#FFF8F3', 'bg2' => '#FFE6D8', 'angle' => 160, 'text' => '#1C1411', 'card' => '#FFFFFF', 'cardText' => '#1C1411', 'accent' => '#F70202', 'accent2' => '#FF5500'],
        'night' => ['label' => 'Night', 'bg1' => '#14100E', 'bg2' => '#2B1912', 'angle' => 160, 'text' => '#FFFFFF', 'card' => '#241915', 'cardText' => '#FFFFFF', 'accent' => '#FF3B1F', 'accent2' => '#FF7A00'],
        'ember' => ['label' => 'Ember', 'bg1' => '#F70202', 'bg2' => '#FF5500', 'angle' => 135, 'text' => '#FFFFFF', 'card' => '#FFFFFF', 'cardText' => '#1C1411', 'accent' => '#F70202', 'accent2' => '#FF5500'],
        'cream' => ['label' => 'Cream', 'bg1' => '#FBF3E4', 'bg2' => '#F3E3C5', 'angle' => 180, 'text' => '#2A1D12', 'card' => '#FFFFFF', 'cardText' => '#2A1D12', 'accent' => '#C2410C', 'accent2' => '#F97316'],
    ];

    public static function tableReady(): bool
    {
        try {
            return Schema::hasTable('dm_screens') && Schema::hasTable('dm_items') && Schema::hasTable('dm_publish_log');
        } catch (\Throwable $e) {
            return false;
        }
    }

    public static function defaults(string $name = 'Counter TV'): array
    {
        $p = self::PRESETS['studio'];

        return [
            'name' => $name, 'orientation' => 'landscape', 'layout' => 'cinema', 'seconds' => 10, 'per' => 8,
            'motion' => 'rise', 'idle' => true, 'hide_oos' => true, 'ticker' => 'Fresh stock every morning. Ask our team for cuts and weights.',
            'offers_title' => 'More today',
            'theme' => ['preset' => 'studio', 'bg1' => $p['bg1'], 'bg2' => $p['bg2'], 'angle' => $p['angle'], 'text' => $p['text'], 'card' => $p['card'], 'cardText' => $p['cardText'],
                'accent' => $p['accent'], 'accent2' => $p['accent2'], 'font' => 'Outfit', 'bgImage' => '', 'bgImageOpacity' => 25],
            'show' => ['logo' => true, 'clock' => true, 'outlet' => true, 'mrp' => true, 'save' => true, 'stock' => true],
            'items' => [], 'offers' => [],
        ];
    }

    private static function color($v, string $fallback): string
    {
        return is_string($v) && preg_match('/^#[0-9a-fA-F]{6}$/', $v) ? strtoupper($v) : $fallback;
    }

    private static function file($v): string
    {
        $v = basename((string) $v);

        return preg_match('/^[A-Za-z0-9._-]{3,120}$/', $v) && ! str_contains($v, '..') ? $v : '';
    }

    private static function when($v): ?string
    {
        $v = trim((string) $v);
        if ($v === '') {
            return null;
        }
        $t = strtotime($v);

        return $t ? date('Y-m-d H:i', $t) : null;
    }

    private static function bool($v, bool $d = false): bool
    {
        return $v === null ? $d : filter_var($v, FILTER_VALIDATE_BOOLEAN);
    }

    /** Clean any submitted set-up. Unknown keys are dropped; every value is forced into a safe range. */
    public static function sanitize(array $in, ?array $base = null): array
    {
        $d = $base ?: self::defaults();
        $o = $d;
        $o['name'] = mb_substr(trim((string) ($in['name'] ?? $d['name'])) ?: $d['name'], 0, 80);
        $o['orientation'] = in_array($in['orientation'] ?? '', ['landscape', 'portrait'], true) ? $in['orientation'] : $d['orientation'];
        $o['layout'] = array_key_exists($in['layout'] ?? '', self::LAYOUTS) ? $in['layout'] : $d['layout'];
        $o['seconds'] = max(5, min(120, (int) ($in['seconds'] ?? $d['seconds'])));
        $o['per'] = in_array((int) ($in['per'] ?? $d['per']), [4, 6, 8, 10, 12], true) ? (int) ($in['per'] ?? $d['per']) : $d['per'];
        $o['motion'] = in_array($in['motion'] ?? '', ['rise', 'fade', 'none'], true) ? $in['motion'] : $d['motion'];
        $o['idle'] = self::bool($in['idle'] ?? null, $d['idle']);
        $o['hide_oos'] = self::bool($in['hide_oos'] ?? null, $d['hide_oos']);
        $o['ticker'] = mb_substr(trim((string) ($in['ticker'] ?? $d['ticker'])), 0, 180);
        $o['offers_title'] = mb_substr(trim((string) ($in['offers_title'] ?? $d['offers_title'])) ?: 'More today', 0, 30);

        $t = is_array($in['theme'] ?? null) ? $in['theme'] : [];
        $dt = $d['theme'];
        $o['theme'] = [
            'preset' => array_key_exists($t['preset'] ?? '', self::PRESETS) ? $t['preset'] : (($t['preset'] ?? '') === 'custom' ? 'custom' : $dt['preset']),
            'bg1' => self::color($t['bg1'] ?? null, $dt['bg1']), 'bg2' => self::color($t['bg2'] ?? null, $dt['bg2']),
            'angle' => max(0, min(360, (int) ($t['angle'] ?? $dt['angle']))),
            'text' => self::color($t['text'] ?? null, $dt['text']), 'card' => self::color($t['card'] ?? null, $dt['card']),
            'cardText' => self::color($t['cardText'] ?? null, $dt['cardText']),
            'accent' => self::color($t['accent'] ?? null, $dt['accent']), 'accent2' => self::color($t['accent2'] ?? null, $dt['accent2']),
            'font' => in_array($t['font'] ?? '', self::FONTS, true) ? $t['font'] : $dt['font'],
            'bgImage' => array_key_exists('bgImage', $t) ? self::file($t['bgImage']) : $dt['bgImage'],
            'bgImageOpacity' => max(0, min(100, (int) ($t['bgImageOpacity'] ?? $dt['bgImageOpacity']))),
        ];

        $s = is_array($in['show'] ?? null) ? $in['show'] : [];
        foreach (['logo', 'clock', 'outlet', 'mrp', 'save', 'stock'] as $k) {
            $o['show'][$k] = self::bool($s[$k] ?? null, $d['show'][$k]);
        }

        $items = [];
        $seen = [];
        foreach (array_slice(is_array($in['items'] ?? null) ? $in['items'] : [], 0, 60) as $r) {
            $id = (int) (is_array($r) ? ($r['id'] ?? 0) : $r);
            if ($id < 1 || isset($seen[$id])) {
                continue;
            }
            $seen[$id] = true;
            $items[] = ['id' => $id, 'from' => is_array($r) ? self::when($r['from'] ?? null) : null, 'until' => is_array($r) ? self::when($r['until'] ?? null) : null, 'featured' => is_array($r) && self::bool($r['featured'] ?? null)];
        }
        $o['items'] = $items;

        $offers = [];
        foreach (array_slice(is_array($in['offers'] ?? null) ? $in['offers'] : [], 0, 8) as $r) {
            if (! is_array($r)) {
                continue;
            }
            $name = mb_substr(trim((string) ($r['name'] ?? '')), 0, 40);
            if ($name === '') {
                continue;
            }
            $price = $r['price'] ?? null;
            $offers[] = [
                'name' => $name, 'price' => ($price === null || $price === '' || ! is_numeric($price)) ? null : round(max(0, (float) $price), 2),
                'pack' => mb_substr(trim((string) ($r['pack'] ?? '')), 0, 20), 'image' => self::file($r['image'] ?? ''),
                'bg' => self::color($r['bg'] ?? null, '#FFF1E8'), 'color' => self::color($r['color'] ?? null, '#1C1411'),
            ];
        }
        $o['offers'] = $offers;

        return $o;
    }

    public static function hash(array $cfg): string
    {
        return substr(sha1(json_encode($cfg)), 0, 16);
    }

    public static function newKey(): string
    {
        return bin2hex(random_bytes(16));
    }

    /** Library rows (dm_items) joined with the live product, as ready-to-show values. Keyed by dm_items.id. */
    public static function resolve(array $ids): array
    {
        $ids = array_values(array_unique(array_map('intval', $ids)));
        if (! $ids) {
            return [];
        }
        $rows = DB::table('dm_items')->whereIn('id', $ids)->get();
        $codes = $rows->pluck('product_code')->filter()->unique()->values()->all();
        $prods = $codes ? DB::table('products')->whereIn('product_id', $codes)->get(['product_id', 'product_name', 'unit_quantity', 'product_unit', 'main_price', 'MRP', 'product_image', 'status'])->keyBy('product_id') : collect();
        $out = [];
        foreach ($rows as $r) {
            $p = $r->product_code ? ($prods[$r->product_code] ?? null) : null;
            $price = $r->price !== null ? (float) $r->price : ($p ? (float) $p->main_price : 0.0);
            $mrp = $r->mrp !== null ? (float) $r->mrp : ($p ? (float) $p->MRP : 0.0);
            $img = '';
            if ($r->image) {
                $img = '/uploads/images/digital_menu/' . $r->image;
            } elseif ($p && $p->product_image) {
                $img = '/uploads/images/products/' . $p->product_image;
            }
            $out[(int) $r->id] = [
                'id' => (int) $r->id, 'code' => $r->product_code, 'name' => trim((string) ($r->name ?: ($p->product_name ?? ''))),
                'pack' => trim((string) ($r->pack ?: ($p ? trim($p->unit_quantity . ' ' . $p->product_unit) : ''))),
                'price' => $price, 'mrp' => $mrp > $price ? $mrp : 0.0, 'desc' => (string) $r->description, 'image' => $img,
                'cutout' => (bool) $r->cutout, 'badge' => (string) $r->badge, 'category' => (string) $r->category,
                'veg' => $r->veg === null ? null : (bool) $r->veg, 'active' => $r->status === 'active', 'product_missing' => $r->product_code && ! $p,
            ];
        }

        return $out;
    }

    /** Packs that may still be sold at this outlet (same rule as the website and the counter). [code => packs] */
    public static function packs(int $hubId, array $codes): array
    {
        $codes = array_values(array_unique(array_filter($codes)));
        if (! $codes || $hubId < 1) {
            return [];
        }
        $live = DB::table('inventory')->where('hub_id', $hubId)->whereIn('product_id', $codes)->orderBy('id')->get(['product_id', 'live_stock'])->unique('product_id')->pluck('live_stock', 'product_id');
        try {
            $net = StockGuard::net($hubId, $live);
        } catch (\Throwable $e) {
            $net = $live;
        }
        $net = $net instanceof \Illuminate\Support\Collection ? $net->all() : (array) $net;
        $o = [];
        foreach ($codes as $c) {
            $o[$c] = (float) ($net[$c] ?? 0);
        }

        return $o;
    }

    public static function outletLabel(int $hubId): string
    {
        $hub = DB::table('hubs')->where('id', $hubId)->first();
        if (! $hub) {
            return '';
        }
        $city = Schema::hasTable('cities') ? (string) DB::table('cities')->where('id', $hub->city_id)->value('city') : '';

        return trim($city . ($hub->hub ? ' (' . $hub->hub . ')' : ''));
    }

    /** The JSON the TV player reads. Nothing internal (no staff, no database ids, no paths other than picture addresses). */
    public static function payload(array $cfg, int $hubId): array
    {
        $tz = new \DateTimeZone((string) env('DIGITAL_MENU_TZ', 'Asia/Kolkata'));
        $now = (new \DateTime('now', $tz))->format('Y-m-d H:i');
        $res = self::resolve(array_column($cfg['items'], 'id'));
        $codes = [];
        foreach ($res as $r) {
            if ($r['code']) {
                $codes[] = $r['code'];
            }
        }
        $packs = self::packs($hubId, $codes);
        $items = [];
        foreach ($cfg['items'] as $ref) {
            $r = $res[$ref['id']] ?? null;
            if (! $r || ! $r['active'] || $r['product_missing'] || $r['name'] === '' || $r['price'] <= 0) {
                continue;
            }
            if (! empty($ref['from']) && $now < $ref['from']) {
                continue;
            }
            if (! empty($ref['until']) && $now > $ref['until']) {
                continue;
            }
            $few = false;
            if ($r['code'] && array_key_exists($r['code'], $packs)) {
                if ($cfg['hide_oos'] && $packs[$r['code']] <= 0) {
                    continue;
                }
                $few = $packs[$r['code']] > 0 && $packs[$r['code']] <= 3;
            }
            $items[] = [
                'name' => $r['name'], 'pack' => $r['pack'], 'price' => $r['price'], 'mrp' => $r['mrp'],
                'save' => $r['mrp'] > $r['price'] ? (int) round(($r['mrp'] - $r['price']) / $r['mrp'] * 100) : 0,
                'desc' => $r['desc'], 'img' => $r['image'], 'cutout' => $r['cutout'], 'badge' => $r['badge'], 'cat' => $r['category'], 'veg' => $r['veg'],
                'few' => $few, 'star' => ! empty($ref['featured']),
            ];
        }
        $offers = [];
        foreach ($cfg['offers'] as $o) {
            $offers[] = ['name' => $o['name'], 'price' => $o['price'], 'pack' => $o['pack'], 'img' => $o['image'] ? '/uploads/images/digital_menu/' . $o['image'] : '', 'bg' => $o['bg'], 'color' => $o['color']];
        }
        $t = $cfg['theme'];
        $body = [
            'screen' => ['name' => $cfg['name'], 'orientation' => $cfg['orientation'], 'layout' => $cfg['layout'], 'seconds' => $cfg['seconds'], 'per' => $cfg['per'],
                'motion' => $cfg['motion'], 'idle' => $cfg['idle'], 'ticker' => $cfg['ticker'], 'offersTitle' => $cfg['offers_title'], 'outlet' => self::outletLabel($hubId), 'show' => $cfg['show']],
            'theme' => ['bg1' => $t['bg1'], 'bg2' => $t['bg2'], 'angle' => $t['angle'], 'text' => $t['text'], 'card' => $t['card'], 'cardText' => $t['cardText'],
                'accent' => $t['accent'], 'accent2' => $t['accent2'], 'font' => $t['font'], 'bgImage' => $t['bgImage'] ? '/uploads/images/digital_menu/' . $t['bgImage'] : '', 'bgImageOpacity' => $t['bgImageOpacity']],
            'items' => $items, 'offers' => $offers,
        ];
        $body['version'] = substr(sha1(json_encode($body)), 0, 16);

        return $body;
    }
}
