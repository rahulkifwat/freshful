<?php

namespace App\Http\Controllers\CountryConsole;

use App\Services\CountryConsole\CountryAnalytics;
use App\Services\CountryConsole\CountryOps;
use App\Services\CountryConsole\CountryScope;
use App\Services\CountryConsole\CountryStock;
use App\Services\CountryConsole\CountryTargets;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Routing\Route as RouteObj;

/** Freshful country console (22 Oct 2026). Every endpoint answers JSON; the page itself is one Blade shell. */
class CountryConsoleController extends Controller
{
    /** The old /country_manager/dashboard route, kept to show it at /country_manager/console/classic. */
    public static ?RouteObj $classic = null;

    private function actor(Request $r): array { return (array) $r->attributes->get('country_actor', []); }
    private function scope(Request $r): CountryScope { return new CountryScope($r->all()); }
    private function range(Request $r, string $def = 'today'): array { return CountryScope::range($r->input('from'), $r->input('to'), $def); }
    private function json(array $d, int $code = 200) { return response()->json($d, $code)->header('Cache-Control', 'no-store'); }
    private function result(array $d) { return $this->json($d, !empty($d['ok']) ? 200 : 422); }

    public function app(Request $r)
    {
        return response()->view('country_console.app', ['actor' => $this->actor($r), 'meta' => CountryScope::options(), 'reasons' => CountryStock::reasons(),
            'groups' => CountryAnalytics::GROUPS, 'moveTypes' => CountryStock::MOVE_TYPES, 'tStatus' => CountryOps::T_STATUS, 'targetsOn' => CountryTargets::on()])
            ->header('Cache-Control', 'no-store, private')->header('X-Robots-Tag', 'noindex, nofollow');
    }

    public function classic(Request $r)
    {
        if (static::$classic) { try { static::$classic->bind($r); return static::$classic->run(); } catch (\Throwable $e) {} }
        return redirect()->route('ccc.home');
    }

    public function meta(Request $r) { return $this->json(['ok' => true] + CountryScope::options()); }

    public function overview(Request $r)
    {
        [$f, $t] = $this->range($r);
        return $this->json(CountryAnalytics::overview($this->scope($r), $f, $t));
    }

    public function live(Request $r)
    {
        $s = $this->scope($r); $ids = $s->ids(); $hubs = CountryScope::hubs();
        $l = CountryAnalytics::liveNow($ids);
        $rows = [];
        foreach ($ids as $h) {
            $b = $l['by_hub'][$h] ?? ['new' => 0, 'accepted' => 0, 'out' => 0, 'late' => 0];
            $rows[] = ['hub_id' => $h, 'hub' => $hubs[$h]['name'] ?? '', 'city' => $hubs[$h]['city'] ?? '', 'active' => $hubs[$h]['active'] ?? false] + $b + ['open' => $b['new'] + $b['accepted'] + $b['out']];
        }
        usort($rows, fn ($a, $b) => [$b['late'], $b['open']] <=> [$a['late'], $a['open']]);
        $late = array_map(fn ($x) => ['order_id' => $x['id'], 'hub' => $hubs[$x['hub']]['name'] ?? '', 'city' => $hubs[$x['hub']]['city'] ?? '', 'stage' => $x['stage'], 'type' => $x['type'], 'value' => $x['value'], 'placed_at' => $x['at'], 'due_at' => $x['due']], $l['late_rows']);
        return $this->json(['ok' => true, 'stages' => $l['stages'], 'hubs' => $rows, 'late' => $late, 'server_time' => time()]);
    }

    public function orders(Request $r)
    {
        [$f, $t] = $this->range($r);
        $res = CountryOps::orders($this->scope($r), $f, $t, $r->only(['stage', 'status', 'type', 'pay', 'q', 'view']), (int) $r->input('page', 1));
        if ($r->input('format') === 'csv') {
            $head = ['Order ID', 'Placed', 'Hub', 'City', 'State', 'Customer', 'Phone', 'Area', 'Items', 'Amount', 'Payment', 'Payment status', 'Delivery', 'Slot', 'Schedule date', 'Status', 'Stage', 'Rider', 'Late', 'Cancel reason'];
            $rows = array_map(fn ($o) => [$o['order_id'], $o['placed_at'] ? date('Y-m-d H:i', $o['placed_at']) : '', $o['hub'], $o['city'], $o['state'], $o['customer'], $o['phone'], $o['area'], $o['items'], $o['amount'], $o['payment_type'], $o['payment_status'], $o['delivery_type'], $o['slot'], $o['schedule_date'], $o['status'], $o['stage'], $o['rider'] ?? '', $o['late'] ? 'yes' : '', $o['cancel_reason']], $res['all']);
            return $this->csv('orders-' . $f . '-to-' . $t . '.csv', $head, $rows);
        }
        unset($res['all']);
        return $this->json($res);
    }

    private function csv(string $name, array $head, array $rows)
    {
        return response()->streamDownload(function () use ($head, $rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            $safe = fn ($v) => is_string($v) && $v !== '' && in_array($v[0], ['=', '+', '-', '@'], true) && !is_numeric($v) ? "'" . $v : $v;   // no spreadsheet formulas
            fputcsv($out, $head);
            foreach ($rows as $row) fputcsv($out, array_map($safe, $row));
            fclose($out);
        }, preg_replace('/[^\w.\-]/', '', $name), ['Content-Type' => 'text/csv; charset=UTF-8', 'Cache-Control' => 'no-store']);
    }

    public function order(Request $r, string $id) { return $this->result(CountryOps::order($id)); }
    public function orderCancel(Request $r, string $id) { return $this->result(CountryOps::cancel($id, (string) $r->input('reason', ''), $this->actor($r))); }
    public function orderMove(Request $r, string $id) { return $this->result(CountryOps::move($id, (int) $r->input('to_hub'), (string) $r->input('note', ''), $this->actor($r))); }
    public function cancels(Request $r) { [$f, $t] = $this->range($r); return $this->json(CountryAnalytics::cancellations($this->scope($r), $f, $t)); }

    public function stockProducts(Request $r) { return $this->json(CountryStock::byProduct($this->scope($r), (string) $r->input('q', ''), (string) $r->input('category', ''), (string) $r->input('status', ''))); }
    public function stockProduct(Request $r, int $id) { return $this->result(CountryStock::productHubs($this->scope($r), $id)); }
    public function stockHubs(Request $r) { return $this->json(CountryStock::byHub($this->scope($r))); }
    public function hubProducts(Request $r, int $hub) { return $this->result(CountryStock::hubProducts($hub)); }
    public function danger(Request $r) { return $this->result(CountryStock::danger($this->scope($r))); }

    public function dangerPlan(Request $r)
    {
        $hub = (int) $r->input('hub_id', 0);
        $ids = $hub > 0 ? [$hub] : $this->scope($r)->ids();
        $ids = array_values(array_filter($ids, fn ($h) => (CountryScope::hub($h)['active'] ?? false)));
        if (!$ids) return $this->result(['ok' => false, 'message' => 'No active hub selected.']);
        return $this->result(CountryStock::runPlans($ids, $this->actor($r)));
    }

    public function inward(Request $r) { return $this->json(CountryStock::pendingInward($this->scope($r))); }
    public function ledger(Request $r) { [$f, $t] = $this->range($r, '7'); return $this->json(CountryStock::ledger($this->scope($r), $f, $t, (string) $r->input('type', ''), mb_substr((string) $r->input('q', ''), 0, 60))); }
    public function count(Request $r) { return $this->result(CountryStock::correct((int) $r->input('hub_id'), (int) $r->input('product_id'), (float) $r->input('counted', -1), (string) $r->input('note', ''), $this->actor($r))); }
    public function writeOff(Request $r) { return $this->result(CountryStock::writeOff((int) $r->input('hub_id'), (int) $r->input('product_id'), (float) $r->input('qty', 0), (string) $r->input('reason', ''), (string) $r->input('note', ''), $this->actor($r))); }
    public function wastage(Request $r) { [$f, $t] = $this->range($r, '30'); return $this->json(CountryStock::wastageReport($this->scope($r), $f, $t)); }

    public function transfers(Request $r) { [$f, $t] = $this->range($r, '7'); return $this->json(CountryOps::transfers($this->scope($r), $f, $t, (string) $r->input('status', ''))); }
    public function transferCreate(Request $r) { return $this->result(CountryOps::transferCreate((int) $r->input('from_hub'), (int) $r->input('to_hub'), (array) $r->input('lines', []), (string) $r->input('note', ''), $this->actor($r))); }
    public function transferCancel(Request $r, int $id) { return $this->result(CountryOps::transferCancel($id, (string) $r->input('note', ''), $this->actor($r))); }

    public function cash(Request $r) { [$f, $t] = $this->range($r, '7'); return $this->json(CountryOps::cash($this->scope($r), $f, $t)); }
    public function riders(Request $r) { return $this->json(CountryOps::riders($this->scope($r))); }

    public function customers(Request $r)
    {
        $res = CountryOps::customers($this->scope($r), mb_substr((string) $r->input('q', ''), 0, 60), (string) $r->input('grade', ''), (string) $r->input('segment', ''), (int) $r->input('page', 1));
        if ($r->input('format') === 'csv') {
            $rows = array_map(fn ($c) => [$c['id'], $c['name'], $c['phone'], $c['email'], $c['hub'], $c['city'], $c['state'], $c['grade'], $c['segment'], $c['orders'], $c['spend'], $c['orders90'], $c['spend90'], $c['last'] ? date('Y-m-d', $c['last']) : '', $c['wallet']], $res['all']);
            return $this->csv('customers.csv', ['ID', 'Name', 'Phone', 'Email', 'Main hub', 'City', 'State', 'Grade', 'Segment', 'Orders', 'Spend', 'Orders 90d', 'Spend 90d', 'Last order', 'Wallet'], $rows);
        }
        unset($res['all']);
        return $this->json($res);
    }

    public function customer(Request $r, int $id) { return $this->result(CountryOps::customer($id)); }
    public function grievances(Request $r) { [$f, $t] = $this->range($r, '30'); return $this->json(CountryOps::grievances($this->scope($r), $f, $t)); }
    public function sales(Request $r) { [$f, $t] = $this->range($r, '30'); return $this->json(CountryAnalytics::sales($this->scope($r), $f, $t, (string) $r->input('group', 'day'))); }
    public function targets(Request $r) { return $this->json(CountryTargets::list((string) $r->input('month', date('Y-m')))); }
    public function targetSave(Request $r) { return $this->result(CountryTargets::save((string) $r->input('scope', ''), (int) $r->input('scope_id', 0), (string) $r->input('month', ''), (float) $r->input('sales_target', 0), (int) $r->input('orders_target', 0), (string) $r->input('note', ''), $this->actor($r))); }
    public function targetDelete(Request $r, int $id) { return $this->result(CountryTargets::delete($id, $this->actor($r))); }
}
