<?php

namespace App\Services\AdminConsole;

use App\Services\CountryConsole\CountryScope;
use App\Services\PosConsole\OrderFlow;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

/**
 * Freshful admin console (admin update 2): customer care.
 *   Refunds   - what each customer is owed (cancelled orders paid online; prepaid orders delivered lighter than ordered;
 *               dispute settlements; goodwill), paid back to the Freshful wallet (ledger entry, once only) or recorded as
 *               paid by UPI / bank / payment gateway with the reference.
 *   Tickets   - support tickets with the whole conversation, priority, owner, due time; complaints customers filed in
 *               the app (grievance table) come in automatically; contact-us and franchise enquiries are replied to here.
 *   Disputes  - a ticket about money (weight / price, missing item, quality, rider cash, refund) with an outcome;
 *               settling in the customer's favour creates the refund.
 *   Reviews   - ratings & reviews the app stores, with admin replies and hide / show.
 * Replies go by e-mail when the site's mail is set up (MAIL_MAILER), otherwise they are saved and a WhatsApp / call link is offered.
 */
class AdminCare
{
    public const STATUSES = ['open' => 'Open', 'in_progress' => 'In progress', 'waiting' => 'Waiting for customer', 'resolved' => 'Resolved', 'closed' => 'Closed'];
    public const PRIORITIES = ['urgent' => 2, 'high' => 8, 'normal' => 24, 'low' => 72];   // hours to answer
    public const DISPUTE_TYPES = ['Weight or price charged', 'Missing item', 'Quality / freshness', 'Wrong item', 'Rider cash / change', 'Refund not received', 'Late delivery', 'Other'];
    public const OUTCOMES = ['refund' => 'Refund in full', 'partial' => 'Partial refund', 'replaced' => 'Replaced / re-delivered', 'rejected' => 'Rejected (no fault found)', 'other' => 'Other'];

    private static function h(string $t, ?string $c = null): bool { return CountryScope::has($t, $c); }
    public static function on(): bool { return self::h('support_tickets') && self::h('support_ticket_messages'); }

    /* =================================================================== refunds */

    private static function orderInfo(array $ids): array
    {
        if (!$ids) return [];
        $sel = ['order_id', DB::raw('MAX(buyer_id) AS buyer'), DB::raw('MAX(hub_id) AS hub'), DB::raw('MAX(order_status) AS st'), DB::raw('MAX(payment_type) AS pay'), DB::raw('MAX(payment_status) AS ps'),
            DB::raw('MAX(total_amount) AS t'), DB::raw('MAX(delivery_charge) AS d'), DB::raw('MAX(date_added) AS at'), DB::raw(self::h('orders', 'amount_to_return') ? 'MAX(amount_to_return) AS r' : '0 AS r'),
            DB::raw(self::h('orders', 'payment_id') ? 'MAX(payment_id) AS pid' : "'' AS pid"), DB::raw(self::h('orders', 'reason_for_order_cancel') ? 'MAX(reason_for_order_cancel) AS why' : "'' AS why")];
        $o = [];
        foreach (array_chunk($ids, 800) as $c) foreach (DB::table('orders')->whereIn('order_id', $c)->groupBy('order_id')->select($sel)->get() as $r) $o[(string) $r->order_id] = $r;
        return $o;
    }

    private static function paidOnline($r): bool
    {
        return strcasecmp(trim((string) $r->pay), 'Online') === 0 && (strcasecmp(trim((string) $r->ps), 'Paid') === 0 || trim((string) $r->pid) !== '');
    }

    public static function refunds(CountryScope $scope, string $from, string $to, string $status): array
    {
        if (!self::h('admin_refunds')) return ['ok' => false, 'message' => 'The refunds table is not installed yet.'];
        $ids = $scope->ids(); $hubs = CountryScope::hubs();
        $rec = DB::table('admin_refunds')->get()->keyBy(fn ($r) => $r->order_id . '|' . $r->reason);
        // candidates: cancelled + paid online; prepaid online delivered with money to return (weighed lighter)
        $cand = [];
        $base = DB::table('orders')->whereIn('hub_id', $ids ?: [0])->whereBetween('date_added', [$from . ' 00:00:00', $to . ' 23:59:59'])->where('payment_type', 'Online');
        foreach ((clone $base)->whereIn('order_status', OrderFlow::statusesFor(['cancelled']))->distinct()->pluck('order_id') as $oid) $cand[(string) $oid . '|cancelled'] = ['order_id' => (string) $oid, 'reason' => 'cancelled'];
        if (self::h('orders', 'amount_to_return')) foreach ((clone $base)->where('amount_to_return', '>', 0)->whereNotIn('order_status', OrderFlow::statusesFor(['cancelled']))->distinct()->pluck('order_id') as $oid) $cand[(string) $oid . '|weight'] = ['order_id' => (string) $oid, 'reason' => 'weight'];
        foreach ($rec as $k => $r) if (substr((string) $r->created_at, 0, 10) >= $from && substr((string) $r->created_at, 0, 10) <= $to) $cand[$k] = ['order_id' => (string) $r->order_id, 'reason' => (string) $r->reason];
        $info = self::orderInfo(array_values(array_unique(array_column($cand, 'order_id'))));
        $buyers = [];
        $bids = array_values(array_unique(array_filter(array_map(fn ($r) => (int) $r->buyer, $info))));
        foreach (array_chunk($bids, 800) as $c) foreach (DB::table('buyers')->whereIn('id', $c)->get(['id', 'name', 'phone', 'wallet_amount']) as $b) $buyers[(int) $b->id] = $b;
        $rows = []; $sum = ['pending' => 0, 'pending_value' => 0.0, 'done' => 0, 'done_value' => 0.0, 'rejected' => 0];
        foreach ($cand as $k => $c) {
            $o = $info[$c['order_id']] ?? null; $r = $rec[$k] ?? null;
            if (!$o && !$r) continue;
            if ($o && !in_array((int) $o->hub, $ids, true) && !$r) continue;
            if (!$r && $c['reason'] === 'cancelled' && $o && !self::paidOnline($o)) continue; // online but never paid: nothing to refund
            $due = $r ? (float) $r->amount : ($c['reason'] === 'weight' ? round((float) $o->r, 2) : round((float) $o->t + (float) $o->d, 2));
            $st = $r ? (string) $r->status : 'pending';
            if ($status !== '' && $status !== $st) continue;
            $b = $o ? ($buyers[(int) $o->buyer] ?? null) : null;
            $rows[] = ['key' => $k, 'id' => $r ? (int) $r->id : null, 'order_id' => $c['order_id'], 'reason' => $c['reason'], 'amount' => $due, 'status' => $st, 'method' => (string) ($r->method ?? ''), 'reference' => (string) ($r->reference ?? ''),
                'note' => (string) ($r->note ?? ''), 'by' => (string) ($r->by_name ?? ''), 'done_at' => $r ? CountryScope::ts($r->updated_at) : null, 'placed' => $o ? CountryScope::ts($o->at) : null,
                'pay' => (string) ($o->pay ?? ''), 'payment_id' => (string) ($o->pid ?? ''), 'why' => (string) ($o->why ?? ''), 'hub' => $o ? ($hubs[(int) $o->hub]['name'] ?? '') : '',
                'buyer_id' => $o ? (int) $o->buyer : (int) ($r->buyer_id ?? 0), 'customer' => (string) ($b->name ?? ''), 'phone' => (string) ($b->phone ?? ''), 'wallet' => $b ? (float) $b->wallet_amount : null, 'ticket_id' => $r ? (int) ($r->ticket_id ?? 0) : 0];
            if ($st === 'pending') { $sum['pending']++; $sum['pending_value'] += $due; } elseif ($st === 'done') { $sum['done']++; $sum['done_value'] += $due; } else $sum['rejected']++;
        }
        usort($rows, fn ($a, $b) => [$a['status'] !== 'pending', -($a['placed'] ?? 0)] <=> [$b['status'] !== 'pending', -($b['placed'] ?? 0)]);
        $sum['pending_value'] = round($sum['pending_value'], 2); $sum['done_value'] = round($sum['done_value'], 2);
        return ['ok' => true, 'rows' => $rows, 'summary' => $sum, 'wallet_ledger' => self::h('buyer_wallet_ledger')];
    }

    /** Pay a refund. $method wallet = credit the Freshful wallet now (once only per order + reason); upi / bank / gateway / cash = record it. */
    public static function refund(string $orderId, string $reason, float $amount, string $method, string $ref, string $note, ?int $ticketId, array $actor): array
    {
        if (!self::h('admin_refunds')) return ['ok' => false, 'message' => 'The refunds table is not installed yet.'];
        if (!in_array($reason, ['cancelled', 'weight', 'dispute', 'manual'], true)) return ['ok' => false, 'message' => 'Unknown refund reason.'];
        if (!in_array($method, ['wallet', 'upi', 'bank', 'gateway', 'cash'], true)) return ['ok' => false, 'message' => 'Choose how the money goes back.'];
        $o = self::orderInfo([$orderId])[$orderId] ?? null;
        if (!$o) return ['ok' => false, 'message' => 'Order not found.'];
        $amount = round($amount, 2);
        $bill = round((float) $o->t + (float) $o->d, 2);
        if ($amount <= 0) return ['ok' => false, 'message' => 'Enter the amount to refund.'];
        $already = (float) DB::table('admin_refunds')->where('order_id', $orderId)->where('status', 'done')->where('reason', '!=', $reason)->sum('amount');
        if ($amount + $already > $bill + 0.01) return ['ok' => false, 'message' => 'That is more than the order value (' . $bill . ($already ? ', ' . $already . ' already refunded' : '') . ').'];
        if ($method !== 'wallet' && trim($ref) === '' && $method !== 'cash') return ['ok' => false, 'message' => 'Enter the UPI / bank / gateway reference so the refund can be traced.'];
        $buyer = (int) $o->buyer;
        $key = 'order-refund:' . $orderId . ':' . $reason;
        try {
            $res = DB::transaction(function () use ($orderId, $reason, $amount, $method, $ref, $note, $ticketId, $actor, $o, $buyer, $key) {
                $ex = DB::table('admin_refunds')->where('order_id', $orderId)->where('reason', $reason)->lockForUpdate()->first();
                if ($ex && $ex->status === 'done') return ['ok' => false, 'message' => 'This refund was already paid on ' . substr((string) $ex->updated_at, 0, 16) . ' (' . $ex->method . ').'];
                $now = date('Y-m-d H:i:s');
                if ($method === 'wallet') {
                    $b = DB::table('buyers')->where('id', $buyer)->lockForUpdate()->first();
                    if (!$b) return ['ok' => false, 'message' => 'The customer account was not found, so the wallet cannot be credited.'];
                    if (self::h('buyer_wallet_ledger') && self::h('buyer_wallet_ledger', 'idempotency_key') && DB::table('buyer_wallet_ledger')->where('idempotency_key', $key)->exists()) return ['ok' => false, 'message' => 'The wallet was already credited for this refund.'];
                    $bal = round((float) $b->wallet_amount + $amount, 2);
                    DB::table('buyers')->where('id', $buyer)->update(['wallet_amount' => $bal]);
                    if (self::h('buyer_wallet_ledger')) {
                        $l = ['buyer_id' => $buyer, 'event_type' => 'refund', 'amount' => $amount, 'balance_after' => $bal, 'description' => 'Refund for order ' . $orderId . ' (' . $reason . ')', 'reference' => $orderId];
                        if (self::h('buyer_wallet_ledger', 'idempotency_key')) $l['idempotency_key'] = $key;
                        if (self::h('buyer_wallet_ledger', 'created_at')) $l['created_at'] = $now;
                        if (self::h('buyer_wallet_ledger', 'updated_at')) $l['updated_at'] = $now;
                        DB::table('buyer_wallet_ledger')->insert($l);
                    }
                }
                $v = ['buyer_id' => $buyer ?: null, 'hub_id' => (int) $o->hub ?: null, 'amount' => $amount, 'method' => $method, 'status' => 'done', 'reference' => mb_substr(trim($ref), 0, 120), 'note' => mb_substr(trim($note), 0, 255),
                    'ticket_id' => $ticketId, 'by_name' => mb_substr((string) ($actor['name'] ?? ''), 0, 120), 'updated_at' => $now];
                if ($ex) DB::table('admin_refunds')->where('id', $ex->id)->update($v); else DB::table('admin_refunds')->insert($v + ['order_id' => $orderId, 'reason' => $reason, 'created_at' => $now]);
                return ['ok' => true];
            });
        } catch (\Throwable $e) { return ['ok' => false, 'message' => 'Could not save the refund: ' . AdminRecords::dbError($e)]; }
        if (!$res['ok']) return $res;
        AdminAudit::log($actor, 'refund', 'Orders', 'admin_refunds', $orderId, 'Refunded ' . $amount . ' for order ' . $orderId . ' (' . $reason . ') by ' . $method . ($ref ? ' ref ' . $ref : ''), null, ['amount' => $amount, 'method' => $method]);
        if ($ticketId) self::system($ticketId, 'Refund of ' . $amount . ' paid by ' . $method . ($ref ? ' (ref ' . $ref . ')' : '') . '.');
        return ['ok' => true, 'message' => $method === 'wallet' ? 'Rs. ' . $amount . ' added to the customer\'s Freshful wallet.' : 'Refund of Rs. ' . $amount . ' recorded (' . strtoupper($method) . ').'];
    }

    public static function refundReject(string $orderId, string $reason, string $note, array $actor): array
    {
        if (trim($note) === '') return ['ok' => false, 'message' => 'Write why no refund is due.'];
        $o = self::orderInfo([$orderId])[$orderId] ?? null;
        if (!$o) return ['ok' => false, 'message' => 'Order not found.'];
        $ex = DB::table('admin_refunds')->where('order_id', $orderId)->where('reason', $reason)->first();
        if ($ex && $ex->status === 'done') return ['ok' => false, 'message' => 'This refund was already paid.'];
        $now = date('Y-m-d H:i:s');
        $v = ['buyer_id' => (int) $o->buyer ?: null, 'hub_id' => (int) $o->hub ?: null, 'amount' => 0, 'status' => 'rejected', 'note' => mb_substr(trim($note), 0, 255), 'by_name' => mb_substr((string) ($actor['name'] ?? ''), 0, 120), 'updated_at' => $now];
        if ($ex) DB::table('admin_refunds')->where('id', $ex->id)->update($v); else DB::table('admin_refunds')->insert($v + ['order_id' => $orderId, 'reason' => $reason, 'created_at' => $now]);
        AdminAudit::log($actor, 'refund rejected', 'Orders', 'admin_refunds', $orderId, 'No refund for order ' . $orderId . ': ' . $note);
        return ['ok' => true, 'message' => 'Marked as no refund due.'];
    }

    /* =================================================================== tickets & disputes */

    private static function grievanceTable(): ?array
    {
        foreach (['grievance', 'grievances', 'complaints', 'complaint'] as $t) {
            if (!self::h($t)) continue;
            $pick = function (array $c) use ($t) { foreach ($c as $x) if (self::h($t, $x)) return $x; return null; };
            return ['table' => $t, 'buyer' => $pick(['customer_id', 'buyer_id', 'user_id']), 'order' => $pick(['order_id']), 'text' => $pick(['complaint_details', 'description', 'details', 'message', 'complaint', 'query']),
                'cat' => $pick(['category_id', 'grievance_category_id']), 'status' => $pick(['status']), 'date' => $pick(['date_added', 'created_at']), 'subject' => $pick(['subject', 'title'])];
        }
        return null;
    }

    /** Bring complaints filed in the app into the ticket list (once each). */
    public static function sync(): int
    {
        if (!self::on()) return 0;
        $g = self::grievanceTable(); if (!$g) return 0;
        $have = DB::table('support_tickets')->where('source', 'grievance')->pluck('source_id')->map(fn ($v) => (int) $v)->flip()->all();
        $cats = self::h('grievance_category') ? DB::table('grievance_category')->pluck('name', 'id')->all() : [];
        $n = 0;
        $q = DB::table($g['table'])->orderBy('id');
        foreach ($q->limit(5000)->get() as $r) {
            if (isset($have[(int) $r->id])) continue;
            $buyer = $g['buyer'] ? (int) $r->{$g['buyer']} : 0;
            $b = $buyer ? DB::table('buyers')->where('id', $buyer)->first() : null;
            $oid = $g['order'] ? (string) $r->{$g['order']} : '';
            $hub = $oid !== '' ? DB::table('orders')->where('order_id', $oid)->value('hub_id') : null;
            $text = $g['text'] ? trim(strip_tags((string) $r->{$g['text']})) : '';
            $st = $g['status'] ? strtolower(trim((string) $r->{$g['status']})) : 'open';
            $at = $g['date'] ? ((string) $r->{$g['date']} ?: date('Y-m-d H:i:s')) : date('Y-m-d H:i:s');
            try {
                DB::table('support_tickets')->insert(['code' => self::code(), 'kind' => 'ticket', 'source' => 'grievance', 'source_id' => (int) $r->id, 'buyer_id' => $buyer ?: null, 'order_id' => $oid ?: null, 'hub_id' => $hub ?: null,
                    'contact_name' => (string) ($b->name ?? ''), 'contact_phone' => (string) ($b->phone ?? ''), 'contact_email' => (string) ($b->email ?? ''),
                    'category' => mb_substr((string) ($g['cat'] ? ($cats[(int) $r->{$g['cat']}] ?? '') : ''), 0, 80), 'subject' => mb_substr($g['subject'] ? (string) $r->{$g['subject']} : (mb_substr($text, 0, 80) ?: 'Complaint from the app'), 0, 200),
                    'body' => $text, 'status' => in_array($st, ['closed', 'resolved', 'done', 'complete', 'completed'], true) ? 'closed' : 'open', 'priority' => 'normal', 'due_at' => date('Y-m-d H:i:s', strtotime($at) + 24 * 3600),
                    'created_by' => 'Customer (app)', 'created_at' => $at, 'updated_at' => $at]);
                $n++;
            } catch (\Throwable $e) {}
        }
        return $n;
    }

    private static function code(): string
    {
        for ($i = 0; $i < 5; $i++) { $c = 'T' . date('ymd') . strtoupper(substr(bin2hex(random_bytes(3)), 0, 4)); if (!DB::table('support_tickets')->where('code', $c)->exists()) return $c; }
        return 'T' . date('ymdHis');
    }

    public static function tickets(string $kind, string $status, string $q, string $from, string $to): array
    {
        if (!self::on()) return ['ok' => false, 'message' => 'The ticket tables are not installed yet.'];
        $new = self::sync();
        $hubs = CountryScope::hubs();
        $b = DB::table('support_tickets')->where('kind', $kind)->where('created_at', '>=', $from . ' 00:00:00')->where('created_at', '<=', $to . ' 23:59:59');
        $q = trim($q);
        if ($q !== '') $b->where(function ($w) use ($q) { $w->where('code', $q)->orWhere('order_id', $q)->orWhere('contact_phone', 'like', '%' . $q . '%')->orWhere('contact_name', 'like', '%' . $q . '%')->orWhere('subject', 'like', '%' . $q . '%'); });
        $all = $b->orderByDesc('id')->limit(3000)->get();
        $cnt = array_fill_keys(array_keys(self::STATUSES), 0); $overdue = 0; $now = time();
        $last = DB::table('support_ticket_messages')->whereIn('ticket_id', $all->pluck('id')->all() ?: [0])->groupBy('ticket_id')->select('ticket_id', DB::raw('MAX(created_at) AS at'), DB::raw('COUNT(*) AS n'))->get()->keyBy('ticket_id');
        $rows = [];
        foreach ($all as $t) {
            $cnt[$t->status] = ($cnt[$t->status] ?? 0) + 1;
            $open = !in_array($t->status, ['resolved', 'closed'], true);
            $late = $open && $t->due_at && strtotime($t->due_at) < $now; if ($late) $overdue++;
            if ($status !== '' && !($status === 'overdue' ? $late : ($status === 'active' ? $open : $t->status === $status))) continue;
            $rows[] = ['id' => (int) $t->id, 'code' => $t->code, 'source' => $t->source, 'subject' => $t->subject, 'category' => $t->category, 'customer' => $t->contact_name, 'phone' => $t->contact_phone, 'order_id' => (string) $t->order_id,
                'hub' => $hubs[(int) $t->hub_id]['name'] ?? '', 'status' => $t->status, 'priority' => $t->priority, 'assigned' => $t->assigned_to, 'at' => CountryScope::ts($t->created_at), 'due' => CountryScope::ts($t->due_at), 'late' => $late,
                'replies' => (int) ($last[$t->id]->n ?? 0), 'last' => CountryScope::ts($last[$t->id]->at ?? null), 'outcome' => $t->outcome, 'claim' => (float) $t->claim_amount, 'settled' => (float) $t->settled_amount];
        }
        return ['ok' => true, 'rows' => $rows, 'counts' => $cnt + ['overdue' => $overdue, 'all' => count($all)], 'imported' => $new, 'statuses' => self::STATUSES, 'types' => self::DISPUTE_TYPES, 'outcomes' => self::OUTCOMES,
            'categories' => self::h('grievance_category') ? DB::table('grievance_category')->orderBy('name')->pluck('name')->all() : [], 'mail' => self::mailOn()];
    }

    public static function mailOn(): bool
    {
        $m = strtolower((string) config('mail.default', 'log'));
        return !in_array($m, ['log', 'array', ''], true);
    }

    public static function create(array $in, array $actor): array
    {
        if (!self::on()) return ['ok' => false, 'message' => 'The ticket tables are not installed yet.'];
        $kind = in_array($in['kind'] ?? '', ['ticket', 'dispute'], true) ? $in['kind'] : 'ticket';
        $subject = trim((string) ($in['subject'] ?? '')); $body = trim((string) ($in['body'] ?? ''));
        if ($subject === '' || mb_strlen($subject) > 200) return ['ok' => false, 'message' => 'Write a short subject.'];
        $oid = trim((string) ($in['order_id'] ?? ''));
        $buyer = null; $hub = null;
        if ($oid !== '') {
            $o = DB::table('orders')->where('order_id', $oid)->first(['buyer_id', 'hub_id']);
            if (!$o) return ['ok' => false, 'message' => 'Order ' . $oid . ' not found.'];
            $buyer = (int) $o->buyer_id; $hub = (int) $o->hub_id;
        }
        $phone = preg_replace('/\D+/', '', (string) ($in['phone'] ?? ''));
        if (strlen($phone) === 12 && str_starts_with($phone, '91')) $phone = substr($phone, 2);
        if (!$buyer && $phone !== '') $buyer = (int) DB::table('buyers')->where('phone', $phone)->value('id') ?: null;
        $b = $buyer ? DB::table('buyers')->where('id', $buyer)->first() : null;
        if (!$b && $phone === '' && $oid === '') return ['ok' => false, 'message' => 'Enter the customer mobile or the order number.'];
        if ($kind === 'dispute' && $oid === '') return ['ok' => false, 'message' => 'A dispute needs the order number.'];
        $pr = isset(self::PRIORITIES[$in['priority'] ?? '']) ? $in['priority'] : ($kind === 'dispute' ? 'high' : 'normal');
        $claim = is_numeric($in['claim'] ?? null) ? round((float) $in['claim'], 2) : 0;
        $now = date('Y-m-d H:i:s');
        $id = (int) DB::table('support_tickets')->insertGetId(['code' => self::code(), 'kind' => $kind, 'source' => mb_substr((string) ($in['source'] ?? 'manual'), 0, 20), 'source_id' => isset($in['source_id']) ? (int) $in['source_id'] : null,
            'buyer_id' => $buyer, 'order_id' => $oid ?: null, 'hub_id' => $hub, 'contact_name' => mb_substr((string) ($b->name ?? ($in['name'] ?? '')), 0, 120), 'contact_phone' => $phone ?: (string) ($b->phone ?? ''),
            'contact_email' => mb_substr((string) ($in['email'] ?? ($b->email ?? '')), 0, 150), 'category' => mb_substr(trim((string) ($in['category'] ?? '')), 0, 80), 'subject' => $subject, 'body' => mb_substr($body, 0, 5000),
            'status' => 'open', 'priority' => $pr, 'claim_amount' => $claim, 'due_at' => date('Y-m-d H:i:s', time() + self::PRIORITIES[$pr] * 3600), 'created_by' => mb_substr((string) ($actor['name'] ?? ''), 0, 120), 'created_at' => $now, 'updated_at' => $now]);
        AdminAudit::log($actor, 'add', 'Customer service', 'support_tickets', $id, 'Opened ' . $kind . ' "' . $subject . '"');
        return ['ok' => true, 'id' => $id, 'message' => ($kind === 'dispute' ? 'Dispute' : 'Ticket') . ' opened.'];
    }

    public static function detail(int $id): array
    {
        $t = DB::table('support_tickets')->where('id', $id)->first();
        if (!$t) return ['ok' => false, 'message' => 'Ticket not found.'];
        $msgs = DB::table('support_ticket_messages')->where('ticket_id', $id)->orderBy('id')->get()->map(fn ($m) => ['id' => (int) $m->id, 'type' => $m->author_type, 'author' => $m->author, 'body' => $m->body, 'channel' => $m->channel, 'sent' => $m->send_status, 'at' => CountryScope::ts($m->created_at)])->all();
        $order = null;
        if ($t->order_id) { $oi = self::orderInfo([(string) $t->order_id])[(string) $t->order_id] ?? null; if ($oi) $order = ['id' => (string) $t->order_id, 'status' => (string) $oi->st, 'stage' => OrderFlow::stage($oi->st), 'amount' => round((float) $oi->t + (float) $oi->d, 2), 'pay' => (string) $oi->pay, 'placed' => CountryScope::ts($oi->at)]; }
        $refunds = self::h('admin_refunds') && $t->order_id ? DB::table('admin_refunds')->where('order_id', $t->order_id)->get()->map(fn ($r) => ['reason' => $r->reason, 'amount' => (float) $r->amount, 'status' => $r->status, 'method' => $r->method])->all() : [];
        $other = $t->buyer_id ? DB::table('support_tickets')->where('buyer_id', $t->buyer_id)->where('id', '!=', $id)->count() : 0;
        $hub = CountryScope::hub((int) $t->hub_id);
        return ['ok' => true, 'ticket' => (array) $t + ['hub' => $hub['name'] ?? '', 'late' => !in_array($t->status, ['resolved', 'closed'], true) && $t->due_at && strtotime($t->due_at) < time()],
            'messages' => $msgs, 'order' => $order, 'refunds' => $refunds, 'other_tickets' => $other, 'mail' => self::mailOn(), 'statuses' => self::STATUSES, 'outcomes' => self::OUTCOMES];
    }

    private static function system(int $id, string $text): void
    {
        try { DB::table('support_ticket_messages')->insert(['ticket_id' => $id, 'author_type' => 'system', 'author' => 'System', 'body' => $text, 'channel' => 'note', 'created_at' => date('Y-m-d H:i:s')]); } catch (\Throwable $e) {}
    }

    public static function reply(int $id, string $body, string $channel, string $statusAfter, array $actor): array
    {
        $t = DB::table('support_tickets')->where('id', $id)->first();
        if (!$t) return ['ok' => false, 'message' => 'Ticket not found.'];
        $body = trim($body);
        if ($body === '' || mb_strlen($body) > 5000) return ['ok' => false, 'message' => 'Write the reply.'];
        if (!in_array($channel, ['note', 'email', 'whatsapp', 'sms', 'phone'], true)) $channel = 'note';
        $send = ''; $wa = null; $msg = 'Reply saved.';
        if ($channel === 'email') {
            if (!filter_var($t->contact_email, FILTER_VALIDATE_EMAIL)) return ['ok' => false, 'message' => 'This customer has no e-mail address. Use WhatsApp or a call.'];
            if (!self::mailOn()) { $send = 'not_sent'; $msg = 'Saved, but NOT e-mailed: the site\'s e-mail is not set up (MAIL_MAILER). Send it by WhatsApp instead.'; }
            else {
                try {
                    Mail::raw($body . "\n\n-- \nFreshful customer care\nTicket " . $t->code, function ($m) use ($t) { $m->to($t->contact_email)->subject('Re: ' . $t->subject . ' [' . $t->code . ']'); });
                    $send = 'sent'; $msg = 'E-mail sent to ' . $t->contact_email . '.';
                } catch (\Throwable $e) { $send = 'not_sent'; $msg = 'Saved, but the e-mail failed: ' . mb_substr($e->getMessage(), 0, 120); }
            }
        }
        if ($channel === 'whatsapp') {
            $ph = preg_replace('/\D+/', '', (string) $t->contact_phone);
            if (strlen($ph) === 10) $ph = '91' . $ph;
            if (strlen($ph) < 11) return ['ok' => false, 'message' => 'This customer has no mobile number.'];
            $wa = 'https://wa.me/' . $ph . '?text=' . rawurlencode($body); $send = 'opened_whatsapp'; $msg = 'Saved. WhatsApp opens with the message ready: press send there.';
        }
        $now = date('Y-m-d H:i:s');
        DB::table('support_ticket_messages')->insert(['ticket_id' => $id, 'author_type' => $channel === 'note' ? 'note' : 'staff', 'author' => mb_substr((string) ($actor['name'] ?? ''), 0, 120), 'body' => $body, 'channel' => $channel, 'send_status' => $send, 'created_at' => $now]);
        $upd = ['updated_at' => $now];
        if ($channel !== 'note' && !$t->first_reply_at) $upd['first_reply_at'] = $now;
        if (isset(self::STATUSES[$statusAfter]) && $statusAfter !== $t->status) { $upd['status'] = $statusAfter; if (in_array($statusAfter, ['resolved', 'closed'], true)) $upd['closed_at'] = $now; }
        elseif ($channel !== 'note' && $t->status === 'open') $upd['status'] = 'in_progress';
        DB::table('support_tickets')->where('id', $id)->update($upd);
        if (isset($upd['status'])) self::mirror($t, $upd['status']);
        AdminAudit::log($actor, 'reply', 'Customer service', 'support_tickets', $id, 'Replied on ' . $t->code . ' by ' . $channel);
        return ['ok' => true, 'message' => $msg, 'whatsapp' => $wa];
    }

    public static function update(int $id, array $in, array $actor): array
    {
        $t = DB::table('support_tickets')->where('id', $id)->first();
        if (!$t) return ['ok' => false, 'message' => 'Ticket not found.'];
        $v = []; $now = date('Y-m-d H:i:s'); $log = [];
        if (isset($in['status']) && isset(self::STATUSES[$in['status']]) && $in['status'] !== $t->status) { $v['status'] = $in['status']; $log[] = 'status ' . self::STATUSES[$in['status']]; if (in_array($in['status'], ['resolved', 'closed'], true)) $v['closed_at'] = $now; else $v['closed_at'] = null; }
        if (isset($in['priority']) && isset(self::PRIORITIES[$in['priority']]) && $in['priority'] !== $t->priority) { $v['priority'] = $in['priority']; $v['due_at'] = date('Y-m-d H:i:s', strtotime((string) $t->created_at) + self::PRIORITIES[$in['priority']] * 3600); $log[] = 'priority ' . $in['priority']; }
        if (array_key_exists('assigned_to', $in) && mb_substr(trim((string) $in['assigned_to']), 0, 120) !== $t->assigned_to) { $v['assigned_to'] = mb_substr(trim((string) $in['assigned_to']), 0, 120); $log[] = 'assigned to ' . ($v['assigned_to'] ?: 'nobody'); }
        if (isset($in['outcome']) && ($in['outcome'] === '' || isset(self::OUTCOMES[$in['outcome']])) && $in['outcome'] !== $t->outcome) { $v['outcome'] = $in['outcome']; $log[] = 'outcome ' . (self::OUTCOMES[$in['outcome']] ?? 'none'); }
        if (isset($in['settled']) && is_numeric($in['settled']) && round((float) $in['settled'], 2) !== round((float) $t->settled_amount, 2)) { $v['settled_amount'] = round((float) $in['settled'], 2); $log[] = 'settled ' . $v['settled_amount']; }
        if (!$v) return ['ok' => true, 'message' => 'Nothing changed.'];
        $v['updated_at'] = $now;
        DB::table('support_tickets')->where('id', $id)->update($v);
        self::system($id, ucfirst(implode(', ', $log)) . ' by ' . ($actor['name'] ?? 'admin') . '.');
        if (isset($v['status'])) self::mirror($t, $v['status']);
        AdminAudit::log($actor, 'edit', 'Customer service', 'support_tickets', $id, 'Updated ' . $t->code . ': ' . implode(', ', $log), $t, DB::table('support_tickets')->where('id', $id)->first());
        return ['ok' => true, 'message' => 'Saved.'];
    }

    /** Keep the app's complaint status in step (open / closed) for complaints that came from the app. */
    private static function mirror(object $t, string $to): void
    {
        if ($t->source !== 'grievance' || !$t->source_id) return;
        $g = self::grievanceTable(); if (!$g || !$g['status']) return;
        try {
            $vals = DB::table($g['table'])->select($g['status'])->distinct()->limit(20)->pluck($g['status'])->map(fn ($v) => strtolower((string) $v))->all();
            $closed = in_array($to, ['resolved', 'closed'], true);
            $val = $closed ? (in_array('closed', $vals, true) || !in_array('resolved', $vals, true) ? 'closed' : 'resolved') : (in_array('open', $vals, true) || !in_array('pending', $vals, true) ? 'open' : 'pending');
            DB::table($g['table'])->where('id', $t->source_id)->update([$g['status'] => $val]);
        } catch (\Throwable $e) {}
    }

    /** Settle a dispute: outcome + refund (wallet / recorded) in one step. */
    public static function settle(int $id, string $outcome, float $amount, string $method, string $ref, string $note, array $actor): array
    {
        $t = DB::table('support_tickets')->where('id', $id)->first();
        if (!$t || $t->kind !== 'dispute') return ['ok' => false, 'message' => 'Dispute not found.'];
        if (!isset(self::OUTCOMES[$outcome])) return ['ok' => false, 'message' => 'Choose the outcome.'];
        if (in_array($outcome, ['refund', 'partial'], true)) {
            if (!$t->order_id) return ['ok' => false, 'message' => 'This dispute has no order, so there is nothing to refund against.'];
            $r = self::refund((string) $t->order_id, 'dispute', $amount, $method, $ref, $note ?: ('Dispute ' . $t->code), $id, $actor);
            if (!$r['ok']) return $r;
        }
        DB::table('support_tickets')->where('id', $id)->update(['outcome' => $outcome, 'settled_amount' => in_array($outcome, ['refund', 'partial'], true) ? round($amount, 2) : 0, 'status' => 'resolved', 'closed_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')]);
        self::system($id, 'Dispute settled: ' . self::OUTCOMES[$outcome] . ($note ? ' - ' . $note : '') . '.');
        AdminAudit::log($actor, 'settle', 'Customer service', 'support_tickets', $id, 'Settled dispute ' . $t->code . ': ' . self::OUTCOMES[$outcome] . (in_array($outcome, ['refund', 'partial'], true) ? ' ' . $amount : ''));
        return ['ok' => true, 'message' => 'Dispute settled: ' . self::OUTCOMES[$outcome] . '.'];
    }

    /* =================================================================== enquiries (contact us, franchise) with reply */

    public static function enquiries(string $type): array
    {
        $tb = $type === 'franchise' ? 'frenchisee_enquiry' : 'contact_us';
        if (!self::h($tb)) return ['ok' => false, 'message' => 'Table ' . $tb . ' not found.'];
        $tk = self::on() ? DB::table('support_tickets')->where('source', $type)->get()->keyBy('source_id') : collect();
        $rows = [];
        foreach (DB::table($tb)->orderByDesc('id')->limit(3000)->get() as $r) {
            $t = $tk[(int) $r->id] ?? null;
            $name = $type === 'franchise' ? (string) $r->name : trim(($r->fname ?? '') . ' ' . ($r->lname ?? ''));
            $rows[] = ['id' => (int) $r->id, 'name' => $name, 'email' => (string) ($r->email ?? ''), 'phone' => (string) ($r->phone ?? ''), 'text' => (string) ($r->query ?? ''), 'at' => CountryScope::ts($r->created_at ?? null),
                'ticket_id' => $t ? (int) $t->id : null, 'status' => $t ? $t->status : 'new', 'code' => $t->code ?? ''];
        }
        return ['ok' => true, 'rows' => $rows, 'mail' => self::mailOn()];
    }

    /** Open (or find) the ticket for an enquiry so it can be answered and tracked. */
    public static function enquiryTicket(string $type, int $rowId, array $actor): array
    {
        if (!self::on()) return ['ok' => false, 'message' => 'The ticket tables are not installed yet.'];
        $ex = DB::table('support_tickets')->where('source', $type)->where('source_id', $rowId)->first();
        if ($ex) return ['ok' => true, 'id' => (int) $ex->id];
        $tb = $type === 'franchise' ? 'frenchisee_enquiry' : 'contact_us';
        $r = DB::table($tb)->where('id', $rowId)->first();
        if (!$r) return ['ok' => false, 'message' => 'Enquiry not found.'];
        $name = $type === 'franchise' ? (string) $r->name : trim(($r->fname ?? '') . ' ' . ($r->lname ?? ''));
        $phone = preg_replace('/\D+/', '', (string) ($r->phone ?? ''));
        $buyer = $phone !== '' ? (int) DB::table('buyers')->where('phone', substr($phone, -10))->value('id') : 0;
        $now = date('Y-m-d H:i:s'); $at = (string) ($r->created_at ?? $now) ?: $now;
        $id = (int) DB::table('support_tickets')->insertGetId(['code' => self::code(), 'kind' => 'enquiry', 'source' => $type, 'source_id' => $rowId, 'buyer_id' => $buyer ?: null, 'contact_name' => mb_substr($name, 0, 120),
            'contact_phone' => mb_substr($phone, 0, 20), 'contact_email' => mb_substr((string) ($r->email ?? ''), 0, 150), 'category' => $type === 'franchise' ? 'Franchise' : 'Contact us',
            'subject' => $type === 'franchise' ? 'Franchise enquiry from ' . $name : mb_substr(trim((string) ($r->query ?? '')) ?: 'Contact us message', 0, 200), 'body' => (string) ($r->query ?? ''),
            'status' => 'open', 'priority' => $type === 'franchise' ? 'high' : 'normal', 'due_at' => date('Y-m-d H:i:s', time() + ($type === 'franchise' ? 8 : 24) * 3600), 'created_by' => 'Website', 'created_at' => $at, 'updated_at' => $now]);
        return ['ok' => true, 'id' => $id];
    }

    /* =================================================================== ratings & reviews */

    public static function reviewSource(): ?array
    {
        $tables = [];
        try { foreach (DB::select('SHOW TABLES') as $t) $tables[] = array_values((array) $t)[0]; } catch (\Throwable $e) { return null; }
        foreach ($tables as $tb) {
            if (!preg_match('/review|rating|feedback/i', $tb) || in_array($tb, ['admin_review_replies'], true)) continue;
            $rate = null; foreach (['rating', 'ratings', 'stars', 'rate', 'star', 'rating_value'] as $c) if (self::h($tb, $c)) { $rate = $c; break; }
            if (!$rate) continue;
            $p = function (array $c) use ($tb) { foreach ($c as $x) if (self::h($tb, $x)) return $x; return null; };
            return ['table' => $tb, 'rating' => $rate, 'text' => $p(['review', 'comment', 'comments', 'feedback', 'message', 'description', 'review_text']), 'buyer' => $p(['buyer_id', 'user_id', 'customer_id']),
                'product' => $p(['product_id', 'item_id']), 'order' => $p(['order_id']), 'rider' => $p(['delivery_boy_id', 'rider_id', 'driver_id']), 'status' => $p(['status', 'is_approved', 'approved', 'is_active']), 'date' => $p(['created_at', 'date_added', 'date'])];
        }
        return null;
    }

    public static function reviews(): array
    {
        $s = self::reviewSource();
        if (!$s) return ['ok' => true, 'available' => false, 'rows' => [], 'reason' => 'No ratings table was found in the database: the app does not save ratings yet.'];
        $rep = self::h('admin_review_replies') ? DB::table('admin_review_replies')->where('table_name', $s['table'])->get()->keyBy('row_id') : collect();
        $rows = []; $dist = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0]; $sum = 0; $n = 0;
        $q = DB::table($s['table'])->orderByDesc($s['date'] ?: 'id')->limit(3000)->get();
        $bids = $s['buyer'] ? $q->pluck($s['buyer'])->filter()->unique()->all() : [];
        $buyers = $bids ? DB::table('buyers')->whereIn('id', $bids)->pluck('name', 'id')->all() : [];
        $pids = $s['product'] ? $q->pluck($s['product'])->filter()->unique()->all() : [];
        $prods = $pids ? DB::table('products')->whereIn('id', $pids)->pluck('product_name', 'id')->all() : [];
        foreach ($q as $r) {
            $st = (float) $r->{$s['rating']}; $k = max(1, min(5, (int) round($st))); if ($st > 0) { $dist[$k]++; $sum += $st; $n++; }
            $re = $rep[(int) $r->id] ?? null;
            $rows[] = ['id' => (int) $r->id, 'stars' => $st, 'text' => $s['text'] ? (string) $r->{$s['text']} : '', 'customer' => $s['buyer'] ? (string) ($buyers[(int) $r->{$s['buyer']}] ?? ('#' . $r->{$s['buyer']})) : '',
                'product' => $s['product'] ? (string) ($prods[(int) $r->{$s['product']}] ?? '') : '', 'order_id' => $s['order'] ? (string) $r->{$s['order']} : '', 'at' => $s['date'] ? CountryScope::ts($r->{$s['date']}) : null,
                'status' => $s['status'] ? (string) $r->{$s['status']} : null, 'reply' => (string) ($re->reply ?? ''), 'hidden' => (bool) ($re->hidden ?? false)];
        }
        return ['ok' => true, 'available' => true, 'table' => $s['table'], 'rows' => $rows, 'avg' => $n ? round($sum / $n, 2) : null, 'count' => $n, 'dist' => $dist, 'has_status' => (bool) $s['status']];
    }

    public static function reviewSave(int $rowId, string $reply, ?bool $hidden, array $actor): array
    {
        $s = self::reviewSource(); if (!$s) return ['ok' => false, 'message' => 'No ratings table.'];
        $r = DB::table($s['table'])->where('id', $rowId)->first(); if (!$r) return ['ok' => false, 'message' => 'Review not found.'];
        $now = date('Y-m-d H:i:s');
        $v = ['reply' => mb_substr(trim($reply), 0, 2000), 'by_name' => mb_substr((string) ($actor['name'] ?? ''), 0, 120), 'updated_at' => $now];
        if ($hidden !== null) $v['hidden'] = $hidden;
        $ex = DB::table('admin_review_replies')->where('table_name', $s['table'])->where('row_id', $rowId)->exists();
        if ($ex) DB::table('admin_review_replies')->where('table_name', $s['table'])->where('row_id', $rowId)->update($v); else DB::table('admin_review_replies')->insert($v + ['table_name' => $s['table'], 'row_id' => $rowId, 'created_at' => $now, 'hidden' => $hidden ?? false]);
        if ($hidden !== null && $s['status']) {
            $vals = DB::table($s['table'])->select($s['status'])->distinct()->limit(10)->pluck($s['status'])->map(fn ($x) => strtolower((string) $x))->all();
            $on = in_array('active', $vals, true) ? 'active' : (in_array('approved', $vals, true) ? 'approved' : '1');
            $off = in_array('inactive', $vals, true) ? 'inactive' : (in_array('disabled', $vals, true) ? 'disabled' : (in_array('rejected', $vals, true) ? 'rejected'
                : ($on === 'active' ? 'inactive' : ($on === 'approved' ? 'rejected' : '0'))));
            try { DB::table($s['table'])->where('id', $rowId)->update([$s['status'] => $hidden ? $off : $on]); } catch (\Throwable $e) {}
        }
        AdminAudit::log($actor, 'review', 'Customer service', $s['table'], $rowId, ($hidden ? 'Hid' : ($hidden === false ? 'Showed' : 'Replied to')) . ' review #' . $rowId);
        return ['ok' => true, 'message' => $hidden === null ? 'Reply saved.' : ($hidden ? 'Review hidden.' : 'Review shown.')];
    }
}
