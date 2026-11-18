<?php

namespace App\Services\AdminConsole;

use App\Services\CountryConsole\CountryScope;
use App\Services\DriverManagementService;
use App\Services\PosConsole\OrderFlow;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Freshful admin console: Driver management (drivers update 1, 28 Oct 2026).
 *
 * Rider app accounts live in driver_registration (+ driver_profile, driver_vehicles, driver_bank_details,
 * driver_identity_documents, driver_wallet, driver_locations). Every rider app account also has a
 * delivery_boy row (delivery_boy.regid = driver_registration.id), and that delivery_boy row is the rider
 * the hubs, the POS and every console work with.
 *
 * Rules:
 * - Approve / reject / activate / deactivate go through DriverManagementService, the same code the
 *   rider app and the admin token API use, so the app sees exactly the same states as before.
 * - Order assignment here stores delivery_boy.id in orders.rider_id, the same id the POS and hub
 *   consoles store, so the hub sees the rider the admin chose.
 * - Document checks are kept in our own table (driver_doc_reviews). The *_status columns the rider app
 *   reads are never written here.
 * - Driver documents are not public files: they are sent only to a logged-in admin (file()).
 */
class AdminDrivers
{
    public const DOCS = [
        'aadhaar_card'    => ['Aadhaar card', 'aadhaar_card_front', 'aadhaar_card_back'],
        'driving_license' => ['Driving licence', 'driving_license_front', 'driving_license_back'],
        'pan_card'        => ['PAN card', 'pan_card_front', 'pan_card_back'],
    ];

    /** Things the admin checks before approving: key => label. */
    public const CHECKS = [
        'photo' => 'Profile photo', 'personal' => 'Personal information', 'aadhaar_card' => 'Aadhaar card',
        'driving_license' => 'Driving licence', 'pan_card' => 'PAN card', 'vehicle' => 'Vehicle', 'bank' => 'Bank account',
    ];

    public const FILE_FIELDS = ['profile_photo', 'aadhaar_card_front', 'aadhaar_card_back', 'driving_license_front', 'driving_license_back', 'pan_card_front', 'pan_card_back'];

    /** Wallet entries the admin can add: kind => [sign, label]. Types are the ones the driver_wallet_transactions enum already has. */
    public const MOVES = [
        'admin_credit' => [1, 'Credit (adjustment)'], 'trip_earning' => [1, 'Delivery earning'], 'tip' => [1, 'Tip'],
        'admin_debit' => [-1, 'Debit (adjustment)'], 'penalty' => [-1, 'Penalty'],
    ];

    private const VEHICLES_WITHOUT_NUMBER = ['bicycle', 'cycle', 'bi-cycle'];

    private static function svc(): DriverManagementService { return app(DriverManagementService::class); }
    private static function has(string $t, ?string $c = null): bool { return CountryScope::has($t, $c); }
    private static function now(): string { return date('Y-m-d H:i:s'); }
    private static function s($v): string { return trim((string) ($v ?? '')); }

    /* ================================================================== drivers */

    /** Normalised approval state: pending | approved | rejected | incomplete */
    public static function stateOf(?string $v): string
    {
        $v = strtolower(self::s($v));
        return $v === DriverManagementService::PENDING ? 'pending' : ($v === 'approved' ? 'approved' : ($v === 'rejected' ? 'rejected' : 'incomplete'));
    }

    /** The delivery_boy row of each app account: regid => row (latest row when there are several). */
    private static function boys(array $regids): array
    {
        if (!$regids || !self::has('delivery_boy', 'regid')) return [];
        $out = [];
        $rows = DB::table('delivery_boy')->whereIn('regid', array_map('strval', $regids))->orderBy('id')->get(['id', 'regid', 'name', 'phone', 'employee_id', 'hub_id', 'status']);
        foreach ($rows as $r) $out[(int) $r->regid] = $r;   // later (newer) rows win
        return $out;
    }

    private static function hubLabel(int $hubId): string
    {
        if (!$hubId) return '';
        $h = CountryScope::hub($hubId);
        return $h ? $h['name'] . ($h['city'] ? ', ' . $h['city'] : '') : 'Hub #' . $hubId;
    }

    private static function docComplete(?object $d, string $doc): bool
    {
        if (!$d) return false;
        [, $f, $b] = self::DOCS[$doc];
        return self::s($d->$f ?? '') !== '' && self::s($d->$b ?? '') !== '';
    }

    /** Fingerprint of what was checked, so a later change by the driver shows as "changed since checked". */
    private static function sig(string $key, array $x): string
    {
        $p = $x['profile']; $v = $x['vehicle']; $b = $x['bank']; $d = $x['documents'];
        $vals = match ($key) {
            'photo' => [$p->profile_photo ?? ''],
            'personal' => [$p->name ?? '', $p->father_name ?? '', $p->dob ?? '', $p->phone ?? '', $p->blood_group ?? '', $p->address ?? '', $p->emergency_name ?? '', $p->emergency_relationship ?? '', $p->emergency_phone ?? ''],
            'vehicle' => [$v->vehicle_type ?? '', $v->vehicle_number ?? ''],
            'bank' => [$b->account_holder_name ?? '', $b->account_number ?? '', $b->ifsc_code ?? '', $b->bank_name ?? '', $b->branch_name ?? ''],
            default => isset(self::DOCS[$key]) ? [$d->{self::DOCS[$key][1]} ?? '', $d->{self::DOCS[$key][2]} ?? ''] : [],
        };
        return substr(md5(implode("\x1f", array_map(fn ($s) => self::s($s), $vals))), 0, 16);
    }

    private static function reviews(array $regids): array
    {
        if (!$regids || !self::has('driver_doc_reviews')) return [];
        $out = [];
        foreach (DB::table('driver_doc_reviews')->whereIn('regid', $regids)->get() as $r) $out[(int) $r->regid][(string) $r->doc] = $r;
        return $out;
    }

    /** Open orders (with the hub or out with the rider) per delivery_boy id. */
    private static function openOrders(array $boyIds): array
    {
        if (!$boyIds) return [];
        $st = OrderFlow::statusesFor(['new', 'accepted', 'out']);
        return DB::table('orders')->whereIn('rider_id', $boyIds)->whereIn('order_status', $st)
            ->groupBy('rider_id')->select('rider_id', DB::raw('COUNT(DISTINCT order_id) AS n'))->pluck('n', 'rider_id')->map(fn ($v) => (int) $v)->all();
    }

    private static function delivered30(array $boyIds): array
    {
        if (!$boyIds) return [];
        $st = OrderFlow::statusesFor(['unsettled', 'delivered']);
        return DB::table('orders')->whereIn('rider_id', $boyIds)->whereIn('order_status', $st)->where('date_added', '>=', date('Y-m-d 00:00:00', strtotime('-29 days')))
            ->groupBy('rider_id')->select('rider_id', DB::raw('COUNT(DISTINCT order_id) AS n'))->pluck('n', 'rider_id')->map(fn ($v) => (int) $v)->all();
    }

    /** Every app account with its state, for the list screens. */
    public static function list(): array
    {
        $q = DB::table('driver_registration AS d')
            ->leftJoin('driver_profile AS p', 'p.regid', '=', 'd.id')
            ->leftJoin('driver_vehicles AS v', 'v.regid', '=', 'd.id')
            ->select('d.id', 'd.name AS reg_name', 'd.email', 'd.phone', 'd.emp_no', 'd.status', 'd.verification_status', 'd.verified_at', 'd.approved_at', 'd.approved_by', 'd.rejection_reason',
                'p.name AS p_name', 'p.profile_photo', 'p.working_status', 'p.working_status_at', 'p.created_at AS profile_at', 'p.dob', 'v.vehicle_type', 'v.vehicle_number')
            ->orderByDesc('d.id')->limit((int) config('admin_console.list_limit', 5000));
        $rows = $q->get();
        // one profile / vehicle per driver even if the tables hold duplicates
        $seen = []; $list = [];
        foreach ($rows as $r) { if (isset($seen[$r->id])) continue; $seen[$r->id] = 1; $list[] = $r; }
        $ids = array_map(fn ($r) => (int) $r->id, $list);

        $docs = $ids ? DB::table('driver_identity_documents')->whereIn('regid', $ids)->orderBy('id')->get()->keyBy('regid') : collect();
        $banks = $ids ? DB::table('driver_bank_details')->whereIn('regid', $ids)->pluck('regid')->flip() : collect();
        $wallets = $ids ? DB::table('driver_wallet')->whereIn('regid', $ids)->get(['regid', 'balance', 'status'])->keyBy('regid') : collect();
        $locs = ($ids && self::has('driver_locations')) ? DB::table('driver_locations')->whereIn('regid', $ids)->get(['regid', 'updated_at'])->keyBy('regid') : collect();
        $boys = self::boys($ids);
        $boyIds = array_values(array_map(fn ($b) => (int) $b->id, $boys));
        $open = self::openOrders($boyIds); $del = self::delivered30($boyIds);
        $rev = self::reviews($ids);

        $out = []; $counts = ['all' => 0, 'pending' => 0, 'approved' => 0, 'rejected' => 0, 'incomplete' => 0, 'deactive' => 0, 'on_duty' => 0, 'no_wallet' => 0];
        foreach ($list as $r) {
            $id = (int) $r->id; $d = $docs[$id] ?? null; $b = $boys[$id] ?? null; $w = $wallets[$id] ?? null;
            $state = self::stateOf($r->verification_status);
            $susp = self::svc()->isSuspended($r->status);
            $docsDone = 0; foreach (array_keys(self::DOCS) as $k) if (self::docComplete($d, $k)) $docsDone++;
            $problems = 0; foreach (($rev[$id] ?? []) as $x) if ($x->decision === 'problem') $problems++;
            $name = self::s($r->p_name) !== '' ? self::s($r->p_name) : (self::s($r->reg_name) !== '' ? self::s($r->reg_name) : self::s($r->email));
            $duty = self::s($r->working_status) === 'on_duty' ? 'on_duty' : 'off_duty';
            $row = [
                'id' => $id, 'name' => $name, 'email' => self::s($r->email), 'phone' => self::s($r->phone), 'emp_no' => self::s($r->emp_no),
                'photo' => self::s($r->profile_photo) !== '', 'state' => $state, 'raw_state' => self::s($r->verification_status), 'suspended' => $susp,
                'duty' => $duty, 'duty_at' => CountryScope::ts($r->working_status_at),
                'vehicle' => self::s($r->vehicle_type), 'vehicle_no' => self::s($r->vehicle_number),
                'created' => CountryScope::ts($r->profile_at), 'submitted' => CountryScope::ts($r->verified_at), 'decided' => CountryScope::ts($r->approved_at),
                'reason' => self::s($r->rejection_reason), 'docs' => $docsDone, 'bank' => isset($banks[$id]), 'personal' => self::s($r->p_name) !== '' || self::s($r->dob) !== '',
                'wallet' => $w ? round((float) $w->balance, 2) : null, 'wallet_blocked' => $w && $w->status === 'blocked',
                'boy_id' => $b ? (int) $b->id : null, 'employee_id' => $b ? self::s($b->employee_id) : '', 'hub_id' => $b ? (int) $b->hub_id : 0,
                'hub' => $b ? self::hubLabel((int) $b->hub_id) : '', 'boy_active' => $b ? CountryScope::isActive($b->status) : null,
                'open' => $b ? ($open[(int) $b->id] ?? 0) : 0, 'delivered30' => $b ? ($del[(int) $b->id] ?? 0) : 0,
                'seen' => CountryScope::ts($locs[$id]->updated_at ?? null), 'problems' => $problems,
            ];
            $out[] = $row;
            $counts['all']++; $counts[$state]++;
            if ($susp) $counts['deactive']++;
            if ($duty === 'on_duty' && !$susp) $counts['on_duty']++;
            if ($state === 'approved' && $w === null) $counts['no_wallet']++;
        }
        return ['ok' => true, 'rows' => $out, 'counts' => $counts, 'checks' => self::CHECKS, 'blank_boys' => count(self::blankBoys())];
    }

    /** Raw rows for one driver, or null. */
    private static function load(int $id): ?array
    {
        $d = DB::table('driver_registration')->where('id', $id)->first();
        if (!$d) return null;
        unset($d->password);
        $first = fn (string $t) => DB::table($t)->where('regid', $id)->orderByDesc('id')->first();
        return [
            'driver' => $d, 'profile' => $first('driver_profile'), 'vehicle' => $first('driver_vehicles'), 'bank' => $first('driver_bank_details'),
            'documents' => DB::table('driver_identity_documents')->where('regid', $id)->orderByDesc('id')->first(),
            'wallet' => DB::table('driver_wallet')->where('regid', $id)->first(),
            'location' => self::has('driver_locations') ? DB::table('driver_locations')->where('regid', $id)->first() : null,
            'boy' => self::boys([$id])[$id] ?? null,
        ];
    }

    /** Everything about one driver for the review / detail drawer. */
    public static function detail(int $id): array
    {
        $x = self::load($id);
        if (!$x) return ['ok' => false, 'message' => 'Driver not found.'];
        $d = $x['driver']; $p = $x['profile']; $v = $x['vehicle']; $bk = $x['bank']; $doc = $x['documents']; $w = $x['wallet']; $loc = $x['location']; $boy = $x['boy'];
        $rev = self::reviews([$id])[$id] ?? [];

        // documents
        $docs = [];
        foreach (self::DOCS as $k => [$label, $f, $b]) {
            $docs[] = ['key' => $k, 'label' => $label, 'front' => self::s($doc->$f ?? '') !== '' ? $f : null, 'back' => self::s($doc->$b ?? '') !== '' ? $b : null,
                'app_status' => self::s($doc->{$k . '_status'} ?? ''), 'complete' => self::docComplete($doc, $k)];
        }
        // checks
        $vt = strtolower(self::s($v->vehicle_type ?? ''));
        $present = [
            'photo' => self::s($p->profile_photo ?? '') !== '',
            'personal' => $p !== null && self::s($p->name ?? '') !== '',
            'vehicle' => $v !== null && $vt !== '' && (self::s($v->vehicle_number ?? '') !== '' || in_array($vt, self::VEHICLES_WITHOUT_NUMBER, true)),
            'bank' => $bk !== null && self::s($bk->account_number ?? '') !== '' && self::s($bk->ifsc_code ?? '') !== '',
        ] + array_combine(array_keys(self::DOCS), array_map(fn ($k) => self::docComplete($doc, $k), array_keys(self::DOCS)));
        $checks = [];
        foreach (self::CHECKS as $k => $label) {
            $r = $rev[$k] ?? null; $sig = self::sig($k, $x);
            $checks[$k] = ['label' => $label, 'present' => $present[$k] ?? false, 'decision' => $r ? (string) $r->decision : null, 'note' => $r ? (string) $r->note : '',
                'by' => $r ? (string) $r->actor_name : '', 'at' => $r ? CountryScope::ts($r->updated_at) : null, 'changed' => $r ? $r->sig !== $sig : false];
        }

        // warnings the admin should see before approving
        $warn = [];
        $age = null;
        if (!empty($p->dob) && strtotime((string) $p->dob)) { $age = (int) floor((time() - strtotime((string) $p->dob)) / (365.25 * 86400)); if ($age < 18) $warn[] = 'Under 18 by the date of birth (' . $age . ' years).'; }
        if ($v && $vt !== '' && !in_array($vt, self::VEHICLES_WITHOUT_NUMBER, true) && self::s($v->vehicle_number ?? '') === '') $warn[] = 'No vehicle number for a ' . $v->vehicle_type . '.';
        $dup = function (string $table, string $col, $val, string $what) use ($id, &$warn) {
            $val = self::s($val); if ($val === '' || !self::has($table, $col)) return;
            $idCol = $table === 'driver_registration' ? 'id' : 'regid';
            $others = DB::table($table)->where($col, $val)->where($idCol, '<>', $id)->pluck($idCol)->unique()->values()->all();
            if ($others) $warn[] = $what . ' is also used by driver #' . implode(', #', array_slice($others, 0, 5)) . '.';
        };
        $dup('driver_registration', 'phone', $d->phone, 'Phone ' . self::s($d->phone));
        if ($bk) $dup('driver_bank_details', 'account_number', $bk->account_number, 'Bank account ' . self::mask($bk->account_number));
        if ($v) $dup('driver_vehicles', 'vehicle_number', $v->vehicle_number, 'Vehicle number ' . self::s($v->vehicle_number));
        if ($bk && self::s($bk->ifsc_code) !== '' && !preg_match('/^[A-Z]{4}0[A-Z0-9]{6}$/', strtoupper(self::s($bk->ifsc_code)))) $warn[] = 'IFSC code "' . self::s($bk->ifsc_code) . '" does not look valid (11 characters, like SBIN0001234).';
        if (!$boy) $warn[] = 'No delivery boy record is linked to this app account, so no hub can send orders to this driver.';
        elseif (!(int) $boy->hub_id) $warn[] = 'Not linked to a hub yet. The app links the driver to the nearest hub when the driver opens it with location on.';

        // money and work
        $txns = [];
        if ($w) foreach (DB::table('driver_wallet_transactions')->where('regid', $id)->orderByDesc('id')->limit(100)->get() as $t) {
            $txns[] = ['id' => (int) $t->id, 'type' => (string) $t->type, 'amount' => round((float) $t->amount, 2), 'status' => (string) $t->status, 'ref' => (string) $t->reference_id, 'note' => (string) $t->description, 'at' => CountryScope::ts($t->created_at)];
        }
        $orders = []; $stats = ['open' => 0, 'delivered' => 0, 'cancelled' => 0];
        if ($boy) {
            $rows = DB::table('orders')->where('rider_id', (int) $boy->id)->where('date_added', '>=', date('Y-m-d 00:00:00', strtotime('-59 days')))
                ->groupBy('order_id')->select('order_id', DB::raw('MAX(order_status) AS st'), DB::raw('MAX(date_added) AS at'), DB::raw('MAX(hub_id) AS hub'), DB::raw('MAX(total_amount) + MAX(delivery_charge) AS amt'),
                    DB::raw('MAX(payment_type) AS pay'), DB::raw('MAX(delivery_type) AS dtype'), DB::raw('MAX(delivery_complete_time) AS done'))
                ->orderByDesc('at')->limit(200)->get();
            foreach ($rows as $o) {
                $stg = OrderFlow::stage($o->st);
                if (in_array($stg, ['new', 'accepted', 'out'], true)) $stats['open']++; elseif (in_array($stg, ['unsettled', 'delivered'], true)) $stats['delivered']++; elseif ($stg === 'cancelled') $stats['cancelled']++;
                $orders[] = ['order_id' => (string) $o->order_id, 'status' => (string) $o->st, 'stage' => $stg, 'at' => CountryScope::ts($o->at), 'hub' => self::hubLabel((int) $o->hub), 'amount' => round((float) $o->amt, 2), 'pay' => (string) $o->pay, 'type' => (string) $o->dtype, 'done' => CountryScope::ts($o->done)];
            }
        }
        $lang = self::s($p->language ?? '');
        // The app sends JSON like [{"label":"English","value":"En"}]; the column is only 50 long, so longer lists arrive cut off.
        $langs = json_decode($lang, true);
        if (is_array($langs)) $lang = implode(', ', array_filter(array_map(fn ($l) => is_array($l) ? (string) ($l['label'] ?? $l['value'] ?? '') : (string) $l, $langs)));
        elseif (str_starts_with($lang, '[') && preg_match_all('/"label"\s*:\s*"([^"]+)"/', $lang, $m)) $lang = implode(', ', $m[1]) . ' (list cut off)';

        return ['ok' => true, 'driver' => [
            'id' => (int) $d->id, 'name' => self::s($p->name ?? '') ?: (self::s($d->name) ?: self::s($d->email)), 'email' => self::s($d->email), 'phone' => self::s($d->phone), 'emp_no' => self::s($d->emp_no),
            'state' => self::stateOf($d->verification_status), 'raw_state' => self::s($d->verification_status), 'suspended' => self::svc()->isSuspended($d->status), 'account' => self::s($d->status),
            'submitted' => CountryScope::ts($d->verified_at), 'decided' => CountryScope::ts($d->approved_at), 'decided_by' => self::adminName((int) ($d->approved_by ?? 0)), 'reason' => self::s($d->rejection_reason),
            'photo' => self::s($p->profile_photo ?? '') !== '',
            'duty' => self::s($p->working_status ?? '') === 'on_duty' ? 'on_duty' : 'off_duty', 'duty_at' => CountryScope::ts($p->working_status_at ?? null),
            'profile' => $p ? ['name' => self::s($p->name), 'father' => self::s($p->father_name), 'dob' => self::s($p->dob), 'age' => $age, 'phone' => self::s($p->phone), 'blood' => self::s($p->blood_group),
                'address' => self::s($p->address), 'language' => $lang, 'em_name' => self::s($p->emergency_name), 'em_rel' => self::s($p->emergency_relationship), 'em_phone' => self::s($p->emergency_phone),
                'referral' => self::s($p->referral_code), 'created' => CountryScope::ts($p->created_at ?? null)] : null,
            'vehicle' => $v ? ['type' => self::s($v->vehicle_type), 'number' => self::s($v->vehicle_number), 'updated' => CountryScope::ts($v->updated_at ?? null)] : null,
            'bank' => $bk ? ['holder' => self::s($bk->account_holder_name), 'account' => self::s($bk->account_number), 'masked' => self::mask($bk->account_number), 'ifsc' => strtoupper(self::s($bk->ifsc_code)), 'bank' => self::s($bk->bank_name), 'branch' => self::s($bk->branch_name)] : null,
            'docs' => $docs, 'checks' => $checks, 'warnings' => $warn,
            'location' => $loc ? ['lat' => (float) $loc->lat, 'lng' => (float) $loc->lng, 'address' => self::s($loc->address), 'at' => CountryScope::ts($loc->updated_at)] : null,
            'boy' => $boy ? ['id' => (int) $boy->id, 'employee_id' => self::s($boy->employee_id), 'hub_id' => (int) $boy->hub_id, 'hub' => self::hubLabel((int) $boy->hub_id), 'active' => CountryScope::isActive($boy->status), 'name' => self::s($boy->name)] : null,
            'wallet' => $w ? ['id' => (int) $w->id, 'balance' => round((float) $w->balance, 2), 'currency' => self::s($w->currency) ?: 'INR', 'status' => self::s($w->status)] : null,
            'txns' => $txns, 'orders' => $orders, 'stats' => $stats,
        ], 'moves' => array_map(fn ($m) => ['sign' => $m[0], 'label' => $m[1]], self::MOVES)];
    }

    private static function mask($acct): string
    {
        $a = preg_replace('/\s+/', '', self::s($acct));
        return strlen($a) > 4 ? str_repeat('•', min(8, strlen($a) - 4)) . substr($a, -4) : $a;
    }

    private static function adminName(int $id): string
    {
        if (!$id || !self::has('admins')) return '';
        $a = DB::table('admins')->where('id', $id)->first(['name', 'email']);
        return $a ? (self::s($a->name) ?: self::s($a->email)) : 'Admin #' . $id;
    }

    /* ================================================================== actions */

    private static function actorId(array $actor): int
    {
        $id = (int) ($actor['id'] ?? 0);
        if ($id <= 0) throw new \RuntimeException('Please log in again.');
        return $id;
    }

    public static function review(int $id, string $key, string $decision, string $note, array $actor): array
    {
        if (!isset(self::CHECKS[$key])) return ['ok' => false, 'message' => 'Unknown item.'];
        if (!in_array($decision, ['ok', 'problem', 'clear'], true)) return ['ok' => false, 'message' => 'Choose OK or Problem.'];
        if (!self::has('driver_doc_reviews')) return ['ok' => false, 'message' => 'The review table is missing. Run the installer again.'];
        $x = self::load($id);
        if (!$x) return ['ok' => false, 'message' => 'Driver not found.'];
        $note = mb_substr(trim($note), 0, 250);
        if ($decision === 'problem' && mb_strlen($note) < 3) return ['ok' => false, 'message' => 'Write what is wrong. The driver sees it when you reject.'];
        if ($decision === 'clear') {
            DB::table('driver_doc_reviews')->where('regid', $id)->where('doc', $key)->delete();
            return ['ok' => true, 'message' => self::CHECKS[$key] . ': check removed.'];
        }
        $now = self::now();
        DB::table('driver_doc_reviews')->updateOrInsert(['regid' => $id, 'doc' => $key], [
            'decision' => $decision, 'note' => $decision === 'ok' ? '' : $note, 'sig' => self::sig($key, $x),
            'actor_id' => self::actorId($actor), 'actor_name' => mb_substr((string) ($actor['name'] ?? 'Admin'), 0, 120), 'updated_at' => $now, 'created_at' => $now,
        ]);
        AdminAudit::log($actor, 'review', 'Drivers', 'driver_doc_reviews', $id, 'Driver #' . $id . ' ' . self::CHECKS[$key] . ': ' . ($decision === 'ok' ? 'looks good' : 'problem - ' . $note));
        return ['ok' => true, 'message' => self::CHECKS[$key] . ($decision === 'ok' ? ' marked OK.' : ' marked as a problem.')];
    }

    public static function approve(int $id, array $actor): array
    {
        $x = self::load($id);
        if (!$x) return ['ok' => false, 'message' => 'Driver not found.'];
        $before = ['verification_status' => $x['driver']->verification_status];
        self::svc()->approve($id, self::actorId($actor));   // same rule as the app API: only pending -> approved, wallet created
        // The hubs list riders from delivery_boy; fill in the name and phone the app never copied there.
        $boy = $x['boy'];
        if ($boy) {
            $upd = [];
            $name = self::s($x['profile']->name ?? '') ?: self::s($x['driver']->name);
            if (self::s($boy->name) === '' && $name !== '') $upd['name'] = mb_substr($name, 0, 255);
            if (self::s($boy->phone) === '' && self::s($x['driver']->phone) !== '') $upd['phone'] = self::s($x['driver']->phone);
            if ($upd) DB::table('delivery_boy')->where('id', (int) $boy->id)->update($upd);
        }
        AdminAudit::log($actor, 'approve', 'Drivers', 'driver_registration', $id, 'Approved driver #' . $id . ' ' . (self::s($x['profile']->name ?? '') ?: self::s($x['driver']->email)), $before, ['verification_status' => 'approved']);
        return ['ok' => true, 'message' => 'Driver approved. The driver can now go on duty in the app.'];
    }

    public static function reject(int $id, string $reason, array $actor): array
    {
        $reason = trim($reason);
        if (mb_strlen($reason) < 5) return ['ok' => false, 'message' => 'Write the reason (at least 5 letters). The driver sees it in the app.'];
        $x = self::load($id);
        if (!$x) return ['ok' => false, 'message' => 'Driver not found.'];
        self::svc()->reject($id, self::actorId($actor), $reason);   // only pending -> rejected; reason saved for the app
        AdminAudit::log($actor, 'reject', 'Drivers', 'driver_registration', $id, 'Rejected driver #' . $id . ': ' . mb_substr($reason, 0, 180), ['verification_status' => $x['driver']->verification_status], ['verification_status' => 'rejected']);
        return ['ok' => true, 'message' => 'Driver rejected. The driver sees the reason and can fix it and send again.'];
    }

    public static function toggle(int $id, array $actor): array
    {
        $x = self::load($id);
        if (!$x) return ['ok' => false, 'message' => 'Driver not found.'];
        self::actorId($actor);
        $next = self::svc()->toggleAccountStatus($id);   // deactivating also sets the driver off duty (same as before)
        AdminAudit::log($actor, 'toggle', 'Drivers', 'driver_registration', $id, ($next === 'active' ? 'Activated' : 'Deactivated') . ' driver #' . $id, ['status' => $x['driver']->status], ['status' => $next]);
        return ['ok' => true, 'status' => $next, 'message' => $next === 'active' ? 'Driver activated.' : 'Driver deactivated and set off duty. No new orders can be given to this driver.'];
    }

    /* ================================================================== files */

    /** Full path of a driver's photo / document image, or null. Only for the logged-in admin (see the controller). */
    public static function file(int $id, string $field): ?string
    {
        if (!in_array($field, self::FILE_FIELDS, true)) return null;
        if ($field === 'profile_photo') { $name = DB::table('driver_profile')->where('regid', $id)->orderByDesc('id')->value('profile_photo'); $folder = 'profile_photos'; }
        else { $name = DB::table('driver_identity_documents')->where('regid', $id)->orderByDesc('id')->value($field); $folder = 'documents'; }
        $name = basename(self::s($name));
        if ($name === '' || !preg_match('/\.(jpe?g|png|webp|gif)$/i', $name)) return null;
        foreach (self::uploadDirs($folder) as $dir) {
            $p = $dir . '/' . $name;
            if (is_file($p)) return $p;
        }
        return null;
    }

    /** Folders the rider app API (and the old api/ folder) saved driver files into. */
    public static function uploadDirs(string $folder): array
    {
        $extra = array_filter(array_map('trim', explode(',', (string) config('admin_drivers.upload_dirs', ''))));
        $c = array_merge(array_map(fn ($d) => rtrim($d, '/') . '/' . $folder, $extra), [
            public_path('uploads/driver/' . $folder), base_path('../public_html/uploads/driver/' . $folder), base_path('../public_html/api/uploads/driver/' . $folder),
            base_path('../public_html/api/driver/uploads/' . $folder), public_path('api/uploads/driver/' . $folder),
        ]);
        return array_values(array_unique(array_filter($c, 'is_dir')));
    }

    /* ================================================================== order assignment */

    /** App names for delivery_boy rows that have no name (the rider app never copied the name there): regid => name. */
    private static function appNames(array $regids): array
    {
        $regids = array_values(array_unique(array_filter(array_map(fn ($v) => ctype_digit((string) $v) ? (int) $v : 0, $regids))));
        if (!$regids) return [];
        $out = [];
        foreach (DB::table('driver_registration AS d')->leftJoin('driver_profile AS p', 'p.regid', '=', 'd.id')->whereIn('d.id', $regids)->get(['d.id', 'd.name AS rn', 'd.email', 'p.name AS pn']) as $r) {
            $n = self::s($r->pn) ?: (self::s($r->rn) ?: self::s($r->email));
            if ($n !== '' && !isset($out[(int) $r->id])) $out[(int) $r->id] = $n;
        }
        return $out;
    }

    private static function boyName(?object $b, array $names): string
    {
        if (!$b) return '';
        return self::s($b->name) ?: ($names[(int) $b->regid] ?? ('Rider #' . $b->id));
    }

    private static function boyStateOf(?object $reg): array
    {
        // [can take orders, label, tone]
        if (!$reg) return [true, 'No app', 'p-mute'];
        if (self::svc()->isSuspended($reg->status)) return [false, 'Deactivated', 'p-bad'];
        $st = self::stateOf($reg->verification_status);
        if ($st !== 'approved') return [false, $st === 'pending' ? 'App: waiting approval' : ($st === 'rejected' ? 'App: rejected' : 'App: not finished'), 'p-warn'];
        return [true, 'App approved', 'p-ok'];
    }

    /** One row per order that can get a rider (new / accepted) or change rider (out for delivery). */
    public static function assignable(CountryScope $scope, string $filter, string $q): array
    {
        $hubs = $scope->ids();
        if (!$hubs) return ['ok' => true, 'rows' => [], 'counts' => ['unassigned' => 0, 'assigned' => 0, 'out' => 0, 'all' => 0]];
        $st = OrderFlow::statusesFor(['new', 'accepted', 'out']);
        $g = DB::table('orders')->whereIn('hub_id', $hubs)->whereIn('order_status', $st)->groupBy('order_id')
            ->select('order_id', DB::raw('MIN(id) AS first_id'), DB::raw('COUNT(*) AS items'), DB::raw('MAX(rider_id) AS rider_id'), DB::raw('MAX(order_status) AS st'));
        $rows = DB::table('orders AS o')->joinSub($g, 'g', 'g.first_id', '=', 'o.id')
            ->leftJoin('buyers AS b', 'b.id', '=', 'o.buyer_id')->leftJoin('buyer_address AS a', 'a.id', '=', 'o.address_id')
            ->select('o.order_id', 'o.hub_id', 'o.date_added', 'o.total_amount', 'o.delivery_charge', 'o.payment_type', 'o.payment_status', 'o.delivery_type', 'o.schedule_date', 'o.schedule_time',
                'g.items', 'g.rider_id', 'g.st', 'b.name AS buyer', 'b.phone AS bphone', 'a.name AS aname', 'a.phone AS aphone', 'a.street_name', 'a.locality', 'a.landmark', 'a.city')
            ->orderByDesc('o.id')->limit(2000)->get();
        $riderIds = array_values(array_unique(array_filter(array_map(fn ($r) => (int) $r->rider_id, $rows->all()))));
        $boys = $riderIds ? DB::table('delivery_boy')->whereIn('id', $riderIds)->get(['id', 'name', 'phone', 'employee_id', 'regid'])->keyBy('id') : collect();
        $names = self::appNames($boys->pluck('regid')->all());
        $out = []; $counts = ['unassigned' => 0, 'assigned' => 0, 'out' => 0, 'all' => 0];
        $q = mb_strtolower(trim($q));
        foreach ($rows as $r) {
            $stage = OrderFlow::stage($r->st);
            $rid = (int) $r->rider_id; $boy = $rid ? ($boys[$rid] ?? null) : null;
            $row = ['order_id' => (string) $r->order_id, 'stage' => $stage, 'status' => (string) $r->st, 'hub_id' => (int) $r->hub_id, 'hub' => self::hubLabel((int) $r->hub_id),
                'at' => CountryScope::ts($r->date_added), 'due' => OrderFlow::dueAt($r->delivery_type, $r->date_added, $r->schedule_date, $r->schedule_time),
                'amount' => round((float) $r->total_amount + (float) $r->delivery_charge, 2), 'pay' => (string) $r->payment_type, 'paid' => (string) $r->payment_status,
                'type' => (string) $r->delivery_type, 'slot' => trim(self::s($r->schedule_date) . ' ' . self::s($r->schedule_time)), 'items' => (int) $r->items,
                'customer' => self::s($r->buyer) ?: self::s($r->aname), 'phone' => self::s($r->bphone) ?: self::s($r->aphone),
                'address' => implode(', ', array_filter([self::s($r->street_name), self::s($r->locality), self::s($r->landmark), self::s($r->city)])),
                'rider_id' => $rid ?: 0, 'rider' => $boy ? self::boyName($boy, $names) : ($rid ? 'Rider #' . $rid : ''), 'rider_phone' => $boy ? self::s($boy->phone) : '',
                'rider_app' => $boy && self::s($boy->regid) !== ''];
            if ($q !== '' && !str_contains(mb_strtolower($row['order_id'] . ' ' . $row['customer'] . ' ' . $row['phone'] . ' ' . $row['rider']), $q)) continue;
            $counts['all']++;
            if ($stage === 'out') $counts['out']++; elseif ($rid) $counts['assigned']++; else $counts['unassigned']++;
            $ok = match ($filter) { 'assigned' => $stage !== 'out' && $rid > 0, 'out' => $stage === 'out', 'all' => true, default => $stage !== 'out' && !$rid };
            if ($ok) $out[] = $row;
        }
        return ['ok' => true, 'rows' => $out, 'counts' => $counts];
    }

    /** Riders of the order's hub, with what the admin needs to choose one. */
    public static function ridersFor(string $orderId): array
    {
        $o = DB::table('orders')->where('order_id', $orderId)->groupBy('order_id')->select(DB::raw('MAX(hub_id) AS hub'), DB::raw('MAX(rider_id) AS rider'), DB::raw('MAX(order_status) AS st'))->first();
        if (!$o) return ['ok' => false, 'message' => 'Order not found.'];
        $hub = (int) $o->hub;
        if (CountryScope::$allowed !== null && !in_array($hub, CountryScope::$allowed, true)) return ['ok' => false, 'message' => 'Order not found.'];
        $boys = DB::table('delivery_boy')->where('hub_id', $hub)->whereRaw("LOWER(CAST(status AS CHAR)) IN ('active','1')")->orderBy('name')->get(['id', 'name', 'phone', 'employee_id', 'regid']);
        $ids = $boys->pluck('id')->map(fn ($v) => (int) $v)->all();
        $names = self::appNames($boys->pluck('regid')->all());
        $regIds = $boys->pluck('regid')->filter(fn ($v) => ctype_digit((string) $v))->map(fn ($v) => (int) $v)->all();
        $regs = $regIds ? DB::table('driver_registration')->whereIn('id', $regIds)->get(['id', 'status', 'verification_status'])->keyBy('id') : collect();
        $duty = $regIds ? DB::table('driver_profile')->whereIn('regid', $regIds)->pluck('working_status', 'regid') : collect();
        $pos = ($ids && self::has('pos_rider_status')) ? DB::table('pos_rider_status')->whereIn('rider_id', $ids)->get()->keyBy('rider_id') : collect();
        $out = $ids ? DB::table('orders')->whereIn('rider_id', $ids)->whereIn('order_status', OrderFlow::statusesFor(['out']))->groupBy('rider_id')->select('rider_id', DB::raw('COUNT(DISTINCT order_id) AS n'))->pluck('n', 'rider_id') : collect();
        $pre = $ids ? DB::table('orders')->whereIn('rider_id', $ids)->whereIn('order_status', OrderFlow::statusesFor(['new', 'accepted']))->groupBy('rider_id')->select('rider_id', DB::raw('COUNT(DISTINCT order_id) AS n'))->pluck('n', 'rider_id') : collect();
        $today = $ids ? DB::table('orders')->whereIn('rider_id', $ids)->where('delivery_complete_time', '>=', date('Y-m-d 00:00:00'))->groupBy('rider_id')->select('rider_id', DB::raw('COUNT(DISTINCT order_id) AS n'))->pluck('n', 'rider_id') : collect();
        $list = [];
        foreach ($boys as $b) {
            $reg = ctype_digit((string) $b->regid) ? ($regs[(int) $b->regid] ?? null) : null;
            [$can, $label, $tone] = self::boyStateOf($reg);
            $p = $pos[(int) $b->id] ?? null;
            $hubOff = $p && !(int) $p->on_duty;
            $list[] = ['id' => (int) $b->id, 'name' => self::boyName($b, $names), 'phone' => self::s($b->phone), 'code' => self::s($b->employee_id),
                'can' => $can, 'app' => $label, 'tone' => $tone, 'driver_id' => $reg ? (int) $reg->id : null,
                'app_duty' => $reg ? ((string) ($duty[(int) $reg->id] ?? 'off_duty') === 'on_duty' ? 'on' : 'off') : null, 'hub_off' => $hubOff,
                'out' => (int) ($out[(int) $b->id] ?? 0), 'pre' => (int) ($pre[(int) $b->id] ?? 0), 'today' => (int) ($today[(int) $b->id] ?? 0), 'current' => (int) $b->id === (int) $o->rider];
        }
        usort($list, fn ($a, $b) => [!$a['can'], $a['hub_off'], $a['out'], $a['name']] <=> [!$b['can'], $b['hub_off'], $b['out'], $b['name']]);
        return ['ok' => true, 'hub' => self::hubLabel($hub), 'hub_id' => $hub, 'stage' => OrderFlow::stage($o->st), 'current' => (int) $o->rider, 'riders' => $list];
    }

    /**
     * Give an order to a rider of its hub. New / accepted orders: the rider is set and the status is not changed
     * (the hub still accepts, weighs and dispatches in the POS). Out for delivery: the rider is changed (same as the POS).
     * $expected is the rider the admin saw (0 = none): if someone changed it meanwhile nothing is saved.
     */
    public static function assign(string $orderId, int $riderId, int $expected, array $actor): array
    {
        $orderId = trim($orderId);
        if (!preg_match('/^[A-Za-z0-9_\-]{1,40}$/', $orderId)) return ['ok' => false, 'message' => 'Order not found.'];
        if ($riderId <= 0) return ['ok' => false, 'message' => 'Choose the rider.'];
        self::actorId($actor);
        $res = DB::transaction(function () use ($orderId, $riderId, $expected) {
            $rows = DB::table('orders')->where('order_id', $orderId)->lockForUpdate()->get(['id', 'order_status', 'rider_id', 'hub_id']);
            if ($rows->isEmpty()) return ['ok' => false, 'message' => 'Order not found.'];
            $hub = (int) $rows->max('hub_id');
            if (CountryScope::$allowed !== null && !in_array($hub, CountryScope::$allowed, true)) return ['ok' => false, 'message' => 'Order not found.'];
            $stages = $rows->map(fn ($r) => OrderFlow::stage($r->order_status))->unique()->values();
            if ($stages->count() !== 1 || !in_array($stages[0], ['new', 'accepted', 'out'], true)) {
                $s = $stages->first();
                return ['ok' => false, 'message' => $s === 'unsettled' ? 'This order is delivered and waiting for the cash; the rider cannot be changed.' : ($s === 'delivered' ? 'This order is already delivered.' : ($s === 'cancelled' ? 'This order was cancelled.' : 'The rider of this order cannot be changed now.'))];
            }
            $stage = $stages[0];
            $current = (int) $rows->max('rider_id');
            if ($current === $riderId) return ['ok' => false, 'message' => 'The order is already with this rider.'];
            if ($current !== $expected) return ['ok' => false, 'message' => 'Someone changed the rider of this order a moment ago. The list is refreshed; check and try again.', 'stale' => true];
            $boy = DB::table('delivery_boy')->where('id', $riderId)->lockForUpdate()->first(['id', 'name', 'hub_id', 'status', 'regid']);
            if (!$boy || (int) $boy->hub_id !== $hub) return ['ok' => false, 'message' => 'That rider does not belong to the hub of this order.'];
            if (!CountryScope::isActive($boy->status)) return ['ok' => false, 'message' => 'That rider is switched off at the hub.'];
            $reg = ctype_digit((string) $boy->regid) ? DB::table('driver_registration')->where('id', (int) $boy->regid)->first(['id', 'status', 'verification_status']) : null;
            [$can, $label] = self::boyStateOf($reg);
            if (!$can) return ['ok' => false, 'message' => 'This rider cannot take orders: ' . strtolower($label) . '.'];
            $upd = ['rider_id' => $riderId];
            if (self::has('orders', 'updated_at')) $upd['updated_at'] = self::now();
            $n = DB::table('orders')->where('order_id', $orderId)
                ->where(fn ($w) => $expected === 0 ? $w->whereNull('rider_id')->orWhere('rider_id', 0) : $w->where('rider_id', $expected))
                ->update($upd);
            if ($n !== $rows->count()) throw new \RuntimeException('This order was just changed by someone else. Refresh and try again.');
            return ['ok' => true, 'stage' => $stage, 'hub' => $hub, 'from' => $current, 'name' => self::boyName($boy, self::appNames([$boy->regid]))];
        });
        if (!$res['ok']) return $res;
        $who = trim(($actor['name'] ?? 'Admin') . ' (admin)');
        $action = $res['stage'] === 'out' ? 'reassign' : 'assign';
        $text = ($res['stage'] === 'out' ? 'Rider changed to ' : ($res['from'] ? 'Rider changed before dispatch to ' : 'Rider chosen by head office: ')) . $res['name'];
        try {
            Log::info('[freshful-audit] admin-' . $action, ['order' => $orderId, 'hub' => $res['hub'], 'rider' => $riderId, 'from' => $res['from'], 'by' => ($actor['guard'] ?? 'admin') . '#' . ($actor['id'] ?? '?')]);
            if (self::has('pos_order_events')) DB::table('pos_order_events')->insert([
                'order_id' => mb_substr($orderId, 0, 40), 'hub_id' => $res['hub'], 'action' => $action,
                'detail' => json_encode(['text' => $text, 'rider_id' => $riderId, 'from_rider' => $res['from'] ?: null], JSON_UNESCAPED_UNICODE),
                'actor_guard' => $actor['guard'] ?? 'admin', 'actor_id' => (int) ($actor['id'] ?? 0), 'actor_name' => mb_substr($who, 0, 120), 'created_at' => self::now(),
            ]);
        } catch (\Throwable $e) { /* the change is saved; a log failure must not undo it */ }
        AdminAudit::log($actor, $action, 'Drivers', 'orders', $orderId, 'Order ' . $orderId . ': ' . $text, ['rider_id' => $res['from']], ['rider_id' => $riderId]);
        return ['ok' => true, 'message' => 'Order ' . $orderId . ($res['stage'] === 'out' ? ' is now with ' : ' will go with ') . $res['name'] . '.'];
    }

    /* ================================================================== wallets */

    public static function wallets(): array
    {
        $regs = DB::table('driver_registration AS d')->leftJoin('driver_profile AS p', 'p.regid', '=', 'd.id')
            ->select('d.id', 'd.name AS rn', 'd.email', 'd.phone', 'd.status', 'd.verification_status', 'p.name AS pn')->orderByDesc('d.id')->get()->unique('id');
        $w = DB::table('driver_wallet')->get()->keyBy('regid');
        $last = DB::table('driver_wallet_transactions')->groupBy('regid')->select('regid', DB::raw('MAX(created_at) AS at'), DB::raw('COUNT(*) AS n'))->get()->keyBy('regid');
        $since = date('Y-m-d 00:00:00', strtotime('monday this week'));
        $week = DB::table('driver_wallet_transactions')->where('created_at', '>=', $since)->where('status', 'completed')->whereIn('type', ['credit', 'trip_earning', 'tip', 'admin_credit', 'deposit'])
            ->groupBy('regid')->select('regid', DB::raw('SUM(amount) AS s'))->pluck('s', 'regid');
        $pend = DB::table('driver_wallet_transactions')->where('type', 'withdrawal_request')->where('status', 'pending')->groupBy('regid')->select('regid', DB::raw('SUM(amount) AS s'), DB::raw('COUNT(*) AS n'))->get()->keyBy('regid');
        $rows = []; $tot = ['balance' => 0.0, 'wallets' => 0, 'missing' => 0, 'pending' => 0.0];
        foreach ($regs as $r) {
            $id = (int) $r->id; $x = $w[$id] ?? null; $state = self::stateOf($r->verification_status);
            if (!$x && $state !== 'approved') continue;
            $row = ['id' => $id, 'name' => self::s($r->pn) ?: (self::s($r->rn) ?: self::s($r->email)), 'phone' => self::s($r->phone), 'state' => $state, 'suspended' => self::svc()->isSuspended($r->status),
                'wallet' => $x !== null, 'balance' => $x ? round((float) $x->balance, 2) : null, 'status' => $x ? self::s($x->status) : '', 'week' => round((float) ($week[$id] ?? 0), 2),
                'pending' => round((float) ($pend[$id]->s ?? 0), 2), 'pending_n' => (int) ($pend[$id]->n ?? 0), 'last' => CountryScope::ts($last[$id]->at ?? null), 'txns' => (int) ($last[$id]->n ?? 0)];
            $rows[] = $row;
            if ($x) { $tot['wallets']++; $tot['balance'] += $row['balance']; } else $tot['missing']++;
            $tot['pending'] += $row['pending'];
        }
        return ['ok' => true, 'rows' => $rows, 'total' => $tot, 'moves' => array_map(fn ($m) => ['sign' => $m[0], 'label' => $m[1]], self::MOVES)];
    }

    /** Add a credit or debit to a driver's wallet, with the same locking and ledger row as the app's wallet API. */
    public static function walletMove(int $id, string $kind, float $amount, string $note, array $actor): array
    {
        if (!isset(self::MOVES[$kind])) return ['ok' => false, 'message' => 'Choose what this entry is.'];
        $amount = round($amount, 2);
        $max = (float) config('admin_drivers.wallet_max_entry', 50000) ?: 50000.0;
        if ($amount <= 0) return ['ok' => false, 'message' => 'Enter an amount above 0.'];
        if ($amount > $max) return ['ok' => false, 'message' => 'One entry can be at most ₹' . number_format($max) . '.'];
        $note = trim($note);
        if (mb_strlen($note) < 3) return ['ok' => false, 'message' => 'Write the reason. The driver sees it in the wallet history.'];
        $adminId = self::actorId($actor);
        [$sign, $label] = self::MOVES[$kind];
        $r = DB::transaction(function () use ($id, $kind, $amount, $sign, $note, $adminId) {
            $w = DB::table('driver_wallet')->where('regid', $id)->lockForUpdate()->first();
            if (!$w) return ['ok' => false, 'message' => 'This driver has no wallet yet. Wallets are made when a driver is approved.'];
            if ($w->status === 'blocked') return ['ok' => false, 'message' => 'This wallet is blocked.'];
            if ($sign < 0 && (float) $w->balance < $amount) return ['ok' => false, 'message' => 'Not enough balance: the wallet has ₹' . number_format((float) $w->balance, 2) . '.'];
            $bal = round((float) $w->balance + $sign * $amount, 2); $now = self::now();
            DB::table('driver_wallet')->where('id', $w->id)->update(['balance' => $bal, 'updated_at' => $now]);
            DB::table('driver_wallet_transactions')->insert(['wallet_id' => $w->id, 'regid' => $id, 'type' => $kind, 'amount' => $amount,
                'reference_id' => 'admin-' . $adminId . '-' . date('YmdHis'), 'description' => mb_substr($note, 0, 500), 'status' => 'completed', 'created_at' => $now]);
            return ['ok' => true, 'before' => round((float) $w->balance, 2), 'balance' => $bal];
        });
        if (!$r['ok']) return $r;
        AdminAudit::log($actor, $sign > 0 ? 'credit' : 'debit', 'Drivers', 'driver_wallet', $id, $label . ' ₹' . number_format($amount, 2) . ' for driver #' . $id . ': ' . mb_substr($note, 0, 150), ['balance' => $r['before']], ['balance' => $r['balance']]);
        return ['ok' => true, 'balance' => $r['balance'], 'message' => $label . ' of ₹' . number_format($amount, 2) . ' saved. New balance ₹' . number_format($r['balance'], 2) . '.'];
    }

    /** Approved drivers without a wallet (approved before wallets were made automatically) get one, like approval does. */
    public static function createMissingWallets(array $actor): array
    {
        self::actorId($actor);
        $ids = DB::table('driver_registration')->where('verification_status', 'approved')->pluck('id')->map(fn ($v) => (int) $v)->all();
        $have = DB::table('driver_wallet')->whereIn('regid', $ids ?: [0])->pluck('regid')->map(fn ($v) => (int) $v)->all();
        $miss = array_values(array_diff($ids, $have));
        $now = self::now(); $n = 0;
        foreach ($miss as $id) {
            $n += DB::table('driver_wallet')->insertOrIgnore(['regid' => $id, 'balance' => 0, 'currency' => 'INR', 'status' => 'active', 'created_at' => $now, 'updated_at' => $now]);
        }
        if ($n) AdminAudit::log($actor, 'create', 'Drivers', 'driver_wallet', implode(',', $miss), 'Made ' . $n . ' missing driver wallet(s) (balance 0)');
        return ['ok' => true, 'message' => $n ? $n . ' wallet' . ($n === 1 ? '' : 's') . ' made with ₹0. Those drivers can now see their wallet in the app.' : 'Every approved driver already has a wallet.'];
    }

    /** delivery_boy rows of app drivers with an empty name or phone (the hubs and POS show these riders without a name). */
    public static function blankBoys(): array
    {
        if (!self::has('delivery_boy', 'regid')) return [];
        $rows = DB::table('delivery_boy')->whereNotNull('regid')->where('regid', '<>', '')->where(fn ($w) => $w->where('name', '')->orWhere('phone', ''))->get(['id', 'regid', 'name', 'phone']);
        $rows = $rows->filter(fn ($b) => ctype_digit((string) $b->regid));
        if ($rows->isEmpty()) return [];
        $names = self::appNames($rows->pluck('regid')->all());
        $phones = DB::table('driver_registration')->whereIn('id', $rows->pluck('regid')->map(fn ($v) => (int) $v)->all())->pluck('phone', 'id');
        $out = [];
        foreach ($rows as $b) {
            $upd = [];
            if (self::s($b->name) === '' && isset($names[(int) $b->regid])) $upd['name'] = mb_substr($names[(int) $b->regid], 0, 255);
            if (self::s($b->phone) === '' && self::s($phones[(int) $b->regid] ?? '') !== '') $upd['phone'] = self::s($phones[(int) $b->regid]);
            if ($upd) $out[(int) $b->id] = $upd;
        }
        return $out;
    }

    /** Copy the app name / phone into delivery_boy where they are empty. Nothing that has a value is changed. */
    public static function fillBoyNames(array $actor): array
    {
        self::actorId($actor);
        $n = 0;
        foreach (self::blankBoys() as $id => $upd) {
            $q = DB::table('delivery_boy')->where('id', $id);
            foreach (array_keys($upd) as $col) $q->where($col, '');   // only if still empty
            $n += $q->update($upd);
        }
        if ($n) AdminAudit::log($actor, 'update', 'Drivers', 'delivery_boy', '', 'Filled the name / phone of ' . $n . ' rider app delivery boy record(s) from the app');
        return ['ok' => true, 'message' => $n ? $n . ' rider record' . ($n === 1 ? '' : 's') . ' now show the driver name in the hub and POS screens.' : 'Every rider record already has a name.'];
    }

    public static function pendingCount(): int
    {
        return (int) DB::table('driver_registration')->where('verification_status', DriverManagementService::PENDING)->count();
    }
}
