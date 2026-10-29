<?php

namespace App\Http\Controllers\ProductionConsole;

use App\Http\Controllers\CountryConsole\CountryConsoleController;
use App\Services\CountryConsole\CountryScope;
use App\Services\ProductionConsole\ProductionAccess;
use App\Services\ProductionConsole\ProductionBoard;
use App\Services\ProductionConsole\ProductionDispatch;
use App\Services\ProductionConsole\ProductionPlan;
use Illuminate\Http\Request;

/**
 * Freshful production console (production update 1, 28 Oct 2026). Extends the country console controller, so every shared
 * screen (stock sheet, inward, danger, wastage, orders...) runs the exact same code; adds the production screens.
 */
class ProductionConsoleController extends CountryConsoleController
{
    private function a(Request $r): array { return (array) $r->attributes->get('country_actor', []); }
    private function sc(Request $r): CountryScope { return new CountryScope($r->all()); }
    private function out(array $d) { return response()->json($d, !empty($d['ok']) ? 200 : 422)->header('Cache-Control', 'no-store'); }
    private function rng(Request $r, string $def): array { return CountryScope::range($r->input('from'), $r->input('to'), $def); }

    public function app(Request $r)
    {
        $r->attributes->set('country_actor', $this->a($r) + ['screens' => ProductionAccess::screens(), 'perm_role' => ProductionAccess::role()['role'] ?? null]);
        return parent::app($r);
    }

    public function prHome(Request $r) { return $this->out(ProductionBoard::home($this->sc($r))); }

    public function prPlan(Request $r)
    {
        return $this->out(ProductionPlan::build($this->sc($r), (string) $r->input('date', ''), mb_substr((string) $r->input('q', ''), 0, 60), mb_substr((string) $r->input('category', ''), 0, 80), $r->boolean('need')));
    }

    public function prPlanSave(Request $r) { return $this->out(ProductionPlan::save((string) $r->input('date', ''), (array) $r->input('cells', []), $this->a($r))); }
    public function prSheet(Request $r) { return $this->out(ProductionPlan::sheet($this->sc($r), (string) $r->input('date', ''))); }

    public function prSend(Request $r)
    {
        return $this->out(ProductionDispatch::send((array) $r->input('lines', []), (string) $r->input('date', ''), (string) $r->input('note', ''), (string) $r->input('vehicle', ''), $this->a($r), (string) $r->input('client_ref', '')));
    }

    public function prBatches(Request $r) { [$f, $t] = $this->rng($r, '7'); return $this->out(ProductionDispatch::list($this->sc($r), $f, $t, (string) $r->input('status', ''))); }
    public function prBatch(Request $r, int $id) { return $this->out(ProductionDispatch::batch($id)); }
    public function prLineCancel(Request $r, int $id) { return $this->out(ProductionDispatch::cancelLine($id, (string) $r->input('note', ''), $this->a($r))); }
    public function prReceipts(Request $r) { [$f, $t] = $this->rng($r, '7'); return $this->out(ProductionDispatch::receipts($this->sc($r), $f, $t)); }
    public function prBoard(Request $r) { return $this->out(ProductionBoard::board($this->sc($r), mb_substr((string) $r->input('q', ''), 0, 60), mb_substr((string) $r->input('category', ''), 0, 80), (string) $r->input('show', ''))); }
    public function prStale(Request $r) { return $this->out(ProductionDispatch::stale($this->sc($r))); }
    public function prStaleClear(Request $r) { return $this->out(ProductionDispatch::clearStale($this->sc($r), (array) $r->input('hubs', []), (string) $r->input('note', ''), $this->a($r))); }
    public function prStaleRestore(Request $r, int $id) { return $this->out(ProductionDispatch::restoreClear($id, $this->a($r))); }
    public function prProducts(Request $r) { return $this->out(ProductionBoard::products($this->sc($r))); }
    public function prProductToggle(Request $r, int $id) { return $this->out(ProductionBoard::toggle($id, $this->a($r))); }
}
