<?php

namespace App\Http\Controllers\Api\BuyerApp;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** Push token of the customer's phone, kept in buyers.fcm_id (the column the old app and login already use). */
class DeviceController extends AppController
{
    /** POST /api/app/device-token {token} */
    public function store(Request $request): JsonResponse
    {
        $buyerId = $this->ownBuyerId($request);
        $token = trim((string) $request->input('token', ''));

        if (strlen($token) < 20 || strlen($token) > 4000 || ! preg_match('/^[A-Za-z0-9_:.\-\[\]]+$/', $token)) {
            return $this->fail('Invalid device token', 'invalid_token');
        }

        DB::transaction(function () use ($buyerId, $token) {
            // A phone belongs to one customer: if someone else logged in on it before, they stop getting its pushes.
            DB::table('buyers')->where('fcm_id', $token)->where('id', '!=', $buyerId)->update(['fcm_id' => null]);
            DB::table('buyers')->where('id', $buyerId)->update(['fcm_id' => $token]);
        });

        return $this->ok([], 'Device saved');
    }

    /** POST /api/app/device-token/clear - call on logout so pushes stop for this customer */
    public function clear(Request $request): JsonResponse
    {
        $buyerId = $this->ownBuyerId($request);
        DB::table('buyers')->where('id', $buyerId)->update(['fcm_id' => null]);

        return $this->ok([], 'Device removed');
    }
}
