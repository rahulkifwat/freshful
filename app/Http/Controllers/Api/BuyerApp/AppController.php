<?php

namespace App\Http\Controllers\Api\BuyerApp;

use App\Http\Controllers\Api\Buyer\BuyerController;
use App\Models\Buyer;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

/**
 * Shared helpers for the customer-app API (/api/app/*).
 * Answers keep the shape the rest of the API uses ({"result": bool, "message": ...}, HTTP 200)
 * and failures also carry a short "code" the app can act on.
 */
abstract class AppController extends BuyerController
{
    /** Same as the existing /api/otp login: long sessions, since customers log in by SMS. */
    protected const TOKEN_TTL_DAYS = 90;

    protected function ok(array $extra = [], string $message = 'success'): JsonResponse
    {
        return $this->respond(['result' => true, 'message' => $message] + $extra);
    }

    protected function fail(string $message, string $code, array $extra = []): JsonResponse
    {
        return $this->respond(['result' => false, 'message' => $message, 'code' => $code] + $extra);
    }

    /** First buyer row for a 10-digit phone, also matching older spellings (91…, +91…, 0…). */
    protected function findBuyerByPhone(string $phone): ?object
    {
        return DB::table('buyers')
            ->whereIn('phone', [$phone, '91' . $phone, '+91' . $phone, '0' . $phone])
            ->orderBy('id')
            ->first();
    }

    protected function findBuyerById(int $id): ?object
    {
        return DB::table('buyers')->where('id', $id)->first();
    }

    /** Only fields the app needs. Never password, otp or tokens. */
    protected function buyerPayload(object $row): array
    {
        return [
            'id'            => (int) $row->id,
            'name'          => (string) ($row->name ?? ''),
            'email'         => (string) ($row->email ?? ''),
            'gender'        => (string) ($row->gender ?? ''),
            'phone'         => (string) ($row->phone ?? ''),
            'wallet_amount' => (float) ($row->wallet_amount ?? 0),
            'referral_code' => $row->referral_code ?? null,
        ];
    }

    /** True until the customer has a name, an e-mail and at least one delivery address. */
    protected function needsProfile(object $row): bool
    {
        return trim((string) ($row->name ?? '')) === ''
            || trim((string) ($row->email ?? '')) === ''
            || ! DB::table('buyer_address')->where('buyer_id', $row->id)->exists();
    }

    /** New Sanctum token, same kind as /api/otp issues (ability "buyer"). */
    protected function issueToken(int $buyerId): array
    {
        $buyer = Buyer::findOrFail($buyerId);
        $buyer->tokens()->where('expires_at', '<', now())->delete();

        $token = $buyer->createToken('buyer-app', ['buyer'], now()->addDays(self::TOKEN_TTL_DAYS));

        return [
            'token'      => $token->plainTextToken,
            'expires_at' => $token->accessToken->expires_at?->toDateTimeString(),
        ];
    }
}
