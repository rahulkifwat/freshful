<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Freshful hub console - lets in a logged-in hub manager (guard hub_users) that has a hub.
 * Puts the person on the request as "hub_actor": id, name, hub_id.
 */
class HubConsoleAuth
{
    public function handle(Request $request, Closure $next)
    {
        try {
            $user = Auth::guard('hub_users')->user();
        } catch (\Throwable $e) {
            $user = null;
        }
        $active = $user && in_array(strtolower((string) ($user->status ?? 'active')), ['active', '1'], true);
        if ($user && $active && (int) ($user->hub_id ?? 0) > 0) {
            $request->attributes->set('hub_actor', [
                'guard' => 'hub_users', 'id' => (int) $user->id, 'name' => (string) ($user->name ?: ($user->email ?? 'Hub manager')),
                'hub_id' => (int) $user->hub_id, 'role' => 'Hub manager', 'email' => (string) ($user->email ?? ''),
            ]);
            return $next($request);
        }
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['ok' => false, 'message' => 'Your session has ended. Please log in again.', 'login' => true], 401);
        }
        return redirect()->route('hub.login');
    }
}
