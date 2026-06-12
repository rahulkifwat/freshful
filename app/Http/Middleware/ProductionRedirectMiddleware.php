<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProductionRedirectMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (Auth::guard('productions')->check()) {
            return redirect()->route('production.dashboard');
        }
        return $next($request);
    }
}
