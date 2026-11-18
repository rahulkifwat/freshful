<?php

namespace App\Services\AdminConsole;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Admin > Marketing > Home pictures: the ribbon banner and the picture tiles (Shop by store, Shop by cuisine, recipes).
 * They are rows of app_home_blocks (types ribbon and tiles); each tile is one line "Title | picture | link | #colour" in items_text.
 */
class HomePics
{
    private const SHAPES = ['store', 'cuisine', 'recipe'];

    private static function clean(string $v, int $max): string
    {
        return mb_substr(trim(str_replace(['|', "\r", "\n"], ' ', $v)), 0, $max);
    }

    private static function link(string $v): string
    {
        $v = trim($v);

        return preg_match('/^(categories|coupons|cart|search|(category|section|search):[A-Za-z0-9_\- ]{1,40})$/', $v) ? $v : '';
    }

    private static function picture(string $v): string
    {
        $v = trim($v);
        if ($v === '') {
            return '';
        }
        if (! preg_match('#^https?://#i', $v)) {
            $v = 'https://freshful.in/' . ltrim($v, '/');
        }

        return preg_match('#^https://[^\s|]{4,190}$#i', $v) ? $v : '';
    }

    public static function state(): array
    {
        $out = [];
        if (Schema::hasTable('app_home_blocks')) {
            foreach (DB::table('app_home_blocks')->whereIn('type', ['ribbon', 'tiles'])->orderBy('position')->orderBy('id')->limit(40)->get() as $r) {
                $items = [];
                foreach (preg_split('/\r\n|\r|\n/', (string) $r->items_text) as $line) {
                    if (trim($line) === '') {
                        continue;
                    }
                    $p = array_map('trim', explode('|', $line));
                    $items[] = ['title' => $p[0] ?? '', 'image' => $p[1] ?? '', 'link' => $p[2] ?? '', 'color' => $p[3] ?? ''];
                }
                $out[] = ['id' => (int) $r->id, 'type' => $r->type, 'shape' => (string) $r->block_key, 'title' => (string) $r->title, 'subtitle' => (string) $r->subtitle,
                    'on' => $r->status === 'active', 'items' => $items];
            }
        }

        return ['ready' => Schema::hasTable('app_home_blocks'), 'blocks' => $out];
    }

    public static function save(array $in): array
    {
        if (! Schema::hasTable('app_home_blocks')) {
            return ['ok' => false, 'message' => 'The App control tables are missing. Install App control first.'];
        }
        $blocks = isset($in['blocks']) && is_array($in['blocks']) ? $in['blocks'] : [];
        $del = isset($in['delete']) && is_array($in['delete']) ? array_map('intval', $in['delete']) : [];
        try {
            DB::transaction(function () use ($blocks, $del) {
                $mine = DB::table('app_home_blocks')->whereIn('type', ['ribbon', 'tiles'])->pluck('id')->map(fn ($x) => (int) $x)->all();
                foreach ($del as $id) {
                    if (in_array($id, $mine, true)) {
                        DB::table('app_home_blocks')->where('id', $id)->delete();
                    }
                }
                $next = (int) DB::table('app_home_blocks')->max('position') + 10;
                foreach (array_slice($blocks, 0, 40) as $b) {
                    $type = ($b['type'] ?? '') === 'ribbon' ? 'ribbon' : 'tiles';
                    $shape = in_array($b['shape'] ?? '', self::SHAPES, true) ? $b['shape'] : 'store';
                    $lines = [];
                    foreach (array_slice((array) ($b['items'] ?? []), 0, $type === 'ribbon' ? 1 : 12) as $it) {
                        $title = self::clean((string) ($it['title'] ?? ''), 60);
                        $img = self::picture((string) ($it['image'] ?? ''));
                        if ($title === '' && $img === '') {
                            continue;
                        }
                        $color = preg_match('/^#[0-9A-Fa-f]{6}$/', trim((string) ($it['color'] ?? ''))) ? strtoupper(trim($it['color'])) : '';
                        $lines[] = $title . ' | ' . $img . ' | ' . self::link((string) ($it['link'] ?? '')) . ($color !== '' ? ' | ' . $color : '');
                    }
                    $row = ['type' => $type, 'title' => self::clean((string) ($b['title'] ?? ''), 120) ?: null, 'subtitle' => self::clean((string) ($b['subtitle'] ?? ''), 160) ?: null,
                        'block_key' => $type === 'tiles' ? $shape : null, 'items_text' => implode("\n", $lines), 'status' => ! empty($b['on']) ? 'active' : 'inactive', 'updated_at' => now()];
                    $id = (int) ($b['id'] ?? 0);
                    if ($id > 0 && in_array($id, $mine, true)) {
                        DB::table('app_home_blocks')->where('id', $id)->update($row);
                    } else {
                        DB::table('app_home_blocks')->insert($row + ['position' => $next, 'created_at' => now()]);
                        $next += 10;
                    }
                }
            });
        } catch (\Throwable $e) {
            return ['ok' => false, 'message' => 'Could not save: ' . mb_substr($e->getMessage(), 0, 160)];
        }
        Cache::forget('app_home_blocks_v1');

        return ['ok' => true, 'message' => 'Saved. The app shows it within a minute. Use App control to drag the block to where you want it.'];
    }

    /** Saves one uploaded picture under uploads/images/home and returns its address. */
    public static function image($file): array
    {
        if (! $file || ! $file->isValid()) {
            return ['ok' => false, 'message' => 'Choose a picture first.'];
        }
        if ($file->getSize() > 4 * 1024 * 1024) {
            return ['ok' => false, 'message' => 'The picture is bigger than 4 MB. Make it smaller and try again.'];
        }
        $ext = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'][(string) $file->getMimeType()] ?? null;
        if ($ext === null || @getimagesize($file->getRealPath()) === false) {
            return ['ok' => false, 'message' => 'Only JPG, PNG or WEBP pictures can be used.'];
        }
        $root = null;
        foreach ([base_path('../public_html'), public_path()] as $cand) {
            if (is_dir($cand . '/uploads/images')) {
                $root = $cand;
                break;
            }
        }
        if ($root === null) {
            return ['ok' => false, 'message' => 'The pictures folder was not found on the server.'];
        }
        $dir = $root . '/uploads/images/home';
        if (! is_dir($dir) && ! @mkdir($dir, 0755, true)) {
            return ['ok' => false, 'message' => 'The server could not create the pictures folder.'];
        }
        $name = 'home_' . date('Ymd_His') . '_' . substr(bin2hex(random_bytes(4)), 0, 6) . '.' . $ext;
        try {
            $file->move($dir, $name);
        } catch (\Throwable $e) {
            return ['ok' => false, 'message' => 'The server could not save the picture.'];
        }

        return ['ok' => true, 'message' => 'Uploaded.', 'url' => 'https://freshful.in/uploads/images/home/' . $name];
    }
}
