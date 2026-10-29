<?php

namespace App\Http\Controllers\PosConsole;

use App\Http\Controllers\Controller;
use App\Services\PosConsole\OrderFlow;
use App\Services\PosConsole\PosConsoleService;
use Illuminate\Http\Request;

/**
 * Freshful POS console (/pos/console) - live order board for one hub.
 */
class PosConsoleController extends Controller
{
    private function actor(Request $r): array
    {
        return (array) $r->attributes->get('pos_actor', []);
    }

    private function svc(Request $r): PosConsoleService
    {
        return new PosConsoleService((int) ($this->actor($r)['hub_id'] ?? 0));
    }

    public function board(Request $r)
    {
        $svc = $this->svc($r);
        return view('pos_console.board', [
            'actor'  => $this->actor($r),
            'hub'    => $svc->hub(),
            'stages' => OrderFlow::STAGE_LABEL,
        ]);
    }

    public function feed(Request $r)
    {
        return response()->json($this->svc($r)->feed())->header('Cache-Control', 'no-store');
    }

    public function order(Request $r, string $orderId)
    {
        $d = $this->svc($r)->detail($orderId);
        return $d ? response()->json(['ok' => true, 'order' => $d])
                  : response()->json(['ok' => false, 'message' => 'Order not found in this hub.'], 404);
    }

    public function action(Request $r, string $orderId)
    {
        $data = $r->validate([
            'action'   => 'required|string|in:accept,pack,dispatch,deliver,reject,cancel',
            'reason'   => 'nullable|string|max:250',
            'rider_id' => 'nullable|integer',
        ]);
        $res = $this->svc($r)->act($orderId, $data['action'], $data['reason'] ?? null, isset($data['rider_id']) ? (int) $data['rider_id'] : null, $this->actor($r));
        return response()->json($res, $res['ok'] ? 200 : 422);
    }

    public function settle(Request $r, string $orderId)
    {
        $data = $r->validate([
            'method'   => 'required|string|in:Cash,UPI,Card,Online',
            'received' => 'nullable|numeric|min:0|max:1000000',
            'ref'      => 'nullable|string|max:120',
        ]);
        $res = $this->svc($r)->settle($orderId, $data['method'], isset($data['received']) ? (float) $data['received'] : null, $data['ref'] ?? null, $this->actor($r));
        return response()->json($res, $res['ok'] ? 200 : 422);
    }

    public function search(Request $r)
    {
        return response()->json(['ok' => true, 'orders' => $this->svc($r)->search((string) $r->query('q', ''))]);
    }

    public function print(Request $r, string $orderId, string $format)
    {
        $svc = $this->svc($r);
        $d = $svc->detail($orderId);
        abort_if(!$d, 404);
        $view = ['thermal' => 'pos_console.print_thermal', 'a4' => 'pos_console.print_a4', 'pick' => 'pos_console.print_pick'][$format] ?? null;
        abort_if(!$view, 404);
        return view($view, ['o' => $d, 'hub' => $svc->hub(), 'actor' => $this->actor($r)]);
    }

    public function report(Request $r)
    {
        $date = (string) $r->query('date', date('Y-m-d'));
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || strtotime($date) === false) $date = date('Y-m-d');
        $svc = $this->svc($r);
        return view('pos_console.report', ['rep' => $svc->dayReport($date), 'hub' => $svc->hub(), 'actor' => $this->actor($r)]);
    }
}
