<?php

namespace App\Http\Middleware;

use App\Exceptions\ApiException;
use App\Support\DriverJwt;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Replaces the legacy authenticate() call: requires "Authorization: Bearer <jwt>"
 * and exposes the decoded payload as $request->attributes->get('driver').
 */
class DriverJwtMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();
        if (! $token) {
            throw new ApiException('No token provided');
        }

        $request->attributes->set('driver', DriverJwt::decode($token));

        return $next($request);
    }
}
