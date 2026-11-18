<?php

namespace App\Services\Storefront;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Home page settings an admin can change (table storefront_settings: name, value).
 * Safe when the table is missing: every read falls back to the defaults.
 */
class StorefrontSettings
{
    public const DEFAULTS = [
        'cat_bg_from' => '#FFF1E6',
        'cat_bg_to' => '#FDE6EF',
        'cat_bg_angle' => '135',
        'cat_bg_opacity' => '100',
        'cat_title' => 'Shop by category',
        'cat_sub' => "Find what you need for today's meal",
        'cat_columns' => '8',
        'hero_source' => 'products',
        'hero_seconds' => '6',
        'hero_tag' => '',
        'hero_count' => '4',
        'show_side' => '1',
        'show_trust' => '1',
        'show_categories' => '1',
        'show_buy_again' => '1',
        'show_coupons' => '1',
        'section_limit' => '8',
        'hidden_tags' => '',
        'stock_badges' => '1',
        'low_stock_at' => '5',
    ];

    public const FLAGS = ['show_side', 'show_trust', 'show_categories', 'show_buy_again', 'show_coupons', 'stock_badges'];

    public const PRESETS = [
        'Peach' => ['#FFF1E6', '#FDE6EF'],
        'Mint' => ['#E6F7EE', '#E3F0FF'],
        'Lavender' => ['#EFE7FF', '#FDE7F3'],
        'Butter' => ['#FFF7D6', '#FFE8D6'],
        'Sky' => ['#E3F2FF', '#EEE6FF'],
        'White' => ['#FFFFFF', '#FFFFFF'],
    ];

    private ?array $cache = null;

    public function tableReady(): bool
    {
        try {
            return Schema::hasTable('storefront_settings');
        } catch (Throwable $e) {
            return false;
        }
    }

    public function all(): array
    {
        if ($this->cache !== null) {
            return $this->cache;
        }
        $values = self::DEFAULTS;
        try {
            if ($this->tableReady()) {
                foreach (DB::table('storefront_settings')->get(['name', 'value']) as $row) {
                    $values[(string) $row->name] = (string) $row->value;
                }
            }
        } catch (Throwable $e) {
            // defaults
        }

        return $this->cache = $values;
    }

    public function flag(string $name): bool
    {
        return (string) $this->get($name, '1') !== '0';
    }

    public function int(string $name, int $default, int $min, int $max): int
    {
        return max($min, min($max, (int) $this->get($name, $default)));
    }

    /** Tag names the admin switched off on the home page (lower case). */
    public function hiddenTags(): array
    {
        return array_values(array_filter(array_map(fn ($t) => mb_strtolower(trim($t)), explode('|', (string) $this->get('hidden_tags', '')))));
    }

    public function get(string $name, $default = null)
    {
        return $this->all()[$name] ?? $default;
    }

    public function set(array $values): void
    {
        foreach ($values as $name => $value) {
            DB::table('storefront_settings')->updateOrInsert(
                ['name' => $name],
                ['value' => (string) $value, 'updated_at' => now()]
            );
        }
        $this->cache = null;
    }

    public function forget(string $name): void
    {
        DB::table('storefront_settings')->where('name', $name)->delete();
        $this->cache = null;
    }

    public static function validHex(?string $hex): bool
    {
        return is_string($hex) && preg_match('/^#[0-9a-fA-F]{6}$/', $hex) === 1;
    }

    public static function rgba(string $hex, int $opacityPercent): string
    {
        if (! self::validHex($hex)) {
            $hex = '#FFFFFF';
        }
        $r = hexdec(substr($hex, 1, 2));
        $g = hexdec(substr($hex, 3, 2));
        $b = hexdec(substr($hex, 5, 2));
        $a = max(0, min(100, $opacityPercent)) / 100;

        return 'rgba('.$r.','.$g.','.$b.','.rtrim(rtrim(number_format($a, 2, '.', ''), '0'), '.').')';
    }

    /** CSS background for the "Shop by category" panel. */
    public function categoryBackground(): string
    {
        $from = $this->get('cat_bg_from');
        $to = $this->get('cat_bg_to');
        $angle = max(0, min(360, (int) $this->get('cat_bg_angle', 135)));
        $opacity = max(0, min(100, (int) $this->get('cat_bg_opacity', 100)));

        return 'linear-gradient('.$angle.'deg,'.self::rgba($from, $opacity).','.self::rgba($to, $opacity).')';
    }

    /** High-resolution category images uploaded in the admin: category id => public URL. */
    public function categoryHd(): array
    {
        $out = [];
        foreach ($this->all() as $name => $value) {
            if (strncmp($name, 'cat_hd_', 7) === 0 && $value !== '') {
                $out[(int) substr($name, 7)] = asset('uploads/images/category_hd/'.$value);
            }
        }

        return $out;
    }
}
