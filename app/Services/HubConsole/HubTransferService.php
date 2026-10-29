<?php

namespace App\Services\HubConsole;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Inter-hub stock movement (22 Oct 2026). Replaces the old hub pages that were never connected to the database:
 * InterHub Orders, InterHub Moments View, Request Outward Stocks, Transfer Danger Stock, Hub Inward/Outward Transactions.
 *
 *   send:    my hub -> another hub. Stock leaves my hub when I press Send (status in_transit).
 *   request: I ask another hub for stock (status requested). That hub fulfils it (becomes in_transit) or declines it.
 *   receive: the receiving hub counts what arrived. A difference needs a note. Stock is added only then.
 *   cancel:  the sender can cancel while in transit (stock goes back); the requester can withdraw a request.
 */
class HubTransferService
{
    public const REASONS = ['danger' => 'Danger stock (11:00 plan)', 'balance' => 'Balance stock between hubs', 'older' => 'Move danger stock (sell faster)', 'request' => 'Requested by the other hub', 'other' => 'Other'];

    public function __construct(private int $hubId) {}

    private static function q(float $n): string { return rtrim(rtrim(number_format($n, 3, '.', ''), '0'), '.'); }

    /** Active hubs other than mine (cities included) - same status rule as the POS relocation list. */
    public function hubs(): array
    {
        $q = DB::table('hubs AS h')->where('h.id', '<>', $this->hubId)->whereRaw("LOWER(CAST(h.status AS CHAR)) IN ('active','1')");
        if (HubStock::has('cities')) $q->leftJoin('cities AS c', 'c.id', '=', 'h.city_id')->addSelect('c.city');
        return $q->addSelect('h.id', 'h.hub')->orderBy('h.hub')->get()->map(fn ($h) => ['id' => (int) $h->id, 'hub' => (string) $h->hub, 'city' => (string) ($h->city ?? '')])->all();
    }

    private function hubName(int $id): string
    {
        return (string) (DB::table('hubs')->where('id', $id)->value('hub') ?? ('Hub ' . $id));
    }

    /** product id (products.id) => [code, name, pack] */
    private function products(array $ids): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
        if (!$ids) return [];
        $q = DB::table('products AS p')->whereIn('p.id', $ids);
        if (HubStock::has('items')) $q->leftJoin('items AS i', 'i.id', '=', 'p.item_id')->addSelect('i.item');
        $out = [];
        foreach ($q->addSelect('p.id', 'p.product_id', 'p.product_name', 'p.unit_quantity', 'p.product_unit')->get() as $p) {
            if (!$p->product_id) continue;
            $uq = (float) $p->unit_quantity;
            $out[(int) $p->id] = ['code' => (string) $p->product_id, 'name' => trim((string) (($p->item ?? null) ?: $p->product_name)),
                'pack' => trim(($uq ? self::q($uq) : '') . ' ' . $p->product_unit)];
        }
        return $out;
    }

    /** Clean the posted lines: [[product_id, qty]] -> [products.id => qty], positive only, max 200 lines. */
    private static function lines(array $raw): array
    {
        $out = [];
        foreach (array_slice($raw, 0, 200) as $l) {
            $id = (int) ($l['product_id'] ?? 0); $qty = round((float) ($l['qty'] ?? 0), 3);
            if ($id > 0 && $qty > 0 && $qty <= 100000) $out[$id] = ($out[$id] ?? 0) + $qty;
        }
        return $out;
    }

    /** Meeting point for the two hubs' riders: the midpoint between the hubs (owner's process: riders meet in the centre and exchange). */
    public static function meetPoint(int $a, int $b): array
    {
        $h = DB::table('hubs')->whereIn('id', [$a, $b])->get(['id', 'hub', 'hub_lat', 'hub_lng'])->keyBy('id');
        $x = $h[$a] ?? null; $y = $h[$b] ?? null;
        if (!$x || !$y || !is_numeric($x->hub_lat) || !is_numeric($x->hub_lng) || !is_numeric($y->hub_lat) || !is_numeric($y->hub_lng)) return [null, null, null];
        $lat = round(((float) $x->hub_lat + (float) $y->hub_lat) / 2, 7); $lng = round(((float) $x->hub_lng + (float) $y->hub_lng) / 2, 7);
        return ['Midpoint ' . $x->hub . ' - ' . $y->hub, $lat, $lng];
    }

    private function meetCols(int $a, int $b): array
    {
        if (!HubStock::has('hub_transfers', 'meet_point')) return [];
        [$t, $lat, $lng] = self::meetPoint($a, $b);
        return ['meet_point' => $t, 'meet_lat' => $lat, 'meet_lng' => $lng];
    }

    private static function lotsOf($json): ?array
    {
        if (!$json) return null;
        $l = json_decode((string) $json, true);
        return is_array($l) ? $l : null;
    }

    private static function saveLots(int $transferId, string $code, array $lots): void
    {
        if ($lots && HubStock::has('hub_transfer_items', 'lots')) DB::table('hub_transfer_items')->where('transfer_id', $transferId)->where('product_code', $code)->update(['lots' => json_encode($lots)]);
    }

    private function newRef(): string
    {
        do { $ref = 'IH' . date('ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 5)); } while (DB::table('hub_transfers')->where('ref', $ref)->exists());
        return $ref;
    }

    private function rider(?int $riderId): ?object
    {
        if (!$riderId) return null;
        return DB::table('delivery_boy')->where('id', $riderId)->where('hub_id', $this->hubId)->first(['id', 'name']);
    }

    /* ---------------------------------------------------------------- reading */

    public function list(string $from = '', string $to = ''): array
    {
        if (!HubStock::has('hub_transfers')) return ['ok' => false, 'message' => 'Transfers are not installed yet.'];
        $from = $from ?: date('Y-m-d', strtotime('-30 days')); $to = $to ?: date('Y-m-d');
        $rows = DB::table('hub_transfers')->where(fn ($w) => $w->where('from_hub_id', $this->hubId)->orWhere('to_hub_id', $this->hubId))
            ->where(function ($w) use ($from, $to) {
                $w->whereBetween('created_at', [$from . ' 00:00:00', $to . ' 23:59:59'])->orWhereIn('status', ['requested', 'in_transit', 'planned']);
            })->orderByDesc('id')->limit(400)->get();
        $items = $rows->isEmpty() ? collect() : DB::table('hub_transfer_items')->whereIn('transfer_id', $rows->pluck('id'))->get()->groupBy('transfer_id');
        $names = DB::table('hubs')->whereIn('id', $rows->pluck('from_hub_id')->merge($rows->pluck('to_hub_id'))->unique())->pluck('hub', 'id');
        $list = [];
        $counts = ['incoming' => 0, 'requests_to_me' => 0, 'my_requests' => 0, 'outgoing' => 0, 'planned' => 0];
        foreach ($rows as $r) {
            $mineOut = (int) $r->from_hub_id === $this->hubId;
            $lines = ($items[$r->id] ?? collect())->map(fn ($i) => [
                'code' => $i->product_code, 'name' => $i->product_name, 'pack' => $i->pack,
                'requested' => $i->requested_qty !== null ? (float) $i->requested_qty : null, 'sent' => $i->sent_qty !== null ? (float) $i->sent_qty : null,
                'received' => $i->received_qty !== null ? (float) $i->received_qty : null,
                'lots' => self::lotsOf($i->lots ?? null),
            ])->values()->all();
            $diff = 0.0; foreach ($lines as $l) if ($l['received'] !== null && $l['sent'] !== null) $diff += $l['received'] - $l['sent'];
            $dir = $mineOut ? 'out' : 'in';
            if ($r->status === 'in_transit' && !$mineOut) $counts['incoming']++;
            if ($r->status === 'in_transit' && $mineOut) $counts['outgoing']++;
            if ($r->status === 'requested' && $mineOut) $counts['requests_to_me']++;
            if ($r->status === 'requested' && !$mineOut) $counts['my_requests']++;
            if ($r->status === 'planned' && $mineOut) $counts['planned']++;
            if ($r->status === 'planned' && !$mineOut) continue;   // the receiving hub sees it only once it is dispatched
            $list[] = [
                'id' => (int) $r->id, 'ref' => $r->ref, 'outward_ref' => $r->outward_ref ?? null, 'kind' => $r->kind, 'direction' => $dir, 'status' => $r->status,
                'from' => (string) ($names[$r->from_hub_id] ?? $r->from_hub_id), 'to' => (string) ($names[$r->to_hub_id] ?? $r->to_hub_id),
                'other_hub' => (string) ($mineOut ? ($names[$r->to_hub_id] ?? '') : ($names[$r->from_hub_id] ?? '')),
                'reason' => $r->reason, 'reason_label' => self::REASONS[$r->reason] ?? '', 'instruction' => $r->instruction,
                'rider' => $r->rider_name, 'created_by' => $r->created_by, 'sent_by' => $r->sent_by, 'received_by' => $r->received_by,
                'created_at' => strtotime((string) $r->created_at) ?: null, 'sent_at' => $r->sent_at ? strtotime((string) $r->sent_at) : null,
                'received_at' => $r->received_at ? strtotime((string) $r->received_at) : null, 'receive_note' => $r->receive_note, 'close_note' => $r->close_note,
                'meet_point' => $r->meet_point ?? null, 'meet_lat' => isset($r->meet_lat) ? (float) $r->meet_lat : null, 'meet_lng' => isset($r->meet_lng) ? (float) $r->meet_lng : null,
                'lines' => $lines, 'items' => count($lines), 'qty' => array_sum(array_map(fn ($l) => $l['sent'] ?? $l['requested'] ?? 0, $lines)), 'difference' => round($diff, 3),
                'can' => [
                    'receive' => $r->status === 'in_transit' && !$mineOut,
                    'cancel' => ($r->status === 'in_transit' && $mineOut) || ($r->status === 'requested' && !$mineOut) || ($r->status === 'planned' && $mineOut),
                    'fulfil' => in_array($r->status, ['requested', 'planned'], true) && $mineOut,
                    'decline' => $r->status === 'requested' && $mineOut,
                ],
            ];
        }
        return ['ok' => true, 'from' => $from, 'to' => $to, 'transfers' => $list, 'counts' => $counts, 'hubs' => $this->hubs(), 'reasons' => self::REASONS,
            'riders' => DB::table('delivery_boy')->where('hub_id', $this->hubId)->whereRaw("LOWER(CAST(status AS CHAR)) IN ('active','1')")->orderBy('name')->get(['id', 'name'])->map(fn ($r) => ['id' => (int) $r->id, 'name' => $r->name])->all()];
    }

    /** Pending counts only (for the badges and insights). */
    public function counts(): array
    {
        if (!HubStock::has('hub_transfers')) return ['incoming' => 0, 'requests_to_me' => 0, 'my_requests' => 0, 'outgoing' => 0];
        $c = DB::table('hub_transfers')->whereIn('status', ['requested', 'in_transit', 'planned'])->where(fn ($w) => $w->where('from_hub_id', $this->hubId)->orWhere('to_hub_id', $this->hubId))
            ->get(['status', 'from_hub_id']);
        $o = ['incoming' => 0, 'requests_to_me' => 0, 'my_requests' => 0, 'outgoing' => 0];
        foreach ($c as $r) {
            $out = (int) $r->from_hub_id === $this->hubId;
            if ($r->status === 'planned') { if ($out) $o['planned'] = ($o['planned'] ?? 0) + 1; continue; }
            if ($r->status === 'in_transit') $o[$out ? 'outgoing' : 'incoming']++; else $o[$out ? 'requests_to_me' : 'my_requests']++;
        }
        return $o;
    }

    /** code => [name, category] for the transaction lines */
    private static function productInfo(array $codes): array
    {
        $codes = array_values(array_unique(array_filter($codes)));
        if (!$codes) return [];
        $q = DB::table('products AS p')->whereIn('p.product_id', $codes)->leftJoin('items AS i', 'i.id', '=', 'p.item_id');
        $cat = null;
        if (HubStock::has('category')) {
            if (HubStock::has('items', 'cat_id')) { $q->leftJoin('category AS c', 'c.id', '=', 'i.cat_id'); $cat = 'c.name'; }
            elseif (HubStock::has('products', 'category_id')) { $q->leftJoin('category AS c', 'c.id', '=', 'p.category_id'); $cat = 'c.name'; }
        }
        $out = [];
        foreach ($q->get(array_filter(['p.product_id', 'p.product_name', 'i.item', $cat ? $cat . ' AS cat' : null])) as $r) $out[(string) $r->product_id] = [trim((string) ($r->item ?: $r->product_name)), (string) ($r->cat ?? '')];
        return $out;
    }

    /**
     * Hub Inward/Outward Transactions (old Communicator page): one line per product, for stock between hubs and stock
     * from the main store. $other: '' all, 'store' main store only, or a hub id. $pending: only what is not received yet.
     */
    public function transactions(string $from, string $to, string $dir, string $other, bool $pending): array
    {
        $lines = [];
        if (HubStock::has('hub_transfers') && $other !== 'store') {
            $q = DB::table('hub_transfer_items AS i')->join('hub_transfers AS t', 't.id', '=', 'i.transfer_id')
                ->where(fn ($w) => $w->where('t.from_hub_id', $this->hubId)->orWhere('t.to_hub_id', $this->hubId))->whereNotIn('t.status', ['planned', 'requested', 'declined']);
            if ($pending) $q->where('t.status', 'in_transit'); else $q->where(fn ($w) => $w->whereBetween('t.sent_at', [$from . ' 00:00:00', $to . ' 23:59:59'])->orWhere('t.status', 'in_transit'));
            if ($dir === 'in') $q->where('t.to_hub_id', $this->hubId); elseif ($dir === 'out') $q->where('t.from_hub_id', $this->hubId);
            if ($other !== '' && ctype_digit($other)) $q->where(fn ($w) => $w->where('t.from_hub_id', (int) $other)->orWhere('t.to_hub_id', (int) $other));
            $rows = $q->orderByDesc('t.sent_at')->limit(2000)->get(['i.id', 'i.product_code', 'i.product_name', 'i.sent_qty', 'i.received_qty', 't.ref', 't.outward_ref', 't.status', 't.from_hub_id', 't.to_hub_id', 't.sent_at', 't.received_at', 't.received_by', 't.sent_by', 't.instruction', 't.receive_note', 't.rider_name']);
            $names = DB::table('hubs')->pluck('hub', 'id');
            foreach ($rows as $r) {
                $lines[] = ['id' => 'T' . $r->id, 'ref' => $r->ref . ($r->outward_ref ? ' / ' . $r->outward_ref : ''), 'dir' => (int) $r->to_hub_id === $this->hubId ? 'in' : 'out',
                    'dispatched_at' => $r->sent_at ? strtotime((string) $r->sent_at) : null, 'received_at' => $r->received_at ? strtotime((string) $r->received_at) : null,
                    'accepted_by' => $r->received_by, 'sent_by' => $r->sent_by, 'rider' => $r->rider_name, 'source' => (string) ($names[$r->from_hub_id] ?? $r->from_hub_id), 'to' => (string) ($names[$r->to_hub_id] ?? $r->to_hub_id),
                    'code' => $r->product_code, 'product' => $r->product_name, 'sent' => $r->sent_qty !== null ? (float) $r->sent_qty : null, 'received' => $r->received_qty !== null ? (float) $r->received_qty : null,
                    'instruction' => trim(($r->instruction ?? '') . ($r->receive_note ? ' | Received: ' . $r->receive_note : ''), ' |'), 'status' => $r->status];
            }
        }
        if ($dir !== 'out' && ($other === '' || $other === 'store')) {
            $me = (string) DB::table('hubs')->where('id', $this->hubId)->value('hub');
            if (!$pending && HubStock::has('hub_stock_moves')) {
                foreach (DB::table('hub_stock_moves')->where('hub_id', $this->hubId)->where('type', 'inward')->whereBetween('created_at', [$from . ' 00:00:00', $to . ' 23:59:59'])->orderByDesc('id')->limit(1000)->get() as $m) {
                    $lines[] = ['id' => 'M' . $m->id, 'ref' => 'Store delivery', 'dir' => 'in', 'dispatched_at' => null, 'received_at' => strtotime((string) $m->created_at) ?: null, 'accepted_by' => $m->by_name, 'sent_by' => null, 'rider' => null,
                        'source' => 'Main store', 'to' => $me, 'code' => $m->product_code, 'product' => null, 'sent' => $m->expected_qty !== null ? (float) $m->expected_qty : null, 'received' => (float) $m->change_qty,
                        'instruction' => (string) ($m->note ?? ''), 'status' => 'received'];
                }
            }
            foreach (DB::table('inventory')->where('hub_id', $this->hubId)->where('stock', '>', 0)->get(['id', 'product_id', 'stock', 'updated_at']) as $p) {
                $lines[] = ['id' => 'P' . $p->id, 'ref' => 'Store delivery', 'dir' => 'in', 'dispatched_at' => $p->updated_at ? strtotime((string) $p->updated_at) : null, 'received_at' => null, 'accepted_by' => null, 'sent_by' => null, 'rider' => null,
                    'source' => 'Main store', 'to' => $me, 'code' => (string) $p->product_id, 'product' => null, 'sent' => (float) $p->stock, 'received' => null, 'instruction' => 'Waiting to be received', 'status' => 'in_transit'];
            }
        }
        $info = self::productInfo(array_column($lines, 'code'));
        foreach ($lines as &$l) { $l['product'] = $l['product'] ?: ($info[$l['code']][0] ?? $l['code']); $l['category'] = $info[$l['code']][1] ?? ''; $l['difference'] = $l['received'] !== null && $l['sent'] !== null ? round($l['received'] - $l['sent'], 3) : null; }
        unset($l);
        usort($lines, fn ($a, $b) => ($b['received_at'] ?? $b['dispatched_at'] ?? PHP_INT_MAX) <=> ($a['received_at'] ?? $a['dispatched_at'] ?? PHP_INT_MAX));
        $hubs = []; foreach ($this->hubs() as $h) $hubs[] = ['id' => $h['id'], 'hub' => $h['hub']];
        return ['ok' => true, 'lines' => $lines, 'from' => $from, 'to' => $to, 'hubs' => $hubs];
    }

    /* ---------------------------------------------------------------- actions */

    private function insertTransfer(array $head, array $prods, array $qty, string $col): int
    {
        $id = (int) DB::table('hub_transfers')->insertGetId($head + ['created_at' => now(), 'updated_at' => now()]);
        foreach ($qty as $pid => $q) {
            $p = $prods[$pid];
            DB::table('hub_transfer_items')->insert(['transfer_id' => $id, 'product_code' => $p['code'], 'product_name' => mb_substr($p['name'], 0, 160), 'pack' => mb_substr($p['pack'], 0, 40), $col => $q]);
        }
        return $id;
    }

    /** Send stock from my hub to another hub now. */
    public function send(int $toHub, array $rawLines, string $reason, string $instruction, ?int $riderId, array $actor, string $outwardRef = ''): array
    {
        $outwardRef = mb_substr(trim(preg_replace('/[^\w\-\/ ]/u', '', $outwardRef)), 0, 60);
        if ($toHub === $this->hubId || !in_array($toHub, array_column($this->hubs(), 'id'), true)) return ['ok' => false, 'message' => 'Choose an active hub to send to.'];
        $qty = self::lines($rawLines);
        if (!$qty) return ['ok' => false, 'message' => 'Add at least one product with a quantity.'];
        $prods = $this->products(array_keys($qty));
        if (count($prods) !== count($qty)) return ['ok' => false, 'message' => 'One of the products was not found.'];
        $reason = array_key_exists($reason, self::REASONS) ? $reason : 'other';
        $rider = $this->rider($riderId);
        if ($riderId && !$rider) return ['ok' => false, 'message' => 'That rider does not belong to this hub.'];

        $res = DB::transaction(function () use ($toHub, $qty, $prods, $reason, $instruction, $rider, $actor, $outwardRef) {
            foreach ($qty as $pid => $q) {
                $live = (float) (DB::table('inventory')->where('product_id', $prods[$pid]['code'])->where('hub_id', $this->hubId)->lockForUpdate()->value('live_stock') ?? 0);
                if ($q > $live + 0.0001) return ['ok' => false, 'message' => $prods[$pid]['name'] . ': only ' . self::q($live) . ' in stock, cannot send ' . self::q($q) . '.'];
            }
            $ref = $this->newRef();
            $name = mb_substr((string) ($actor['name'] ?? ''), 0, 120);
            $head = HubStock::has('hub_transfers', 'outward_ref') ? ['outward_ref' => $outwardRef !== '' ? $outwardRef : null] : [];
            $id = $this->insertTransfer($head + ['ref' => $ref, 'kind' => 'send', 'from_hub_id' => $this->hubId, 'to_hub_id' => $toHub, 'status' => 'in_transit', 'reason' => $reason,
                'instruction' => trim($instruction) !== '' ? mb_substr(trim($instruction), 0, 500) : null, 'rider_id' => $rider->id ?? null, 'rider_name' => $rider->name ?? null,
                'created_by_id' => $actor['id'] ?? null, 'created_by' => $name, 'sent_by_id' => $actor['id'] ?? null, 'sent_by' => $name, 'sent_at' => now()] + $this->meetCols($this->hubId, $toHub), $prods, $qty, 'sent_qty');
            $to = $this->hubName($toHub);
            foreach ($qty as $pid => $q) { [, , $lots] = HubStock::adjust($this->hubId, $prods[$pid]['code'], -$q, 'transfer_out', $ref, 'Sent to ' . $to, $actor); self::saveLots($id, $prods[$pid]['code'], $lots); }
            return ['ok' => true, 'id' => $id, 'ref' => $ref, 'message' => 'Transfer ' . $ref . ' sent to ' . $to . ' (' . count($qty) . ' product(s)). Stock has left this hub.'];
        });
        if ($res['ok']) Log::info('[freshful-audit] hub-transfer-send', ['hub' => $this->hubId, 'to' => $toHub, 'ref' => $res['ref'], 'by' => $actor['id'] ?? null]);
        return $res;
    }

    /** Ask another hub for stock. */
    public function request(int $fromHub, array $rawLines, string $note, array $actor): array
    {
        if ($fromHub === $this->hubId || !in_array($fromHub, array_column($this->hubs(), 'id'), true)) return ['ok' => false, 'message' => 'Choose an active hub to ask.'];
        $qty = self::lines($rawLines);
        if (!$qty) return ['ok' => false, 'message' => 'Add at least one product with a quantity.'];
        $prods = $this->products(array_keys($qty));
        if (count($prods) !== count($qty)) return ['ok' => false, 'message' => 'One of the products was not found.'];
        $ref = $this->newRef();
        DB::transaction(fn () => $this->insertTransfer(['ref' => $ref, 'kind' => 'request', 'from_hub_id' => $fromHub, 'to_hub_id' => $this->hubId, 'status' => 'requested', 'reason' => 'request',
            'instruction' => trim($note) !== '' ? mb_substr(trim($note), 0, 500) : null, 'created_by_id' => $actor['id'] ?? null, 'created_by' => mb_substr((string) ($actor['name'] ?? ''), 0, 120)],
            $prods, $qty, 'requested_qty'));
        return ['ok' => true, 'ref' => $ref, 'message' => 'Request ' . $ref . ' sent to ' . $this->hubName($fromHub) . '. They will see it in their Transfers page.'];
    }

    private function lockOwn(int $id): ?object
    {
        return DB::table('hub_transfers')->where('id', $id)->where(fn ($w) => $w->where('from_hub_id', $this->hubId)->orWhere('to_hub_id', $this->hubId))->lockForUpdate()->first();
    }

    /** Send what another hub asked for. $sentLines: [[code, qty]] - 0 means "not sending this one". */
    public function fulfil(int $id, array $sentLines, ?int $riderId, string $instruction, array $actor, string $outwardRef = ''): array
    {
        $outwardRef = mb_substr(trim(preg_replace('/[^\w\-\/ ]/u', '', $outwardRef)), 0, 60);
        $rider = $this->rider($riderId);
        if ($riderId && !$rider) return ['ok' => false, 'message' => 'That rider does not belong to this hub.'];
        $sent = [];
        foreach (array_slice($sentLines, 0, 200) as $l) { $c = (string) ($l['code'] ?? ''); $q = round((float) ($l['qty'] ?? 0), 3); if ($c !== '' && $q >= 0 && $q <= 100000) $sent[$c] = $q; }
        return DB::transaction(function () use ($id, $sent, $rider, $instruction, $actor, $outwardRef) {
            $t = $this->lockOwn($id);
            if (!$t || (int) $t->from_hub_id !== $this->hubId || !in_array($t->status, ['requested', 'planned'], true)) return ['ok' => false, 'message' => 'This transfer is no longer waiting for you.'];
            $items = DB::table('hub_transfer_items')->where('transfer_id', $id)->get();
            $any = false;
            foreach ($items as $i) {
                $q = $sent[$i->product_code] ?? (float) $i->requested_qty;
                if ($q <= 0) continue;
                $live = (float) (DB::table('inventory')->where('product_id', $i->product_code)->where('hub_id', $this->hubId)->lockForUpdate()->value('live_stock') ?? 0);
                if ($q > $live + 0.0001) return ['ok' => false, 'message' => $i->product_name . ': only ' . self::q($live) . ' in stock.'];
                $any = true;
            }
            if (!$any) return ['ok' => false, 'message' => 'Enter at least one quantity to send, or decline the request.'];
            $to = $this->hubName((int) $t->to_hub_id);
            foreach ($items as $i) {
                $q = $sent[$i->product_code] ?? (float) $i->requested_qty;
                DB::table('hub_transfer_items')->where('id', $i->id)->update(['sent_qty' => max(0, $q)]);
                if ($q > 0) { [, , $lots] = HubStock::adjust($this->hubId, $i->product_code, -$q, 'transfer_out', $t->ref, 'Sent to ' . $to . ($t->status === 'planned' ? ' (11:00 danger stock plan)' : ' (their request)'), $actor); self::saveLots($id, $i->product_code, $lots); }
            }
            $name = mb_substr((string) ($actor['name'] ?? ''), 0, 120);
            $upd = ['status' => 'in_transit', 'sent_by_id' => $actor['id'] ?? null, 'sent_by' => $name, 'sent_at' => now(), 'rider_id' => $rider->id ?? null, 'rider_name' => $rider->name ?? null, 'updated_at' => now()];
            if ($outwardRef !== '' && HubStock::has('hub_transfers', 'outward_ref')) $upd['outward_ref'] = $outwardRef;
            if (empty($t->meet_point)) $upd += $this->meetCols($this->hubId, (int) $t->to_hub_id);
            if (trim($instruction) !== '') $upd['instruction'] = mb_substr(trim(($t->instruction ? $t->instruction . ' | ' : '') . trim($instruction)), 0, 500);
            DB::table('hub_transfers')->where('id', $id)->update($upd);
            return ['ok' => true, 'message' => ($t->status === 'planned' ? 'Danger stock ' : 'Request ') . $t->ref . ' dispatched to ' . $to . '. Stock has left this hub.'];
        });
    }

    public function decline(int $id, string $note, array $actor): array
    {
        if (trim($note) === '') return ['ok' => false, 'message' => 'Write why you cannot send it.'];
        return DB::transaction(function () use ($id, $note) {
            $t = $this->lockOwn($id);
            if (!$t || (int) $t->from_hub_id !== $this->hubId || $t->status !== 'requested') return ['ok' => false, 'message' => 'This request is no longer waiting for you.'];
            DB::table('hub_transfers')->where('id', $id)->update(['status' => 'declined', 'close_note' => mb_substr(trim($note), 0, 500), 'closed_at' => now(), 'updated_at' => now()]);
            return ['ok' => true, 'message' => 'Request ' . $t->ref . ' declined.'];
        });
    }

    /** Sender cancels a transfer still on the road (stock comes back), or the requester withdraws a request. */
    public function cancel(int $id, string $note, array $actor): array
    {
        if (trim($note) === '') return ['ok' => false, 'message' => 'Write the reason for cancelling.'];
        return DB::transaction(function () use ($id, $note, $actor) {
            $t = $this->lockOwn($id);
            if (!$t) return ['ok' => false, 'message' => 'Transfer not found.'];
            $mineOut = (int) $t->from_hub_id === $this->hubId;
            if ($t->status === 'in_transit' && $mineOut) {
                foreach (DB::table('hub_transfer_items')->where('transfer_id', $id)->get() as $i) {
                    if ((float) $i->sent_qty > 0) HubStock::adjust($this->hubId, $i->product_code, (float) $i->sent_qty, 'transfer_back', $t->ref, 'Transfer cancelled: ' . trim($note), $actor, null, self::lotsOf($i->lots ?? null));
                }
            } elseif (!($t->status === 'requested' && !$mineOut) && !($t->status === 'planned' && $mineOut)) {
                return ['ok' => false, 'message' => 'This transfer can no longer be cancelled from your hub.'];
            }
            DB::table('hub_transfers')->where('id', $id)->update(['status' => 'cancelled', 'close_note' => mb_substr(trim($note), 0, 500), 'closed_at' => now(), 'updated_at' => now()]);
            return ['ok' => true, 'message' => 'Transfer ' . $t->ref . ' cancelled' . ($t->status === 'in_transit' ? ' and the stock is back in this hub.' : '.')];
        });
    }

    /** Receiving hub counts what arrived. $lines: [[code, qty]]. */
    public function receive(int $id, array $lines, string $note, array $actor): array
    {
        $got = [];
        foreach (array_slice($lines, 0, 200) as $l) { $c = (string) ($l['code'] ?? ''); $q = (float) ($l['qty'] ?? -1); if ($c !== '' && $q >= 0 && $q <= 100000) $got[$c] = round($q, 3); }
        $res = DB::transaction(function () use ($id, $got, $note, $actor) {
            $t = $this->lockOwn($id);
            if (!$t || (int) $t->to_hub_id !== $this->hubId || $t->status !== 'in_transit') return ['ok' => false, 'message' => 'This transfer is not waiting to be received by your hub.'];
            $items = DB::table('hub_transfer_items')->where('transfer_id', $id)->get();
            $diff = false;
            foreach ($items as $i) {
                if (!array_key_exists($i->product_code, $got)) return ['ok' => false, 'message' => 'Enter the received quantity for every product.'];
                if (abs($got[$i->product_code] - (float) $i->sent_qty) > 0.0001) $diff = true;
            }
            if ($diff && trim($note) === '') return ['ok' => false, 'message' => 'What arrived is different from what was sent. Write a note (damaged, short, extra).'];
            $from = $this->hubName((int) $t->from_hub_id);
            foreach ($items as $i) {
                $q = $got[$i->product_code];
                DB::table('hub_transfer_items')->where('id', $i->id)->update(['received_qty' => $q]);
                if ($q > 0 || (float) $i->sent_qty > 0) {
                    HubStock::adjust($this->hubId, $i->product_code, $q, 'transfer_in', $t->ref, 'Received from ' . $from . (trim($note) !== '' ? ': ' . trim($note) : ''), $actor, (float) $i->sent_qty, self::lotsOf($i->lots ?? null));
                }
            }
            DB::table('hub_transfers')->where('id', $id)->update(['status' => 'received', 'received_by_id' => $actor['id'] ?? null, 'received_by' => mb_substr((string) ($actor['name'] ?? ''), 0, 120),
                'received_at' => now(), 'receive_note' => trim($note) !== '' ? mb_substr(trim($note), 0, 500) : null, 'updated_at' => now()]);
            return ['ok' => true, 'ref' => $t->ref, 'message' => 'Transfer ' . $t->ref . ' received' . ($diff ? ' with a difference (noted).' : '. Everything matched.')];
        });
        if ($res['ok']) Log::info('[freshful-audit] hub-transfer-receive', ['hub' => $this->hubId, 'ref' => $res['ref'], 'by' => $actor['id'] ?? null]);
        return $res;
    }
}
