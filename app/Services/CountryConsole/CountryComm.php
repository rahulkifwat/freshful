<?php

namespace App\Services\CountryConsole;

use App\Services\HubConsole\HubConsoleService;
use App\Services\HubConsole\HubPeopleService;
use App\Services\HubConsole\HubStock;
use App\Services\HubConsole\HubTransferService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Communicator, delivery boys and wallet transactions for the country console (22 Oct 2026, update 2).
 * The country manager acts ON BEHALF of a hub through that hub's own services, so every rule, stock move and
 * audit line is exactly what the hub itself would produce; the country manager's name is recorded as the actor.
 */
class CountryComm
{
    private static function hubOk(int $h): bool { return CountryScope::hub($h) !== null; }
    private static function no(string $m): array { return ['ok' => false, 'message' => $m]; }
    private static function tag(array $actor): array { return ['id' => $actor['id'] ?? null, 'name' => mb_substr(($actor['name'] ?? 'Country manager') . ' (country)', 0, 120), 'guard' => $actor['guard'] ?? null]; }

    /* ------------------------------------------------------------------ communicator */

    /** Accept stock sent to a hub (pending inward), as the hub would. */
    public static function acceptInward(int $hubId, int $invId, float $received, string $note, array $actor): array
    {
        if (!self::hubOk($hubId)) return self::no('Hub not found.');
        $res = (new HubConsoleService($hubId))->acceptInward($invId, round($received, 3), trim(strip_tags($note)), self::tag($actor));
        if ($res['ok']) { $res['message'] = CountryScope::hub($hubId)['name'] . ': ' . $res['message']; Log::info('[freshful-audit] country-accept-inward', ['hub' => $hubId, 'inv' => $invId, 'received' => $received, 'by' => $actor['id'] ?? null]); }
        return $res;
    }

    /** Send stock now from one hub to another (old "InterHub orders"): stock leaves the sending hub immediately. */
    public static function send(int $fromHub, int $toHub, array $lines, string $reason, string $instruction, ?int $riderId, string $challan, array $actor): array
    {
        if (!HubStock::has('hub_transfers')) return self::no('Transfers are not installed.');
        if (!self::hubOk($fromHub) || !self::hubOk($toHub)) return self::no('Choose both hubs.');
        if ($fromHub === $toHub) return self::no('Choose two different hubs.');
        $res = (new HubTransferService($fromHub))->send($toHub, $lines, $reason, trim(strip_tags($instruction)), $riderId ?: null, self::tag($actor), $challan);
        if ($res['ok']) Log::info('[freshful-audit] country-transfer-send', ['from' => $fromHub, 'to' => $toHub, 'ref' => $res['ref'] ?? null, 'by' => $actor['id'] ?? null]);
        return $res;
    }

    /** A transfer the person may act on: at least one of its two hubs is theirs. */
    public static function transfer(int $id): ?object
    {
        $t = HubStock::has('hub_transfers') ? DB::table('hub_transfers')->where('id', $id)->first() : null;
        if ($t && !self::hubOk((int) $t->from_hub_id) && !self::hubOk((int) $t->to_hub_id)) return null;
        return $t;
    }

    private static function riderHub(int $id): ?int
    {
        $h = DB::table('delivery_boy')->where('id', $id)->value('hub_id');
        return $h !== null && self::hubOk((int) $h) ? (int) $h : null;
    }

    /** Dispatch a requested or planned transfer from the sending hub (with its rider). */
    public static function dispatch(int $id, array $lines, ?int $riderId, string $instruction, string $challan, array $actor): array
    {
        $t = self::transfer($id);
        if (!$t) return self::no('Transfer not found.');
        $res = (new HubTransferService((int) $t->from_hub_id))->fulfil($id, $lines, $riderId ?: null, trim(strip_tags($instruction)), self::tag($actor), $challan);
        if ($res['ok']) Log::info('[freshful-audit] country-transfer-dispatch', ['id' => $id, 'by' => $actor['id'] ?? null]);
        return $res;
    }

    /** Receive a transfer at the receiving hub (count what arrived). */
    public static function receive(int $id, array $lines, string $note, array $actor): array
    {
        $t = self::transfer($id);
        if (!$t) return self::no('Transfer not found.');
        $res = (new HubTransferService((int) $t->to_hub_id))->receive($id, $lines, trim(strip_tags($note)), self::tag($actor));
        if ($res['ok']) Log::info('[freshful-audit] country-transfer-receive', ['id' => $id, 'by' => $actor['id'] ?? null]);
        return $res;
    }

    /** Decline a request for the sending hub. */
    public static function decline(int $id, string $note, array $actor): array
    {
        $t = self::transfer($id);
        if (!$t) return self::no('Transfer not found.');
        return (new HubTransferService((int) $t->from_hub_id))->decline($id, trim(strip_tags($note)) . (trim($note) !== '' ? ' (country manager)' : ''), self::tag($actor));
    }

    /** Riders of a hub (for the send / dispatch forms). */
    public static function hubRiders(int $hubId): array
    {
        if (!self::hubOk($hubId)) return self::no('Hub not found.');
        $rows = DB::table('delivery_boy')->where('hub_id', $hubId)->whereRaw("LOWER(CAST(status AS CHAR)) IN ('active','1')")->orderBy('name')->get(['id', 'name', 'phone']);
        return ['ok' => true, 'rows' => $rows->map(fn ($r) => ['id' => (int) $r->id, 'name' => (string) $r->name, 'phone' => (string) $r->phone])->all()];
    }

    /* ------------------------------------------------------------------ delivery boys */

    public static function riderSave(int $hubId, ?int $id, array $d, array $actor): array
    {
        if ($id) {
            $cur = self::riderHub($id);
            if ($cur === null) return self::no('Rider not found.');
            $hubId = (int) $cur;   // editing keeps the rider in their hub; moving hub is done with "Move"
        } elseif (!self::hubOk($hubId)) return self::no('Choose the hub.');
        $res = (new HubPeopleService($hubId))->saveRider($id, $d, self::tag($actor));
        if ($res['ok']) Log::info('[freshful-audit] country-rider-save', ['hub' => $hubId, 'rider' => $id, 'by' => $actor['id'] ?? null]);
        return $res;
    }

    public static function riderToggle(int $id, array $actor): array
    {
        $h = self::riderHub($id);
        if ($h === null) return self::no('Rider not found.');
        return (new HubPeopleService($h))->toggleRider($id, self::tag($actor));
    }

    /** Move a rider to another hub (only when they have no order on the road). */
    public static function riderMove(int $id, int $toHub, array $actor): array
    {
        $r = self::riderHub($id) !== null ? DB::table('delivery_boy')->where('id', $id)->first() : null;
        if (!$r) return self::no('Rider not found.');
        if (!self::hubOk($toHub)) return self::no('Choose the hub.');
        if ((int) $r->hub_id === $toHub) return self::no('The rider is already in that hub.');
        $busy = CountryScope::has('orders', 'rider_id') && DB::table('orders')->where('rider_id', $id)->whereIn('order_status', \App\Services\PosConsole\OrderFlow::statusesFor(['out']))->exists();
        if ($busy) return self::no($r->name . ' still has orders out for delivery. Finish them first.');
        DB::table('delivery_boy')->where('id', $id)->update(['hub_id' => $toHub]);
        if (HubStock::has('pos_rider_status')) DB::table('pos_rider_status')->where('rider_id', $id)->delete();
        Log::info('[freshful-audit] country-rider-move', ['rider' => $id, 'from' => (int) $r->hub_id, 'to' => $toHub, 'by' => $actor['id'] ?? null]);
        return ['ok' => true, 'message' => $r->name . ' moved to ' . CountryScope::hub($toHub)['name'] . '.'];
    }

    /** Full rider list with the fields the edit form needs (never the password). */
    public static function riders(CountryScope $scope): array
    {
        $base = CountryOps::riders($scope);
        $ids = array_column($base['rows'], 'id');
        $extra = [];
        $cols = array_values(array_filter(['id', 'employee_id', 'gender', 'date_added', 'regid'], fn ($c) => CountryScope::has('delivery_boy', $c)));
        foreach (array_chunk($ids, 1000) as $c) foreach (DB::table('delivery_boy')->whereIn('id', $c)->get($cols) as $r) $extra[(int) $r->id] = $r;
        foreach ($base['rows'] as &$r) {
            $e = $extra[$r['id']] ?? null;
            $r['employee_id'] = (string) ($e->employee_id ?? ''); $r['gender'] = (string) ($e->gender ?? '');
            $r['joined'] = isset($e->date_added) ? CountryScope::ts($e->date_added) : null; $r['app'] = !empty($e->regid ?? null);
        }
        unset($r);
        return $base;
    }

    /* ------------------------------------------------------------------ wallet transactions */

    public static function wallets(CountryScope $scope, string $from, string $to, string $q): array
    {
        if (!HubStock::has('wallet_transaction_history')) return ['ok' => true, 'available' => false, 'rows' => []];
        $hubs = CountryScope::hubs(); $rows = []; $sum = ['wallet' => 0.0, 'credit' => 0.0];
        foreach ($scope->ids() as $h) {
            try { $w = (new HubPeopleService($h))->wallets($from, $to, $q); } catch (\Throwable $e) { continue; }
            foreach ($w['rows'] ?? [] as $r) { $rows[] = $r + ['hub' => $hubs[$h]['name'] ?? '', 'city' => $hubs[$h]['city'] ?? '']; $sum['wallet'] += $r['wallet_amount']; $sum['credit'] += $r['credit_amount']; }
        }
        usort($rows, fn ($a, $b) => ($b['at'] ?? 0) <=> ($a['at'] ?? 0));
        // Ledger of the newer wallet module (top-ups, referral rewards), if present.
        $ledger = [];
        if (HubStock::has('buyer_wallet_ledger') && CountryScope::$allowed === null) {   // the wallet ledger has no hub, so only for All India
            $lq = DB::table('buyer_wallet_ledger AS l')->leftJoin('buyers AS b', 'b.id', '=', 'l.buyer_id')->whereBetween('l.created_at', [$from . ' 00:00:00', $to . ' 23:59:59'])->orderByDesc('l.id')->limit(1000);
            foreach ($lq->get(['l.id', 'l.buyer_id', 'l.event_type', 'l.amount', 'l.balance_after', 'l.description', 'l.reference', 'l.created_at', 'b.name', 'b.phone']) as $l) {
                if (trim($q) !== '' && !str_contains(mb_strtolower($l->name . ' ' . $l->phone . ' ' . $l->reference), mb_strtolower(trim($q)))) continue;
                $ledger[] = ['id' => (int) $l->id, 'buyer_id' => (int) $l->buyer_id, 'customer' => trim((string) $l->name) ?: (string) $l->phone, 'phone' => (string) $l->phone, 'type' => (string) $l->event_type,
                    'amount' => (float) $l->amount, 'balance' => (float) $l->balance_after, 'note' => (string) $l->description, 'ref' => (string) $l->reference, 'at' => CountryScope::ts($l->created_at)];
            }
        }
        return ['ok' => true, 'available' => true, 'rows' => array_slice($rows, 0, 3000), 'ledger' => $ledger, 'summary' => array_map(fn ($v) => round($v, 2), $sum)];
    }
}
