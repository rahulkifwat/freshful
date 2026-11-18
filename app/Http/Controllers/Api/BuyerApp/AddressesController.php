<?php

namespace App\Http\Controllers\Api\BuyerApp;

use App\Services\BuyerApp\ServiceArea;
use App\Services\BuyerApp\Shop\Latin1;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Saved delivery addresses for the app.
 * The hub is ALWAYS worked out here from the coordinates (ServiceArea); a hub_id sent by the phone is ignored,
 * because the older address and order calls trusted whatever hub the phone sent.
 * An address outside every delivery area can still be saved (covered=false, hub 0), it just cannot be ordered to.
 * Deleting hides the address (app_deleted_at) so past orders and invoices keep theirs.
 */
class AddressesController extends AppController
{
    private const MAX = 10;
    private const TYPES = ['home' => 'Home', 'office' => 'Office', 'others' => 'Others', 'other' => 'Others'];
    /** orders that still need the address to deliver */
    private const OPEN = ['Order Pending', 'Order Placed', 'Order Accepted', 'Order Dispatched', 'Order Shipped', 'Order Processed'];

    /** GET /api/app/addresses */
    public function index(Request $request): JsonResponse
    {
        $buyerId = $this->ownBuyerId($request);
        $rows = $this->mine($buyerId)->orderByDesc('id')->get();

        $out = [];
        foreach ($rows as $r) {
            $out[] = $this->present($r);
        }

        return $this->ok(['count' => count($out), 'addresses' => $out]);
    }

    /** POST /api/app/addresses {type?, name, phone, street_name, locality, landmark?, city, latitude, longitude} */
    public function store(Request $request): JsonResponse
    {
        $buyerId = $this->ownBuyerId($request);

        [$v, $err] = $this->clean($request, false);
        if ($err) {
            return $err;
        }
        if ($this->mine($buyerId)->count() >= self::MAX) {
            return $this->fail('You can save up to ' . self::MAX . ' addresses. Please remove one first.', 'too_many_addresses');
        }

        $cov = ServiceArea::check($v['latitude'], $v['longitude']);
        $id = DB::table('buyer_address')->insertGetId($this->dbRow($v) + [
            'buyer_id' => $buyerId,
            'hub_id'   => $cov['covered'] ? (int) $cov['hub']['id'] : 0,
            'status'   => 'active',
        ]);

        return $this->ok(
            ['address' => $this->present(DB::table('buyer_address')->where('id', $id)->first())],
            $cov['covered'] ? 'Address saved. We\'ll deliver here.' : 'Address saved. We\'ll be coming to your area soon.'
        );
    }

    /** POST /api/app/addresses/{id}  any of the fields; latitude and longitude only together */
    public function update(Request $request, string $id): JsonResponse
    {
        $buyerId = $this->ownBuyerId($request);
        $row = $this->mine($buyerId)->where('id', (int) $id)->first();
        if (! $row) {
            return $this->fail('Address not found', 'address_not_found');
        }

        [$v, $err] = $this->clean($request, true);
        if ($err) {
            return $err;
        }
        if (! $v) {
            return $this->fail('Nothing to change', 'nothing_to_change');
        }

        $set = $this->dbRow($v);
        if (isset($v['latitude'])) {
            $cov = ServiceArea::check($v['latitude'], $v['longitude']);
            $set['hub_id'] = $cov['covered'] ? (int) $cov['hub']['id'] : 0;
        }
        DB::table('buyer_address')->where('id', (int) $id)->where('buyer_id', $buyerId)->update($set);

        return $this->ok(['address' => $this->present(DB::table('buyer_address')->where('id', (int) $id)->first())], 'Address updated');
    }

    /** POST /api/app/addresses/{id}/delete */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $buyerId = $this->ownBuyerId($request);
        $row = $this->mine($buyerId)->where('id', (int) $id)->first();
        if (! $row) {
            return $this->fail('Address not found', 'address_not_found');
        }
        if (DB::table('orders')->where('address_id', (int) $id)->whereIn('order_status', self::OPEN)->exists()) {
            return $this->fail('An order is on its way to this address. You can remove it after delivery.', 'address_in_use');
        }

        DB::table('buyer_address')->where('id', (int) $id)->where('buyer_id', $buyerId)->update(['app_deleted_at' => date('Y-m-d H:i:s')]);

        return $this->ok([], 'Address removed');
    }

    private function mine(int $buyerId)
    {
        return DB::table('buyer_address')->where('buyer_id', $buyerId)->whereNull('app_deleted_at');
    }

    /** Coordinates of a saved row: the decimal columns, else the older text columns; null when neither is usable. */
    private function coords(object $r): array
    {
        foreach ([[$r->latitude ?? null, $r->longitude ?? null], [$r->user_lat ?? null, $r->user_lng ?? null]] as [$a, $b]) {
            if (is_numeric($a) && is_numeric($b) && $this->inIndia((float) $a, (float) $b)) {
                return [(float) $a, (float) $b];
            }
        }

        return [null, null];
    }

    private function inIndia(float $lat, float $lng): bool
    {
        return $lat >= 6.0 && $lat <= 38.0 && $lng >= 68.0 && $lng <= 98.9;
    }

    private function present(object $r): array
    {
        [$lat, $lng] = $this->coords($r);
        $cov = $lat === null ? null : ServiceArea::check($lat, $lng);

        return [
            'id' => (int) $r->id, 'type' => (string) ($r->type ?: 'Others'), 'name' => (string) $r->name, 'phone' => (string) $r->phone,
            'street_name' => (string) $r->street_name, 'locality' => (string) $r->locality, 'landmark' => (string) $r->landmark, 'city' => (string) $r->city,
            'latitude' => $lat, 'longitude' => $lng,
            'needs_location' => $lat === null,
            'covered' => $cov ? $cov['covered'] : false,
            'hub' => $cov ? $cov['hub'] : null,
            'delivery_charge' => $cov ? $cov['delivery_charge'] : null,
            'delivery_charge_set' => $cov ? $cov['delivery_charge_set'] : false,
        ];
    }

    /** Values for the buyer_address columns (latitude/longitude also copied to the older text columns the app and invoices read). */
    private function dbRow(array $v): array
    {
        $row = array_intersect_key($v, array_flip(['name', 'phone', 'street_name', 'locality', 'landmark', 'city', 'type']));
        if (isset($v['latitude'])) {
            $row['latitude'] = round($v['latitude'], 7);
            $row['longitude'] = round($v['longitude'], 7);
            $row['user_lat'] = (string) round($v['latitude'], 7);
            $row['user_lng'] = (string) round($v['longitude'], 7);
        }

        return $row;
    }

    /** @return array{0: array, 1: ?JsonResponse} cleaned values (only the ones given when $partial) and a failure answer */
    private function clean(Request $request, bool $partial): array
    {
        $v = [];
        $text = [
            'name' => [2, 60, true], 'street_name' => [3, 120, true], 'locality' => [2, 120, true], 'city' => [2, 60, true], 'landmark' => [0, 120, false],
        ];
        foreach ($text as $k => [$min, $max, $req]) {
            if (! $request->has($k) && ($partial || ! $req)) {
                continue;
            }
            $s = trim((string) $request->input($k, ''));
            $len = mb_strlen($s);
            if ($len < $min || $len > $max || ($req && $s === '')) {
                return [[], $this->fail('Please check the ' . str_replace('_', ' ', $k), 'invalid_' . $k)];
            }
            if (! Latin1::canStore($s)) {
                return [[], $this->fail('Please remove emojis or special characters from the address', 'text_unsupported')];
            }
            $v[$k] = $s;
        }

        if ($request->has('phone') || ! $partial) {
            $p = preg_replace('/\D/', '', (string) $request->input('phone', ''));
            if (strlen($p) === 12 && str_starts_with($p, '91')) {
                $p = substr($p, 2);
            } elseif (strlen($p) === 11 && str_starts_with($p, '0')) {
                $p = substr($p, 1);
            }
            if (! preg_match('/^[6-9]\d{9}$/', $p)) {
                return [[], $this->fail('Please enter a 10-digit mobile number', 'invalid_phone')];
            }
            $v['phone'] = $p;
        }

        if ($request->has('type') || ! $partial) {
            $t = strtolower(trim((string) $request->input('type', 'Home')));
            if (! isset(self::TYPES[$t])) {
                return [[], $this->fail('Address type must be Home, Office or Others', 'invalid_type')];
            }
            $v['type'] = self::TYPES[$t];
        }

        $hasLat = $request->has('latitude');
        $hasLng = $request->has('longitude');
        if (! $partial || $hasLat || $hasLng) {
            $lat = $request->input('latitude');
            $lng = $request->input('longitude');
            if (! is_numeric($lat) || ! is_numeric($lng)) {
                return [[], $this->fail('Please choose the location on the map', 'location_required')];
            }
            if (! $this->inIndia((float) $lat, (float) $lng)) {
                return [[], $this->fail('Please choose a place in India', 'invalid_location')];
            }
            $v['latitude'] = (float) $lat;
            $v['longitude'] = (float) $lng;
        }

        return [$v, null];
    }
}
