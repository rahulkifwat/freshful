<?php

namespace App\Services\CountryConsole;

/**
 * Units (update 3, owner's rule): stock is counted in PACKS (one product = one pack, e.g. "500 Gram"). For display,
 * meat, chicken, seafood, vegetables and fruit are shown as WEIGHT (packs x pack size -> kg / g); eggs and other
 * count items as PIECES (packs x pieces per pack). The pack size comes from the product (unit_quantity + product_unit),
 * exactly as entered when the product was created.
 *   kind weight: per = grams per pack     (Gram / g / gm / Kg / kilo)
 *   kind volume: per = ml per pack        (ml / Ltr / litre)
 *   kind count:  per = pieces per pack    (Pcs / Piece / Nos / Pack / Dozen x12 / Tray x30 / anything else)
 * suspect = a weight pack under 50 g or over 25 kg (e.g. "Chicken Mini Bites 5 Gram") - the product needs correcting.
 */
class CountryUnits
{
    public static function of($uq, $unit): array
    {
        $q = (float) $uq; $u = strtolower(trim((string) $unit)); $u = preg_replace('/[^a-z]/', '', $u);
        if ($q <= 0) $q = 1;
        if (in_array($u, ['g', 'gm', 'gms', 'gram', 'grams', 'grm', 'gr'], true)) return self::w($q);
        if (in_array($u, ['kg', 'kgs', 'kilo', 'kilos', 'kilogram', 'kilograms'], true)) return self::w($q * 1000);
        if (in_array($u, ['ml', 'mls', 'millilitre', 'milliliter'], true)) return ['kind' => 'volume', 'per' => $q, 'uom' => 'L', 'suspect' => false];
        if (in_array($u, ['l', 'lt', 'ltr', 'ltrs', 'litre', 'litres', 'liter', 'liters'], true)) return ['kind' => 'volume', 'per' => $q * 1000, 'uom' => 'L', 'suspect' => false];
        if (in_array($u, ['dozen', 'dozens', 'dz'], true)) return ['kind' => 'count', 'per' => $q * 12, 'uom' => 'pcs', 'suspect' => false];
        if (in_array($u, ['tray', 'trays'], true)) return ['kind' => 'count', 'per' => $q * 30, 'uom' => 'pcs', 'suspect' => false];
        return ['kind' => 'count', 'per' => $q, 'uom' => 'pcs', 'suspect' => false];
    }

    private static function w(float $g): array
    {
        return ['kind' => 'weight', 'per' => $g, 'uom' => 'kg', 'suspect' => $g < 50 || $g > 25000];
    }

    /** Packs -> display number in the product's unit (kg, L or pcs). */
    public static function amount(float $packs, array $u): float
    {
        return $u['kind'] === 'count' ? round($packs * $u['per'], 3) : round($packs * $u['per'] / 1000, 3);
    }

    /** "10 kg", "750 g", "12 pcs", "1.5 L" */
    public static function text(float $packs, array $u): string
    {
        $v = $packs * $u['per'];
        if ($u['kind'] === 'count') return CountryScope::q($v) . ' pcs';
        if ($u['kind'] === 'volume') return abs($v) >= 1000 ? CountryScope::q($v / 1000) . ' L' : CountryScope::q($v) . ' ml';
        return abs($v) >= 1000 ? CountryScope::q(round($v / 1000, 3)) . ' kg' : CountryScope::q(round($v)) . ' g';
    }
}
