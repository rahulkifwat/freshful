<?php

namespace App\Services\DigitalMenu;

/**
 * Removes a plain (white, grey, cream or any single colour) studio background from a product picture and
 * writes a trimmed PNG with a soft edge. Uses only the GD extension, so it runs on the shared host.
 * It is not an AI cutter: it works best for dishes and products photographed on a plain background.
 */
class Cutout
{
    public static function available(): bool
    {
        return extension_loaded('gd') && function_exists('imagecreatetruecolor') && function_exists('imagepng');
    }

    private static function load(string $path)
    {
        $sz = @getimagesize($path);
        if (! $sz) {
            return null;
        }
        switch ($sz[2]) {
            case IMAGETYPE_PNG:
                return @imagecreatefrompng($path) ?: null;
            case IMAGETYPE_JPEG:
                return @imagecreatefromjpeg($path) ?: null;
            case IMAGETYPE_WEBP:
                return function_exists('imagecreatefromwebp') ? (@imagecreatefromwebp($path) ?: null) : null;
        }

        return null;
    }

    /** @return array{0:bool,1:string,2:array} [ok, message, info] */
    public static function run(string $src, string $dst, int $tol = 40, int $maxSide = 1200): array
    {
        if (! self::available()) {
            return [false, 'The server has no image library for background removal.', []];
        }
        $tol = max(10, min(90, $tol));
        $im = self::load($src);
        if (! $im) {
            return [false, 'The picture could not be read.', []];
        }
        $w0 = imagesx($im);
        $h0 = imagesy($im);
        $k = min(1, $maxSide / max($w0, $h0));
        $w = max(1, (int) round($w0 * $k));
        $h = max(1, (int) round($h0 * $k));
        $img = imagecreatetruecolor($w, $h);
        imagealphablending($img, false);
        imagesavealpha($img, true);
        imagefill($img, 0, 0, imagecolorallocatealpha($img, 255, 255, 255, 0));
        imagealphablending($img, true);
        imagecopyresampled($img, $im, 0, 0, 0, 0, $w, $h, $w0, $h0);
        imagedestroy($im);
        imagealphablending($img, false);

        // pixel table: r,g,b,a(0..127 gd alpha)
        $n = $w * $h;
        $R = new \SplFixedArray($n);
        $G = new \SplFixedArray($n);
        $Bc = new \SplFixedArray($n);
        $A = new \SplFixedArray($n);
        $transparentEdge = 0;
        $edgeN = 0;
        for ($y = 0; $y < $h; $y++) {
            for ($x = 0; $x < $w; $x++) {
                $c = imagecolorat($img, $x, $y);
                $i = $y * $w + $x;
                $A[$i] = ($c >> 24) & 0x7F;
                $R[$i] = ($c >> 16) & 0xFF;
                $G[$i] = ($c >> 8) & 0xFF;
                $Bc[$i] = $c & 0xFF;
                if ($x === 0 || $y === 0 || $x === $w - 1 || $y === $h - 1) {
                    $edgeN++;
                    if ($A[$i] > 100) {
                        $transparentEdge++;
                    }
                }
            }
        }
        if ($edgeN && $transparentEdge / $edgeN > 0.9) {
            imagedestroy($img);
            copy($src, $dst);

            return [true, 'The picture already has a transparent background.', ['already' => true]];
        }

        // background colour = median of the border pixels
        $er = [];
        $eg = [];
        $eb = [];
        $push = function ($i) use (&$er, &$eg, &$eb, $R, $G, $Bc) {
            $er[] = $R[$i];
            $eg[] = $G[$i];
            $eb[] = $Bc[$i];
        };
        for ($x = 0; $x < $w; $x++) {
            $push($x);
            $push(($h - 1) * $w + $x);
        }
        for ($y = 1; $y < $h - 1; $y++) {
            $push($y * $w);
            $push($y * $w + $w - 1);
        }
        sort($er);
        sort($eg);
        sort($eb);
        $m = intdiv(count($er), 2);
        $br = $er[$m];
        $bg = $eg[$m];
        $bb = $eb[$m];
        $tot = count($er);
        // uniformity check on the real border pixels
        $uni = 0;
        $chk = function ($i) use (&$uni, $R, $G, $Bc, $br, $bg, $bb, $tol) {
            $d = sqrt(($R[$i] - $br) ** 2 + ($G[$i] - $bg) ** 2 + ($Bc[$i] - $bb) ** 2);
            if ($d <= $tol * 1.6) {
                $uni++;
            }
        };
        for ($x = 0; $x < $w; $x++) {
            $chk($x);
            $chk(($h - 1) * $w + $x);
        }
        for ($y = 1; $y < $h - 1; $y++) {
            $chk($y * $w);
            $chk($y * $w + $w - 1);
        }
        if ($uni / max(1, $tot) < 0.6) {
            imagedestroy($img);

            return [false, 'The background is not plain, so it cannot be removed automatically. Use a photo on a plain white or single colour background, or upload a PNG that is already cut out.', []];
        }

        // flood fill from the border through pixels that look like the background
        $tol2 = $tol * $tol;
        $bgm = str_repeat("\0", $n);
        $stack = new \SplFixedArray($n);
        $sp = 0;
        $try = function ($i) use (&$bgm, &$stack, &$sp, $R, $G, $Bc, $A, $br, $bg, $bb, $tol2) {
            if ($bgm[$i] !== "\0") {
                return;
            }
            $d = ($R[$i] - $br) ** 2 + ($G[$i] - $bg) ** 2 + ($Bc[$i] - $bb) ** 2;
            if ($d <= $tol2 || $A[$i] > 100) {
                $bgm[$i] = "\1";
                $stack[$sp++] = $i;
            }
        };
        for ($x = 0; $x < $w; $x++) {
            $try($x);
            $try(($h - 1) * $w + $x);
        }
        for ($y = 0; $y < $h; $y++) {
            $try($y * $w);
            $try($y * $w + $w - 1);
        }
        while ($sp > 0) {
            $i = $stack[--$sp];
            $x = $i % $w;
            if ($x > 0) {
                $try($i - 1);
            }
            if ($x < $w - 1) {
                $try($i + 1);
            }
            if ($i >= $w) {
                $try($i - $w);
            }
            if ($i < $n - $w) {
                $try($i + $w);
            }
        }
        $removed = substr_count($bgm, "\1");
        if ($removed < $n * 0.03) {
            imagedestroy($img);

            return [false, 'Nothing could be removed. Try the Strong setting, or use a plain background.', []];
        }
        if ($removed > $n * 0.97) {
            imagedestroy($img);

            return [false, 'Too much was removed, the dish has nearly the same colour as the background. Try the Gentle setting.', []];
        }

        // soft edge: pixels within 2 px of the removed area get partial transparency and lose the background tint
        $edge = str_repeat("\0", $n);
        for ($pass = 1; $pass <= 2; $pass++) {
            $nxt = $edge;
            for ($y = 0; $y < $h; $y++) {
                $o = $y * $w;
                for ($x = 0; $x < $w; $x++) {
                    $i = $o + $x;
                    if ($bgm[$i] === "\1") {
                        continue;
                    }
                    $hit = ($x > 0 && ($bgm[$i - 1] === "\1" || ($pass > 1 && $edge[$i - 1] === "\1")))
                        || ($x < $w - 1 && ($bgm[$i + 1] === "\1" || ($pass > 1 && $edge[$i + 1] === "\1")))
                        || ($y > 0 && ($bgm[$i - $w] === "\1" || ($pass > 1 && $edge[$i - $w] === "\1")))
                        || ($y < $h - 1 && ($bgm[$i + $w] === "\1" || ($pass > 1 && $edge[$i + $w] === "\1")));
                    if ($hit && $nxt[$i] === "\0") {
                        $nxt[$i] = "\1";
                    }
                }
            }
            $edge = $nxt;
        }

        $out = imagecreatetruecolor($w, $h);
        imagealphablending($out, false);
        imagesavealpha($out, true);
        $clear = imagecolorallocatealpha($out, 0, 0, 0, 127);
        imagefill($out, 0, 0, $clear);
        $minx = $w;
        $miny = $h;
        $maxx = 0;
        $maxy = 0;
        $lo = $tol * 0.45;
        $span = max(1.0, $tol * 0.55);
        for ($y = 0; $y < $h; $y++) {
            for ($x = 0; $x < $w; $x++) {
                $i = $y * $w + $x;
                if ($bgm[$i] === "\1") {
                    continue;
                }
                $r = $R[$i];
                $g = $G[$i];
                $b = $Bc[$i];
                $a = 1.0 - ($A[$i] / 127);
                if ($edge[$i] === "\1") {
                    $d = sqrt(($r - $br) ** 2 + ($g - $bg) ** 2 + ($b - $bb) ** 2);
                    $t = max(0.0, min(1.0, ($d - $lo) / $span));
                    $t = $t * $t * (3 - 2 * $t);
                    $a *= max(0.15, $t);
                    if ($a > 0.02 && $t < 1) {
                        // take the background tint out of the colour
                        $r = (int) max(0, min(255, ($r - $br * (1 - $t)) / max(0.2, $t)));
                        $g = (int) max(0, min(255, ($g - $bg * (1 - $t)) / max(0.2, $t)));
                        $b = (int) max(0, min(255, ($b - $bb * (1 - $t)) / max(0.2, $t)));
                    }
                }
                if ($a < 0.03) {
                    continue;
                }
                imagesetpixel($out, $x, $y, imagecolorallocatealpha($out, $r, $g, $b, (int) round((1 - $a) * 127)));
                if ($a > 0.2) {
                    $minx = min($minx, $x);
                    $maxx = max($maxx, $x);
                    $miny = min($miny, $y);
                    $maxy = max($maxy, $y);
                }
            }
        }
        imagedestroy($img);
        if ($maxx <= $minx || $maxy <= $miny) {
            imagedestroy($out);

            return [false, 'Nothing was left after removing the background. Try the Gentle setting.', []];
        }
        $pad = (int) round(max($maxx - $minx, $maxy - $miny) * 0.03);
        $cx = max(0, $minx - $pad);
        $cy = max(0, $miny - $pad);
        $cw = min($w - $cx, $maxx - $minx + 1 + 2 * $pad);
        $ch = min($h - $cy, $maxy - $miny + 1 + 2 * $pad);
        $fin = imagecreatetruecolor($cw, $ch);
        imagealphablending($fin, false);
        imagesavealpha($fin, true);
        imagefill($fin, 0, 0, imagecolorallocatealpha($fin, 0, 0, 0, 127));
        imagecopy($fin, $out, 0, 0, $cx, $cy, $cw, $ch);
        $ok = imagepng($fin, $dst, 6);
        imagedestroy($out);
        imagedestroy($fin);

        return $ok ? [true, 'Background removed.', ['w' => $cw, 'h' => $ch, 'removed' => round($removed / $n * 100)]] : [false, 'The cut-out could not be saved.', []];
    }
}
