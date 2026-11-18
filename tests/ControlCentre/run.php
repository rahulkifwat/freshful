<?php
/* Freshful Control Centre update 1: tests. Run: php tests/ControlCentre/run.php  (needs the local rig, see tests/ControlCentre/README.txt)
   Uses the real Laravel query builder and schema on an in-memory SQLite database, the real migration, and the real console services. */
require getenv('CC_RIG') ?: '/home/claude/lab/bootstrap.php';

use App\Services\ControlCentre\AlertEngine;
use App\Services\ControlCentre\DataHealth;
use App\Services\ControlCentre\Metrics;
use App\Services\CountryConsole\CountryScope;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

$pass = 0; $fail = 0;
function ok($c, $m) { global $pass, $fail; if ($c) { $pass++; echo "  ok   $m\n"; } else { $fail++; echo "  FAIL $m\n"; } }
function eq($a, $b, $m) { ok($a === $b, $m . ($a === $b ? '' : ' (got ' . var_export($a, true) . ', want ' . var_export($b, true) . ')')); }
function resetCaches() { $r = new ReflectionClass(CountryScope::class); $p = $r->getProperty('cols'); $p->setAccessible(true); $p->setValue(null, []); CountryScope::forget(); CountryScope::$allowed = null; }

function build(bool $withLots = true, bool $withAudit = false, bool $withAlerts = true): void
{
    foreach (['cc_alert_events', 'cc_alerts', 'admin_audit_log', 'hub_stock_lots', 'inventory', 'orders', 'products', 'hubs', 'cities', 'states'] as $t) Schema::dropIfExists($t);
    Schema::create('states', function (Blueprint $t) { $t->increments('id'); $t->string('name'); });
    Schema::create('cities', function (Blueprint $t) { $t->increments('id'); $t->string('city'); $t->integer('state_id'); });
    Schema::create('hubs', function (Blueprint $t) { $t->increments('id'); $t->string('hub'); $t->integer('city_id'); $t->string('status')->default('Active'); });
    Schema::create('products', function (Blueprint $t) { $t->increments('id'); $t->string('product_id'); $t->string('main_price')->default('0'); $t->string('cost_price')->nullable(); });
    Schema::create('orders', function (Blueprint $t) {
        $t->increments('id'); $t->string('order_id'); $t->integer('hub_id')->nullable(); $t->integer('product_id')->nullable(); $t->string('quantity')->default('1');
        $t->string('order_status'); $t->timestamp('date_added')->nullable(); $t->string('total_amount')->default('0'); $t->string('delivery_charge')->default('0');
        $t->string('delivery_type')->nullable(); $t->string('payment_type')->nullable(); $t->integer('buyer_id')->default(1); $t->string('schedule_date')->nullable(); $t->string('schedule_time')->nullable();
        $t->string('amount_to_collect')->nullable(); $t->string('amount_to_return')->nullable(); $t->string('delivery_complete_time')->nullable(); $t->string('reason_for_order_cancel')->nullable();
    });
    Schema::create('inventory', function (Blueprint $t) { $t->increments('id'); $t->integer('hub_id'); $t->string('product_id'); $t->string('stock')->default('0'); $t->string('live_stock')->default('0'); $t->string('fresh_stock')->default('0'); $t->string('variance')->default('0'); $t->timestamp('updated_at')->nullable(); });
    if ($withLots) Schema::create('hub_stock_lots', function (Blueprint $t) { $t->bigIncrements('id'); $t->integer('hub_id'); $t->string('product_code'); $t->date('received_on'); $t->decimal('qty_in', 12, 3)->default(0); $t->decimal('qty_left', 12, 3)->default(0); });
    if ($withAudit) (require '/home/claude/src/database/migrations/2026_10_22_600000_create_admin_audit_log_table.php')->up();
    if ($withAlerts) (require '/home/claude/src/database/migrations/2026_11_15_100000_create_cc_alert_tables.php')->up();
    DB::table('states')->insert(['name' => 'Kerala']); DB::table('cities')->insert(['city' => 'Kochi', 'state_id' => 1]);
    DB::table('hubs')->insert([['hub' => 'Kochi Hub', 'city_id' => 1], ['hub' => 'Edappally Hub', 'city_id' => 1]]);
    DB::table('products')->insert([['product_id' => 'P1', 'main_price' => '100', 'cost_price' => '60'], ['product_id' => 'P2', 'main_price' => '50', 'cost_price' => null]]);
    resetCaches();
}
function order(string $id, int $hub, string $status, int $ago, array $x = []): void
{
    global $NOW;
    DB::table('orders')->insert($x + ['order_id' => $id, 'hub_id' => $hub, 'product_id' => 1, 'quantity' => '1', 'order_status' => $status, 'date_added' => date('Y-m-d H:i:s', $NOW - $ago),
        'total_amount' => '200', 'delivery_charge' => '0', 'delivery_type' => 'Express', 'payment_type' => 'Cash', 'amount_to_collect' => '0', 'amount_to_return' => '0']);
}
$NOW = strtotime('2026-11-15 12:00:00');
$H = 3600; $D = 86400;
function byRule(array $c, string $rule): array { return array_values(array_filter($c, fn ($a) => $a['rule'] === $rule)); }

echo "1. Metric definitions\n";
build(); $defs = Metrics::definitions();
foreach (['sales', 'orders', 'aov', 'cancel_rate', 'on_time', 'cash_pending', 'stock_cover', 'expired_stock', 'margin', 'forecast'] as $k) ok(isset($defs[$k]) && strlen($defs[$k][1]) > 20, "defined: $k");
$list = array_column(Metrics::listForScreen(), null, 'key');
ok($list['margin']['available'] === false && $list['margin']['why'] !== '', 'margin is labelled unavailable with a reason (no mock numbers)');
ok($list['forecast']['available'] === false, 'forecast is labelled unavailable');
ok($list['sales']['available'] === true, 'sales is available');
order('A1', 1, 'Order Delivered', 5 * $H);
$f = Metrics::freshness(); ok($f['last_order_at'] !== null && $f['now_text'] !== '', 'freshness reports latest order time and read time');

echo "2. Data health\n";
build(false);
order('B1', 1, 'Order Placed', $H, ['payment_type' => '']); order('B2', 1, 'Order Placed', $H, ['delivery_type' => null]); order('B3', 1, 'Order Placed', $H, ['product_id' => 99]); order('B4', 2, 'Order Placed', $H, ['hub_id' => 0]);
order('LEG', 1, 'Order Delivered', 200 * $D, ['product_id' => 77]);
DB::table('inventory')->insert([['hub_id' => 1, 'product_id' => 'P1', 'live_stock' => '-3'], ['hub_id' => 1, 'product_id' => 'ZZ', 'live_stock' => '4']]);
$c = array_column(DataHealth::run(), null, 'key'); resetCaches();
eq($c['pay_blank']['bad'], 1, 'one order with blank payment type found');
eq($c['type_blank']['bad'], 1, 'one order with blank delivery type found');
eq($c['product_orphan']['bad'], 1, 'one recent order line with missing product found');
ok(strpos($c['product_orphan']['detail'], 'older order lines') !== false, 'legacy orphan order lines (over 90 days) are reported separately, not counted as a problem');
eq($c['hub_missing']['bad'], 1, 'one order without hub found');
eq($c['stock_negative']['bad'], 1, 'one negative stock line found');
eq($c['stock_orphan']['bad'], 1, 'one stock line without product found');
eq($c['lots']['status'], 'na', 'missing stock-age table is reported as cannot-check, not as ok');
eq($c['cost_missing']['bad'], 1, 'product without cost price found');
$s = DataHealth::summary(array_values($c)); ok($s['overall'] === 'bad' && $s['na'] >= 1, 'summary rolls up worst status');
build(); order('C1', 1, 'Order Placed', $H); $c = array_column(DataHealth::run(), null, 'key');
eq($c['pay_blank']['status'], 'ok', 'clean data passes');

echo "3. Alert rules from real records\n";
build();
order('S1', 1, 'Order Placed', 3 * $H, ['total_amount' => '300']); order('S2', 1, 'Order Dispatched', 5 * $H, ['total_amount' => '500']);
order('S3', 2, 'Order Placed', 600);                                    // 10 minutes old: not late
order('K1', 2, 'Order Processed', 2 * $D, ['total_amount' => '700']);   // delivered 2 days ago, cash not settled
for ($i = 1; $i <= 7; $i++) order("OK$i", 1, 'Order Delivered', 3 * $H + $i * 60);
for ($i = 1; $i <= 3; $i++) order("X$i", 1, 'Order Cancel', 4 * $H + $i * 60, ['reason_for_order_cancel' => 'No stock']);
DB::table('inventory')->insert([['hub_id' => 1, 'product_id' => 'P1', 'live_stock' => '1'], ['hub_id' => 1, 'product_id' => 'P2', 'live_stock' => '500']]);
DB::table('hub_stock_lots')->insert([['hub_id' => 1, 'product_code' => 'P1', 'received_on' => date('Y-m-d', $NOW - 3 * $D), 'qty_in' => 5, 'qty_left' => 5], ['hub_id' => 1, 'product_code' => 'P2', 'received_on' => date('Y-m-d', $NOW - 3 * $D), 'qty_in' => 10, 'qty_left' => 10], ['hub_id' => 1, 'product_code' => 'P2', 'received_on' => date('Y-m-d', $NOW), 'qty_in' => 9, 'qty_left' => 9]]);
DB::table('inventory')->insert(['hub_id' => 1, 'product_id' => 'OLD9', 'live_stock' => '50']); DB::table('hub_stock_lots')->insert(['hub_id' => 1, 'product_code' => 'OLD9', 'received_on' => date('Y-m-d', $NOW - 5 * $D), 'qty_in' => 50, 'qty_left' => 50]);
$cand = AlertEngine::candidates($NOW);
$st = byRule($cand, 'stuck_orders'); eq(count($st), 1, 'stuck orders: one alert (hub 1 only; the 10-minute-old order is not late)');
eq($st[0]['records'] ?? 0, 2, 'stuck orders: 2 records'); eq($st[0]['impact_value'] ?? 0, 800.0, 'stuck orders: impact is the order value 300 + 500');
$cp = byRule($cand, 'cash_pending'); eq(count($cp), 1, 'cash pending: one alert'); eq($cp[0]['impact_value'] ?? 0, 700.0, 'cash pending: impact 700'); eq($cp[0]['hub_id'] ?? 0, 2, 'cash pending: hub 2');
$cr = byRule($cand, 'cancel_rate'); eq(count($cr), 1, 'cancel rate: one alert (3 of 12 = 25%)'); ok(strpos($cr[0]['title'] ?? '', '25%') !== false, 'cancel rate: states 25%'); eq($cr[0]['confidence'] ?? '', 'low', 'cancel rate: low confidence on 12 orders');
$ex = byRule($cand, 'expired_stock'); eq(count($ex), 1, 'expired stock: one alert for the hub'); eq($ex[0]['records'] ?? 0, 2, 'expired stock: 2 products (an old stock line for a product no longer in the catalogue is ignored)'); eq($ex[0]['impact_value'] ?? 0, 600.0, 'expired stock: P1 capped at live stock 1 x Rs 100, plus P2 10 x Rs 50 = 600');
DB::table('inventory')->where('product_id', 'P1')->update(['live_stock' => '0']); DB::table('inventory')->where('product_id', 'P2')->update(['live_stock' => '9']);
eq(count(byRule(AlertEngine::candidates($NOW), 'expired_stock')), 0, 'expired stock: none when live stock is 0 or only the new lot is left (lot table out of date)');
DB::table('inventory')->where('product_id', 'P1')->update(['live_stock' => '1']); DB::table('inventory')->where('product_id', 'P2')->update(['live_stock' => '500']);
foreach ($cand as $a) ok(!empty($a['next_step']) && !empty($a['title']) && isset($a['confidence']), 'alert has title, next step, confidence: ' . $a['fingerprint']);
$lc = byRule($cand, 'low_cover'); eq(count($lc), 1, 'low cover: one alert (P1 sells 11 in 7 days, stock 1; P2 has no sales and 500 in stock)'); eq($lc[0]['records'] ?? 0, 1, 'low cover: only P1 counted');
DB::table('inventory')->where('product_id', 'P1')->update(['live_stock' => '100']);
eq(count(byRule(AlertEngine::candidates($NOW), 'low_cover')), 0, 'low cover: clears when stock is enough');
DB::table('inventory')->where('product_id', 'P1')->update(['live_stock' => '1']);
ok(count(array_filter($cand, fn ($a) => $a['fingerprint'] === 'data_quality|cost_missing')) === 0, 'missing cost price stays on the Data health page, not an alert');

echo "4. Scan, actions and audit trail\n";
$r = AlertEngine::scan($NOW); ok($r['ok'] && $r['new'] >= 5, 'first scan creates alerts: ' . json_encode($r));
$r2 = AlertEngine::scan($NOW); eq($r2['new'], 0, 'second scan creates no duplicates'); eq($r2['resolved'], 0, 'second scan resolves nothing');
$l = AlertEngine::list(); $id = $l['alerts'][0]['id']; ok($l['ok'] && count($l['alerts']) >= 5, 'list returns active alerts'); ok($l['alerts'][0]['severity'] === 'bad' || $l['alerts'][0]['severity'] === 'warn', 'sorted by severity');
$actor = ['id' => 7, 'name' => 'Anaikar', 'role' => 'admin'];
ok(!AlertEngine::act($id, 'dismiss', $actor, ['note' => ''], $NOW)['ok'], 'dismiss without a reason is refused');
ok(!AlertEngine::act($id, 'assign', $actor, ['assignee' => ''], $NOW)['ok'], 'assign without a name is refused');
ok(!AlertEngine::act($id, 'delete', $actor, [], $NOW)['ok'], 'unknown action refused');
ok(!AlertEngine::act(99999, 'acknowledge', $actor, [], $NOW)['ok'], 'missing alert refused');
ok(AlertEngine::act($id, 'acknowledge', $actor, [], $NOW)['ok'], 'acknowledge works');
ok(!AlertEngine::act($id, 'acknowledge', $actor, [], $NOW)['ok'], 'acknowledge twice refused');
ok(AlertEngine::act($id, 'assign', $actor, ['assignee' => 'Hub manager Kochi'], $NOW)['ok'], 'assign works');
eq(DB::table('cc_alerts')->where('id', $id)->value('assigned_name'), 'Hub manager Kochi', 'assignee saved');
$id2 = $l['alerts'][1]['id']; ok(AlertEngine::act($id2, 'dismiss', $actor, ['note' => 'Known, hub closed for repairs'], $NOW)['ok'], 'dismiss with reason works');
AlertEngine::scan($NOW);
eq(DB::table('cc_alerts')->where('id', $id2)->value('status'), 'dismissed', 'dismissed alert stays dismissed after a new scan');
ok(is_int(AlertEngine::list()['last_scan']), 'last scan time is a unix time the screen can show'); $h = AlertEngine::history($id); ok(is_int($h['events'][0]['at'] ?? null), 'history times are unix times'); ok($h['ok'] && count($h['events']) >= 3 && $h['events'][0]['actor'] === 'Anaikar', 'history shows who did what: ' . count($h['events']) . ' events');
ok(AlertEngine::act($id2, 'reopen', $actor, [], $NOW)['ok'], 'reopen works');
// condition clears -> resolved; returns -> reopened
$fp = DB::table('cc_alerts')->where('rule', 'cash_pending')->first();
DB::table('orders')->where('order_id', 'K1')->update(['order_status' => 'Order Delivered']);
$r3 = AlertEngine::scan($NOW); ok($r3['resolved'] >= 1, 'alert is resolved when its condition clears');
eq(DB::table('cc_alerts')->where('id', $fp->id)->value('status'), 'resolved', 'cash alert shows resolved');
DB::table('orders')->where('order_id', 'K1')->update(['order_status' => 'Order Processed']);
$r4 = AlertEngine::scan($NOW); ok($r4['reopened'] >= 1, 'alert reopens when the condition returns');
build(true, true); order('Q1', 1, 'Order Placed', 3 * $H); AlertEngine::scan($NOW); $x = AlertEngine::list()['alerts'][0]['id'];
AlertEngine::act($x, 'acknowledge', $actor, [], $NOW);
eq(DB::table('admin_audit_log')->where('module', 'control_centre')->count(), 1, 'action is also written to the admin audit log');

build(); order('F1', 1, 'Order Placed', 3 * $H);
$e1 = AlertEngine::ensureFresh(600, $NOW); ok($e1 !== null && $e1['ok'] && $e1['new'] >= 1, 'opening the Alert centre scans when alerts were never made');
eq(AlertEngine::ensureFresh(600, $NOW + 60), null, 'no new scan within 10 minutes');
ok(AlertEngine::ensureFresh(600, $NOW + 1200) !== null, 'scans again after 10 minutes');
echo "5. Empty data, ranges and failures\n";
build(); DB::table('products')->delete(); $e = AlertEngine::scan($NOW); ok($e['ok'] && $e['found'] === 0, 'empty database: scan finds nothing and does not fail');
eq(AlertEngine::list()['alerts'], [], 'empty database: empty list');
build(); order('OLD', 1, 'Order Placed', 20 * $D); $c = AlertEngine::candidates($NOW); eq(count(byRule($c, 'stuck_orders')), 0, 'orders older than the 7 day window are outside the alert period');
build(true, false, false); $m = AlertEngine::list(); ok($m['ok'] === false && $m['message'] !== '', 'alert tables missing: clear message, no crash');
ok(AlertEngine::scan($NOW)['ok'] === false, 'scan without tables: clear message');
build(false); order('Z1', 1, 'Order Placed', 3 * $H); ok(count(AlertEngine::candidates($NOW)) >= 1, 'no stock-age table: other alerts still work');
build(); CountryScope::$allowed = [2]; CountryScope::forget(); order('H1', 1, 'Order Placed', 3 * $H); order('H2', 2, 'Order Placed', 3 * $H);
$c = AlertEngine::candidates($NOW, (new CountryScope([]))->ids()); ok(count($c) === 1 && $c[0]['hub_id'] === 2, 'hub scope: a person limited to hub 2 sees only hub 2 alerts'); CountryScope::$allowed = null; resetCaches();

echo "6. Access and known columns (source checks)\n";
$ctl = file_get_contents('/home/claude/src/app/Http/Controllers/ControlCentre/ControlCentreController.php');
preg_match_all('/public function (\w+)\(Request \$r[^)]*\)\s*\{\s*if \(\$g = \$this->guard\(\$r\)\) return \$g;/', $ctl, $guarded); preg_match_all('/public function (\w+)\(/', $ctl, $all);
eq(count($guarded[1]), count($all[1]), 'every controller method starts with the admin guard (' . implode(',', $all[1]) . ')');
ok(strpos($ctl, "'admin'") !== false, 'guard checks the admin role');
$routes = file_get_contents('/home/claude/src/routes/control_centre.php'); ok(strpos($routes, 'adc.ad.cc.') !== false && strpos($routes, 'Route::post') !== false && substr_count($routes, 'throttle') >= 2, 'write routes are throttled');
$src = '';
foreach (['Metrics', 'DataHealth', 'AlertEngine'] as $n) $src .= file_get_contents('/home/claude/src/app/Services/ControlCentre/' . $n . '.php'); // update 1 files; the AI client has its own checks in run_ai.php
ok(!preg_match('/orders\.created_at|o\.created_at/', $src), 'never uses orders.created_at (the real date column is date_added)');
ok(strpos($src, 'date_added') !== false, 'uses date_added');
ok(!preg_match('/api[_-]?key|sk-|Authorization|curl_/i', $src . file_get_contents('/home/claude/src/resources/views/country_console/assets/cx.js')), 'no provider keys or outbound AI calls in this update');
ok(!preg_match('/->(delete|update|insert)\(/', file_get_contents('/home/claude/src/app/Services/ControlCentre/DataHealth.php') . file_get_contents('/home/claude/src/app/Services/ControlCentre/Metrics.php')), 'data health and metrics are read only');
$mig = file_get_contents('/home/claude/src/database/migrations/2026_11_15_100000_create_cc_alert_tables.php');
ok(strpos($mig, 'dropIfExists(\'cc_alerts\')') !== false && strpos($mig, 'hasTable') !== false, 'migration is additive (hasTable guard) and reversible (down drops only its tables)');
$m = require '/home/claude/src/database/migrations/2026_11_15_100000_create_cc_alert_tables.php'; build(); $m->down(); resetCaches(); ok(!Schema::hasTable('cc_alerts') && !Schema::hasTable('cc_alert_events') && Schema::hasTable('orders'), 'rollback removes only the two new tables'); $m->up(); $m->up(); resetCaches(); ok(Schema::hasTable('cc_alerts'), 'migration can run twice safely');

echo "\n$pass passed, $fail failed\n"; exit($fail ? 1 : 0);
