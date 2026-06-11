<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CustomerCareManagerRedirectMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::guard('customer_care_managers')->check()) {
            return redirect()->route('customer_care_manager.dashboard');
        }

        return $next($request);
    }
}
