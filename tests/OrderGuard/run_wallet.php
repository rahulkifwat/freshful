<?php
/* Manager wallet top-up tests. Local rig only: OG_RIG=/path/bootstrap.php php tests/OrderGuard/run_wallet.php */
$boot = getenv('OG_RIG');
if (!$boot) { fwrite(STDERR, "These tests build their own tables, so they only run in the local rig (OG_RIG).\n"); exit(0); }
require $boot;
use App\Services\Wallet\ManagerWallet;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
$p = 0; $f = 0;
function ok($c, $m) { global $p, $f; if ($c) { $p++; echo "  ok   $m\n"; } else { $f++; echo "  FAIL $m\n"; } }
app('config')->set('wallet_guard', ['roles' => ['marketing', 'operation'], 'max_each' => 2000, 'max_daily' => 5000, 'min_reason' => 5]);
Schema::create('buyers', function ($t) { $t->increments('id'); $t->string('name'); $t->string('wallet_amount')->default('0'); });
Schema::create('buyer_wallet_ledger', function ($t) { $t->increments('id'); $t->integer('buyer_id'); $t->string('event_type'); $t->string('amount'); $t->string('balance_after'); $t->string('description'); $t->string('reference'); $t->string('idempotency_key')->unique(); $t->string('created_at'); $t->string('updated_at'); });
Schema::create('wallet_transaction_history', function ($t) { $t->increments('id'); $t->integer('buyer_id'); $t->string('amount'); $t->string('type')->nullable(); $t->string('note')->nullable(); $t->string('created_at')->nullable(); });
DB::table('buyers')->insert([['name' => 'A', 'wallet_amount' => '10'], ['name' => 'B', 'wallet_amount' => '0']]);
$bal = fn($id) => (float) DB::table('buyers')->where('id', $id)->value('wallet_amount');
$ts = strtotime('2026-11-15 12:00:00');
$t = fn($role, $actor, $in, $at = null) => ManagerWallet::topup($role, $actor, $in, $at ?? $ts);
$good = ['buyer_id' => '1', 'amount' => '100', 'note' => 'Goodwill for late delivery'];

echo "a normal top-up:\n";
$r = $t('marketing', 12, $good);
ok($r['ok'] && $bal(1) == 110.0, 'balance 10 + 100 = 110 | ' . $r['message']);
$l = DB::table('buyer_wallet_ledger')->first();
ok($l && $l->event_type === 'manager_topup' && (float) $l->amount == 100.0 && (float) $l->balance_after == 110.0, 'ledger row with amount and balance_after');
ok(strpos($l->description, 'Marketing manager #12') !== false && strpos($l->description, 'Goodwill') !== false, 'ledger says who added it and why');
ok($l->reference === 'mgr:marketing:12', 'ledger reference names the role and person');
$h = DB::table('wallet_transaction_history')->first();
ok($h && $h->type === 'credit' && (float) $h->amount == 100.0, 'old history row written too');

echo "who may add:\n";
foreach (['hr', 'planning', 'production', 'customer_care', ''] as $role) { $b = $bal(1); $r = $t($role, 5, $good, $ts + 500); ok(!$r['ok'] && $bal(1) == $b, "role '$role' refused, balance unchanged"); }
ok($t('operation', 3, $good, $ts + 700)['ok'], 'operation allowed');
ok(!$t('marketing', 0, $good, $ts + 900)['ok'], 'no signed-in person refused');

echo "amount rules:\n";
foreach (['0', '0.5', '-50', '2000.01', '99999', 'abc', '', null] as $a) { $b = $bal(2); $r = $t('marketing', 12, ['buyer_id' => 2, 'amount' => $a, 'note' => 'Reason here'], $ts + 1000); ok(!$r['ok'] && $bal(2) == $b, 'amount ' . var_export($a, true) . ' refused'); }
ok($t('marketing', 12, ['buyer_id' => 2, 'amount' => '2000', 'note' => 'Exactly the limit'], $ts + 1100)['ok'] && $bal(2) == 2000.0, 'exactly 2000 allowed');
ok($t('marketing', 13, ['buyer_id' => 2, 'amount' => '12.345', 'note' => 'Odd decimals'], $ts + 1200)['ok'] && abs($bal(2) - 2012.35) < 0.001, '12.345 rounds to 12.35');

echo "customer and reason rules:\n";
foreach ([['buyer_id' => 999], ['buyer_id' => 'x'], ['buyer_id' => '1; drop'], ['buyer_id' => '1.5'], ['buyer_id' => null]] as $bad) { $r = $t('marketing', 12, $bad + ['amount' => 10, 'note' => 'A fine reason'], $ts + 1300); ok(!$r['ok'], 'customer ' . json_encode($bad['buyer_id']) . ' refused'); }
foreach ([null, '', '   ', 'ok', 'abcd'] as $n) { $b = $bal(1); $r = $t('marketing', 12, ['buyer_id' => 1, 'amount' => 10, 'note' => $n], $ts + 1400); ok(!$r['ok'] && $bal(1) == $b, 'reason ' . var_export($n, true) . ' refused'); }
ok($t('planning_is_not_allowed_but_description_key', 1, [], $ts)['ok'] === false, 'junk role refused');
$r = $t('operation', 3, ['buyer_id' => 1, 'amount' => 10, 'description' => 'From the description field'], $ts + 1500);
ok($r['ok'], 'description field accepted as the reason (Planning-style form)');

echo "double click and daily limit:\n";
$a = $t('marketing', 20, ['buyer_id' => 2, 'amount' => 50, 'note' => 'Refund order 1'], $ts + 2000);
$b = $bal(2); $c = $t('marketing', 20, ['buyer_id' => 2, 'amount' => 50, 'note' => 'Refund order 1'], $ts + 2030);
ok($a['ok'] && !$c['ok'] && $bal(2) == $b, 'same top-up in the same minute is refused, nothing added twice | ' . $c['message']);
ok($t('marketing', 20, ['buyer_id' => 2, 'amount' => 50, 'note' => 'Refund order 1'], $ts + 2100)['ok'], 'the same top-up two minutes later is allowed');
$u = 30; $sum = 0; $i = 0;
while ($sum + 2000 <= 4000) { $r = $t('marketing', $u, ['buyer_id' => 1, 'amount' => 2000, 'note' => 'Bulk ' . $i], $ts + 3000 + $i * 120); $sum += 2000; $i++; ok($r['ok'], "2000 top-up $i for the day"); }
$r = $t('marketing', $u, ['buyer_id' => 1, 'amount' => 1001, 'note' => 'Over the day'], $ts + 3500); ok(!$r['ok'], 'Rs 1001 would pass Rs 5000 for the day: refused');
ok($t('marketing', $u, ['buyer_id' => 1, 'amount' => 1000, 'note' => 'Exactly day limit'], $ts + 3600)['ok'], 'Rs 1000 reaches exactly Rs 5000: allowed');
$b = $bal(1); $r = $t('marketing', $u, ['buyer_id' => 1, 'amount' => 1, 'note' => 'One more rupee'], $ts + 4000);
ok(!$r['ok'] && $bal(1) == $b && strpos($r['message'], 'daily limit') !== false, 'Rs 5,000 per day reached, next refused | ' . $r['message']);
ok($t('marketing', 31, ['buyer_id' => 1, 'amount' => 1, 'note' => 'Another person'], $ts + 4000)['ok'], 'another person has their own daily limit');
DB::table('buyer_wallet_ledger')->update(['created_at' => '2026-11-15 12:30:00']); // the test clock is simulated, so date the rows on the simulated day
ok($t('marketing', $u, ['buyer_id' => 1, 'amount' => 1, 'note' => 'Next day is fine'], $ts + 86400 + 4000)['ok'], 'next day the limit starts again');

echo "reason optional when switched off:\n";
app('config')->set('wallet_guard.min_reason', 0);
$r = $t('operation', 3, ['buyer_id' => 2, 'amount' => 10], $ts + 90000); ok($r['ok'], 'no reason accepted when WALLET_MIN_REASON=0');
ok(strpos(DB::table('buyer_wallet_ledger')->orderByDesc('id')->value('description'), 'no reason given') !== false, 'ledger says no reason given');
app('config')->set('wallet_guard.min_reason', 5);
echo "money adds up:\n";
$ledger = (float) DB::table('buyer_wallet_ledger')->where('buyer_id', 1)->sum('amount');
ok(abs($bal(1) - (10 + $ledger)) < 0.001, 'wallet = starting 10 + sum of ledger rows (nothing lost or invented)');
$last = DB::table('buyer_wallet_ledger')->where('buyer_id', 1)->orderByDesc('id')->first();
ok(abs((float) $last->balance_after - $bal(1)) < 0.001, 'last ledger balance_after equals the wallet');
echo "\n$p passed, $f failed\n"; exit($f ? 1 : 0);
