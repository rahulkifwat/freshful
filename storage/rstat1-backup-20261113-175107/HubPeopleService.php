<?php

namespace App\Services\HubConsole;

use App\Services\PosConsole\OrderFlow;
use App\Services\PosConsole\PosConsoleService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

/**
 * Riders (delivery boys) and customers of one hub (22 Oct 2026).
 * Old pages covered: Add Delivery Boy, All Delivery Boy, Update Delivery Boy, All Customers, Customer Order, Wallet History.
 */
class HubPeopleService
{
    public function __construct(private int $hubId) {}

    private static function active($s): bool { return in_array(strtolower(trim((string) $s)), ['active', '1'], true); }

    /* ---------------------------------------------------------------- riders */

    public function riders(): array
    {
        $rows = DB::table('delivery_boy')->where('hub_id', $this->hubId)->orderByRaw("LOWER(CAST(status AS CHAR)) IN ('active','1') DESC")->orderBy('name')->get();
        $live = [];
        try { foreach ((new PosConsoleService($this->hubId))->feed()['riders'] as $r) if ($r['id']) $live[(int) $r['id']] = $r; } catch (\Throwable $e) {}
        $since = date('Y-m-d 00:00:00', strtotime('-29 days'));
        $done = OrderFlow::statusesFor(['unsettled', 'delivered']);
        $stats = DB::table('orders')->where('hub_id', $this->hubId)->whereIn('order_status', $done)->where('date_added', '>=', $since)->whereNotNull('rider_id')
            ->groupBy('rider_id')->select('rider_id', DB::raw('COUNT(DISTINCT order_id) AS n'), DB::raw("COUNT(DISTINCT CASE WHEN date_added >= '" . date('Y-m-d 00:00:00', strtotime('-6 days')) . "' THEN order_id END) AS w"))
            ->get()->keyBy('rider_id');
        $out = [];
        foreach ($rows as $r) {
            $l = $live[(int) $r->id] ?? null; $s = $stats[$r->id] ?? null;
            $out[] = ['id' => (int) $r->id, 'name' => (string) $r->name, 'phone' => (string) $r->phone, 'gender' => (string) ($r->gender ?? ''),
                'employee_id' => (string) ($r->employee_id ?? ''), 'active' => self::active($r->status), 'joined' => $r->date_added ? strtotime((string) $r->date_added) : null,
                'state' => $l['state'] ?? (self::active($r->status) ? 'available' : 'off'), 'today' => $l['delivered_today'] ?? 0, 'out' => $l['out'] ?? 0,
                'cash_due' => $l['cash_due'] ?? 0, 'week' => (int) ($s->w ?? 0), 'month' => (int) ($s->n ?? 0), 'app' => !empty($r->regid)];
        }
        return ['ok' => true, 'riders' => $out];
    }

    /**
     * Passwords set from the console are always stored as a bcrypt hash, never as plain text (the old panel stored and
     * displayed them in plain text). Existing rows are not touched. If a legacy login compares plain text, that login
     * must be moved to Hash::check before riders created here can use it - see the installer's diagnostic line.
     */
    private static function passwordValue(string $plain): string
    {
        return Hash::make($plain);
    }

    public function saveRider(?int $id, array $d, array $actor): array
    {
        $name = trim((string) ($d['name'] ?? '')); $phone = preg_replace('/\D/', '', (string) ($d['phone'] ?? ''));
        $emp = strtoupper(trim((string) ($d['employee_id'] ?? ''))); $pass = (string) ($d['password'] ?? ''); $gender = (string) ($d['gender'] ?? '');
        if (strlen($phone) === 12 && str_starts_with($phone, '91')) $phone = substr($phone, 2);
        if (mb_strlen($name) < 2) return ['ok' => false, 'message' => 'Enter the rider\'s name.'];
        if (!preg_match('/^[6-9]\d{9}$/', $phone)) return ['ok' => false, 'message' => 'Enter a valid 10-digit mobile number.'];
        if ($emp === '' || !preg_match('/^[A-Z0-9_\-]{2,20}$/', $emp)) return ['ok' => false, 'message' => 'Enter an employee ID (letters and numbers).'];
        if (!$id && strlen($pass) < 6) return ['ok' => false, 'message' => 'Set a password of at least 6 characters for the rider app.'];
        if ($id && $pass !== '' && strlen($pass) < 6) return ['ok' => false, 'message' => 'The new password must have at least 6 characters.'];
        if (!in_array($gender, ['Male', 'Female', 'Other', ''], true)) $gender = '';
        $status = ($d['status'] ?? 'active') === 'inactive' ? 'inactive' : 'active';
        $dupe = fn ($col, $val) => DB::table('delivery_boy')->where($col, $val)->when($id, fn ($q) => $q->where('id', '<>', $id))->exists();
        if ($dupe('phone', $phone)) return ['ok' => false, 'message' => 'Another rider already uses this mobile number.'];
        if ($dupe('employee_id', $emp)) return ['ok' => false, 'message' => 'This employee ID is already taken.'];

        $row = ['name' => mb_substr($name, 0, 60), 'phone' => $phone, 'gender' => $gender];
        if ($pass !== '') $row['password'] = self::passwordValue($pass);
        if ($id) {
            $r = DB::table('delivery_boy')->where('id', $id)->where('hub_id', $this->hubId)->first();
            if (!$r) return ['ok' => false, 'message' => 'Rider not found in this hub.'];
            if ((string) $r->employee_id === '') $row['employee_id'] = $emp;   // the old panel kept the employee ID read-only
            DB::table('delivery_boy')->where('id', $id)->update($row);
            Log::info('[freshful-audit] hub-rider-update', ['hub' => $this->hubId, 'rider' => $id, 'by' => $actor['id'] ?? null]);
            return ['ok' => true, 'message' => $name . ' updated.' . ($pass !== '' ? ' New app password is set.' : '')];
        }
        $row += ['employee_id' => $emp, 'hub_id' => $this->hubId, 'status' => $status, 'date_added' => now()];
        $newId = DB::table('delivery_boy')->insertGetId($row);
        Log::info('[freshful-audit] hub-rider-add', ['hub' => $this->hubId, 'rider' => $newId, 'by' => $actor['id'] ?? null]);
        return ['ok' => true, 'message' => $name . ' added' . ($status === 'inactive' ? ' (switched off)' : '') . '.'];
    }

    public function toggleRider(int $id, array $actor): array
    {
        $r = DB::table('delivery_boy')->where('id', $id)->where('hub_id', $this->hubId)->first();
        if (!$r) return ['ok' => false, 'message' => 'Rider not found in this hub.'];
        $on = !self::active($r->status);
        if (!$on) {
            $busy = DB::table('orders')->where('hub_id', $this->hubId)->where('rider_id', $id)->whereIn('order_status', OrderFlow::statusesFor(['out']))->exists();
            if ($busy) return ['ok' => false, 'message' => $r->name . ' still has orders out for delivery. Finish them first.'];
        }
        DB::table('delivery_boy')->where('id', $id)->update(['status' => $on ? 'active' : 'inactive']);
        Log::info('[freshful-audit] hub-rider-status', ['hub' => $this->hubId, 'rider' => $id, 'on' => $on, 'by' => $actor['id'] ?? null]);
        return ['ok' => true, 'message' => $r->name . ($on ? ' is active again.' : ' is switched off and will not get orders.')];
    }

    /* ---------------------------------------------------------------- customers */

    /** Customers of this hub: anyone with an address in the hub area, or who ordered from it. */
    /**
     * Customer grade from the last 90 days at this hub (CTO rule, thresholds in config/hub_console.php):
     * Gold = 8+ orders or Rs 8,000+; Silver = 3+ orders or Rs 2,500+; Bronze = at least 1 order; otherwise "No recent orders".
     */
    public static function grade(int $orders90, float $spend90): string
    {
        $g = (array) config('hub_console.grades', []);
        $gold = $g['gold'] ?? ['orders' => 8, 'spend' => 8000]; $silver = $g['silver'] ?? ['orders' => 3, 'spend' => 2500];
        if ($orders90 >= $gold['orders'] || $spend90 >= $gold['spend']) return 'gold';
        if ($orders90 >= $silver['orders'] || $spend90 >= $silver['spend']) return 'silver';
        return $orders90 >= 1 ? 'bronze' : 'none';
    }

    public function customers(string $q, string $segment, string $grade = '', string $joined = ''): array
    {
        $agg = DB::table('orders')->where('hub_id', $this->hubId)->whereNotIn('order_status', OrderFlow::statusesFor(['cancelled']))
            ->groupBy('buyer_id')->select('buyer_id', DB::raw('COUNT(DISTINCT order_id) AS n'), DB::raw('MIN(date_added) AS first_at'), DB::raw('MAX(date_added) AS last_at'))->get()->keyBy('buyer_id');
        // Order value per buyer (one row per order: total + delivery + weight adjustments)
        $val = DB::query()->fromSub(DB::table('orders')->where('hub_id', $this->hubId)->whereNotIn('order_status', OrderFlow::statusesFor(['cancelled']))
            ->groupBy('order_id')->select(DB::raw('MAX(buyer_id) AS b'), DB::raw('MAX(CAST(total_amount AS DECIMAL(12,2))) + MAX(CAST(COALESCE(delivery_charge,0) AS DECIMAL(12,2))) AS v')), 'x')
            ->groupBy('b')->select('b', DB::raw('SUM(v) AS s'))->pluck('s', 'b');
        $g90 = DB::query()->fromSub(DB::table('orders')->where('hub_id', $this->hubId)->whereNotIn('order_status', OrderFlow::statusesFor(['cancelled']))
            ->where('date_added', '>=', date('Y-m-d 00:00:00', strtotime('-90 days')))->groupBy('order_id')
            ->select(DB::raw('MAX(buyer_id) AS b'), DB::raw('MAX(CAST(total_amount AS DECIMAL(12,2))) + MAX(CAST(COALESCE(delivery_charge,0) AS DECIMAL(12,2))) AS v')), 'x')
            ->groupBy('b')->select('b', DB::raw('COUNT(*) AS n'), DB::raw('SUM(v) AS s'))->get()->keyBy('b');
        $ids = $agg->keys()->map(fn ($v) => (int) $v)->all();
        if (HubStock::has('buyer_address', 'hub_id')) $ids = array_merge($ids, DB::table('buyer_address')->where('hub_id', $this->hubId)->distinct()->pluck('buyer_id')->map(fn ($v) => (int) $v)->all());
        $ids = array_values(array_unique(array_filter($ids)));
        $cols = ['id', 'name', 'phone', 'email'];
        foreach (['gender', 'wallet_amount', 'status', 'date_added'] as $c) if (HubStock::has('buyers', $c)) $cols[] = $c;
        $buyers = [];
        foreach (array_chunk($ids, 1000) as $ch) foreach (DB::table('buyers')->whereIn('id', $ch)->get($cols) as $b) $buyers[] = $b;
        $now = time(); $out = []; $seg = ['all' => 0, 'new' => 0, 'repeat' => 0, 'loyal' => 0, 'at_risk' => 0, 'no_order' => 0]; $grades = ['gold' => 0, 'silver' => 0, 'bronze' => 0, 'none' => 0];
        $term = mb_strtolower(trim($q));
        foreach ($buyers as $b) {
            $a = $agg[$b->id] ?? null; $n = (int) ($a->n ?? 0); $last = $a ? strtotime((string) $a->last_at) : null;
            $s = $n === 0 ? 'no_order' : ($n >= 2 && $last && $now - $last > 30 * 86400 ? 'at_risk' : ($n >= 5 ? 'loyal' : ($n >= 2 ? 'repeat' : 'new')));
            $x = $g90[$b->id] ?? null; $gr = self::grade((int) ($x->n ?? 0), (float) ($x->s ?? 0));
            $jt = isset($b->date_added) && $b->date_added ? strtotime((string) $b->date_added) : null;
            if ($joined !== '' && (!$jt || date('Y-m-d', $jt) !== $joined)) continue;
            $seg['all']++; $seg[$s]++; $grades[$gr]++;
            if ($segment !== '' && $segment !== 'all' && $segment !== $s) continue;
            if ($grade !== '' && $grade !== $gr) continue;
            if ($term !== '' && !str_contains(mb_strtolower($b->name . ' ' . $b->phone . ' ' . $b->email), $term)) continue;
            $out[] = ['id' => (int) $b->id, 'name' => trim((string) $b->name) ?: ('Customer ' . $b->phone), 'phone' => (string) $b->phone, 'email' => (string) $b->email,
                'gender' => (string) ($b->gender ?? ''), 'wallet' => (float) ($b->wallet_amount ?? 0), 'active' => !isset($b->status) || $b->status === null || self::active($b->status) || $b->status === '',
                'joined' => isset($b->date_added) && $b->date_added ? strtotime((string) $b->date_added) : null, 'orders' => $n, 'spend' => round((float) ($val[$b->id] ?? 0), 2),
                'first' => $a ? strtotime((string) $a->first_at) : null, 'last' => $last, 'segment' => $s, 'grade' => $gr,
                'orders90' => (int) ($x->n ?? 0), 'spend90' => round((float) ($x->s ?? 0), 2), 'status' => isset($b->status) ? (string) $b->status : ''];
        }
        usort($out, fn ($x, $y) => [$y['spend'], $y['last'] ?? 0] <=> [$x['spend'], $x['last'] ?? 0]);
        return ['ok' => true, 'customers' => array_slice($out, 0, 1000), 'total' => count($out), 'segments' => $seg, 'grades' => $grades,
            'grade_rule' => ['gold' => (array) config('hub_console.grades.gold', ['orders' => 8, 'spend' => 8000]), 'silver' => (array) config('hub_console.grades.silver', ['orders' => 3, 'spend' => 2500])]];
    }

    /** Wallet transactions on this hub's orders (old Wallet History, for the whole hub). */
    public function wallets(string $from, string $to, string $q): array
    {
        if (!HubStock::has('wallet_transaction_history')) return ['ok' => true, 'rows' => [], 'available' => false];
        $ord = DB::table('orders')->where('hub_id', $this->hubId)->groupBy('order_id')->select('order_id', DB::raw('MAX(payment_type) AS pt'), DB::raw('MAX(buyer_id) AS bid'));
        $rows = DB::table('wallet_transaction_history AS w')->joinSub($ord, 'o', 'o.order_id', '=', 'w.order_id')->leftJoin('buyers AS b', 'b.id', '=', 'w.buyer_id')
            ->whereBetween('w.date_added', [$from . ' 00:00:00', $to . ' 23:59:59'])->orderByDesc('w.id')->limit(2000)
            ->get(['w.id', 'w.buyer_id', 'w.order_id', 'w.order_amount', 'w.wallet_amount', 'w.credit_amount', 'w.date_added', 'o.pt', 'b.name', 'b.phone']);
        $term = mb_strtolower(trim($q)); $out = []; $sum = ['wallet' => 0.0, 'credit' => 0.0];
        foreach ($rows as $w) {
            if ($term !== '' && !str_contains(mb_strtolower($w->order_id . ' ' . $w->name . ' ' . $w->phone), $term)) continue;
            $out[] = ['id' => (int) $w->id, 'buyer_id' => (int) $w->buyer_id, 'customer' => trim((string) $w->name) ?: (string) $w->phone, 'phone' => (string) $w->phone, 'order_id' => (string) $w->order_id,
                'order_amount' => (float) $w->order_amount, 'wallet_amount' => (float) $w->wallet_amount, 'credit_amount' => (float) $w->credit_amount, 'payment_type' => (string) ($w->pt ?? ''),
                'at' => strtotime((string) $w->date_added) ?: null];
            $sum['wallet'] += (float) $w->wallet_amount; $sum['credit'] += (float) $w->credit_amount;
        }
        return ['ok' => true, 'available' => true, 'rows' => $out, 'from' => $from, 'to' => $to, 'summary' => array_map(fn ($v) => round($v, 2), $sum)];
    }

    public function customer(int $id): array
    {
        // Only customers of this hub: an order here, or an address in this hub's area.
        $mine = DB::table('orders')->where('buyer_id', $id)->where('hub_id', $this->hubId)->exists()
            || (HubStock::has('buyer_address', 'hub_id') && DB::table('buyer_address')->where('buyer_id', $id)->where('hub_id', $this->hubId)->exists());
        $b = $mine ? DB::table('buyers')->where('id', $id)->first() : null;
        if (!$b) return ['ok' => false, 'message' => 'Customer not found in this hub.'];
        $orders = DB::table('orders')->where('buyer_id', $id)->where('hub_id', $this->hubId)->groupBy('order_id')
            ->select('order_id', DB::raw('MAX(date_added) AS at'), DB::raw('MAX(order_status) AS st'), DB::raw('MAX(delivery_type) AS type'), DB::raw('MAX(payment_type) AS pay'),
                DB::raw('MAX(CAST(total_amount AS DECIMAL(12,2))) + MAX(CAST(COALESCE(delivery_charge,0) AS DECIMAL(12,2))) AS amount'), DB::raw('COUNT(*) AS items'))
            ->orderByDesc('at')->limit(200)->get()
            ->map(fn ($o) => ['order_id' => $o->order_id, 'at' => strtotime((string) $o->at) ?: null, 'status' => $o->st, 'stage' => OrderFlow::stage($o->st), 'type' => $o->type, 'pay' => $o->pay, 'amount' => (float) $o->amount, 'items' => (int) $o->items])->all();
        $addrQ = DB::table('buyer_address')->where('buyer_id', $id);
        $addr = $addrQ->limit(10)->get()->map(fn ($a) => ['text' => trim(implode(', ', array_filter([$a->name ?? '', $a->street_name ?? '', $a->locality ?? '', $a->landmark ?? '', $a->city ?? '']))),
            'type' => $a->type ?? '', 'here' => isset($a->hub_id) ? (int) $a->hub_id === $this->hubId : null])->all();
        $wallet = [];
        if (HubStock::has('buyer_wallet_ledger')) {
            foreach (DB::table('buyer_wallet_ledger')->where('buyer_id', $id)->orderByDesc('id')->limit(100)->get() as $w) {
                $wallet[] = ['at' => strtotime((string) ($w->created_at ?? '')) ?: null, 'what' => (string) ($w->description ?: $w->event_type), 'amount' => (float) $w->amount, 'balance' => (float) $w->balance_after, 'ref' => '',
                    'order_amount' => null, 'wallet_amount' => (float) $w->amount < 0 ? -(float) $w->amount : 0.0, 'credit_amount' => (float) $w->amount > 0 ? (float) $w->amount : 0.0, 'payment_type' => 'Wallet', 'source' => 'ledger'];
            }
        }
        if (HubStock::has('wallet_transaction_history')) {
            $wq = DB::table('wallet_transaction_history AS w')->leftJoin(DB::raw('(SELECT order_id, MAX(payment_type) AS pt, MAX(hub_id) AS hub FROM orders WHERE buyer_id = ' . (int) $id . ' GROUP BY order_id) AS o'), 'o.order_id', '=', 'w.order_id')
                ->where('w.buyer_id', $id)->orderByDesc('w.id')->limit(100)->get(['w.*', 'o.pt', 'o.hub']);
            foreach ($wq as $w) {
                $amt = (float) $w->credit_amount > 0 ? (float) $w->credit_amount : -(float) $w->wallet_amount;
                $here = $w->hub === null || (int) $w->hub === $this->hubId;
                $wallet[] = ['at' => strtotime((string) $w->date_added) ?: null, 'what' => ((float) $w->credit_amount > 0 ? 'Credit' : ((float) $w->wallet_amount > 0 ? 'Used on order' : 'Order')), 'amount' => $amt, 'balance' => null,
                    'ref' => $here ? (string) $w->order_id : '', 'order_amount' => (float) $w->order_amount, 'wallet_amount' => (float) $w->wallet_amount, 'credit_amount' => (float) $w->credit_amount,
                    'payment_type' => (string) ($w->pt ?? ''), 'source' => 'history'];
            }
        }
        usort($wallet, fn ($x, $y) => ($y['at'] ?? 0) <=> ($x['at'] ?? 0));
        $spend = 0; $n = 0; $s90 = 0; $n90 = 0; $c90 = strtotime('-90 days');
        foreach ($orders as $o) if ($o['stage'] !== 'cancelled') { $spend += $o['amount']; $n++; if (($o['at'] ?? 0) >= $c90) { $s90 += $o['amount']; $n90++; } }
        return ['ok' => true, 'customer' => ['id' => (int) $b->id, 'name' => trim((string) $b->name), 'phone' => (string) $b->phone, 'email' => (string) $b->email,
            'wallet' => (float) ($b->wallet_amount ?? 0), 'joined' => isset($b->date_added) && $b->date_added ? strtotime((string) $b->date_added) : null],
            'orders' => $orders, 'addresses' => $addr, 'wallet' => array_slice($wallet, 0, 100), 'stats' => ['orders' => $n, 'spend' => round($spend, 2), 'aov' => $n ? round($spend / $n, 2) : 0, 'grade' => self::grade($n90, $s90), 'orders90' => $n90, 'spend90' => round($s90, 2)]];
    }
}
