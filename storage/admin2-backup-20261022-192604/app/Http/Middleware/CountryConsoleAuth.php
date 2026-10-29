<?php

namespace App\Http\Middleware;

use App\Services\CountryConsole\CountryScope;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Freshful regional consoles - lets in a logged-in country, area or operation manager (update 2, 22 Oct 2026).
 * Guard per role, found in this order: .env override; the "auth:<guard>" of the old dashboard route; the guard named
 * like the role's table (country_managers / area_managers / operation_managers); a guard whose name contains the role.
 * What the person may see (CountryScope::$allowed):
 *   country   - every hub in India;
 *   area      - the hubs in area_managers.hub_id (comma list, e.g. "27,33"); if empty, the hubs of area_managers.city_id;
 *   operation - every hub (operation_managers has no city or hub column yet; set OPERATION_CONSOLE_CITIES to limit,
 *               e.g. "4" or "4,5", or put a city_id / hub_id column on operation_managers and it is used automatically).
 */
class CountryConsoleAuth
{
    public const ROLES = [
        'country' => ['prefix' => 'country_manager', 'names' => 'ccc.', 'table' => 'country_managers', 'label' => 'Country manager', 'env' => 'COUNTRY_CONSOLE_GUARD'],
        'area' => ['prefix' => 'area_manager', 'names' => 'cca.', 'table' => 'area_managers', 'label' => 'Area manager', 'env' => 'AREA_CONSOLE_GUARD'],
        'operation' => ['prefix' => 'operation_manager', 'names' => 'cco.', 'table' => 'operation_managers', 'label' => 'Operation manager', 'env' => 'OPERATION_CONSOLE_GUARD'],
        // Admin (admin update 1, 22 Oct 2026): the whole console for all of India plus the head-office modules (catalogue, staff, marketing...).
        // Only /admin/new is pointed at the console; the old /admin/dashboard keeps working as the classic view.
        'admin' => ['prefix' => 'admin', 'names' => 'adc.', 'table' => 'admins', 'label' => 'Admin', 'env' => 'ADMIN_CONSOLE_GUARD', 'replace' => ['admin/new']],
    ];

    /** role => guard read from the old dashboard route (set when routes load) */
    public static array $fromRoute = [];

    public static function guard(string $role = 'country'): ?string
    {
        $cfg = self::ROLES[$role] ?? null;
        if (!$cfg) return null;
        $guards = (array) config('auth.guards', []);
        $g = trim((string) config('country_console.guards.' . $role, ''));
        if ($g !== '' && isset($guards[$g])) return $g;
        if (!empty(self::$fromRoute[$role]) && isset($guards[self::$fromRoute[$role]])) return self::$fromRoute[$role];
        if (isset($guards[$cfg['table']])) return $cfg['table'];
        foreach ($guards as $name => $c) {
            $p = (array) config('auth.providers.' . ($c['provider'] ?? ''), []);
            $table = $p['table'] ?? null;
            if (!$table && !empty($p['model']) && class_exists($p['model'])) { try { $table = (new $p['model'])->getTable(); } catch (\Throwable $e) {} }
            if ($table === $cfg['table']) return (string) $name;
        }
        foreach ($guards as $name => $c) if (str_contains(strtolower((string) $name), $role)) return (string) $name;
        return null;
    }

    /** Hub ids for the person (null = all of India). */
    public static function allowedHubs(string $role, object $user): ?array
    {
        $ids = fn ($v) => array_values(array_unique(array_filter(array_map('intval', preg_split('/[\s,;|]+/', (string) $v)))));
        $cityHubs = function (array $cities) {
            if (!$cities) return [];
            return \Illuminate\Support\Facades\DB::table('hubs')->whereIn('city_id', $cities)->pluck('id')->map(fn ($v) => (int) $v)->all();
        };
        if ($role === 'area') {
            $h = $ids($user->hub_id ?? '');
            return $h ?: $cityHubs($ids($user->city_id ?? ''));
        }
        if ($role === 'operation') {
            $h = $ids($user->hub_id ?? '');
            if ($h) return $h;
            $c = $ids($user->city_id ?? '') ?: $ids(config('country_console.operation_cities', ''));
            return $c ? $cityHubs($c) : null;
        }
        return null;
    }

    public function handle(Request $request, Closure $next, string $role = 'country')
    {
        $cfg = self::ROLES[$role] ?? self::ROLES['country'];
        $guard = self::guard($role);
        $user = null;
        if ($guard) { try { $user = Auth::guard($guard)->user(); } catch (\Throwable $e) { $user = null; } }
        $active = $user && in_array(strtolower((string) ($user->status ?? 'active')), ['active', '1'], true);
        if ($user && $active) {
            CountryScope::$allowed = self::allowedHubs($role, $user);
            CountryScope::forget();
            $request->attributes->set('country_actor', [
                'guard' => $guard, 'id' => (int) $user->id, 'name' => (string) ($user->name ?? '') ?: (string) ($user->email ?? $cfg['label']),
                'email' => (string) ($user->email ?? ''), 'role' => $role, 'role_label' => $cfg['label'], 'prefix' => $cfg['prefix'], 'names' => $cfg['names'],
            ]);
            return $next($request);
        }
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['ok' => false, 'message' => 'Your session has ended. Please log in again.', 'login' => true], 401);
        }
        return redirect()->to(url($cfg['prefix'] . '/login'));
    }
}
