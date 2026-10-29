<?php

namespace App\Services\AdminConsole;

/**
 * Freshful admin console (admin update 2): the Google Maps key.
 * GOOGLE_MAPS_KEY in .env if set; otherwise the same key the customer website already loads Google Maps with
 * (found once in resources/views, then remembered for a day). The key is a browser key: it is visible in the
 * website's own pages anyway, so showing it to the admin page adds no exposure.
 */
class AdminMaps
{
    public static function key(): string
    {
        $k = trim((string) config('admin_console.maps_key', ''));
        if ($k !== '') return $k;
        try {
            return (string) cache()->remember('admin_console_maps_key', 86400, function () {
                foreach ([resource_path('views'), public_path()] as $dir) {
                    if (!is_dir($dir)) continue;
                    $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS));
                    $n = 0;
                    foreach ($it as $f) {
                        if (++$n > 6000) break;
                        if (!$f->isFile() || !preg_match('/\.(php|html|js)$/', $f->getFilename()) || $f->getSize() > 2000000) continue;
                        if (str_contains($f->getPathname(), '/country_console/')) continue;
                        $c = @file_get_contents($f->getPathname());
                        if ($c !== false && preg_match('#maps\.googleapis\.com/maps/api/js\?[^"\'\s>]*key=([A-Za-z0-9_\-]{20,60})#', $c, $m)) return $m[1];
                    }
                }
                return '';
            });
        } catch (\Throwable $e) {
            return '';
        }
    }
}
