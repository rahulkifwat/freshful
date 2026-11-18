<?php

namespace App\Services\BuyerApp;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * What the admin chose in App control: the header colour and the home page blocks.
 * Every problem returns null, which means "the app shows its normal home page". Cached for 60 seconds.
 */
class AppHomeConfig
{
    private const TYPES = ['banners', 'categories', 'products', 'rewards', 'strip', 'coupons', 'cuts', 'reviews', 'ribbon', 'tiles'];

    public static function theme(): ?array
    {
        try {
            return Cache::remember('app_home_theme_v1', 60, function () {
                if (! Schema::hasTable('app_home_theme')) {
                    return null;
                }
                foreach (DB::table('app_home_theme')->orderByDesc('id')->limit(30)->get() as $r) {
                    if (! self::live($r)) {
                        continue;
                    }
                    $from = self::hex($r->color_from ?? '');
                    if ($from === null) {
                        continue;
                    }

                    return ['from' => $from, 'to' => self::hex($r->color_to ?? '') ?? $from, 'text' => ($r->text_color ?? '') === 'dark' ? 'dark' : 'light'];
                }

                return null;
            });
        } catch (\Throwable $e) {
            return null;
        }
    }

    public static function blocks(): ?array
    {
        try {
            return Cache::remember('app_home_blocks_v1', 60, function () {
                if (! Schema::hasTable('app_home_blocks')) {
                    return null;
                }
                $out = [];
                foreach (DB::table('app_home_blocks')->orderBy('position')->orderBy('id')->limit(60)->get() as $r) {
                    if (! self::live($r) || ! in_array($r->type, self::TYPES, true)) {
                        continue;
                    }
                    $b = ['id' => 'b' . $r->id, 'type' => $r->type];
                    foreach (['title', 'subtitle', 'link'] as $k) {
                        if (trim((string) ($r->$k ?? '')) !== '') {
                            $b[$k] = mb_substr(trim((string) $r->$k), 0, 160);
                        }
                    }
                    if (trim((string) ($r->after_row ?? '')) !== '') {
                        $b['after'] = mb_substr(trim((string) $r->after_row), 0, 60);
                    }
                    if (trim((string) ($r->block_key ?? '')) !== '') {
                        $b['key'] = trim((string) $r->block_key);
                    }
                    $items = self::items($r->type, $r->items_text ?? '');
                    if ($items) {
                        $b['items'] = $items;
                    }
                    $out[] = $b;
                }

                return $out ?: null;
            });
        } catch (\Throwable $e) {
            return null;
        }
    }

    private static function live(object $r): bool
    {
        if (! in_array(strtolower(trim((string) ($r->status ?? ''))), ['active', '1', 'on'], true)) {
            return false;
        }
        $today = now('Asia/Kolkata')->format('Y-m-d');
        $from = (string) ($r->starts_on ?? '');
        $to = (string) ($r->ends_on ?? '');
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $from) && $from > $today) {
            return false;
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $to) && $to < $today) {
            return false;
        }

        return true;
    }

    private static function hex(string $v): ?string
    {
        $v = trim($v);

        return preg_match('/^#[0-9A-Fa-f]{6}$/', $v) ? strtoupper($v) : null;
    }

    /** coupons: Title | Text | CODE    reviews: Name | Review | rating 1-5    cuts: Title | Text | link | #colour */
    private static function items(string $type, string $text): array
    {
        if (! in_array($type, ['coupons', 'reviews', 'cuts', 'ribbon', 'tiles'], true)) {
            return [];
        }
        $out = [];
        foreach (preg_split('/\r\n|\r|\n/', $text) as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            $p = array_map(fn ($x) => mb_substr(trim($x), 0, 200), explode('|', $line));
            if ($type === 'ribbon' || $type === 'tiles') {
                $img = trim($p[1] ?? '');
                if ($img !== '' && ! preg_match('#^https?://#i', $img)) {
                    $img = 'https://freshful.in/' . ltrim($img, '/');
                }
                $i = ['title' => $p[0] ?? '', 'link' => $p[2] ?? ''];
                if ($img !== '') {
                    $i['image'] = $img;
                }
                if (self::hex($p[3] ?? '') !== null) {
                    $i['color'] = self::hex($p[3]);
                }
                $out[] = $i;
            } elseif ($type === 'coupons') {
                $out[] = ['title' => $p[0] ?? '', 'text' => $p[1] ?? '', 'code' => strtoupper(preg_replace('/[^A-Za-z0-9_\-]/', '', $p[2] ?? ''))];
            } elseif ($type === 'reviews') {
                $out[] = ['title' => $p[0] ?? '', 'text' => $p[1] ?? '', 'rating' => max(1, min(5, (int) ($p[2] ?? 5)))];
            } else {
                $i = ['title' => $p[0] ?? '', 'text' => $p[1] ?? '', 'link' => $p[2] ?? ''];
                if (self::hex($p[3] ?? '') !== null) {
                    $i['color'] = self::hex($p[3]);
                }
                $out[] = $i;
            }
            if (count($out) >= 12) {
                break;
            }
        }

        return $out;
    }
}
