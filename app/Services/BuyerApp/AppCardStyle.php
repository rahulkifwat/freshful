<?php

namespace App\Services\BuyerApp;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Product card style chosen in Admin > Marketing > App control, and the frame colour for each product tag.
 * get() never throws: any problem returns the defaults, so the app keeps its normal card. Cached for 60 seconds.
 */
class AppCardStyle
{
    public const DEFAULTS = [
        'card_radius' => 32, 'img_radius' => 25, 'img_fit' => 'cover', 'img_shape' => '4:3', 'img_bg' => '#FFFFFF',
        'photo_shadow' => 'soft', 'card_shadow' => 'soft', 'no_photo' => 'category', 'name_lines' => 2, 'show_desc' => 1, 'desc_lines' => 2,
        'show_weight' => 1, 'show_ppk' => 1, 'show_rating' => 0, 'show_chip' => 1,
    ];

    private const CHOICES = [
        'img_fit' => ['cover', 'contain'], 'img_shape' => ['1:1', '4:3', '3:2'], 'photo_shadow' => ['none', 'soft', 'deep'], 'card_shadow' => ['none', 'soft', 'deep'],
        'no_photo' => ['category', 'brand'], 'name_lines' => [1, 2], 'desc_lines' => [1, 2],
    ];
    private const FLAGS = ['show_desc', 'show_weight', 'show_ppk', 'show_rating', 'show_chip'];
    private const RANGES = ['card_radius' => [16, 40], 'img_radius' => [0, 40]];

    public static function hex(string $v): ?string
    {
        $v = trim($v);

        return preg_match('/^#[0-9A-Fa-f]{6}$/', $v) ? strtoupper($v) : null;
    }

    /** What the customer app receives: {settings: {...}, tags: {"best seller": {label, from, to}}} */
    public static function get(): array
    {
        try {
            return Cache::remember('app_card_style_v1', 60, fn () => self::read());
        } catch (\Throwable $e) {
            return ['settings' => self::DEFAULTS, 'tags' => []];
        }
    }

    /** Uncached read, also used by the admin page. */
    public static function read(): array
    {
        $settings = self::DEFAULTS;
        $tags = [];
        if (Schema::hasTable('app_card_style')) {
            foreach (DB::table('app_card_style')->get(['skey', 'svalue']) as $r) {
                if (array_key_exists($r->skey, self::DEFAULTS)) {
                    $settings[$r->skey] = is_int(self::DEFAULTS[$r->skey]) ? (int) $r->svalue : (string) $r->svalue;
                }
            }
        }
        if (Schema::hasTable('app_card_tags')) {
            foreach (DB::table('app_card_tags')->orderBy('id')->limit(60)->get() as $r) {
                $tags[mb_strtolower($r->tag)] = ['label' => $r->tag, 'from' => $r->color_from, 'to' => $r->color_to];
            }
        }

        return ['settings' => $settings, 'tags' => $tags];
    }

    /** Checks and saves the settings. Returns an error message, or null when saved. */
    public static function saveSettings(array $in): ?string
    {
        $clean = [];
        foreach (self::DEFAULTS as $k => $def) {
            if (! array_key_exists($k, $in)) {
                continue;
            }
            $v = $in[$k];
            if (isset(self::CHOICES[$k])) {
                $ok = null;
                foreach (self::CHOICES[$k] as $c) {
                    if ((string) $c === (string) $v) {
                        $ok = $c;
                    }
                }
                if ($ok === null) {
                    return 'Card style: "' . $k . '" has a value that is not allowed.';
                }
                $clean[$k] = (string) $ok;
            } elseif (in_array($k, self::FLAGS, true)) {
                $clean[$k] = ($v === true || $v === 1 || $v === '1' || $v === 'true') ? '1' : '0';
            } elseif (isset(self::RANGES[$k])) {
                [$lo, $hi] = self::RANGES[$k];
                if (! is_numeric($v) || (int) $v < $lo || (int) $v > $hi) {
                    return 'Card style: "' . $k . '" must be a number from ' . $lo . ' to ' . $hi . '.';
                }
                $clean[$k] = (string) (int) $v;
            } elseif ($k === 'img_bg') {
                $h = self::hex((string) $v);
                if ($h === null) {
                    return 'Card style: the image background must be a colour like #FFFFFF.';
                }
                $clean[$k] = $h;
            }
        }
        foreach ($clean as $k => $v) {
            DB::table('app_card_style')->updateOrInsert(['skey' => $k], ['svalue' => $v, 'updated_at' => now(), 'created_at' => now()]);
        }

        return null;
    }

    /** Replaces the whole tag colour list. Returns an error message, or null when saved. */
    public static function saveTags(array $rows): ?string
    {
        $clean = [];
        foreach ($rows as $r) {
            $tag = trim(preg_replace('/\s+/', ' ', (string) ($r['tag'] ?? '')));
            if ($tag === '') {
                continue;
            }
            $from = self::hex((string) ($r['from'] ?? ''));
            $to = self::hex((string) ($r['to'] ?? '')) ?? $from;
            if ($from === null) {
                return 'Tag "' . mb_substr($tag, 0, 40) . '": the colour must look like #FFF3EC.';
            }
            $clean[mb_strtolower($tag)] = ['tag' => mb_substr($tag, 0, 60), 'from' => $from, 'to' => $to];
        }
        if (count($clean) > 40) {
            return 'Use at most 40 tag colours.';
        }
        DB::table('app_card_tags')->delete();
        foreach ($clean as $c) {
            DB::table('app_card_tags')->insert(['tag' => $c['tag'], 'color_from' => $c['from'], 'color_to' => $c['to'], 'created_at' => now(), 'updated_at' => now()]);
        }

        return null;
    }
}
