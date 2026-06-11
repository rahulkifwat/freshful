<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AccountManagerRedirectMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::guard('account_managers')->check()) {
            return redirect()->route('account_manager.dashboard');
        }

        return $next($request);
    }
}
