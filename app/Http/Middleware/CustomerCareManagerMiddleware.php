<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CustomerCareManagerMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::guard('customer_care_managers')->check()) {
            return $next($request);
        }

        return redirect()->route('customer_care_manager.login');
    }
}
