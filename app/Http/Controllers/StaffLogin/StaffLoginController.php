<?php

namespace App\Http\Controllers\StaffLogin;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Routing\Route;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Freshful staff sign-in portal (22 Oct 2026). Shows the new login design.
 * Before showing it, the ORIGINAL page code runs: if it would redirect (for example an already
 * logged-in manager straight to their dashboard), that redirect is kept. Otherwise the new design is shown.
 * The form posts to the original login address, so the password check is the original code.
 */
class StaffLoginController extends Controller
{
    /** @var array<string, Route> original GET routes, by address */
    protected static array $previous = [];

    public static function remember(string $path, Route $route): void
    {
        static::$previous[$path] = $route;
    }

    public function show(Request $request)
    {
        $path = trim($request->path(), '/');
        $old = static::$previous[$path] ?? null;
        if ($old) {
            try {
                $old->bind($request);
                $result = $old->run();
                if ($result instanceof RedirectResponse) {
                    return $result;
                }
            } catch (\Throwable $e) {
                // The original page failed (e.g. /pos/login was already broken): show the new page anyway.
            }
        }

        $roles = [];
        $current = null;
        foreach ((array) config('staff_login.roles', []) as $key => $r) {
            $paths = array_merge([$r['path']], (array) ($r['also'] ?? []));
            if (in_array($path, $paths, true)) {
                $current = $key;
            }
            $roles[$key] = [
                'key' => $key,
                'name' => (string) $r['name'],
                'desc' => (string) ($r['desc'] ?? ''),
                'icon' => (string) ($r['icon'] ?? 'store'),
                'group' => (string) ($r['group'] ?? ''),
                'hidden' => (bool) ($r['hidden'] ?? false),
                'url' => url($r['path']),
                'text' => ($r['login_type'] ?? 'email') === 'text',
            ];
        }
        // Hidden roles (Admin) appear only on their own page.
        $roles = array_filter($roles, fn ($r) => ! $r['hidden'] || $r['key'] === $current);

        $support = (array) config('staff_login.support', []);

        return response()
            ->view('staff_login.login', ['roles' => $roles, 'current' => $current, 'support' => $support])
            ->header('X-Robots-Tag', 'noindex, nofollow')
            ->header('Cache-Control', 'no-store, private');
    }
}
