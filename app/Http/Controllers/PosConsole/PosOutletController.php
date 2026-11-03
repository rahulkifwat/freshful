<?php

namespace App\Http\Controllers\PosConsole;

use App\Services\Outlet\OutletSale;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

/** Outlet counter API inside the POS console (same login and hub as the rest of the POS). */
class PosOutletController extends Controller
{
    private function actor(Request $r): array { return (array) $r->attributes->get('pos_actor', []); }

    private function svc(Request $r): OutletSale { return new OutletSale((int) ($this->actor($r)['hub_id'] ?? 0), $this->actor($r)); }

    private function out(callable $fn)
    {
        try {
            if (!OutletSale::ready()) return response()->json(['ok' => false, 'message' => 'The outlet counter is not set up yet (database tables missing).'], 200)->header('Cache-Control', 'no-store');
            $res = $fn();
            return response()->json($res, 200)->header('Cache-Control', 'no-store');
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['ok' => false, 'message' => 'Could not complete that. Nothing was changed. Please try again.'], 500)->header('Cache-Control', 'no-store');
        }
    }

    public function current(Request $r) { return $this->out(fn () => $this->svc($r)->current()); }

    public function scan(Request $r)
    {
        $v = $r->validate(['barcode' => 'required|string|max:80', 'sale_id' => 'nullable|integer|min:1']);
        return $this->out(fn () => $this->svc($r)->scan($v['sale_id'] ?? null, $v['barcode']));
    }

    public function qty(Request $r, int $saleId, int $itemId)
    {
        $v = $r->validate(['qty' => 'required|integer|min:0|max:500']);
        return $this->out(fn () => $this->svc($r)->setQty($saleId, $itemId, (int) $v['qty']));
    }

    public function cancel(Request $r, int $saleId) { return $this->out(fn () => $this->svc($r)->cancel($saleId)); }

    public function pay(Request $r, int $saleId)
    {
        $v = $r->validate(['method' => 'required|string|in:upi,cash,card', 'ref' => 'nullable|string|max:60', 'tendered' => 'nullable|numeric|min:0|max:10000000']);
        return $this->out(fn () => $this->svc($r)->pay($saleId, $v['method'], $v['ref'] ?? null, isset($v['tendered']) ? (float) $v['tendered'] : null));
    }

    public function show(Request $r, int $saleId) { return $this->out(fn () => $this->svc($r)->show($saleId)); }

    public function summary(Request $r) { return $this->out(fn () => $this->svc($r)->summary()); }

    public function products(Request $r) { return $this->out(fn () => $this->svc($r)->searchProducts((string) $r->query('q', ''))); }

    public function catalog(Request $r) { return $this->out(fn () => $this->svc($r)->catalog((string) $r->query('q', ''), (string) $r->query('tag', ''))); }

    public function kiosk(Request $r) { return $this->out(fn () => $this->svc($r)->kioskWaiting()); }

    public function kioskAdopt(Request $r)
    {
        $v = $r->validate(['ref' => 'required|string|max:30']);
        return $this->out(fn () => $this->svc($r)->kioskAdopt($v['ref']));
    }

    public function link(Request $r)
    {
        $v = $r->validate(['barcode' => 'required|string|max:80', 'code' => 'required|string|max:40', 'weighed' => 'nullable|boolean']);
        return $this->out(fn () => $this->svc($r)->link($v['barcode'], $v['code'], (bool) ($v['weighed'] ?? false)));
    }
}
