<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PlanningManagerMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (!Auth::guard('planning_managers')->check()) {
            return redirect()->route('planning_manager.login');
        }
        return $next($request);
    }
}
