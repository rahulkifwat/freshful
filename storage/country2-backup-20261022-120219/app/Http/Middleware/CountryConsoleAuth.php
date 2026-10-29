<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Freshful country console - lets in a logged-in country manager.
 * The guard is the one the existing country manager panel uses. It is found in this order:
 *   1. COUNTRY_CONSOLE_GUARD in .env;
 *   2. the "auth:<guard>" middleware of the old /country_manager/dashboard route (read when routes load);
 *   3. a configured guard whose name contains "country";
 *   4. a guard whose user table is country_managers.
 * Puts the person on the request as "country_actor".
 */
class CountryConsoleAuth
{
    public static ?string $fromRoute = null;

    public static function guard(): ?string
    {
        $g = trim((string) config('country_console.guard', ''));
        $guards = (array) config('auth.guards', []);
        if ($g !== '' && isset($guards[$g])) return $g;
        if (self::$fromRoute && isset($guards[self::$fromRoute])) return self::$fromRoute;
        foreach ($guards as $name => $cfg) if (str_contains(strtolower((string) $name), 'country')) return (string) $name;
        foreach ($guards as $name => $cfg) {
            $p = (array) config('auth.providers.' . ($cfg['provider'] ?? ''), []);
            $table = $p['table'] ?? null;
            if (!$table && !empty($p['model']) && class_exists($p['model'])) { try { $table = (new $p['model'])->getTable(); } catch (\Throwable $e) {} }
            if (in_array($table, ['country_managers', 'country_manager'], true)) return (string) $name;
        }
        return null;
    }

    public function handle(Request $request, Closure $next)
    {
        $guard = self::guard();
        $user = null;
        if ($guard) { try { $user = Auth::guard($guard)->user(); } catch (\Throwable $e) { $user = null; } }
        $active = $user && in_array(strtolower((string) ($user->status ?? 'active')), ['active', '1'], true);
        if ($user && $active) {
            $request->attributes->set('country_actor', [
                'guard' => $guard, 'id' => (int) $user->id, 'name' => (string) ($user->name ?? '') ?: (string) ($user->email ?? 'Country manager'),
                'email' => (string) ($user->email ?? ''), 'role' => 'Country manager',
            ]);
            return $next($request);
        }
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['ok' => false, 'message' => 'Your session has ended. Please log in again.', 'login' => true], 401);
        }
        return redirect()->to(url('country_manager/login'));
    }
}
