<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class HrManagerRedirectMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::guard('hr_managers')->check()) {
            return redirect()->route('hr_manager.dashboard');
        }
        return $next($request);
    }
}
