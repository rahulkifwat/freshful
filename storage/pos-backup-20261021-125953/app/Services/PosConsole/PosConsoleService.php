<?php

namespace App\Services\PosConsole;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Freshful POS console - everything the console reads or changes, always limited to ONE hub.
 * Orders table has one row per product line; every row of an order shares order_id and status.
 */
class PosConsoleService
{
    private static array $cols = [];

    public function __construct(private int $hubId)
    {
    }

    public function hubId(): int
    {
        return $this->hubId;
    }

    private static function has(string $table, string $col): bool
    {
        $k = $table . '.' . $col;
        if (!array_key_exists($k, self::$cols)) {
            try {
                self::$cols[$k] = Schema::hasColumn($table, $col);
            } catch (\Throwable $e) {
                self::$cols[$k] = false;
            }
        }
        return self::$cols[$k];
    }

    public function hub(): ?object
    {
        return DB::table('hubs')->leftJoin('cities', 'cities.id', '=', 'hubs.city_id')
            ->select('hubs.id', 'hubs.hub', 'hubs.status', 'cities.city')
            ->where('hubs.id', $this->hubId)->first();
    }

    /** One summary row per order for this hub. */
    private function orderSummaryQuery()
    {
        $sel = [
            'o.order_id',
            DB::raw('MIN(o.id) AS first_id'),
            DB::raw('MAX(o.buyer_id) AS buyer_id'),
            DB::raw('MAX(o.address_id) AS address_id'),
            DB::raw('MAX(o.order_status) AS order_status'),
            DB::raw('MAX(o.payment_type) AS payment_type'),
            DB::raw('MAX(o.payment_status) AS payment_status'),
            DB::raw('MAX(o.delivery_type) AS delivery_type'),
            DB::raw('MAX(o.schedule_date) AS schedule_date'),
            DB::raw('MAX(o.schedule_time) AS schedule_time'),
            DB::raw('MAX(o.date_added) AS date_added'),
            DB::raw('MAX(COALESCE(o.updated_at, o.date_added)) AS changed_at'),
            DB::raw('MAX(o.total_amount) AS total_amount'),
            DB::raw('MAX(o.delivery_charge) AS delivery_charge'),
            DB::raw('MAX(o.rider_id) AS rider_id'),
            DB::raw('COUNT(*) AS line_count'),
        ];
        return DB::table('orders AS o')->select($sel)->where('o.hub_id', $this->hubId)->groupBy('o.order_id');
    }

    /** Everything the live board needs, in one call. */
    public function feed(): array
    {
        $today = date('Y-m-d');
        $active = OrderFlow::statusesFor(['new', 'accepted', 'packing', 'out']);
        $done = OrderFlow::statusesFor(['delivered', 'cancelled']);

        $activeRows = (clone $this->orderSummaryQuery())->whereIn('o.order_status', $active)
            ->orderByRaw('MIN(o.id) DESC')->limit(300)->get();
        $todayDone = (clone $this->orderSummaryQuery())->whereIn('o.order_status', $done)
            ->where(function ($q) use ($today) {
                $q->whereDate('o.date_added', $today)->orWhereDate('o.updated_at', $today);
            })->orderByRaw('MAX(COALESCE(o.updated_at, o.date_added)) DESC')->limit(200)->get();
        $unsettled = (clone $this->orderSummaryQuery())->whereIn('o.order_status', OrderFlow::statusesFor(['delivered']))
            ->where(function ($q) {
                $q->whereNull('o.payment_status')->orWhere('o.payment_status', '<>', 'Paid');
            })->orderByRaw('MIN(o.id) DESC')->limit(100)->get();

        $byId = [];
        foreach ([$activeRows, $todayDone, $unsettled] as $set) {
            foreach ($set as $r) {
                $byId[$r->order_id] = $r;
            }
        }
        $orders = $this->decorate(array_values($byId));

        $now = time();
        $counts = array_fill_keys(array_merge(OrderFlow::STAGES, ['late', 'unsettled', 'scheduled_later', 'stale']), 0);
        $sig = [];
        foreach ($orders as $o) {
            $counts[$o['stage']] = ($counts[$o['stage']] ?? 0) + 1;
            if ($o['late']) $counts['late']++;
            if ($o['unsettled']) $counts['unsettled']++;
            if ($o['later']) $counts['scheduled_later']++;
            if ($o['stale']) $counts['stale']++;
            $sig[] = $o['order_id'] . ':' . $o['status'] . ':' . $o['payment_status'] . ':' . $o['rider_id'];
        }
        sort($sig);

        return [
            'ok'          => true,
            'server_time' => $now,
            'version'     => md5(implode('|', $sig)),
            'counts'      => $counts,
            'kpis'        => $this->kpis(),
            'orders'      => $orders,
        ];
    }

    /** Add customer, area, money, deadline and allowed actions to summary rows. */
    private function decorate(array $rows): array
    {
        if (!$rows) return [];
        $buyerIds = array_values(array_unique(array_filter(array_map(fn ($r) => (int) $r->buyer_id, $rows))));
        $addrIds = array_values(array_unique(array_filter(array_map(fn ($r) => (int) $r->address_id, $rows))));
        $riderIds = array_values(array_unique(array_filter(array_map(fn ($r) => (int) $r->rider_id, $rows))));

        $buyers = $buyerIds ? DB::table('buyers')->whereIn('id', $buyerIds)->get(['id', 'name', 'phone'])->keyBy('id') : collect();
        $addrCols = array_values(array_filter(['id', 'name', 'phone', 'locality', 'city'], fn ($c) => self::has('buyer_address', $c)));
        $addrs = $addrIds ? DB::table('buyer_address')->whereIn('id', $addrIds)->get($addrCols)->keyBy('id') : collect();
        $riders = $riderIds ? DB::table('delivery_boy')->whereIn('id', $riderIds)->get(['id', 'name', 'phone'])->keyBy('id') : collect();

        $now = time();
        $endOfToday = strtotime('tomorrow') - 1;
        $out = [];
        foreach ($rows as $r) {
            $b = $buyers[(int) $r->buyer_id] ?? null;
            $a = $addrs[(int) $r->address_id] ?? null;
            $stage = OrderFlow::stage($r->order_status);
            $due = OrderFlow::dueAt($r->delivery_type, $r->date_added, $r->schedule_date, $r->schedule_time);
            $activeStage = in_array($stage, ['new', 'accepted', 'packing', 'out'], true);
            $paid = strtolower(trim((string) $r->payment_status)) === 'paid';
            $amount = round((float) $r->total_amount + (float) $r->delivery_charge, 2);
            $rider = $riders[(int) $r->rider_id] ?? null;
            $placedTs = $r->date_added ? (strtotime($r->date_added) ?: null) : null;
            $stale = OrderFlow::isStale($stage, $due, $placedTs, $now);
            $out[] = [
                'order_id'       => (string) $r->order_id,
                'status'         => (string) $r->order_status,
                'stage'          => $stage,
                'actions'        => OrderFlow::actionsFor($r->order_status),
                'can_settle'     => OrderFlow::canSettle($r->order_status, $r->payment_status),
                'customer'       => self::customerName($b, $a, (int) $r->buyer_id),
                'phone'          => (string) ($a->phone ?? $b->phone ?? ''),
                'area'           => trim(implode(', ', array_filter([$a->locality ?? null, $a->city ?? null]))),
                'items'          => (int) $r->line_count,
                'amount'         => $amount,
                'payment_type'   => (string) ($r->payment_type ?: 'Cash'),
                'payment_status' => (string) ($r->payment_status ?: 'Pending'),
                'paid'           => $paid,
                'unsettled'      => $stage === 'delivered' && !$paid,
                'delivery_type'  => (string) ($r->delivery_type ?: 'Express'),
                'slot'           => trim((string) $r->schedule_time),
                'schedule_date'  => (string) (OrderFlow::normDate($r->schedule_date) ?? ''),
                'placed_at'      => $r->date_added ? strtotime($r->date_added) : null,
                'changed_at'     => $r->changed_at ? strtotime($r->changed_at) : null,
                'due_at'         => $due,
                'late'           => $activeStage && !$stale && $due !== null && $due < $now,
                'later'          => $activeStage && !$stale && $due !== null && $due > $endOfToday,
                'stale'          => $stale,
                'rider_id'       => (int) $r->rider_id ?: null,
                'rider'          => $rider->name ?? null,
            ];
        }
        return $out;
    }

    /** Best available name: account name, then address name, then phone, then the customer number. */
    private static function customerName(?object $b, ?object $a, int $buyerId): string
    {
        foreach ([$b->name ?? null, $a->name ?? null] as $n) {
            $n = trim((string) $n);
            if ($n !== '' && strcasecmp($n, 'null') !== 0) return $n;
        }
        $p = trim((string) ($a->phone ?? $b->phone ?? ''));
        if ($p !== '') return 'Customer ' . $p;
        return $buyerId ? 'Customer #' . $buyerId : 'Walk-in customer';
    }

    /** Cancel every "old open" order of this hub in one go (each one re-checked inside the transaction). */
    public function closeStale(string $reason, array $actor): array
    {
        $reason = mb_substr(trim($reason) !== '' ? trim($reason) : 'Old open order closed in cleanup', 0, 250);
        $active = OrderFlow::statusesFor(['new', 'accepted', 'packing', 'out']);
        $rows = (clone $this->orderSummaryQuery())->whereIn('o.order_status', $active)
            ->where('o.date_added', '<', date('Y-m-d H:i:s', time() - OrderFlow::STALE_AFTER))->limit(500)->get();
        $ids = array_column(array_filter($this->decorate($rows->all()), fn ($o) => $o['stale']), 'order_id');
        if (!$ids) return ['ok' => true, 'closed' => 0, 'message' => 'There are no old open orders.'];

        $closed = DB::transaction(function () use ($ids, $active, $reason) {
            $upd = ['order_status' => OrderFlow::CANCEL];
            if (self::has('orders', 'updated_at')) $upd['updated_at'] = now();
            if (self::has('orders', 'reason_for_order_cancel')) $upd['reason_for_order_cancel'] = $reason;
            $n = 0;
            foreach (array_chunk($ids, 100) as $chunk) {
                $n += DB::table('orders')->where('hub_id', $this->hubId)->whereIn('order_id', $chunk)
                    ->whereIn('order_status', $active)->update($upd);
            }
            return $n;
        });
        $this->audit('pos-close-stale', implode(',', $ids), $actor, ['ok' => true, 'orders' => count($ids), 'rows' => $closed, 'reason' => $reason]);
        return ['ok' => true, 'closed' => count($ids), 'message' => count($ids) . ' old open order(s) closed as cancelled.'];
    }

    /** Today's numbers for this hub. */
    public function kpis(): array
    {
        $today = date('Y-m-d');
        $rows = (clone $this->orderSummaryQuery())->whereDate('o.date_added', $today)->get();
        $orders = 0; $sales = 0.0; $cancelled = 0;
        foreach ($rows as $r) {
            $orders++;
            if (OrderFlow::stage($r->order_status) === 'cancelled') { $cancelled++; continue; }
            $sales += (float) $r->total_amount + (float) $r->delivery_charge;
        }

        $delivered = 0; $packMins = []; $deliverMins = [];
        if (self::has('orders', 'delivery_complete_time')) {
            $d = DB::table('orders')->where('hub_id', $this->hubId)->whereDate('delivery_complete_time', $today)
                ->select('order_id', DB::raw('MAX(date_added) AS placed'), DB::raw('MAX(delivery_complete_time) AS done'))
                ->groupBy('order_id')->get();
            $delivered = $d->count();
            foreach ($d as $x) {
                if ($x->placed && $x->done) $deliverMins[] = max(0, (strtotime($x->done) - strtotime($x->placed)) / 60);
            }
        }
        if (self::has('orders', 'order_complete_time') && self::has('orders', 'shipping_complete_time')) {
            $p = DB::table('orders')->where('hub_id', $this->hubId)->whereDate('shipping_complete_time', $today)
                ->select('order_id', DB::raw('MAX(order_complete_time) AS acc'), DB::raw('MAX(shipping_complete_time) AS out_at'))
                ->groupBy('order_id')->get();
            foreach ($p as $x) {
                if ($x->acc && $x->out_at) $packMins[] = max(0, (strtotime($x->out_at) - strtotime($x->acc)) / 60);
            }
        }

        $cashDue = 0.0;
        $un = (clone $this->orderSummaryQuery())->whereIn('o.order_status', OrderFlow::statusesFor(['delivered']))
            ->where(function ($q) {
                $q->whereNull('o.payment_status')->orWhere('o.payment_status', '<>', 'Paid');
            })->get();
        foreach ($un as $r) {
            $cashDue += (float) $r->total_amount + (float) $r->delivery_charge;
        }

        return [
            'orders_today'      => $orders,
            'sales_today'       => round($sales, 2),
            'delivered_today'   => $delivered,
            'cancelled_today'   => $cancelled,
            'avg_pack_min'      => $packMins ? (int) round(array_sum($packMins) / count($packMins)) : null,
            'avg_delivery_min'  => $deliverMins ? (int) round(array_sum($deliverMins) / count($deliverMins)) : null,
            'unsettled_amount'  => round($cashDue, 2),
            'unsettled_count'   => $un->count(),
        ];
    }

    /** Full detail of one order of this hub, or null. */
    public function detail(string $orderId): ?array
    {
        $lines = DB::table('orders AS o')
            ->leftJoin('products AS p', 'p.id', '=', 'o.product_id')
            ->leftJoin('items AS i', 'i.id', '=', 'p.item_id')
            ->where('o.order_id', $orderId)->where('o.hub_id', $this->hubId)
            ->select('o.*', 'p.product_name', 'p.product_unit', 'p.unit_quantity', 'p.main_price', 'p.MRP', 'p.product_image', 'p.product_id AS sku', 'i.item AS item_name')
            ->orderBy('o.id')->get();
        if ($lines->isEmpty()) return null;

        $f = $lines->first();
        $summary = $this->decorate([(object) [
            'order_id' => $f->order_id, 'buyer_id' => $f->buyer_id, 'address_id' => $f->address_id,
            'order_status' => $f->order_status, 'payment_type' => $f->payment_type, 'payment_status' => $f->payment_status,
            'delivery_type' => $f->delivery_type, 'schedule_date' => $f->schedule_date, 'schedule_time' => $f->schedule_time,
            'date_added' => $f->date_added, 'changed_at' => $f->updated_at ?? $f->date_added, 'total_amount' => $f->total_amount,
            'delivery_charge' => $f->delivery_charge, 'rider_id' => $f->rider_id ?? null, 'line_count' => $lines->count(),
        ]])[0];

        $items = [];
        $itemsValue = 0.0;
        foreach ($lines as $l) {
            $qty = (float) $l->quantity;
            $price = (float) $l->main_price;
            $itemsValue += $qty * $price;
            $items[] = [
                'name'  => trim((string) ($l->item_name ?: $l->product_name ?: ('Product #' . $l->product_id))),
                'sku'   => (string) ($l->sku ?? ''),
                'unit'  => trim(((float) $l->unit_quantity ? rtrim(rtrim(number_format((float) $l->unit_quantity, 3, '.', ''), '0'), '.') . ' ' : '') . (string) $l->product_unit),
                'qty'   => $qty,
                'price' => $price,
                'mrp'   => (float) $l->MRP,
                'line'  => round($qty * $price, 2),
                'image' => $l->product_image ? url('uploads/images/products/' . $l->product_image) : null,
            ];
        }

        $buyer = DB::table('buyers')->where('id', $f->buyer_id)->first();
        $addr = DB::table('buyer_address')->where('id', $f->address_id)->first();
        $addressText = $addr ? trim(implode(', ', array_filter([
            $addr->street_name ?? null, $addr->locality ?? null, $addr->landmark ?? null, $addr->city ?? null,
        ]))) : '';
        $lat = $addr->latitude ?? null;
        $lng = $addr->longitude ?? null;
        $map = ($lat && $lng) ? 'https://www.google.com/maps?q=' . $lat . ',' . $lng
            : ($addressText !== '' ? 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode($addressText) : null);

        $promo = null;
        try {
            $promo = DB::table('home_promotions')
                ->join('buyer_coupon_relation', 'buyer_coupon_relation.promo_id', '=', 'home_promotions.id')
                ->select('home_promotions.title', 'home_promotions.percentage', 'buyer_coupon_relation.discount')
                ->where('buyer_coupon_relation.user_id', $f->buyer_id)
                ->where('buyer_coupon_relation.order_id', $orderId)->first();
        } catch (\Throwable $e) {
            $promo = null;
        }

        $timeline = [['label' => 'Order placed', 'at' => $f->date_added]];
        foreach (['order_complete_time' => 'Accepted', 'processing_complete_time' => 'Packing started',
                  'shipping_complete_time' => 'Out for delivery', 'delivery_complete_time' => 'Delivered'] as $col => $label) {
            if (!empty($f->$col)) $timeline[] = ['label' => $label, 'at' => $f->$col];
        }
        if (OrderFlow::stage($f->order_status) === 'cancelled') {
            $timeline[] = ['label' => 'Cancelled' . (!empty($f->reason_for_order_cancel) ? ': ' . $f->reason_for_order_cancel : ''), 'at' => $f->updated_at ?? null];
        }

        $riders = DB::table('delivery_boy')->where('hub_id', $this->hubId)
            ->where(function ($q) { $q->whereNull('status')->orWhere('status', 'active'); })
            ->orderBy('name')->get(['id', 'name', 'phone']);

        return $summary + [
            'items'          => $items,
            'items_value'    => round($itemsValue, 2),
            'total_amount'   => (float) $f->total_amount,
            'delivery_charge'=> (float) $f->delivery_charge,
            'discount'       => $promo ? round((float) $promo->discount, 2) : 0,
            'promo'          => $promo->title ?? null,
            'buyer_email'    => $buyer->email ?? null,
            'address'        => $addressText,
            'address_name'   => $addr->name ?? null,
            'address_type'   => $addr->type ?? null,
            'map'            => $map,
            'cancel_reason'  => $f->reason_for_order_cancel ?? null,
            'paid_ref'       => $f->payment_id ?? null,
            'collected'      => $f->amount_to_collect ?? null,
            'change'         => $f->amount_to_return ?? null,
            'timeline'       => $timeline,
            'riders'         => $riders->map(fn ($r) => ['id' => $r->id, 'name' => $r->name, 'phone' => $r->phone])->all(),
        ];
    }

    /** Move an order to its next step. Returns ['ok'=>bool, 'message'=>...]. */
    public function act(string $orderId, string $action, ?string $reason, ?int $riderId, array $actor): array
    {
        if (in_array($action, ['reject', 'cancel'], true) && trim((string) $reason) === '') {
            return ['ok' => false, 'message' => 'Please choose a reason.'];
        }
        if ($action === 'dispatch' && $riderId) {
            $okRider = DB::table('delivery_boy')->where('id', $riderId)->where('hub_id', $this->hubId)->exists();
            if (!$okRider) return ['ok' => false, 'message' => 'That rider does not belong to this hub.'];
        }

        $result = DB::transaction(function () use ($orderId, $action, $reason, $riderId) {
            $rows = DB::table('orders')->where('order_id', $orderId)->where('hub_id', $this->hubId)->lockForUpdate()->get(['id', 'order_status']);
            if ($rows->isEmpty()) return ['ok' => false, 'message' => 'Order not found in this hub.'];
            $current = (string) $rows->first()->order_status;
            $move = OrderFlow::move($current, $action);
            if (!$move['ok']) return $move + ['from' => $current];

            $now = now();
            $upd = ['order_status' => $move['to']];
            if (self::has('orders', 'updated_at')) $upd['updated_at'] = $now;
            if ($move['stamp'] && self::has('orders', $move['stamp'])) $upd[$move['stamp']] = $now;
            if (in_array($action, ['reject', 'cancel'], true) && self::has('orders', 'reason_for_order_cancel')) {
                $upd['reason_for_order_cancel'] = mb_substr(trim((string) $reason), 0, 250);
            }
            if ($action === 'dispatch' && $riderId && self::has('orders', 'rider_id')) $upd['rider_id'] = $riderId;

            DB::table('orders')->where('order_id', $orderId)->where('hub_id', $this->hubId)->update($upd);
            return ['ok' => true, 'from' => $current, 'to' => $move['to']];
        });

        $this->audit('pos-' . $action, $orderId, $actor, $result + ['reason' => $reason, 'rider_id' => $riderId]);

        if ($result['ok'] && $action === 'deliver') {
            try {
                if (class_exists(\App\Services\BuyerReferralService::class)) {
                    app(\App\Services\BuyerReferralService::class)->awardForDeliveredOrder($orderId);
                }
            } catch (\Throwable $e) {
                report($e);
            }
        }
        if ($result['ok']) {
            $result['message'] = 'Order ' . $orderId . ': ' . OrderFlow::STAGE_LABEL[OrderFlow::stage($result['to'])];
        }
        return $result;
    }

    /** Mark an order paid. Cash needs the amount received (change is worked out); UPI/Card need a reference. */
    public function settle(string $orderId, string $method, ?float $received, ?string $ref, array $actor): array
    {
        $method = in_array($method, ['Cash', 'UPI', 'Card', 'Online'], true) ? $method : '';
        if ($method === '') return ['ok' => false, 'message' => 'Choose how the customer paid.'];

        $result = DB::transaction(function () use ($orderId, $method, $received, $ref) {
            $rows = DB::table('orders')->where('order_id', $orderId)->where('hub_id', $this->hubId)->lockForUpdate()
                ->get(['id', 'order_status', 'payment_status', 'total_amount', 'delivery_charge']);
            if ($rows->isEmpty()) return ['ok' => false, 'message' => 'Order not found in this hub.'];
            $f = $rows->first();
            if (!OrderFlow::canSettle($f->order_status, $f->payment_status)) {
                return ['ok' => false, 'message' => 'This order cannot be settled now (not out for delivery / delivered, or already paid).'];
            }
            $due = round((float) $f->total_amount + (float) $f->delivery_charge, 2);
            $change = 0.0;
            if ($method === 'Cash') {
                if ($received === null || $received < $due) {
                    return ['ok' => false, 'message' => 'Cash received must be at least ₹' . number_format($due, 2) . '.'];
                }
                $change = round($received - $due, 2);
            } elseif (trim((string) $ref) === '') {
                return ['ok' => false, 'message' => 'Enter the ' . $method . ' reference number.'];
            }

            $upd = ['payment_status' => 'Paid'];
            if (self::has('orders', 'amount_to_collect')) $upd['amount_to_collect'] = $method === 'Cash' ? $received : $due;
            if (self::has('orders', 'amount_to_return')) $upd['amount_to_return'] = $change;
            if (self::has('orders', 'payment_id')) $upd['payment_id'] = mb_substr($method . ($ref ? ':' . trim($ref) : ''), 0, 190);
            if (self::has('orders', 'updated_at')) $upd['updated_at'] = now();
            DB::table('orders')->where('order_id', $orderId)->where('hub_id', $this->hubId)->update($upd);

            return ['ok' => true, 'due' => $due, 'change' => $change,
                'message' => 'Payment settled' . ($change > 0 ? ' - give change ₹' . number_format($change, 2) : '') . '.'];
        });

        $this->audit('pos-settle', $orderId, $actor, $result + ['method' => $method, 'received' => $received, 'ref' => $ref]);
        return $result;
    }

    /** Find orders of this hub by order number or phone. */
    public function search(string $q): array
    {
        $q = trim($q);
        if (mb_strlen($q) < 3) return [];
        $digits = preg_replace('/\D/', '', $q);
        $query = (clone $this->orderSummaryQuery())->orderByRaw('MIN(o.id) DESC')->limit(25);
        $addrIds = [];
        if (strlen($digits) >= 5) {
            $addrIds = DB::table('buyer_address')->where('phone', 'like', '%' . $digits . '%')->limit(200)->pluck('id')->all();
        }
        $query->where(function ($w) use ($q, $addrIds) {
            $w->where('o.order_id', 'like', '%' . $q . '%');
            if ($addrIds) $w->orWhereIn('o.address_id', $addrIds);
        });
        return $this->decorate($query->get()->all());
    }

    /** Day-end report for this hub. */
    public function dayReport(string $date): array
    {
        $rows = (clone $this->orderSummaryQuery())->whereDate('o.date_added', $date)->get();
        $types = ['Express' => [], 'Scheduled' => []];
        $tot = ['received' => 0, 'delivered' => 0, 'cancelled' => 0, 'open' => 0, 'value' => 0.0, 'delivered_value' => 0.0];
        $pay = [];
        foreach ($rows as $r) {
            $t = strtolower((string) $r->delivery_type) === 'scheduled' ? 'Scheduled' : 'Express';
            $stage = OrderFlow::stage($r->order_status);
            $amt = (float) $r->total_amount + (float) $r->delivery_charge;
            $types[$t]['received'] = ($types[$t]['received'] ?? 0) + 1;
            $tot['received']++;
            if ($stage === 'cancelled') {
                $types[$t]['cancelled'] = ($types[$t]['cancelled'] ?? 0) + 1; $tot['cancelled']++;
                continue;
            }
            $tot['value'] += $amt;
            if ($stage === 'delivered') {
                $types[$t]['delivered'] = ($types[$t]['delivered'] ?? 0) + 1; $tot['delivered']++; $tot['delivered_value'] += $amt;
            } else {
                $types[$t]['open'] = ($types[$t]['open'] ?? 0) + 1; $tot['open']++;
            }
            $pt = $r->payment_type ?: 'Cash';
            $pay[$pt] = ($pay[$pt] ?? 0) + $amt;
        }

        // Carried in: orders placed before this day that were still not finished when the day started.
        $carried = 0;
        $before = (clone $this->orderSummaryQuery())->whereDate('o.date_added', '<', $date)
            ->whereIn('o.order_status', OrderFlow::statusesFor(['new', 'accepted', 'packing', 'out', 'delivered']))->get();
        foreach ($before as $r) {
            $stage = OrderFlow::stage($r->order_status);
            $doneAt = null;
            if ($stage === 'delivered') {
                $doneAt = DB::table('orders')->where('order_id', $r->order_id)->max(self::has('orders', 'delivery_complete_time') ? 'delivery_complete_time' : 'updated_at');
            }
            if ($stage !== 'delivered' || ($doneAt && substr((string) $doneAt, 0, 10) >= $date)) $carried++;
        }

        $collected = 0.0; $collectedCount = 0;
        if (self::has('orders', 'updated_at')) {
            $paid = DB::table('orders')->where('hub_id', $this->hubId)->where('payment_status', 'Paid')->whereDate('updated_at', $date)
                ->select('order_id', DB::raw('MAX(total_amount) AS t'), DB::raw('MAX(delivery_charge) AS d'))->groupBy('order_id')->get();
            foreach ($paid as $p) { $collected += (float) $p->t + (float) $p->d; $collectedCount++; }
        }

        return [
            'date' => $date, 'types' => $types, 'totals' => $tot, 'by_payment' => $pay,
            'carried_in' => $carried, 'collected' => round($collected, 2), 'collected_count' => $collectedCount,
        ];
    }

    private function audit(string $action, string $orderId, array $actor, array $extra): void
    {
        try {
            Log::info('[freshful-audit] ' . $action, [
                'panel' => 'pos-console', 'by' => ($actor['guard'] ?? '?') . '#' . ($actor['id'] ?? '?'),
                'hub' => $this->hubId, 'order' => $orderId, 'ip' => request()->ip(),
            ] + $extra);
        } catch (\Throwable $e) {
            // never block the action
        }
    }
}
