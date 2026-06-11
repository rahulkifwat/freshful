<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CountryManagerMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::guard('country_managers')->check()) {
            return $next($request);
        }

        return redirect()->route('country_manager.login');
    }
}
