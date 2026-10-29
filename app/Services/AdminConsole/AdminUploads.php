<?php

namespace App\Services\AdminConsole;

use Illuminate\Http\UploadedFile;

/**
 * Freshful admin console: image uploads, saved exactly where the old admin saved them, so the website and the
 * customer app keep showing them (public_html/uploads/images/<folder>/, file name only in the database).
 * Only real images (jpg, png, webp, gif), checked by their content, at most 4 MB.
 */
class AdminUploads
{
    public const FOLDERS = ['', 'products', 'banners', 'offers', 'coupons', 'promotions', 'certificates', 'users'];
    private const EXT = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp', 'gif' => 'image/gif'];

    /** Folder that holds uploads/images (the live site's public folder). */
    public static function root(): ?string
    {
        $c = trim((string) config('admin_console.uploads_root', ''));
        $cands = array_filter([$c, base_path('../public_html/uploads'), public_path('uploads')]);
        foreach ($cands as $p) { if (is_dir($p . '/images')) return rtrim(realpath($p) ?: $p, '/'); }
        return null;
    }

    public static function url(string $folder, ?string $file): string
    {
        $file = trim((string) $file);
        if ($file === '' || $file === 'false' || $file === '0') return '';
        if (preg_match('#^https?://#i', $file)) return $file;
        return url('uploads/images/' . ($folder !== '' ? $folder . '/' : '') . rawurlencode($file));
    }

    public static function save(UploadedFile $f, string $folder): array
    {
        if (!in_array($folder, self::FOLDERS, true)) return ['ok' => false, 'message' => 'Unknown image folder.'];
        if (!$f->isValid()) return ['ok' => false, 'message' => 'The upload did not finish. Try again.'];
        if ($f->getSize() > 4 * 1024 * 1024) return ['ok' => false, 'message' => 'The image is larger than 4 MB. Make it smaller and try again.'];
        $ext = strtolower($f->getClientOriginalExtension());
        if ($ext === 'jpeg') $ext = 'jpg';
        $mime = '';
        try { $mime = (string) $f->getMimeType(); } catch (\Throwable $e) {}
        $info = @getimagesize($f->getRealPath());
        if (!isset(self::EXT[$ext]) || !$info || !in_array($info['mime'] ?? '', array_values(self::EXT), true)) {
            return ['ok' => false, 'message' => 'Only JPG, PNG, WEBP or GIF images can be uploaded.'];
        }
        $root = self::root();
        if (!$root) return ['ok' => false, 'message' => 'The uploads folder was not found on the server.'];
        $dir = $root . '/images' . ($folder !== '' ? '/' . $folder : '');
        if (!is_dir($dir) && !@mkdir($dir, 0755, true)) return ['ok' => false, 'message' => 'Could not create the image folder.'];
        $name = substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 5) . time() . '.' . $ext;
        try { $f->move($dir, $name); } catch (\Throwable $e) { return ['ok' => false, 'message' => 'Could not save the image.']; }
        return ['ok' => true, 'file' => $name, 'url' => self::url($folder, $name), 'width' => (int) ($info[0] ?? 0), 'height' => (int) ($info[1] ?? 0), 'message' => 'Image uploaded.'];
    }
}
