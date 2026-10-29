<?php

namespace App\Http\Controllers\HubConsole;

use App\Http\Controllers\Controller;
use App\Services\HubConsole\HubCashService;
use App\Services\HubConsole\HubConsoleService;
use App\Services\HubConsole\HubPeopleService;
use App\Services\HubConsole\HubTransferService;
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
        $res = $this->svc($r)->orders($from, $to, (string) $r->query('stage', ''), (string) $r->query('type', ''), (string) $r->query('pay', ''), mb_substr((string) $r->query('q', ''), 0, 40), (string) $r->query('sched', ''));
        return $this->json($res + ['from' => $from, 'to' => $to]);
    }

    /* ---------------- phase 2 (22 Oct 2026) ---------------- */

    private function hubId(Request $r): int { return (int) ($this->actor($r)['hub_id'] ?? 0); }
    private function range(Request $r, int $days = 30): array
    {
        $ok = fn ($d) => is_string($d) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $d);
        $to = $ok($r->query('to')) ? $r->query('to') : date('Y-m-d');
        $from = $ok($r->query('from')) ? $r->query('from') : date('Y-m-d', strtotime("-$days days", strtotime($to)));
        if ($to < $from) [$from, $to] = [$to, $from];
        if (strtotime($to) - strtotime($from) > 186 * 86400) $from = date('Y-m-d', strtotime($to) - 186 * 86400);
        return [$from, $to];
    }
    private function out(array $res) { return $this->json($res, ($res['ok'] ?? false) ? 200 : 422); }

    public function locked(Request $r) { return $this->json($this->svc($r)->locked()); }

    public function ledger(Request $r)
    {
        [$from, $to] = $this->range($r, 7);
        return $this->json($this->svc($r)->ledger($from, $to, (string) $r->query('type', ''), mb_substr((string) $r->query('q', ''), 0, 60)) + ['from' => $from, 'to' => $to]);
    }

    public function inwardMany(Request $r)
    {
        $v = $r->validate(['lines' => 'required|array|min:1|max:300', 'lines.*.inv_id' => 'required|integer|min:1', 'lines.*.received' => 'required|numeric|min:0|max:100000', 'lines.*.note' => 'nullable|string|max:250']);
        return $this->out($this->svc($r)->acceptMany($v['lines'], $this->actor($r)));
    }

    private function tx(Request $r): HubTransferService { return new HubTransferService($this->hubId($r)); }

    public function transfers(Request $r) { [$from, $to] = $this->range($r, 30); return $this->json($this->tx($r)->list($from, $to)); }
    public function network(Request $r) { return $this->json($this->tx($r)->network()); }

    public function transferSend(Request $r)
    {
        $v = $r->validate(['to_hub' => 'required|integer|min:1', 'lines' => 'required|array|min:1|max:200', 'lines.*.product_id' => 'required|integer|min:1', 'lines.*.qty' => 'required|numeric|min:0.001|max:100000',
            'reason' => 'nullable|string|max:20', 'instruction' => 'nullable|string|max:500', 'rider_id' => 'nullable|integer|min:1']);
        return $this->out($this->tx($r)->send((int) $v['to_hub'], $v['lines'], (string) ($v['reason'] ?? 'other'), (string) ($v['instruction'] ?? ''), isset($v['rider_id']) ? (int) $v['rider_id'] : null, $this->actor($r)));
    }

    public function transferRequest(Request $r)
    {
        $v = $r->validate(['from_hub' => 'required|integer|min:1', 'lines' => 'required|array|min:1|max:200', 'lines.*.product_id' => 'required|integer|min:1', 'lines.*.qty' => 'required|numeric|min:0.001|max:100000', 'note' => 'nullable|string|max:500']);
        return $this->out($this->tx($r)->request((int) $v['from_hub'], $v['lines'], (string) ($v['note'] ?? ''), $this->actor($r)));
    }

    public function transferFulfil(Request $r, int $id)
    {
        $v = $r->validate(['lines' => 'required|array|max:200', 'lines.*.code' => 'required|string|max:40', 'lines.*.qty' => 'required|numeric|min:0|max:100000', 'rider_id' => 'nullable|integer|min:1', 'instruction' => 'nullable|string|max:300']);
        return $this->out($this->tx($r)->fulfil($id, $v['lines'], isset($v['rider_id']) ? (int) $v['rider_id'] : null, (string) ($v['instruction'] ?? ''), $this->actor($r)));
    }

    public function transferDecline(Request $r, int $id) { $v = $r->validate(['note' => 'required|string|max:500']); return $this->out($this->tx($r)->decline($id, $v['note'], $this->actor($r))); }
    public function transferCancel(Request $r, int $id) { $v = $r->validate(['note' => 'required|string|max:500']); return $this->out($this->tx($r)->cancel($id, $v['note'], $this->actor($r))); }

    public function transferReceive(Request $r, int $id)
    {
        $v = $r->validate(['lines' => 'required|array|min:1|max:200', 'lines.*.code' => 'required|string|max:40', 'lines.*.qty' => 'required|numeric|min:0|max:100000', 'note' => 'nullable|string|max:500']);
        return $this->out($this->tx($r)->receive($id, $v['lines'], (string) ($v['note'] ?? ''), $this->actor($r)));
    }

    private function cash(Request $r): HubCashService { return new HubCashService($this->hubId($r)); }

    public function cashBook(Request $r) { [$from, $to] = $this->range($r, 13); return $this->json($this->cash($r)->book($from, $to)); }

    public function cashAdd(Request $r)
    {
        $v = $r->validate(['date' => 'required|date_format:Y-m-d', 'type' => 'required|string|max:20', 'amount' => 'required|numeric|min:-10000000|max:10000000',
            'reference' => 'nullable|string|max:120', 'note' => 'nullable|string|max:500', 'receipt' => 'nullable|file|max:6144|mimes:jpg,jpeg,png,webp,pdf']);
        return $this->out($this->cash($r)->add($v['date'], $v['type'], (float) $v['amount'], (string) ($v['reference'] ?? ''), (string) ($v['note'] ?? ''), $r->file('receipt'), $this->actor($r)));
    }

    public function cashVoid(Request $r, int $id) { $v = $r->validate(['note' => 'required|string|max:200']); return $this->out($this->cash($r)->void($id, $v['note'], $this->actor($r))); }

    public function receipt(Request $r, int $id)
    {
        $f = $this->cash($r)->receiptPath($id);
        abort_unless($f, 404);
        return response()->file($f[0], ['Content-Disposition' => 'inline; filename="' . str_replace('"', '', $f[1]) . '"', 'Cache-Control' => 'private, no-store']);
    }

    private function people(Request $r): HubPeopleService { return new HubPeopleService($this->hubId($r)); }

    public function riders(Request $r) { return $this->json($this->people($r)->riders()); }

    public function riderSave(Request $r)
    {
        $v = $r->validate(['id' => 'nullable|integer|min:1', 'name' => 'required|string|max:60', 'phone' => 'required|string|max:16', 'employee_id' => 'required|string|max:20',
            'password' => 'nullable|string|max:60', 'gender' => 'nullable|string|max:10']);
        return $this->out($this->people($r)->saveRider(isset($v['id']) ? (int) $v['id'] : null, $v, $this->actor($r)));
    }

    public function riderToggle(Request $r, int $id) { return $this->out($this->people($r)->toggleRider($id, $this->actor($r))); }

    public function customers(Request $r)
    {
        return $this->json($this->people($r)->customers(mb_substr((string) $r->query('q', ''), 0, 60), (string) $r->query('segment', '')));
    }

    public function customer(Request $r, int $id) { $res = $this->people($r)->customer($id); return $this->json($res, $res['ok'] ? 200 : 404); }
}
