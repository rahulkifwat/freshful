<?php

namespace App\Http\Controllers\AdminConsole;

use App\Services\AdminConsole\AdminDriverEdit;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

/** Freshful admin console: Driver edit / delete / change hub (drivers update 2, 30 Oct 2026). Admin login only. */
class AdminDriverEditController extends Controller
{
    private function actor(Request $r): array { return (array) $r->attributes->get('country_actor', []); }
    private function json(array $d, int $code = 200) { return response()->json($d, $code)->header('Cache-Control', 'no-store'); }
    private function guard(Request $r) { return (($this->actor($r)['role'] ?? '') === 'admin') ? null : $this->json(['ok' => false, 'message' => 'Admin only.'], 403); }
    private function run(callable $fn)
    {
        try { $d = $fn(); return $this->json($d, !empty($d['ok']) ? 200 : 422); }
        catch (\App\Exceptions\ApiException $e) { return $this->json(['ok' => false, 'message' => $e->getMessage()], 422); }
        catch (\Throwable $e) { report($e); return $this->json(['ok' => false, 'message' => 'Could not finish: ' . mb_substr($e->getMessage(), 0, 200)], 422); }
    }

    public function data(Request $r, int $id) { if ($g = $this->guard($r)) return $g; return $this->run(fn () => AdminDriverEdit::data($id)); }
    public function save(Request $r, int $id) { if ($g = $this->guard($r)) return $g; return $this->run(fn () => AdminDriverEdit::save($id, (array) $r->all(), $this->actor($r))); }
    public function destroy(Request $r, int $id) { if ($g = $this->guard($r)) return $g; return $this->run(fn () => AdminDriverEdit::remove($id, $this->actor($r))); }
}
