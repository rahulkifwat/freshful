<?php

namespace App\Http\Controllers\AdminConsole;

use App\Services\AdminConsole\AdminDashPro;
use App\Services\CountryConsole\CountryScope;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

/** Freshful admin console: dashboard 2 and the hub / delivery boy drawer of the live map (30 Oct 2026). Admin login only. Read only. */
class AdminDashProController extends Controller
{
    private function actor(Request $r): array { return (array) $r->attributes->get('country_actor', []); }
    private function json(array $d, int $code = 200) { return response()->json($d, $code)->header('Cache-Control', 'no-store'); }
    private function guard(Request $r) { return (($this->actor($r)['role'] ?? '') === 'admin') ? null : $this->json(['ok' => false, 'message' => 'Admin only.'], 403); }
    private function run(callable $fn)
    {
        try { $d = $fn(); return $this->json($d, !empty($d['ok']) ? 200 : 422); }
        catch (\Throwable $e) { report($e); return $this->json(['ok' => false, 'message' => 'Could not load: ' . mb_substr($e->getMessage(), 0, 200)], 422); }
    }

    public function home(Request $r)
    {
        if ($g = $this->guard($r)) return $g;
        [$f, $t] = CountryScope::range($r->input('from'), $r->input('to'), 'today');
        return $this->run(fn () => AdminDashPro::data(new CountryScope($r->all()), $f, $t));
    }

    public function hub(Request $r, int $id) { if ($g = $this->guard($r)) return $g; return $this->run(fn () => AdminDashPro::hub($id)); }
    public function rider(Request $r, int $id) { if ($g = $this->guard($r)) return $g; return $this->run(fn () => AdminDashPro::rider($id)); }
}
