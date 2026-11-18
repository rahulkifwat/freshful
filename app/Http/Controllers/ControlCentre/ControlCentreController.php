<?php

namespace App\Http\Controllers\ControlCentre;

use App\Services\ControlCentre\AlertEngine;
use App\Services\ControlCentre\Analyst;
use App\Services\CountryConsole\CountryScope;
use App\Services\ControlCentre\DataHealth;
use App\Services\ControlCentre\Metrics;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

/** Freshful Control Centre update 1 (15 Nov 2026): alert centre, data health, metric definitions. Admin login only. Never changes orders, stock or money. */
class ControlCentreController extends Controller
{
    private function actor(Request $r): array { return (array) $r->attributes->get('country_actor', []); }
    private function json(array $d, int $code = 200) { return response()->json($d, $code)->header('Cache-Control', 'no-store'); }
    private function guard(Request $r) { return (($this->actor($r)['role'] ?? '') === 'admin') ? null : $this->json(['ok' => false, 'message' => 'Admin only.'], 403); }
    private function run(callable $fn)
    {
        try { $d = $fn(); return $this->json($d, !empty($d['ok']) ? 200 : 422); }
        catch (\Throwable $e) { report($e); return $this->json(['ok' => false, 'message' => 'Could not complete this. Please try again.'], 422); }
    }

    public function metrics(Request $r)
    {
        if ($g = $this->guard($r)) return $g;
        return $this->run(fn () => ['ok' => true, 'metrics' => Metrics::listForScreen(), 'freshness' => Metrics::freshness()]);
    }

    public function health(Request $r)
    {
        if ($g = $this->guard($r)) return $g;
        return $this->run(function () {
            $c = DataHealth::run();
            return ['ok' => true, 'checks' => $c, 'summary' => DataHealth::summary($c), 'freshness' => Metrics::freshness()];
        });
    }

    public function alerts(Request $r)
    {
        if ($g = $this->guard($r)) return $g;
        return $this->run(function () use ($r) {
            AlertEngine::ensureFresh();
            return AlertEngine::list(['status' => $r->input('status', 'active'), 'severity' => $r->input('severity'), 'hub' => $r->input('hub')]) + ['freshness' => Metrics::freshness()];
        });
    }

    public function scan(Request $r)
    {
        if ($g = $this->guard($r)) return $g;
        return $this->run(fn () => AlertEngine::scan());
    }

    public function history(Request $r, int $id)
    {
        if ($g = $this->guard($r)) return $g;
        return $this->run(fn () => AlertEngine::history($id));
    }

    public function act(Request $r, int $id, string $action)
    {
        if ($g = $this->guard($r)) return $g;
        return $this->run(fn () => AlertEngine::act($id, $action, $this->actor($r), ['note' => $r->input('note'), 'assignee' => $r->input('assignee'), 'self' => (bool) $r->input('self')]));
    }

    public function aiStatus(Request $r)
    {
        if ($g = $this->guard($r)) return $g;
        return $this->run(fn () => Analyst::status());
    }

    public function aiAsk(Request $r)
    {
        if ($g = $this->guard($r)) return $g;
        return $this->run(function () use ($r) {
            [$f, $t] = CountryScope::range($r->input('from'), $r->input('to'), '7');
            return Analyst::ask($this->actor($r), (string) $r->input('question', ''), new CountryScope($r->all()), $f, $t);
        });
    }

    public function aiHistory(Request $r)
    {
        if ($g = $this->guard($r)) return $g;
        return $this->run(fn () => Analyst::history(30));
    }
}
