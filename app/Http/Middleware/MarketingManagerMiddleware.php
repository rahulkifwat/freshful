<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class MarketingManagerMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::guard('marketing_managers')->check()) {
            return $next($request);
        }
        return redirect()->route('marketing_manager.login');
    }
}
