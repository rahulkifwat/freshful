<?php

namespace App\Support;

/**
 * The legacy APIs computed "percent_off" two different ways; the mobile apps
 * read whichever each endpoint returned, so both are kept.
 */
final class Pricing
{
    /** main_price as a % of MRP (cart, get_products_by_tag, get_products_by_category, search_products). */
    public static function priceRatio(array $product): int
    {
        $mrp = (float) ($product['MRP'] ?? 0);

        return $mrp > 0 ? (int) round(((float) $product['main_price'] * 100) / $mrp) : 0;
    }

    /** Real discount % (home_products). */
    public static function discount(array $product): int
    {
        $mrp = (float) ($product['MRP'] ?? 0);

        return $mrp > 0 ? (int) ((($mrp - (float) $product['main_price']) / $mrp) * 100) : 0;
    }
}
