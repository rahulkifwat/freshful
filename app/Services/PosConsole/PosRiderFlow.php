<?php

namespace App\Services\PosConsole;

use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Hub-side delivery hand-over flow (30 Oct 2026).
 *  assign   - the hub gives an accepted order to one of ITS riders. The order shows in the rider app; status stays "accepted".
 *  handover - the hub sends several of one rider's assigned orders out for delivery in one go (uses the normal dispatch step).
 *  deliverMany - the hub marks orders delivered on the rider's behalf (rider phoned in, no network).
 * Nothing here changes the existing steps; it only calls PosConsoleService::act() which already holds every rule.
 */
class PosRiderFlow
{
    private int $hubId;
    private PosConsoleService $svc;

    public function __construct(int $hubId)
    {
        $this->hubId = $hubId;
        $this->svc = new PosConsoleService($hubId);
    }

    private function event(string $orderId, string $action, array $actor, array $data): void
    {
        try {
            $fn = Closure::bind(function ($o, $a, $ac, $d) { return $this->event($o, $a, $ac, $d); }, $this->svc, PosConsoleService::class);
            $fn($orderId, $action, $actor, $data);
        } catch (\Throwable $e) {
            // The timeline is a convenience; never block the order step for it.
        }
    }

    private function ready(): bool
    {
        return Schema::hasTable('pos_rider_assign');
    }

    public function assign(string $orderId, int $riderId, array $actor): array
    {
        if (!$this->ready()) return ['ok' => false, 'message' => 'Rider hand-over is not set up yet (database table missing).'];
        $rider = collect($this->svc->riders())->firstWhere('id', $riderId);
        if (!$rider) return ['ok' => false, 'message' => 'That rider does not belong to this hub.'];

        $res = DB::transaction(function () use ($orderId, $riderId) {
            $rows = DB::table('orders')->where('order_id', $orderId)->where('hub_id', $this->hubId)->lockForUpdate()->get(['id', 'order_status']);
            if ($rows->isEmpty()) return ['ok' => false, 'message' => 'Order not found in this hub.'];
            if (OrderFlow::stage($rows->first()->order_status) !== 'accepted') {
                return ['ok' => false, 'message' => 'Only accepted orders can be given to a rider before hand-over.'];
            }
            $upd = ['rider_id' => $riderId];
            if (Schema::hasColumn('orders', 'updated_at')) $upd['updated_at'] = now();
            DB::table('orders')->where('order_id', $orderId)->where('hub_id', $this->hubId)->update($upd);
            DB::table('pos_rider_assign')->updateOrInsert(['order_id' => $orderId], [
                'rider_id' => $riderId, 'hub_id' => $this->hubId, 'assigned_at' => now(), 'seen_at' => null, 'accepted_at' => null,
            ]);
            return ['ok' => true];
        });
        if ($res['ok']) {
            $this->event($orderId, 'assign', $actor, ['text' => 'Assigned to ' . $rider['name'], 'rider_id' => $riderId]);
            $res['message'] = 'Order ' . $orderId . ' is now in ' . $rider['name'] . '\'s app. Hand it over when the rider is at the hub.';
        }
        return $res;
    }

    /** order_id => rider / seen / accepted for this hub's accepted orders. */
    public function assignments(): array
    {
        if (!$this->ready()) return [];
        $rows = DB::table('pos_rider_assign as a')->join('orders as o', 'o.order_id', '=', 'a.order_id')
            ->where('o.hub_id', $this->hubId)->whereIn('o.order_status', OrderFlow::statusesFor(['accepted']))
            ->distinct()->get(['a.order_id', 'a.rider_id', 'a.seen_at', 'a.accepted_at']);
        $map = [];
        foreach ($rows as $r) {
            $map[$r->order_id] = ['rider_id' => (int) $r->rider_id, 'seen' => !empty($r->seen_at), 'accepted' => !empty($r->accepted_at)];
        }
        return $map;
    }

    public function handover(int $riderId, array $orderIds, array $actor): array
    {
        $rider = collect($this->svc->riders())->firstWhere('id', $riderId);
        if (!$rider) return ['ok' => false, 'message' => 'That rider does not belong to this hub.'];
        $orderIds = array_values(array_unique(array_filter(array_map('strval', $orderIds))));
        if (!$orderIds) return ['ok' => false, 'message' => 'Tick at least one order.'];

        $done = 0; $failed = [];
        foreach ($orderIds as $id) {
            $cur = (int) DB::table('orders')->where('order_id', $id)->where('hub_id', $this->hubId)->value('rider_id');
            if ($cur !== $riderId) { $failed[] = $id . ' (not assigned to this rider)'; continue; }
            $r = $this->svc->act($id, 'dispatch', null, $riderId, $actor);
            if (!empty($r['ok'])) $done++; else $failed[] = $id . ' (' . ($r['message'] ?? 'failed') . ')';
        }
        $msg = $done . ' order' . ($done === 1 ? ' is' : 's are') . ' out for delivery with ' . $rider['name'] . '.';
        if ($failed) $msg .= ' Not sent: ' . implode('; ', $failed);
        return ['ok' => $done > 0, 'message' => $msg, 'done' => $done, 'failed' => $failed];
    }

    public function deliverMany(array $orderIds, ?string $note, array $actor): array
    {
        $orderIds = array_values(array_unique(array_filter(array_map('strval', $orderIds))));
        if (!$orderIds) return ['ok' => false, 'message' => 'Tick at least one order.'];
        $note = trim((string) $note);
        $done = 0; $failed = [];
        foreach ($orderIds as $id) {
            $r = $this->svc->act($id, 'deliver', null, null, $actor);
            if (!empty($r['ok'])) {
                $done++;
                $this->event($id, 'assist', $actor, ['text' => 'Marked delivered by the hub on the rider\'s behalf' . ($note !== '' ? ': ' . mb_substr($note, 0, 150) : '')]);
            } else {
                $failed[] = $id . ' (' . ($r['message'] ?? 'failed') . ')';
            }
        }
        $msg = $done . ' order' . ($done === 1 ? '' : 's') . ' marked delivered.';
        if ($failed) $msg .= ' Not changed: ' . implode('; ', $failed);
        return ['ok' => $done > 0, 'message' => $msg, 'done' => $done, 'failed' => $failed];
    }
}
