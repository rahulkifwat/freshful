<?php

namespace App\Services\ControlCentre;

use App\Services\CountryConsole\CountryScope;
use Illuminate\Support\Facades\DB;

/**
 * Freshful Control Centre update 2 (15 Nov 2026): the AI business analyst.
 * It never writes to business data. Every number comes from the FactsPack (the console's own figures).
 * With an AI key it asks the provider to explain the facts; the reply is checked (cited facts must exist, numbers must match the
 * facts, actions must be on the allowed list) and anything that fails the checks is replaced by a numbers-only answer.
 * Without a key it answers from the numbers directly. Every question and answer is logged to cc_ai_log.
 */
class Analyst
{
    public const MAX_Q = 400;

    public static function inr(float $v): string
    {
        $neg = $v < 0; $v = abs(round($v)); $s = (string) (int) $v;
        if (strlen($s) > 3) { $last = substr($s, -3); $rest = substr($s, 0, -3); $rest = preg_replace('/\B(?=(\d{2})+(?!\d))/', ',', $rest); $s = $rest . ',' . $last; }
        return ($neg ? '-' : '') . 'Rs ' . $s;
    }

    public static function status(): array
    {
        $on = AiClient::configured();
        return ['ok' => true, 'mode' => $on ? 'ai' : 'numbers', 'provider' => $on ? AiClient::provider() : '', 'model' => $on ? AiClient::model() : '',
            'note' => $on ? 'AI is connected. It only sees summary numbers, never customer details.' : 'No AI key is set, so answers are worked out from the numbers directly. See the setup steps to switch AI on.'];
    }

    private static function cap(string $c, int $orders): string
    {
        $rank = ['low' => 0, 'medium' => 1, 'high' => 2]; $c = isset($rank[$c]) ? $c : 'medium';
        $max = $orders < 10 ? 'low' : ($orders < 30 ? 'medium' : 'high');
        return $rank[$c] > $rank[$max] ? $max : $c;
    }

    private static function f(array $facts, string $id) { return $facts[$id]['value'] ?? null; }

    /** Deterministic answer from the facts. Returns [answer, source ids, actions]. */
    public static function numbersAnswer(string $q, array $pack): array
    {
        $F = $pack['facts']; $m = $pack['meta']; $q = strtolower($q);
        $per = $m['from'] === $m['to'] ? $m['from'] : $m['from'] . ' to ' . $m['to'];
        $has = fn (array $w) => (bool) array_filter($w, fn ($x) => strpos($q, $x) !== false);
        $pct = fn ($v) => $v === null ? 'no comparison available' : (($v >= 0 ? '+' : '') . $v . '%');
        $src = []; $act = []; $lines = [];
        if ($has(['margin', 'profit'])) {
            return ['Margin is not available. The cost price of products is incomplete and it is not confirmed whether cost is per pack or per kg. Once confirmed, margin can be added.', [], ['cc-health']];
        }
        if ($has(['forecast', 'predict', 'next week', 'tomorrow', 'will sell'])) {
            return ['A demand forecast is not built yet. With only ' . number_format($m['orders']) . ' orders in this period, any forecast would be unreliable.', [], []];
        }
        if ($has(['cancel'])) {
            $lines[] = self::f($F, 'cancelled') . ' orders were cancelled, ' . self::f($F, 'cancel_rate') . '% of all orders placed.'; $src = ['cancelled', 'cancel_rate']; $act = ['cancels'];
        } elseif ($has(['cash', 'settle', 'rider cash'])) {
            $lines[] = 'Rider cash not yet settled: ' . self::inr((float) self::f($F, 'cash_in_hand')) . '.'; $src = ['cash_in_hand']; $act = ['cash'];
        } elseif ($has(['expire', 'danger', 'stock', 'wast'])) {
            $lines[] = 'Stock value ' . self::inr((float) self::f($F, 'stock_value')) . '; danger stock ' . self::inr((float) self::f($F, 'danger_value')) . '; expired stock ' . self::inr((float) self::f($F, 'expired_value')) . '; wastage in the period ' . self::inr((float) self::f($F, 'wastage_value')) . '.';
            $src = ['stock_value', 'danger_value', 'expired_value', 'wastage_value']; $act = ['danger', 'stock'];
        } elseif ($has(['hub', 'branch', 'best', 'worst', 'rank'])) {
            $hubs = [];
            foreach ($F as $id => $x) if (preg_match('/^hub_(\d+)_sales$/', $id, $mm)) $hubs[] = [$x['value'], preg_replace('/^Hub (.*): sales$/', '$1', $x['label']), $id];
            usort($hubs, fn ($a, $b) => $b[0] <=> $a[0]);
            if (!$hubs) $lines[] = 'No hub sales in this period.'; else { foreach (array_slice($hubs, 0, 5) as $h) { $lines[] = $h[1] . ': ' . self::inr($h[0]); $src[] = $h[2]; } array_unshift($lines, 'Top hubs by sales:'); }
            $act = ['league'];
        } elseif ($has(['product', 'item', 'selling', 'category'])) {
            foreach ($F as $id => $x) if (strpos($id, 'top_product_') === 0 && count($lines) < 5) { $lines[] = preg_replace('/ \(sales value, Rs\)$/', '', $x['label']) . ': ' . self::inr($x['value']); $src[] = $id; }
            if (!$lines) $lines[] = 'No product sales in this period.'; else array_unshift($lines, 'Best selling products by sales value:');
            $act = ['stock'];
        } elseif ($has(['alert', 'problem', 'attention', 'wrong', 'risk', 'issue'])) {
            $n = (int) self::f($F, 'alerts_open'); $lines[] = $n ? $n . ' alerts need attention. Open the alert centre for the list, impact and next steps.' : 'No alerts need attention right now.'; $src = ['alerts_open']; $act = ['cc-alerts', 'cc-health'];
        } elseif ($has(['customer', 'repeat'])) {
            $lines[] = self::f($F, 'customers') . ' customers ordered: ' . self::f($F, 'new_customers') . ' new and ' . self::f($F, 'repeat_customers') . ' repeat.'; $src = ['customers', 'new_customers', 'repeat_customers'];
        } elseif ($has(['deliver', 'on time', 'late', 'delay'])) {
            $ot = self::f($F, 'on_time'); $lines[] = 'Delivered ' . self::f($F, 'delivered') . ' orders; on-time delivery ' . ($ot === null ? 'cannot be worked out (no timed deliveries).' : $ot . '%.') . ' Open now: ' . self::f($F, 'open_now') . ', late now: ' . self::f($F, 'late_now') . '.';
            $src = ['delivered', 'on_time', 'open_now', 'late_now']; $act = ['orders'];
        } else {
            $lines[] = 'Sales ' . self::inr((float) self::f($F, 'sales')) . ' (' . $pct(self::f($F, 'sales_vs')) . ' vs ' . ($m['compare'] ?: 'the comparison period') . '), ' . number_format((int) self::f($F, 'orders')) . ' orders (' . $pct(self::f($F, 'orders_vs')) . '), average order ' . self::inr((float) self::f($F, 'aov')) . ', cancellation rate ' . self::f($F, 'cancel_rate') . '%.';
            $src = ['sales', 'sales_vs', 'orders', 'orders_vs', 'aov', 'cancel_rate']; $act = ['reports', 'orders'];
        }
        $src = array_values(array_filter($src, fn ($id) => isset($F[$id]) || preg_match('/^(hub_|top_product_)/', $id)));
        return ['For ' . $per . ' (' . $m['scope'] . '): ' . implode(' ', $lines), $src, $act];
    }

    /** Numbers in the answer that cannot be found in the facts (small numbers, years and ids are ignored). */
    public static function unmatched(string $answer, array $facts): array
    {
        $known = [];
        foreach ($facts as $x) { if (!is_numeric($x['value'])) continue; $v = (float) $x['value']; foreach ([$v, round($v), round($v, 1), round($v, 2), abs($v)] as $k) $known[(string) round($k, 2)] = true; }
        $bad = [];
        preg_match_all('/\d[\d,]*\.?\d*/', $answer, $mm);
        foreach ($mm[0] as $raw) {
            $raw = rtrim($raw, '.,'); $n = (float) str_replace(',', '', $raw); if ($n < 100 || ($n >= 2000 && $n <= 2100 && strpos($raw, ',') === false)) continue;
            if (isset($known[(string) round($n, 2)]) || isset($known[(string) round($n)])) continue;
            $ok = false; // derived totals: allow a sum or difference of two facts
            $vals = array_values(array_map(fn ($x) => (float) $x['value'], array_filter($facts, fn ($x) => is_numeric($x['value']))));
            $cnt = count($vals);
            for ($i = 0; $i < $cnt && !$ok; $i++) for ($j = $i + 1; $j < $cnt; $j++) { if (abs($vals[$i] + $vals[$j] - $n) < 1 || abs($vals[$i] - $vals[$j] - $n) < 1) { $ok = true; break; } }
            if (!$ok) $bad[] = $raw;
        }
        return array_values(array_unique($bad));
    }

    /** Main entry. $now only for tests. */
    public static function ask(array $actor, string $question, CountryScope $scope, string $from, string $to, ?array $pack = null): array
    {
        $question = trim(preg_replace('/\s+/', ' ', $question));
        if ($question === '') return ['ok' => false, 'message' => 'Type a question.'];
        if (mb_strlen($question) > self::MAX_Q) return ['ok' => false, 'message' => 'Please keep the question under ' . self::MAX_Q . ' characters.'];
        $cap = (int) config('control_centre.ai_daily_cap', 100);
        if (self::logOn() && $cap > 0 && DB::table('cc_ai_log')->where('actor_id', (int) ($actor['id'] ?? 0))->where('created_at', '>=', date('Y-m-d 00:00:00'))->count() >= $cap) {
            return ['ok' => false, 'message' => 'The daily question limit (' . $cap . ') is reached. Try again tomorrow.'];
        }
        $pack = $pack ?? FactsPack::build($scope, $from, $to);
        $F = $pack['facts']; $meta = $pack['meta'];
        $mode = 'numbers'; $status = 'ok'; $note = null; $out = null;
        if (AiClient::configured()) {
            $r = AiClient::ask($question, $pack);
            if (!$r['ok']) { $status = 'fallback'; $note = $r['error']; }
            else {
                $d = $r['data']; $srcOk = array_values(array_filter((array) ($d['sources'] ?? []), fn ($id) => is_string($id) && isset($F[$id])));
                $act = array_values(array_filter((array) ($d['actions'] ?? []), fn ($a) => is_string($a) && isset(FactsPack::ACTIONS[$a])));
                $ans = trim(FactsPack::scrub(mb_substr((string) $d['answer'], 0, 1500)));
                if ($ans === '' || !$srcOk) { $status = 'fallback'; $note = 'The AI answer did not cite any known facts, so it was not used.'; }
                else {
                    $bad = self::unmatched($ans, $F); $conf = in_array($d['confidence'] ?? '', ['high', 'medium', 'low'], true) ? $d['confidence'] : 'medium';
                    $warn = [];
                    if ($bad) { $conf = 'low'; $warn[] = 'Some figures in this answer could not be matched to the data: ' . implode(', ', array_slice($bad, 0, 5)) . '. Check them against the linked records.'; }
                    $out = ['answer' => $ans, 'sources' => $srcOk, 'actions' => $act, 'confidence' => $conf, 'missing' => array_values(array_filter(array_map(fn ($x) => is_string($x) ? mb_substr($x, 0, 200) : null, (array) ($d['missing'] ?? [])))), 'warnings' => $warn];
                    $mode = 'ai';
                }
            }
        }
        if ($out === null) {
            [$ans, $srcIds, $act] = self::numbersAnswer($question, $pack);
            $out = ['answer' => $ans, 'sources' => $srcIds, 'actions' => $act, 'confidence' => 'high', 'missing' => [], 'warnings' => []];
            if (preg_match('/margin|profit|forecast|predict/i', $question)) { $out['confidence'] = 'low'; $out['missing'] = $meta['missing']; }
            if ($status === 'fallback') $out['warnings'][] = 'The AI service was not used for this answer (' . $note . ') so it was worked out from the numbers directly.';
        }
        $out['confidence'] = self::cap($out['confidence'], (int) $meta['orders']);
        if (empty($out['missing'])) $out['missing'] = array_values(array_filter($meta['missing'], fn ($m) => stripos($m, 'on-time') !== false));
        $srcOut = array_map(fn ($id) => $F[$id] + [], array_slice(array_values(array_filter($out['sources'], fn ($id) => isset($F[$id]))), 0, 10));
        $acts = array_map(fn ($k) => ['key' => $k, 'label' => FactsPack::ACTIONS[$k]], $out['actions']);
        $resp = ['ok' => true, 'question' => $question, 'answer' => $out['answer'], 'mode' => $mode, 'confidence' => $out['confidence'], 'warnings' => $out['warnings'], 'missing' => $out['missing'],
            'basis' => ['period_from' => $meta['from'], 'period_to' => $meta['to'], 'compared_with' => $meta['compare'], 'scope' => $meta['scope'], 'data_read_at' => $meta['read_at'], 'orders_in_period' => $meta['orders']],
            'sources' => $srcOut, 'actions' => $acts, 'note' => 'This answer is read only. Nothing was changed.'];
        self::log($actor, $question, $mode, $status, $note, $meta, $resp);
        return $resp;
    }

    public static function logOn(): bool { return CountryScope::has('cc_ai_log'); }

    private static function log(array $actor, string $q, string $mode, string $status, ?string $note, array $meta, array $resp): void
    {
        if (!self::logOn()) return;
        try {
            DB::table('cc_ai_log')->insert(['actor' => mb_substr((string) ($actor['name'] ?? ''), 0, 120), 'actor_id' => (int) ($actor['id'] ?? 0), 'actor_role' => mb_substr((string) ($actor['role'] ?? ''), 0, 30),
                'question' => mb_substr($q, 0, 500), 'mode' => $mode, 'provider' => $mode === 'ai' ? AiClient::provider() : '', 'status' => $status, 'note' => $note !== null ? mb_substr($note, 0, 300) : null,
                'period_from' => $meta['from'] ?: null, 'period_to' => $meta['to'] ?: null, 'scope' => mb_substr((string) $meta['scope'], 0, 200), 'confidence' => $resp['confidence'], 'sources' => count($resp['sources']),
                'answer' => $resp['answer'], 'created_at' => date('Y-m-d H:i:s')]);
        } catch (\Throwable $e) { /* logging must never block an answer */ }
    }

    public static function history(int $limit = 30): array
    {
        if (!self::logOn()) return ['ok' => false, 'message' => 'The AI log table is not installed. Run the migration first.'];
        $rows = DB::table('cc_ai_log')->orderByDesc('id')->limit(max(1, min(100, $limit)))->get();
        return ['ok' => true, 'rows' => $rows->map(fn ($r) => ['id' => (int) $r->id, 'at' => CountryScope::ts($r->created_at), 'actor' => $r->actor, 'question' => $r->question, 'answer' => $r->answer, 'mode' => $r->mode, 'status' => $r->status,
            'note' => $r->note, 'confidence' => $r->confidence, 'period' => trim(($r->period_from ?: '') . ' to ' . ($r->period_to ?: ''), ' to'), 'scope' => $r->scope])->all()];
    }
}
