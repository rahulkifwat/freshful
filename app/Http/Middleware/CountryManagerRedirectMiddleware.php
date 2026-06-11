<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CountryManagerRedirectMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::guard('country_managers')->check()) {
            return redirect()->route('country_manager.dashboard');
        }

        return $next($request);
    }
}
