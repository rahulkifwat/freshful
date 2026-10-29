<?php

namespace App\Http\Middleware;

use App\Services\CountryConsole\CountryScope;
use App\Services\ProductionConsole\ProductionAccess;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Freshful production console (production update 1, 28 Oct 2026): lets in a logged-in production user.
 * Uses the production panel's own login (guard "productions", table "production"); nothing about that login changes.
 * Sets the same request data the country console uses (country_actor), so the shared screens work unchanged,
 * then checks the route against the production role's permissions (ProductionAccess).
 */
class ProductionConsoleAuth
{
    public static function guard(): ?string
    {
        $guards = (array) config('auth.guards', []);
        $g = trim((string) config('production_console.guard', ''));
        if ($g !== '' && isset($guards[$g])) return $g;
        if (isset($guards['productions'])) return 'productions';
        foreach ($guards as $name => $c) {
            $p = (array) config('auth.providers.' . ($c['provider'] ?? ''), []);
            $table = $p['table'] ?? null;
            if (!$table && !empty($p['model']) && class_exists($p['model'])) { try { $table = (new $p['model'])->getTable(); } catch (\Throwable $e) {} }
            if ($table === 'production') return (string) $name;
        }
        foreach ($guards as $name => $c) if (str_contains(strtolower((string) $name), 'production')) return (string) $name;
        return null;
    }

    /** Hubs covered (null = all of India). */
    public static function allowedHubs(): ?array
    {
        $c = array_values(array_unique(array_filter(array_map('intval', preg_split('/[\s,;|]+/', (string) config('production_console.cities', ''))))));
        if (!$c) return null;
        return DB::table('hubs')->whereIn('city_id', $c)->pluck('id')->map(fn ($v) => (int) $v)->all();
    }

    public function handle(Request $request, Closure $next)
    {
        $guard = self::guard();
        $user = null;
        if ($guard) { try { $user = Auth::guard($guard)->user(); } catch (\Throwable $e) { $user = null; } }
        $active = $user && in_array(strtolower((string) ($user->status ?? 'active')), ['active', '1'], true);
        if (!$user || !$active) {
            if ($request->expectsJson() || $request->ajax()) return response()->json(['ok' => false, 'message' => 'Your session has ended. Please log in again.', 'login' => true], 401);
            return redirect()->to(url('production/login'));
        }
        CountryScope::$allowed = self::allowedHubs();
        CountryScope::forget();
        $request->attributes->set('country_actor', [
            'guard' => $guard, 'id' => (int) $user->id, 'name' => (string) ($user->name ?? '') ?: (string) ($user->email ?? 'Production'),
            'email' => (string) ($user->email ?? ''), 'role' => 'production', 'role_label' => 'Production manager', 'prefix' => 'production', 'names' => 'ccp.',
        ]);
        // API calls: the route must be one the production role may use.
        $name = (string) optional($request->route())->getName();
        if (str_starts_with($name, 'ccp.') && !in_array($name, ['ccp.home', 'ccp.classic'], true)) {
            $why = ProductionAccess::api(substr($name, 4));
            if ($why !== null) return response()->json(['ok' => false, 'message' => $why], 403)->header('Cache-Control', 'no-store');
        }
        return $next($request);
    }
}
