<?php

namespace App\Http\Controllers\Api\Buyer;

use App\Exceptions\ApiException;
use App\Http\Controllers\Api\ApiController;
use App\Models\Buyer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

abstract class BuyerController extends ApiController
{
    /** The buyer behind the request's token, or null (only reachable without buyer.api on public routes). */
    protected function tokenBuyer(): ?Buyer
    {
        $buyer = Auth::guard('sanctum')->user();

        return $buyer instanceof Buyer && $buyer->tokenCan('buyer') ? $buyer : null;
    }

    /**
     * The token's buyer id. Older app builds still send buyer_id (or id); it is
     * accepted only when it matches, so nobody can act as another buyer.
     */
    protected function ownBuyerId(Request $request, string $param = 'buyer_id'): int
    {
        $buyer = $this->tokenBuyer();
        if (! $buyer) {
            throw new ApiException('Unauthorized: login required');
        }

        if ($request->filled($param) && (string) $request->input($param) !== (string) $buyer->id) {
            throw new ApiException("$param does not match the logged-in buyer");
        }

        return $buyer->id;
    }
}
