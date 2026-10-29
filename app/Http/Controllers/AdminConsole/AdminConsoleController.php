<?php

namespace App\Http\Controllers\AdminConsole;

use App\Services\AdminConsole\AdminAudit;
use App\Services\AdminConsole\AdminCare;
use App\Services\AdminConsole\AdminIndiaCities;
use App\Services\AdminConsole\AdminLive;
use App\Services\AdminConsole\AdminCatalog;
use App\Services\AdminConsole\AdminDashboard;
use App\Services\AdminConsole\AdminHubs;
use App\Services\AdminConsole\AdminPush;
use App\Services\AdminConsole\AdminRecords;
use App\Services\AdminConsole\AdminReports;
use App\Services\AdminConsole\AdminSettings;
use App\Services\AdminConsole\AdminSheet;
use App\Services\AdminConsole\AdminStaff;
use App\Services\AdminConsole\AdminUploads;
use App\Services\CountryConsole\CountryScope;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

/** Freshful admin console (admin update 1, 22 Oct 2026): head-office modules. JSON in, JSON out; only reachable with the admin login. */
class AdminConsoleController extends Controller
{
    private function actor(Request $r): array { return (array) $r->attributes->get('country_actor', []); }
    private function json(array $d, int $code = 200) { return response()->json($d, $code)->header('Cache-Control', 'no-store'); }
    private function result(array $d) { return $this->json($d, !empty($d['ok']) ? 200 : 422); }
    private function isAdmin(Request $r): bool { return ($this->actor($r)['role'] ?? '') === 'admin'; }
    private function guard(Request $r) { return $this->isAdmin($r) ? null : $this->json(['ok' => false, 'message' => 'Admin only.'], 403); }
    private function safe(callable $fn)
    {
        try { return $this->result($fn()); }
        catch (\Throwable $e) { report($e); return $this->json(['ok' => false, 'message' => 'Could not finish: ' . mb_substr($e->getMessage(), 0, 200)], 422); }
    }

    public function home(Request $r)
    {
        if ($g = $this->guard($r)) return $g;
        [$f, $t] = CountryScope::range($r->input('from'), $r->input('to'), 'today');
        return $this->json(AdminDashboard::data(new CountryScope($r->all()), $f, $t));
    }

    public function meta(Request $r)
    {
        if ($g = $this->guard($r)) return $g;
        return $this->json(['ok' => true, 'records' => AdminRecords::meta(), 'uploads' => AdminUploads::root() !== null, 'audit' => AdminAudit::on(), 'push' => AdminPush::configured()]);
    }

    public function upload(Request $r)
    {
        if ($g = $this->guard($r)) return $g;
        $f = $r->file('file');
        if (!$f) return $this->result(['ok' => false, 'message' => 'Choose an image.']);
        return $this->result(AdminUploads::save($f, (string) $r->input('folder', '')));
    }

    /* ---------------- master lists */
    public function records(Request $r, string $res) { if ($g = $this->guard($r)) return $g; return $this->result(AdminRecords::list($res, (string) $r->input('q', ''), (string) $r->input('status', ''))); }
    public function recordSave(Request $r, string $res) { if ($g = $this->guard($r)) return $g; return $this->safe(fn () => AdminRecords::save($res, $r->input('id') ? (int) $r->input('id') : null, (array) $r->input('values', []), $this->actor($r))); }
    public function recordToggle(Request $r, string $res, int $id) { if ($g = $this->guard($r)) return $g; return $this->safe(fn () => AdminRecords::toggle($res, $id, $this->actor($r))); }
    public function recordDelete(Request $r, string $res, int $id) { if ($g = $this->guard($r)) return $g; return $this->safe(fn () => AdminRecords::delete($res, $id, $this->actor($r))); }

    /* ---------------- products */
    public function products(Request $r) { if ($g = $this->guard($r)) return $g; return $this->json(AdminCatalog::list()); }
    public function productMeta(Request $r) { if ($g = $this->guard($r)) return $g; return $this->json(AdminCatalog::meta()); }
    public function productItems(Request $r, int $cat) { if ($g = $this->guard($r)) return $g; return $this->json(AdminCatalog::items($cat)); }
    public function product(Request $r, int $id) { if ($g = $this->guard($r)) return $g; return $this->result(AdminCatalog::get($id)); }
    public function productSave(Request $r) { if ($g = $this->guard($r)) return $g; return $this->safe(fn () => AdminCatalog::save($r->input('id') ? (int) $r->input('id') : null, (array) $r->input('values', []), $this->actor($r))); }
    public function productToggle(Request $r, int $id) { if ($g = $this->guard($r)) return $g; return $this->safe(fn () => AdminCatalog::toggle($id, $this->actor($r))); }
    public function productBulk(Request $r) { if ($g = $this->guard($r)) return $g; return $this->safe(fn () => AdminCatalog::bulkStatus((array) $r->input('ids', []), (bool) $r->input('on'), $this->actor($r))); }
    public function missing(Request $r) { if ($g = $this->guard($r)) return $g; return $this->json(AdminCatalog::missing()); }

    private function xlsx(string $bin, string $name)
    {
        return response($bin, 200, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'Content-Disposition' => 'attachment; filename="' . $name . '"', 'Cache-Control' => 'no-store']);
    }
    public function productExport(Request $r) { if ($g = $this->guard($r)) return $g; return $this->xlsx(AdminCatalog::exportXlsx(), 'freshful-products-' . date('Y-m-d') . '.xlsx'); }
    public function productTemplate(Request $r) { if ($g = $this->guard($r)) return $g; return $this->xlsx(AdminCatalog::templateXlsx(), 'freshful-new-products-template.xlsx'); }

    public function productExcel(Request $r)
    {
        if ($g = $this->guard($r)) return $g;
        $f = $r->file('file');
        if (!$f || !$f->isValid()) return $this->result(['ok' => false, 'message' => 'Choose the Excel file.']);
        if ($f->getSize() > 5 * 1024 * 1024) return $this->result(['ok' => false, 'message' => 'The file is larger than 5 MB.']);
        $mode = (string) $r->input('mode', 'update'); $apply = filter_var($r->input('apply'), FILTER_VALIDATE_BOOLEAN);
        return $this->safe(function () use ($f, $mode, $apply, $r) {
            $rows = AdminSheet::read($f->getRealPath(), $f->getClientOriginalExtension());
            if (count($rows) < 2) return ['ok' => false, 'message' => 'The file has no rows under the header.'];
            if (count($rows) > 3001) return ['ok' => false, 'message' => 'At most 3000 rows per file.'];
            return $mode === 'import' ? AdminCatalog::import($rows, $apply, $this->actor($r)) : AdminCatalog::bulkUpdate($rows, $apply, $this->actor($r));
        });
    }

    /* ---------------- staff & roles */
    public function staffMeta(Request $r) { if ($g = $this->guard($r)) return $g; return $this->json(AdminStaff::meta()); }
    public function staff(Request $r, string $type) { if ($g = $this->guard($r)) return $g; return $this->result(AdminStaff::list($type)); }
    public function staffSave(Request $r, string $type) { if ($g = $this->guard($r)) return $g; return $this->safe(fn () => AdminStaff::save($type, $r->input('id') ? (int) $r->input('id') : null, (array) $r->input('values', []), $this->actor($r))); }
    public function staffToggle(Request $r, string $type, int $id) { if ($g = $this->guard($r)) return $g; return $this->safe(fn () => AdminStaff::toggle($type, $id, $this->actor($r))); }
    public function roles(Request $r) { if ($g = $this->guard($r)) return $g; return $this->result(AdminStaff::roles()); }
    public function roleSave(Request $r) { if ($g = $this->guard($r)) return $g; return $this->safe(fn () => AdminStaff::roleSave($r->input('id') ? (int) $r->input('id') : null, (array) $r->input('values', []), $this->actor($r))); }

    /* ---------------- hubs */
    public function hubs(Request $r) { if ($g = $this->guard($r)) return $g; return $this->json(AdminHubs::list()); }
    public function hubSave(Request $r) { if ($g = $this->guard($r)) return $g; return $this->safe(fn () => AdminHubs::save($r->input('id') ? (int) $r->input('id') : null, (array) $r->input('values', []), $this->actor($r))); }
    public function hubToggle(Request $r, int $id) { if ($g = $this->guard($r)) return $g; return $this->safe(fn () => AdminHubs::toggle($id, $this->actor($r))); }

    /* ---------------- push */
    public function push(Request $r) { if ($g = $this->guard($r)) return $g; return $this->json(AdminPush::list()); }
    public function pushSend(Request $r) { if ($g = $this->guard($r)) return $g; return $this->safe(fn () => AdminPush::send((string) $r->input('title', ''), (string) $r->input('body', ''), (string) $r->input('image', ''), $this->actor($r))); }

    /* ---------------- reports */
    public function invReport(Request $r)
    {
        if ($g = $this->guard($r)) return $g;
        [$f, $t] = CountryScope::range($r->input('from'), $r->input('to'), 'today');
        $s = new CountryScope($r->all());
        $d = AdminReports::inventory($s, $f, $t);
        if ($r->input('format') === 'xlsx') {
            $title = 'Freshful inventory report · ' . $s->label() . ' · ' . ($f === $t ? date('d M Y', strtotime($f)) : date('d M Y', strtotime($f)) . ' - ' . date('d M Y', strtotime($t)));
            return $this->xlsx(AdminReports::inventoryXlsx($d, $title, (string) $r->input('units', 'w')), 'freshful-inventory-report-' . $f . '.xlsx');
        }
        return $this->json($d + ['label' => $s->label()]);
    }

    public function audit(Request $r)
    {
        if ($g = $this->guard($r)) return $g;
        [$f, $t] = CountryScope::range($r->input('from'), $r->input('to'), '30');
        return $this->json(AdminAudit::list($f, $t, (string) $r->input('q', ''), (string) $r->input('module', '')));
    }

    /* ---------------- admin update 2 */
    private function rng(Request $r, string $d = 'today'): array { return CountryScope::range($r->input('from'), $r->input('to'), $d); }
    public function citiesOf(Request $r, int $state) { if ($g = $this->guard($r)) return $g; return $this->json(AdminIndiaCities::forState($state)); }
    public function productDelete(Request $r, int $id) { if ($g = $this->guard($r)) return $g; return $this->safe(fn () => AdminCatalog::delete($id, $this->actor($r))); }
    public function god(Request $r) { if ($g = $this->guard($r)) return $g; return $this->json(AdminLive::god(new CountryScope($r->all()))); }
    public function heat(Request $r) { if ($g = $this->guard($r)) return $g; [$f, $t] = $this->rng($r, '30'); return $this->json(AdminLive::heat(new CountryScope($r->all()), $f, $t)); }
    public function board(Request $r) { if ($g = $this->guard($r)) return $g; return $this->json(AdminLive::board(new CountryScope($r->all()))); }
    public function boardAct(Request $r) { if ($g = $this->guard($r)) return $g; return $this->safe(fn () => AdminLive::act((string) $r->input('order_id', ''), (string) $r->input('action', ''), $r->input('reason'), $r->input('rider_id') ? (int) $r->input('rider_id') : null, (bool) $r->input('auto'), $this->actor($r))); }
    public function refunds(Request $r) { if ($g = $this->guard($r)) return $g; [$f, $t] = $this->rng($r, '30'); return $this->result(AdminCare::refunds(new CountryScope($r->all()), $f, $t, (string) $r->input('status', ''))); }
    public function refundPay(Request $r) { if ($g = $this->guard($r)) return $g; return $this->safe(fn () => AdminCare::refund((string) $r->input('order_id', ''), (string) $r->input('reason', ''), (float) $r->input('amount', 0), (string) $r->input('method', ''), (string) $r->input('reference', ''), (string) $r->input('note', ''), $r->input('ticket_id') ? (int) $r->input('ticket_id') : null, $this->actor($r))); }
    public function refundReject(Request $r) { if ($g = $this->guard($r)) return $g; return $this->safe(fn () => AdminCare::refundReject((string) $r->input('order_id', ''), (string) $r->input('reason', ''), (string) $r->input('note', ''), $this->actor($r))); }
    public function tickets(Request $r) { if ($g = $this->guard($r)) return $g; [$f, $t] = $this->rng($r, '30'); return $this->result(AdminCare::tickets(in_array($r->input('kind'), ['ticket', 'dispute', 'enquiry'], true) ? $r->input('kind') : 'ticket', (string) $r->input('status', ''), (string) $r->input('q', ''), $f, $t)); }
    public function ticketCreate(Request $r) { if ($g = $this->guard($r)) return $g; return $this->safe(fn () => AdminCare::create((array) $r->input('values', []), $this->actor($r))); }
    public function ticket(Request $r, int $id) { if ($g = $this->guard($r)) return $g; return $this->result(AdminCare::detail($id)); }
    public function ticketReply(Request $r, int $id) { if ($g = $this->guard($r)) return $g; return $this->safe(fn () => AdminCare::reply($id, (string) $r->input('body', ''), (string) $r->input('channel', 'note'), (string) $r->input('status_after', ''), $this->actor($r))); }
    public function ticketUpdate(Request $r, int $id) { if ($g = $this->guard($r)) return $g; return $this->safe(fn () => AdminCare::update($id, (array) $r->input('values', []), $this->actor($r))); }
    public function ticketSettle(Request $r, int $id) { if ($g = $this->guard($r)) return $g; return $this->safe(fn () => AdminCare::settle($id, (string) $r->input('outcome', ''), (float) $r->input('amount', 0), (string) $r->input('method', 'wallet'), (string) $r->input('reference', ''), (string) $r->input('note', ''), $this->actor($r))); }
    public function enquiries(Request $r, string $type) { if ($g = $this->guard($r)) return $g; return $this->result(AdminCare::enquiries($type)); }
    public function enquiryTicket(Request $r, string $type, int $id) { if ($g = $this->guard($r)) return $g; return $this->safe(fn () => AdminCare::enquiryTicket($type, $id, $this->actor($r))); }
    public function reviews(Request $r) { if ($g = $this->guard($r)) return $g; return $this->result(AdminCare::reviews()); }
    public function reviewSave(Request $r, int $id) { if ($g = $this->guard($r)) return $g; return $this->safe(fn () => AdminCare::reviewSave($id, (string) $r->input('reply', ''), $r->has('hidden') ? filter_var($r->input('hidden'), FILTER_VALIDATE_BOOLEAN) : null, $this->actor($r))); }

    /* ---------------- my profile */
    public function profile(Request $r) { if ($g = $this->guard($r)) return $g; return $this->result(AdminSettings::profile((int) ($this->actor($r)['id'] ?? 0))); }
    public function profileSave(Request $r) { if ($g = $this->guard($r)) return $g; return $this->safe(fn () => AdminSettings::save((int) ($this->actor($r)['id'] ?? 0), (array) $r->input('values', []), $this->actor($r))); }
    public function password(Request $r) { if ($g = $this->guard($r)) return $g; return $this->safe(fn () => AdminSettings::password((int) ($this->actor($r)['id'] ?? 0), (string) $r->input('current', ''), (string) $r->input('new', ''), (string) $r->input('confirm', ''), $this->actor($r))); }
}
