<?php

namespace App\Http\Controllers\AdminConsole;

use App\Services\AdminConsole\AdminDrivers;
use App\Services\CountryConsole\CountryScope;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

/** Freshful admin console: Driver management (drivers update 1, 28 Oct 2026). JSON in, JSON out; admin login only. */
class AdminDriversController extends Controller
{
    private function actor(Request $r): array { return (array) $r->attributes->get('country_actor', []); }
    private function json(array $d, int $code = 200) { return response()->json($d, $code)->header('Cache-Control', 'no-store'); }
    private function guard(Request $r) { return (($this->actor($r)['role'] ?? '') === 'admin') ? null : $this->json(['ok' => false, 'message' => 'Admin only.'], 403); }
    private function run(callable $fn)
    {
        try { $d = $fn(); return $this->json($d, !empty($d['ok']) ? 200 : 422); }
        catch (\App\Exceptions\ApiException $e) { return $this->json(['ok' => false, 'message' => $e->getMessage()], 422); }   // rule messages from DriverManagementService
        catch (\Throwable $e) { report($e); return $this->json(['ok' => false, 'message' => 'Could not finish: ' . mb_substr($e->getMessage(), 0, 200)], 422); }
    }

    public function list(Request $r) { if ($g = $this->guard($r)) return $g; return $this->run(fn () => AdminDrivers::list()); }
    public function show(Request $r, int $id) { if ($g = $this->guard($r)) return $g; return $this->run(fn () => AdminDrivers::detail($id)); }
    public function review(Request $r, int $id) { if ($g = $this->guard($r)) return $g; return $this->run(fn () => AdminDrivers::review($id, (string) $r->input('doc', ''), (string) $r->input('decision', ''), (string) $r->input('note', ''), $this->actor($r))); }
    public function approve(Request $r, int $id) { if ($g = $this->guard($r)) return $g; return $this->run(fn () => AdminDrivers::approve($id, $this->actor($r))); }
    public function reject(Request $r, int $id) { if ($g = $this->guard($r)) return $g; return $this->run(fn () => AdminDrivers::reject($id, (string) $r->input('reason', ''), $this->actor($r))); }
    public function toggle(Request $r, int $id) { if ($g = $this->guard($r)) return $g; return $this->run(fn () => AdminDrivers::toggle($id, $this->actor($r))); }

    /** A driver's photo or document image. Not public: only a logged-in admin gets it, and it is never cached. */
    public function file(Request $r, int $id, string $field)
    {
        if ($g = $this->guard($r)) return $g;
        $path = AdminDrivers::file($id, $field);
        if (!$path) return response('File not found on the server.', 404, ['Content-Type' => 'text/plain', 'Cache-Control' => 'no-store']);
        $mime = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp', 'gif' => 'image/gif'][strtolower(pathinfo($path, PATHINFO_EXTENSION))] ?? 'application/octet-stream';
        return response()->file($path, ['Content-Type' => $mime, 'Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff', 'Content-Disposition' => 'inline']);
    }

    public function assignable(Request $r)
    {
        if ($g = $this->guard($r)) return $g;
        $f = in_array($r->input('filter'), ['unassigned', 'assigned', 'out', 'all'], true) ? (string) $r->input('filter') : 'unassigned';
        return $this->run(fn () => AdminDrivers::assignable(new CountryScope($r->all()), $f, (string) $r->input('q', '')));
    }
    public function riders(Request $r, string $order) { if ($g = $this->guard($r)) return $g; return $this->run(fn () => AdminDrivers::ridersFor($order)); }
    public function assign(Request $r) { if ($g = $this->guard($r)) return $g; return $this->run(fn () => AdminDrivers::assign((string) $r->input('order_id', ''), (int) $r->input('rider_id', 0), (int) $r->input('expected_rider_id', 0), $this->actor($r))); }

    public function wallets(Request $r) { if ($g = $this->guard($r)) return $g; return $this->run(fn () => AdminDrivers::wallets()); }
    public function walletMove(Request $r, int $id) { if ($g = $this->guard($r)) return $g; return $this->run(fn () => AdminDrivers::walletMove($id, (string) $r->input('kind', ''), (float) $r->input('amount', 0), (string) $r->input('note', ''), $this->actor($r))); }
    public function walletsMissing(Request $r) { if ($g = $this->guard($r)) return $g; return $this->run(fn () => AdminDrivers::createMissingWallets($this->actor($r))); }
    public function fillNames(Request $r) { if ($g = $this->guard($r)) return $g; return $this->run(fn () => AdminDrivers::fillBoyNames($this->actor($r))); }
    public function counts(Request $r) { if ($g = $this->guard($r)) return $g; return $this->json(['ok' => true, 'pending' => AdminDrivers::pendingCount()]); }
}
