<?php

namespace App\Http\Controllers;

use App\Services\Outlet\OutletSale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Self-order kiosk. No staff login: the kiosk screen is opened with the secret address
 * /kiosk/{OUTLET_KIOSK_KEY}, and it only ever works for the outlet in OUTLET_KIOSK_HUB.
 * A kiosk order is an open counter bill (stock held); the cashier takes payment and hands the items over.
 */
class KioskController extends Controller
{
    private function svc(string $key): OutletSale
    {
        $k = (string) env('OUTLET_KIOSK_KEY', ''); $hub = (int) env('OUTLET_KIOSK_HUB', 0);
        abort_if($k === '' || $hub < 1 || !hash_equals($k, $key) || !OutletSale::kioskReady(), 404);
        return new OutletSale($hub, ['name' => 'Kiosk', 'hub_id' => $hub]);
    }

    private function token(int $id, string $saleNo): string
    {
        return substr(hash_hmac('sha256', $id . '|' . $saleNo, (string) config('app.key')), 0, 20);
    }

    private function json(array $a)
    {
        return response()->json($a)->header('Cache-Control', 'no-store');
    }

    public function page(Request $r, string $key)
    {
        $svc = $this->svc($key);
        $hubId = (int) env('OUTLET_KIOSK_HUB');
        $hub = DB::table('hubs')->where('id', $hubId)->first();
        $city = $hub ? (string) DB::table('cities')->where('id', $hub->city_id)->value('city') : '';
        $u = OutletSale::upi();
        return response()->view('kiosk.index', ['key' => $key, 'place' => trim($city . ($hub ? ' (' . $hub->hub . ')' : '')), 'upi' => $u, 'logo' => url('/assets/images/freshful-logo.png'), 'ph' => url('/assets/images/freshful-placeholder.png'), 'site' => url('/')])
            ->header('Cache-Control', 'no-store');
    }

    public function ping(string $key)
    {
        $this->svc($key);
        return $this->json(['ok' => true, 'token' => csrf_token()]);
    }

    public function catalog(Request $r, string $key)
    {
        try { return $this->json($this->svc($key)->catalog((string) $r->query('q', ''), (string) $r->query('tag', ''))); }
        catch (\Symfony\Component\HttpKernel\Exception\HttpExceptionInterface $e) { throw $e; }
        catch (\Throwable $e) { report($e); return $this->json(['ok' => false, 'message' => 'Could not load the products.']); }
    }

    public function place(Request $r, string $key)
    {
        $svc = $this->svc($key);
        $v = $r->validate(['items' => 'required|array|min:1|max:40', 'items.*.code' => 'required|string|max:40', 'items.*.qty' => 'required|integer|min:1|max:20']);
        try {
            $res = $svc->kioskPlace($v['items']);
            if (empty($res['ok'])) return $this->json($res);
            $s = $res['sale'];
            return $this->json(['ok' => true, 'id' => $s['id'], 'tok' => $this->token($s['id'], $s['sale_no']), 'sale_no' => $s['sale_no'], 'pickup' => $s['pickup'], 'total' => $s['total'], 'items' => $s['items'],
                'upi' => OutletSale::upiLink((float) $s['total'], $s['sale_no']), 'invoice' => InvoiceController::signedUrl('outlet', (string) $s['id'], 'a4')]);
        } catch (\Throwable $e) {
            report($e);
            return $this->json(['ok' => false, 'message' => 'Could not place the order. Nothing was charged. Please try again or ask at the counter.']);
        }
    }

    private function owned(OutletSale $svc, int $id, string $tok): ?array
    {
        $s = DB::table('outlet_sales')->where('id', $id)->where('hub_id', (int) env('OUTLET_KIOSK_HUB'))->first(['id', 'sale_no']);
        return $s && hash_equals($this->token($id, $s->sale_no), $tok) ? ['id' => $id] : null;
    }

    public function status(string $key, int $id, string $tok)
    {
        $svc = $this->svc($key);
        if (!$this->owned($svc, $id, $tok)) return $this->json(['ok' => false, 'message' => 'Order not found.']);
        return $this->json($svc->kioskStatus($id));
    }

    public function cancel(string $key, int $id, string $tok)
    {
        $svc = $this->svc($key);
        if (!$this->owned($svc, $id, $tok)) return $this->json(['ok' => false, 'message' => 'Order not found.']);
        try { return $this->json($svc->kioskCancel($id)); } catch (\Throwable $e) { report($e); return $this->json(['ok' => false, 'message' => 'Could not cancel. Please ask at the counter.']); }
    }
}
