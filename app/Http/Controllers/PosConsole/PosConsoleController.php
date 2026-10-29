<?php

namespace App\Http\Controllers\PosConsole;

use App\Http\Controllers\Controller;
use App\Services\PosConsole\OrderFlow;
use App\Services\PosConsole\PosConsoleService;
use Illuminate\Http\Request;

/**
 * Freshful POS console (/pos/console) - live order board for one hub.
 * Every request is limited to the logged-in user's hub (PosConsoleAuth middleware sets pos_actor).
 */
class PosConsoleController extends Controller
{
    private const ORDER_ID = '/^[A-Za-z0-9_\-]{1,40}$/';

    private function actor(Request $r): array
    {
        return (array) $r->attributes->get('pos_actor', []);
    }

    private function svc(Request $r): PosConsoleService
    {
        return new PosConsoleService((int) ($this->actor($r)['hub_id'] ?? 0));
    }

    private function json(array $data, int $code = 200)
    {
        return response()->json($data, $code)->header('Cache-Control', 'no-store');
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
        return $this->json($this->svc($r)->feed());
    }

    public function order(Request $r, string $orderId)
    {
        $d = $this->svc($r)->detail($orderId);
        return $d ? $this->json(['ok' => true, 'order' => $d]) : $this->json(['ok' => false, 'message' => 'Order not found in this hub.'], 404);
    }

    public function action(Request $r, string $orderId)
    {
        $v = $r->validate([
            'action'   => 'required|string|in:' . implode(',', OrderFlow::SIMPLE_ACTIONS),
            'reason'   => 'nullable|string|max:250',
            'rider_id' => 'nullable|integer|min:1',
            'auto'     => 'nullable|boolean',
        ]);
        $res = $this->svc($r)->act($orderId, $v['action'], $v['reason'] ?? null, isset($v['rider_id']) ? (int) $v['rider_id'] : null, $this->actor($r), (bool) ($v['auto'] ?? false));
        return $this->json($res, $res['ok'] ? 200 : 422);
    }

    public function weigh(Request $r, string $orderId)
    {
        $v = $r->validate([
            'weights'   => 'required|array|max:200',
            'weights.*' => 'nullable|numeric|min:0|max:1000000',
        ]);
        $res = $this->svc($r)->weigh($orderId, $v['weights'], $this->actor($r));
        return $this->json($res, $res['ok'] ? 200 : 422);
    }

    public function riderStatus(Request $r, int $riderId)
    {
        $v = $r->validate(['action' => 'required|in:back,off,on']);
        $res = $this->svc($r)->riderStatus($riderId, $v['action'], $this->actor($r));
        return $this->json($res, $res['ok'] ? 200 : 422);
    }

    public function reassign(Request $r, string $orderId)
    {
        $v = $r->validate(['rider_id' => 'required|integer|min:1']);
        $res = $this->svc($r)->reassign($orderId, (int) $v['rider_id'], $this->actor($r));
        return $this->json($res, $res['ok'] ? 200 : 422);
    }

    public function hubs(Request $r)
    {
        return $this->json(['ok' => true, 'hubs' => $this->svc($r)->hubsForRelocation()]);
    }

    public function relocate(Request $r, string $orderId)
    {
        $v = $r->validate(['hub_id' => 'required|integer|min:1', 'note' => 'nullable|string|max:200']);
        $res = $this->svc($r)->relocate($orderId, (int) $v['hub_id'], (string) ($v['note'] ?? ''), $this->actor($r));
        return $this->json($res, $res['ok'] ? 200 : 422);
    }

    /** Settle one order (from the order) or many (rider handover). */
    public function settle(Request $r)
    {
        $v = $r->validate([
            'orders'   => 'required|array|min:1|max:200',
            'orders.*' => ['required', 'string', 'regex:' . self::ORDER_ID],
            'rider_id' => 'nullable|integer|min:1',
            'cash'     => 'nullable|numeric|min:-1000000|max:1000000',
            'upi'      => 'nullable|numeric|min:0|max:1000000',
            'card'     => 'nullable|numeric|min:0|max:1000000',
            'ref'      => 'nullable|string|max:120',
            'note'     => 'nullable|string|max:250',
        ]);
        $res = $this->svc($r)->settle($v['orders'], isset($v['rider_id']) ? (int) $v['rider_id'] : null,
            (float) ($v['cash'] ?? 0), (float) ($v['upi'] ?? 0), (float) ($v['card'] ?? 0), $v['ref'] ?? null, $v['note'] ?? null, $this->actor($r));
        return $this->json($res, $res['ok'] ? 200 : 422);
    }

    public function closeStale(Request $r)
    {
        $v = $r->validate(['reason' => 'nullable|string|max:250', 'confirm' => 'required|in:yes']);
        return $this->json($this->svc($r)->closeStale((string) ($v['reason'] ?? ''), $this->actor($r)));
    }

    public function search(Request $r)
    {
        return $this->json(['ok' => true, 'orders' => $this->svc($r)->search((string) $r->query('q', ''))]);
    }

    private function range(Request $r): array
    {
        $from = (string) $r->query('from', date('Y-m-d'));
        $to = (string) $r->query('to', $from);
        $ok = fn ($d) => preg_match('/^\d{4}-\d{2}-\d{2}$/', $d) && strtotime($d) !== false;
        if (!$ok($from)) $from = date('Y-m-d');
        if (!$ok($to)) $to = $from;
        if ($to < $from) [$from, $to] = [$to, $from];
        if ((strtotime($to) - strtotime($from)) > 92 * 86400) $from = date('Y-m-d', strtotime($to) - 92 * 86400);
        return [$from, $to];
    }

    public function history(Request $r)
    {
        [$from, $to] = $this->range($r);
        $stage = (string) $r->query('stage', '');
        $orders = $this->svc($r)->history($from, $to, $stage);
        if ($r->query('format') === 'csv') {
            $hub = $this->svc($r)->hub();
            $name = 'freshful-' . preg_replace('/[^a-z0-9]+/i', '-', strtolower($hub->hub ?? 'hub')) . '-orders-' . $from . '-to-' . $to . '.csv';
            return response()->streamDownload(function () use ($orders) {
                $out = fopen('php://output', 'w');
                fwrite($out, "\xEF\xBB\xBF");
                fputcsv($out, ['Order', 'Placed', 'Type', 'Slot', 'Customer', 'Phone', 'Area', 'Status', 'Payment', 'Bill', 'Weight adjustment', 'Total', 'Rider', 'Cancel reason']);
                foreach ($orders as $o) {
                    fputcsv($out, [$o['order_id'], $o['placed_at'] ? date('Y-m-d H:i', $o['placed_at']) : '', $o['delivery_type'],
                        trim($o['schedule_date'] . ' ' . $o['slot']), $o['customer'], $o['phone'], $o['area'], $o['status'], $o['payment_type'],
                        $o['bill'], $o['adjustment'], $o['amount'], $o['rider'] ?? '', $o['cancel_reason']]);
                }
                fclose($out);
            }, $name, ['Content-Type' => 'text/csv; charset=UTF-8']);
        }
        return $this->json(['ok' => true, 'from' => $from, 'to' => $to, 'orders' => $orders]);
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

    private function reportDate(Request $r): string
    {
        $date = (string) $r->input('date', date('Y-m-d'));
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || strtotime($date) === false || $date > date('Y-m-d')) $date = date('Y-m-d');
        return $date;
    }

    public function report(Request $r)
    {
        $svc = $this->svc($r);
        $date = $this->reportDate($r);
        return view('pos_console.report', [
            'rep' => $svc->dayReport($date), 'hub' => $svc->hub(), 'actor' => $this->actor($r), 'mail' => false,
            'headOffice' => (string) config('pos_console.head_office_email', ''),
        ]);
    }

    public function closeDay(Request $r)
    {
        $v = $r->validate([
            'date'         => 'required|date_format:Y-m-d',
            'cash_counted' => 'required|numeric|min:0|max:100000000',
            'note'         => 'nullable|string|max:500',
        ]);
        $svc = $this->svc($r);
        $date = $this->reportDate($r);
        $html = null;
        try {
            $html = view('pos_console.report', [
                'rep' => $svc->dayReport($date), 'hub' => $svc->hub(), 'actor' => $this->actor($r), 'mail' => true,
                'counted' => (float) $v['cash_counted'], 'closeNote' => (string) ($v['note'] ?? ''), 'headOffice' => '',
            ])->render();
        } catch (\Throwable $e) {
            report($e);
        }
        $res = $svc->closeDay($date, (float) $v['cash_counted'], (string) ($v['note'] ?? ''), $this->actor($r), $html);
        if ($r->expectsJson()) return $this->json($res, $res['ok'] ? 200 : 422);
        return redirect()->route('posc.report', ['date' => $date])->with($res['ok'] ? 'posc_ok' : 'posc_err', $res['message']);
    }
}
