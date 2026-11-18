<?php

namespace App\Services\BuyerApp\Checkout;

/**
 * Delivery type and time slot rules. Copied from the website checkout (HomeController, 21 Oct fix) so the app and
 * the website accept exactly the same orders:
 *   Express: 7 AM to 9 PM, delivered in about 30 minutes, order saved with schedule_time "30 min".
 *   Scheduled: today or tomorrow, one of the slots below; a slot that has already started today is refused.
 * Times use the server clock (date()), the same as the website.
 */
final class Slots
{
    public const SLOTS = [
        '7 AM – 9 AM' => 7, '9 AM – 11 AM' => 9, '11 AM – 1 PM' => 11, '1 PM – 3 PM' => 13,
        '3 PM – 5 PM' => 15, '5 PM – 7 PM' => 17, '7 PM – 9 PM' => 19, '9 PM – 11 PM' => 21,
    ];

    public static function expressOpen(?int $ts = null): bool
    {
        $h = (int) date('G', $ts ?? time());

        return $h >= 7 && $h < 21;
    }

    /** @return array{0: ?array, 1: ?array} [type, date, time] or [code, message] */
    public static function resolve(string $type, string $date, string $time, ?int $ts = null): array
    {
        $ts = $ts ?? time();
        $type = $type === '' ? 'Express' : $type;
        if ($type !== 'Express' && $type !== 'Scheduled') {
            return [null, ['invalid_delivery_type', 'Delivery type must be Express or Scheduled.']];
        }
        if ($type === 'Express') {
            if (! self::expressOpen($ts)) {
                return [null, ['express_closed', 'Express delivery is available from 7 AM to 9 PM. Please choose a delivery slot.']];
            }

            return [['type' => 'Express', 'date' => date('Y-m-d', $ts), 'time' => '30 min'], null];
        }

        $time = trim($time);
        if (! isset(self::SLOTS[$time])) {
            return [null, ['invalid_slot', 'Please choose a delivery slot.']];
        }
        $today = date('Y-m-d', $ts);
        $tomorrow = date('Y-m-d', $ts + 86400);
        if ($date !== $today && $date !== $tomorrow) {
            return [null, ['invalid_slot', 'Please choose today or tomorrow for your delivery slot.']];
        }
        if ($date === $today && (int) date('G', $ts) >= self::SLOTS[$time]) {
            return [null, ['slot_passed', 'That slot has already started. Please choose a later slot or Express.']];
        }

        return [['type' => 'Scheduled', 'date' => $date, 'time' => $time], null];
    }

    /** What the app shows on the delivery-time screen. */
    public static function listing(?int $ts = null): array
    {
        $ts = $ts ?? time();
        $h = (int) date('G', $ts);
        $days = [];
        foreach ([['Today', date('Y-m-d', $ts), true], ['Tomorrow', date('Y-m-d', $ts + 86400), false]] as [$label, $d, $isToday]) {
            $slots = [];
            foreach (self::SLOTS as $name => $start) {
                $slots[] = ['time' => $name, 'available' => ! ($isToday && $h >= $start)];
            }
            $days[] = ['label' => $label, 'date' => $d, 'slots' => $slots];
        }
        $open = self::expressOpen($ts);

        return [
            'express' => ['available' => $open, 'eta' => '30 min', 'message' => $open ? 'Delivered in about 30 minutes' : 'Express delivery is available from 7 AM to 9 PM'],
            'days' => $days,
        ];
    }
}
