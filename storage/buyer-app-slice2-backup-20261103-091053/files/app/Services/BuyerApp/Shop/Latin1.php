<?php

namespace App\Services\BuyerApp\Shop;

/**
 * Some old tables (contact_us, orders) are latin1: text with emoji or Indian scripts cannot be saved in them and the
 * database would raise an error. Check first so the app gets a clear message instead.
 * latin1 in MariaDB is really Windows-1252, so curly quotes and the euro sign are fine.
 */
final class Latin1
{
    public static function canStore(string $text): bool
    {
        if ($text === '') {
            return true;
        }
        $there = @mb_convert_encoding($text, 'Windows-1252', 'UTF-8');
        $back = @mb_convert_encoding((string) $there, 'UTF-8', 'Windows-1252');

        return $back === $text;
    }
}
