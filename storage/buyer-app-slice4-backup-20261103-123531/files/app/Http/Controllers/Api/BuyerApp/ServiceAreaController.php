<?php

namespace App\Http\Controllers\Api\BuyerApp;

use App\Services\BuyerApp\ServiceArea;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ServiceAreaController extends AppController
{
    /**
     * GET /api/app/service-area?lat=&lng=
     * covered=true: hub, city, distance, delivery_charge (null + delivery_charge_set=false when the city has no price yet).
     * covered=false: the app shows "We'll be coming to your area soon" and the options (change location, saved address).
     */
    public function check(Request $request): JsonResponse
    {
        $this->ownBuyerId($request);

        $lat = $request->input('lat');
        $lng = $request->input('lng');
        if (! is_numeric($lat) || ! is_numeric($lng)) {
            return $this->fail('Location is required', 'location_required');
        }
        $lat = (float) $lat;
        $lng = (float) $lng;
        // India only: stops 0,0 (a phone that has no GPS fix) and typos from being treated as places.
        if ($lat < 6.0 || $lat > 38.0 || $lng < 68.0 || $lng > 98.9) {
            return $this->fail('Please choose a place in India', 'invalid_location');
        }

        $r = ServiceArea::check($lat, $lng);

        return $this->ok($r + [
            'message' => $r['covered'] ? "We'll deliver here." : "We'll be coming to your area soon.",
            'options' => ['change_location' => true, 'use_saved_address' => true, 'pickup' => []],
        ], $r['covered'] ? "We'll deliver here." : "We'll be coming to your area soon.");
    }
}
