<?php

namespace App\Http\Middleware;

use App\Exceptions\ApiException;
use App\Models\Admin;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Requires "Authorization: Bearer <admin token>" from POST /api/admin/login.
 * Tokens belonging to any other model (e.g. User) are rejected.
 */
class AdminApiMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $admin = Auth::guard('sanctum')->user();

        if (! $admin instanceof Admin || ! $admin->tokenCan('admin')) {
            throw new ApiException('Unauthorized: admin token required');
        }

        Auth::shouldUse('sanctum');

        return $next($request);
    }
}
