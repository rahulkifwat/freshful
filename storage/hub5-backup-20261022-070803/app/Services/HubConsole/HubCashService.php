<?php

namespace App\Services\HubConsole;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Hub cash book (22 Oct 2026). Replaces the old "Add Deposit Receipt" / "View Cash Deposits" mock-ups with real data:
 *   opening balance (yesterday's closing) + cash sale (cash the POS collected from riders that day)
 *   - deposits (bank / CMS pickup, each with a receipt photo) +/- adjustments (with a reason) = closing balance.
 * Cash sale comes from pos_cash_handovers.cash_amount, so it always matches the POS and the day-end report.
 */
class HubCashService
{
    public const TYPES = ['bank' => 'Bank deposit', 'cms' => 'CMS cash pickup', 'adjustment' => 'Adjustment', 'opening' => 'Opening balance'];
    private const MAX_DAYS = 400;

    public function __construct(private int $hubId) {}

    private function cashSales(string $from, string $to): array
    {
        if (!HubStock::has('pos_cash_handovers')) return [];
        return DB::table('pos_cash_handovers')->where('hub_id', $this->hubId)->whereBetween('created_at', [$from . ' 00:00:00', $to . ' 23:59:59'])
            ->groupBy(DB::raw('DATE(created_at)'))->select(DB::raw('DATE(created_at) AS d'), DB::raw('SUM(cash_amount) AS s'))->pluck('s', 'd')->map(fn ($v) => (float) $v)->all();
    }

    /** Day-by-day cash book. Balances are carried from the first day that has any cash activity. */
    public function book(string $from, string $to): array
    {
        if (!HubStock::has('hub_cash_entries')) return ['ok' => false, 'message' => 'Cash book is not installed yet.'];
        $to = min($to, date('Y-m-d'));
        if ($from > $to) $from = $to;
        $start = date('Y-m-d', strtotime('-' . self::MAX_DAYS . ' days', strtotime($to)));
        $first = [];
        if (HubStock::has('pos_cash_handovers')) $first[] = DB::table('pos_cash_handovers')->where('hub_id', $this->hubId)->min(DB::raw('DATE(created_at)'));
        $first[] = DB::table('hub_cash_entries')->where('hub_id', $this->hubId)->whereNull('voided_at')->min('business_date');
        $first = array_filter($first);
        $begin = $first ? max($start, min($first)) : $from;
        $begin = min($begin, $from);

        $sales = $this->cashSales($begin, $to);
        $entries = DB::table('hub_cash_entries')->where('hub_id', $this->hubId)->whereBetween('business_date', [$begin, $to])->orderBy('id')->get();
        $byDay = [];
        foreach ($entries as $e) $byDay[(string) $e->business_date][] = $e;
        $closes = HubStock::has('pos_day_closes') ? DB::table('pos_day_closes')->where('hub_id', $this->hubId)->whereBetween('business_date', [$from, $to])
            ->orderBy('id')->get()->keyBy(fn ($c) => (string) $c->business_date) : collect();

        $bal = 0.0; $rows = [];
        for ($t = strtotime($begin); $t <= strtotime($to); $t += 86400) {
            $d = date('Y-m-d', $t);
            $opening = $bal; $sale = round($sales[$d] ?? 0, 2); $dep = 0.0; $adj = 0.0; $list = [];
            foreach ($byDay[$d] ?? [] as $e) {
                $void = (bool) $e->voided_at;
                if (!$void) {
                    if ($e->type === 'opening') { $opening = (float) $e->amount; }
                    elseif ($e->type === 'adjustment') { $adj += (float) $e->amount; }
                    else { $dep += (float) $e->amount; }
                }
                $list[] = ['id' => (int) $e->id, 'type' => $e->type, 'type_label' => self::TYPES[$e->type] ?? $e->type, 'amount' => (float) $e->amount,
                    'reference' => $e->reference, 'note' => $e->note, 'by' => $e->by_name, 'at' => strtotime((string) $e->created_at) ?: null,
                    'receipt' => $e->receipt_path ? (int) $e->id : null, 'receipt_name' => $e->receipt_name, 'voided' => $void, 'void_note' => $e->void_note];
            }
            $closing = round($opening + $sale - $dep + $adj, 2);
            $bal = $closing;
            if ($d < $from) continue;
            $c = $closes[$d] ?? null;
            $rows[] = ['date' => $d, 'opening' => round($opening, 2), 'cash_sale' => $sale, 'deposits' => round($dep, 2), 'adjustments' => round($adj, 2), 'closing' => $closing,
                'entries' => $list, 'day_closed' => (bool) $c, 'counted' => $c ? (float) $c->cash_counted : null, 'count_diff' => $c ? (float) $c->difference : null];
        }
        // Which days' cash is still at the hub? Deposits clear the oldest cash first, so walk back from today.
        $since = null; $left = $bal;
        for ($t = strtotime($to); $left > 0.5 && $t >= strtotime($begin); $t -= 86400) {
            $d = date('Y-m-d', $t); $s = $sales[$d] ?? 0;
            if ($s > 0) { $since = $d; $left -= $s; }
        }
        $rows = array_reverse($rows);
        $sum = ['cash_sale' => 0.0, 'deposits' => 0.0, 'adjustments' => 0.0];
        foreach ($rows as &$r) {
            $sum['cash_sale'] += $r['cash_sale']; $sum['deposits'] += $r['deposits']; $sum['adjustments'] += $r['adjustments'];
            $r['undeposited'] = $since !== null && $r['date'] >= $since && $r['cash_sale'] > 0;
        }
        unset($r);
        // Hide empty days before any cash activity (nothing to show), keep everything else.
        if ($from !== $to) $rows = array_values(array_filter($rows, fn ($r) => $r['entries'] || abs($r['opening']) > 0.001 || abs($r['closing']) > 0.001 || $r['cash_sale'] > 0 || $r['day_closed']));
        $days = $since ? (int) floor((strtotime(date('Y-m-d')) - strtotime($since)) / 86400) : 0;
        return ['ok' => true, 'from' => $from, 'to' => $to, 'rows' => $rows, 'in_hand' => round($bal, 2), 'summary' => array_map(fn ($v) => round($v, 2), $sum),
            'cash_since' => $since, 'cash_age_days' => $days, 'cash_limit' => (float) config('hub_console.cash_limit', 20000), 'types' => self::TYPES];
    }

    /** Cash the hub is holding right now (closing balance today). */
    public function inHand(): float
    {
        $b = $this->book(date('Y-m-d'), date('Y-m-d'));
        return $b['ok'] ? (float) $b['in_hand'] : 0.0;
    }

    /**
     * Record a deposit / adjustment / opening balance. $file is an UploadedFile or null.
     * Deposits need a reference and a receipt photo (old form: "Invoice/Receipt *"); adjustments need a reason.
     */
    public function add(string $date, string $type, float $amount, string $reference, string $note, $file, array $actor): array
    {
        if (!array_key_exists($type, self::TYPES)) return ['ok' => false, 'message' => 'Choose the entry type.'];
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || $date > date('Y-m-d') || $date < date('Y-m-d', strtotime('-60 days'))) return ['ok' => false, 'message' => 'Choose a date within the last 60 days.'];
        if ($type === 'adjustment' ? abs($amount) < 0.01 : $amount < 0.01) return ['ok' => false, 'message' => 'Enter the amount.'];
        if (abs($amount) > 10000000) return ['ok' => false, 'message' => 'The amount looks wrong.'];
        if (in_array($type, ['bank', 'cms'], true) && trim($reference) === '') return ['ok' => false, 'message' => 'Enter the deposit slip / receipt number.'];
        if (in_array($type, ['bank', 'cms'], true) && !$file) return ['ok' => false, 'message' => 'Attach a photo or PDF of the deposit receipt.'];
        if (in_array($type, ['adjustment', 'opening'], true) && trim($note) === '') return ['ok' => false, 'message' => 'Write the reason for this entry.'];
        if ($type === 'opening' && DB::table('hub_cash_entries')->where('hub_id', $this->hubId)->where('type', 'opening')->whereNull('voided_at')->exists()) {
            return ['ok' => false, 'message' => 'An opening balance is already set. Use an adjustment instead.'];
        }
        $path = null; $name = null;
        if ($file) {
            $ext = strtolower((string) $file->getClientOriginalExtension());
            if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'pdf'], true) || $file->getSize() > 6 * 1024 * 1024) return ['ok' => false, 'message' => 'The receipt must be a JPG, PNG, WEBP or PDF under 6 MB.'];
            $path = $file->storeAs('hub_receipts/' . $this->hubId, date('Ymd-His') . '-' . bin2hex(random_bytes(4)) . '.' . $ext, 'local');
            $name = mb_substr((string) $file->getClientOriginalName(), 0, 160);
        }
        $id = DB::table('hub_cash_entries')->insertGetId([
            'hub_id' => $this->hubId, 'business_date' => $date, 'type' => $type, 'amount' => round($amount, 2),
            'reference' => trim($reference) !== '' ? mb_substr(trim($reference), 0, 120) : null, 'receipt_path' => $path, 'receipt_name' => $name,
            'note' => trim($note) !== '' ? mb_substr(trim($note), 0, 500) : null, 'by_id' => $actor['id'] ?? null, 'by_name' => mb_substr((string) ($actor['name'] ?? ''), 0, 120),
            'created_at' => now(), 'updated_at' => now(),
        ]);
        Log::info('[freshful-audit] hub-cash-entry', ['hub' => $this->hubId, 'id' => $id, 'type' => $type, 'amount' => $amount, 'by' => $actor['id'] ?? null]);
        return ['ok' => true, 'message' => (self::TYPES[$type]) . ' of ₹' . number_format(abs($amount), 2) . ' saved for ' . date('d M', strtotime($date)) . '.'];
    }

    /** Entries are never deleted: they are voided with a reason and stay visible (struck through). */
    public function void(int $id, string $note, array $actor): array
    {
        if (trim($note) === '') return ['ok' => false, 'message' => 'Write why this entry is wrong.'];
        $n = DB::table('hub_cash_entries')->where('id', $id)->where('hub_id', $this->hubId)->whereNull('voided_at')
            ->where('created_at', '>=', now()->subDays(7))->update(['voided_at' => now(), 'void_note' => mb_substr(trim($note) . ' (' . ($actor['name'] ?? '') . ')', 0, 250), 'updated_at' => now()]);
        if (!$n) return ['ok' => false, 'message' => 'Only entries from the last 7 days can be cancelled. Ask head office for older ones.'];
        Log::info('[freshful-audit] hub-cash-void', ['hub' => $this->hubId, 'id' => $id, 'by' => $actor['id'] ?? null]);
        return ['ok' => true, 'message' => 'Entry cancelled. It stays in the book, struck through.'];
    }

    /** Absolute path of a receipt, only for this hub. */
    public function receiptPath(int $id): ?array
    {
        $e = DB::table('hub_cash_entries')->where('id', $id)->where('hub_id', $this->hubId)->first(['receipt_path', 'receipt_name']);
        if (!$e || !$e->receipt_path || !Storage::disk('local')->exists($e->receipt_path)) return null;
        return [Storage::disk('local')->path($e->receipt_path), $e->receipt_name ?: basename($e->receipt_path)];
    }
}
