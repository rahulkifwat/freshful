<?php

namespace App\Http\Middleware;

use App\Exceptions\ApiException;
use App\Models\Buyer;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Requires "Authorization: Bearer <buyer token>" issued by /api/otp.
 * Tokens of any other model (admin, user) are rejected.
 */
class BuyerApiMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $buyer = Auth::guard('sanctum')->user();

        if (! $buyer instanceof Buyer || ! $buyer->tokenCan('buyer')) {
            throw new ApiException('Unauthorized: login required');
        }

        Auth::shouldUse('sanctum');

        return $next($request);
    }
}
