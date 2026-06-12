<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PosMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (!Auth::guard('pos_users')->check()) {
            return redirect()->route('pos.login');
        }
        return $next($request);
    }
}
