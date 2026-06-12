<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PlanningManagerRedirectMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (Auth::guard('planning_managers')->check()) {
            return redirect()->route('planning_manager.dashboard');
        }
        return $next($request);
    }
}
