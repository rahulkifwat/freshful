<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class HubMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::guard('hub_users')->check()) {
            return $next($request);
        }
        return redirect()->route('hub.login');
    }
}
