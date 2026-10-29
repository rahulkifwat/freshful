<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Freshful POS console - lets in a logged-in POS user or hub user that has a hub.
 * Puts the person on the request as "pos_actor": guard, id, name, hub_id.
 */
class PosConsoleAuth
{
    public function handle(Request $request, Closure $next)
    {
        foreach (['pos_users', 'hub_users'] as $guard) {
            try {
                $user = Auth::guard($guard)->user();
            } catch (\Throwable $e) {
                $user = null;
            }
            if ($user && (int) ($user->hub_id ?? 0) > 0 && strtolower((string) ($user->status ?? 'active')) === 'active') {
                $request->attributes->set('pos_actor', [
                    'guard'  => $guard,
                    'id'     => (int) $user->id,
                    'name'   => (string) ($user->name ?: ($user->email ?? 'Staff')),
                    'hub_id' => (int) $user->hub_id,
                    'role'   => $guard === 'hub_users' ? 'Hub manager' : 'POS cashier',
                ]);
                return $next($request);
            }
        }
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['ok' => false, 'message' => 'Your session has ended. Please log in again.', 'login' => true], 401);
        }
        return redirect()->route('posc.login');
    }
}
