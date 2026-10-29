<?php

namespace App\Http\Controllers\HubConsole;

use App\Http\Controllers\Controller;
use App\Services\HubConsole\HubConsoleService;
use Illuminate\Http\Request;

/** Freshful hub console (/hub/console) - the hub manager's command centre. Always limited to the manager's own hub. */
class HubConsoleController extends Controller
{
    private function actor(Request $r): array { return (array) $r->attributes->get('hub_actor', []); }
    private function svc(Request $r): HubConsoleService { return new HubConsoleService((int) ($this->actor($r)['hub_id'] ?? 0)); }
    private function json(array $d, int $code = 200) { return response()->json($d, $code)->header('Cache-Control', 'no-store'); }

    public function app(Request $r)
    {
        return view('hub_console.app', ['actor' => $this->actor($r), 'hub' => $this->svc($r)->hub()]);
    }

    public function overview(Request $r) { return $this->json($this->svc($r)->overview()); }
    public function prep(Request $r) { return $this->json($this->svc($r)->prep()); }
    public function inventory(Request $r) { return $this->json($this->svc($r)->inventory()); }

    public function inward(Request $r)
    {
        $v = $r->validate(['inv_id' => 'required|integer|min:1', 'received' => 'required|numeric|min:0|max:100000', 'note' => 'nullable|string|max:250']);
        $res = $this->svc($r)->acceptInward((int) $v['inv_id'], (float) $v['received'], (string) ($v['note'] ?? ''), $this->actor($r));
        return $this->json($res, $res['ok'] ? 200 : 422);
    }

    public function count(Request $r)
    {
        $v = $r->validate(['product_id' => 'required|integer|min:1', 'counted' => 'required|numeric|min:0|max:100000', 'note' => 'nullable|string|max:250']);
        $res = $this->svc($r)->stockCount((int) $v['product_id'], (float) $v['counted'], (string) ($v['note'] ?? ''), $this->actor($r));
        return $this->json($res, $res['ok'] ? 200 : 422);
    }

    public function orders(Request $r)
    {
        $ok = fn ($d) => is_string($d) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $d);
        $from = $ok($r->query('from')) ? $r->query('from') : date('Y-m-d');
        $to = $ok($r->query('to')) ? $r->query('to') : $from;
        if ($to < $from) [$from, $to] = [$to, $from];
        if (strtotime($to) - strtotime($from) > 92 * 86400) $from = date('Y-m-d', strtotime($to) - 92 * 86400);
        $res = $this->svc($r)->orders($from, $to, (string) $r->query('stage', ''), (string) $r->query('type', ''), (string) $r->query('pay', ''), mb_substr((string) $r->query('q', ''), 0, 40));
        return $this->json($res + ['from' => $from, 'to' => $to]);
    }
}
