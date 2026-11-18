<?php

namespace App\Services\AdminConsole;

use App\Services\BuyerApp\AppCardStyle;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Admin > Marketing > App control: one page for the home block order, the header colour,
 * the product card style and the frame colour of each tag. Every function returns the {ok, message} shape of the console.
 */
class AppControl
{
    private const TYPES = ['banners', 'categories', 'products', 'rewards', 'strip', 'coupons', 'cuts', 'reviews', 'ribbon', 'tiles'];

    public static function state(): array
    {
        $blocks = [];
        if (Schema::hasTable('app_home_blocks')) {
            foreach (DB::table('app_home_blocks')->orderBy('position')->orderBy('id')->limit(80)->get(['id', 'type', 'title', 'subtitle', 'link', 'block_key', 'status', 'starts_on', 'ends_on']) as $r) {
                $blocks[] = ['id' => (int) $r->id, 'type' => $r->type, 'title' => (string) $r->title, 'subtitle' => (string) $r->subtitle, 'key' => (string) $r->block_key,
                    'on' => $r->status === 'active', 'starts_on' => $r->starts_on, 'ends_on' => $r->ends_on];
            }
        }
        $theme = ['color_from' => '#F70202', 'color_to' => '#FF5500', 'text_color' => 'light', 'starts_on' => '', 'ends_on' => '', 'exists' => false];
        if (Schema::hasTable('app_home_theme') && ($t = DB::table('app_home_theme')->orderByDesc('id')->first())) {
            $theme = ['color_from' => (string) $t->color_from, 'color_to' => (string) $t->color_to, 'text_color' => $t->text_color === 'dark' ? 'dark' : 'light',
                'starts_on' => (string) $t->starts_on, 'ends_on' => (string) $t->ends_on, 'exists' => true, 'on' => $t->status === 'active'];
        }
        $style = AppCardStyle::read();
        $tags = [];
        foreach ($style['tags'] as $t) {
            $tags[] = ['tag' => $t['label'], 'from' => $t['from'], 'to' => $t['to']];
        }

        return ['blocks' => $blocks, 'theme' => $theme, 'card' => $style['settings'], 'tags' => $tags, 'tag_options' => self::tagOptions()];
    }

    /** Every product tag in use (products.tag, comma separated), so the admin picks from a list instead of typing. */
    public static function tagOptions(): array
    {
        $set = [];
        try {
            if (Schema::hasTable('products') && Schema::hasColumn('products', 'tag')) {
                foreach (DB::table('products')->whereNotNull('tag')->where('tag', '!=', '')->distinct()->limit(800)->pluck('tag') as $raw) {
                    foreach (explode(',', (string) $raw) as $t) {
                        $t = trim(preg_replace('/\s+/', ' ', $t));
                        if ($t !== '' && mb_strlen($t) <= 60) {
                            $set[mb_strtolower($t)] = $t;
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
        }
        ksort($set);

        return array_values($set);
    }

    public static function save(array $in): array
    {
        foreach (['app_home_blocks', 'app_home_theme', 'app_card_style', 'app_card_tags'] as $tb) {
            if (! Schema::hasTable($tb)) {
                return ['ok' => false, 'message' => 'The App control tables are missing. Run the installer again.'];
            }
        }
        try {
            DB::transaction(function () use ($in) {
            if (isset($in['blocks']) && is_array($in['blocks'])) {
                $ids = DB::table('app_home_blocks')->pluck('id')->map(fn ($x) => (int) $x)->all();
                $pos = 10;
                foreach ($in['blocks'] as $b) {
                    $id = (int) ($b['id'] ?? 0);
                    if (! in_array($id, $ids, true)) {
                        continue;
                    }
                    DB::table('app_home_blocks')->where('id', $id)->update(['position' => $pos, 'status' => ! empty($b['on']) ? 'active' : 'inactive', 'updated_at' => now()]);
                    $pos += 10;
                }
            }
            if (isset($in['theme']) && is_array($in['theme'])) {
                $t = $in['theme'];
                $from = AppCardStyle::hex((string) ($t['from'] ?? ''));
                $to = AppCardStyle::hex((string) ($t['to'] ?? '')) ?? $from;
                if ($from === null) {
                    throw new \RuntimeException('Top section colour: the start colour must look like #F70202.');
                }
                $date = fn ($v) => preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $v) ? (string) $v : null;
                $row = ['color_from' => $from, 'color_to' => $to, 'text_color' => ($t['text'] ?? '') === 'dark' ? 'dark' : 'light',
                    'starts_on' => $date($t['starts_on'] ?? ''), 'ends_on' => $date($t['ends_on'] ?? ''), 'status' => 'active', 'updated_at' => now()];
                $cur = DB::table('app_home_theme')->orderByDesc('id')->value('id');
                if ($cur) {
                    DB::table('app_home_theme')->where('id', $cur)->update($row);
                } else {
                    DB::table('app_home_theme')->insert($row + ['title' => 'App header colour', 'created_at' => now()]);
                }
            }
            if (isset($in['card']) && is_array($in['card']) && ($e = AppCardStyle::saveSettings($in['card']))) {
                throw new \RuntimeException($e);
            }
            if (isset($in['tags']) && is_array($in['tags']) && ($e = AppCardStyle::saveTags($in['tags']))) {
                throw new \RuntimeException($e);
            }
            });
        } catch (\RuntimeException $e) {
            return ['ok' => false, 'message' => $e->getMessage()];
        }
        foreach (['app_home_theme_v1', 'app_home_blocks_v1', 'app_card_style_v1'] as $k) {
            Cache::forget($k);
        }

        return ['ok' => true, 'message' => 'Saved. The app picks it up within a minute.'];
    }

    /** First-time layout: banners, categories and the product rows, so the order can then be changed by dragging. */
    public static function seed(): array
    {
        if (! Schema::hasTable('app_home_blocks')) {
            return ['ok' => false, 'message' => 'The App control tables are missing. Run the installer again.'];
        }
        if (DB::table('app_home_blocks')->count() > 0) {
            return ['ok' => false, 'message' => 'There are already blocks. Nothing was added.'];
        }
        $rows = [['banners', null, null, 10], ['categories', 'Shop by category', 'categories', 20], ['products', null, null, 30]];
        foreach ($rows as [$type, $title, $link, $pos]) {
            DB::table('app_home_blocks')->insert(['type' => $type, 'title' => $title, 'link' => $link, 'position' => $pos, 'status' => 'active', 'created_at' => now(), 'updated_at' => now()]);
        }
        Cache::forget('app_home_blocks_v1');

        return ['ok' => true, 'message' => 'Standard blocks created. Drag them, switch them on or off, then Save.'];
    }
}
