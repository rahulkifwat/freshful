<?php
/* Old order endpoint guard tests. Local rig: OG_RIG=/path/bootstrap.php php tests/OrderGuard/run_legacy.php ; on the server it boots the app (read-only: uses an in-memory copy of nothing, see note). */
$boot = getenv('OG_RIG');
if (!$boot) { fwrite(STDERR, "These tests build their own tables, so they only run in the local rig (OG_RIG).\n"); exit(0); }
require $boot;
use App\Exceptions\ApiException;
use App\Services\Orders\LegacyOrderGuard;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
$p = 0; $f = 0;
function ok($c, $m) { global $p, $f; if ($c) { $p++; echo "  ok   $m\n"; } else { $f++; echo "  FAIL $m\n"; } }
function refused($fn, $m) { try { $fn(); ok(false, "$m (was accepted)"); } catch (ApiException $e) { ok(true, "$m: \"" . $e->getMessage() . '"'); } }

Schema::create('products', function ($t) { $t->increments('id'); $t->string('product_name'); $t->string('MRP')->nullable(); $t->string('main_price')->nullable(); $t->string('status')->default('active'); });
Schema::create('hubs', function ($t) { $t->increments('id'); $t->string('hub'); $t->string('status'); $t->integer('city_id'); $t->string('all_latlng')->nullable(); $t->string('hub_lat')->nullable(); $t->string('hub_lng')->nullable(); });
Schema::create('cities', function ($t) { $t->increments('id'); $t->string('city'); });
Schema::create('delivery_price', function ($t) { $t->increments('id'); $t->string('city'); $t->string('price'); });
Schema::create('buyer_address', function ($t) { $t->increments('id'); $t->integer('buyer_id'); $t->string('latitude')->nullable(); $t->string('longitude')->nullable(); $t->string('user_lat')->nullable(); $t->string('user_lng')->nullable(); });
Schema::create('orders', function ($t) { $t->increments('id'); $t->string('order_id'); $t->integer('buyer_id'); $t->integer('product_id'); $t->string('quantity'); $t->string('date_added')->nullable(); });
DB::table('cities')->insert(['city' => 'Hyderabad']);
DB::table('hubs')->insert([['hub' => 'Basheerabad', 'status' => 'active', 'city_id' => 1, 'all_latlng' => '17.5,78.5,3', 'hub_lat' => '17.5', 'hub_lng' => '78.5'], ['hub' => 'Closed hub', 'status' => 'inactive', 'city_id' => 1, 'all_latlng' => '', 'hub_lat' => '0', 'hub_lng' => '0']]);
DB::table('delivery_price')->insert(['city' => '1', 'price' => '15']);
DB::table('products')->insert([['product_name' => 'Chicken', 'MRP' => '200', 'main_price' => '170', 'status' => 'active'], ['product_name' => 'Fish', 'MRP' => '100', 'main_price' => '0', 'status' => 'active'], ['product_name' => 'Old', 'MRP' => '50', 'main_price' => '50', 'status' => 'inactive']]);
DB::table('buyer_address')->insert([['buyer_id' => 7, 'latitude' => '17.5', 'longitude' => '78.5'], ['buyer_id' => 7, 'latitude' => '', 'longitude' => ''], ['buyer_id' => 7, 'latitude' => '28.6', 'longitude' => '77.2'], ['buyer_id' => 8, 'latitude' => '17.5', 'longitude' => '78.5']]);
$ts = strtotime('2026-11-15 12:00:00');
$in = fn($o = []) => $o + ['hub_id' => 99, 'address_id' => 1, 'total_amount' => '1', 'delivery_charge' => '0', 'payment_type' => '', 'delivery_type' => 'Express', 'status' => 'Delivered', 'order_status' => 'Delivered', 'date_added' => '2020-01-01 00:00:00', 'amount_to_collect' => '0', 'rider_id' => '5'];
$run = fn($o, $ids = ['1'], $q = ['2']) => LegacyOrderGuard::prepare(7, $in($o), $ids, $q, $ts);

echo "price, hub, delivery, status, dates come from the server:\n";
$r = $run([]); $b = $r['base'];
ok($b['total_amount'] === '340', 'total = 170 x 2 = 340 (phone said 1)');
ok($b['hub_id'] === 1, 'hub 1 from the address (phone said 99)');
ok($b['delivery_charge'] === 15, 'delivery 15 from the city price list (phone said 0)');
ok($b['amount_to_collect'] === '355', 'Cash: collect 340 + 15 = 355');
ok($b['payment_type'] === 'Cash', 'blank payment type is saved as Cash');
ok($b['order_status'] === 'Order Pending', 'status forced to Order Pending (phone said Delivered)');
ok($b['date_added'] === '2026-11-15 12:00:00', 'date is the server clock (phone said 2020)');
ok(!array_key_exists('rider_id', $b) && !array_key_exists('status', $b), 'rider_id and status are not saved');
ok($b['delivery_type'] === 'Express' && $b['schedule_time'] === '30 min', 'Express slot set by the server');
ok($r['product_ids'] === [1] && $r['quantities'] === ['2'], 'clean ids and quantities returned');
echo "MRP used when main_price is 0:\n";
ok($run([], ['2'], ['3'])['base']['total_amount'] === '300', 'Fish 100 x 3 = 300');
echo "two lines add up:\n";
ok($run([], ['1', '2'], ['1', '1'])['base']['total_amount'] === '270', '170 + 100 = 270');
echo "non-Cash types keep amount_to_collect at 0:\n";
ok($run(['payment_type' => 'Card'])['base']['amount_to_collect'] === '0', 'Card: 0 (PaymentGuard refuses it next anyway)');
echo "refusals:\n";
refused(fn() => $run([], ['1'], ['0']), 'quantity 0');
refused(fn() => $run([], ['1'], ['-3']), 'negative quantity');
refused(fn() => $run([], ['1'], ['2.5']), 'decimal quantity');
refused(fn() => $run([], ['1'], ['500']), 'quantity 500');
refused(fn() => $run([], ['1', '2'], ['1']), 'mismatched counts');
refused(fn() => $run([], ['abc'], ['1']), 'bad product id');
refused(fn() => $run([], ['999'], ['1']), 'unknown product');
refused(fn() => $run([], ['3'], ['1']), 'inactive product');
refused(fn() => $run([], [], []), 'empty order');
refused(fn() => $run(['address_id' => 4]), "someone else's address");
refused(fn() => $run(['address_id' => 3]), 'address outside every delivery area');
refused(fn() => $run(['address_id' => 2, 'hub_id' => 2]), 'address without a map point and a closed hub');
refused(fn() => $run(['address_id' => 2, 'hub_id' => 777]), 'address without a map point and a made-up hub');
ok($run(['address_id' => 2, 'hub_id' => 1])['base']['hub_id'] === 1, 'address without a map point keeps a real phone hub');
refused(fn() => $run(['delivery_type' => 'Whatever']), 'bad delivery type');
refused(fn() => $run(['delivery_type' => 'Scheduled', 'schedule_date' => '2030-01-01', 'schedule_time' => '9 AM – 11 AM']), 'schedule date far away');
$sch = $run(['delivery_type' => 'Scheduled', 'schedule_date' => '2026-11-16', 'schedule_time' => '9 AM – 11 AM']);
ok($sch['base']['delivery_type'] === 'Scheduled' && $sch['base']['schedule_date'] === '2026-11-16', 'valid scheduled slot accepted');
echo "city with no delivery price:\n";
DB::table('cities')->insert(['city' => 'NoPrice']);
DB::table('hubs')->insert(['hub' => 'Far hub', 'status' => 'active', 'city_id' => 2, 'all_latlng' => '19.0,73.0,3', 'hub_lat' => '19.0', 'hub_lng' => '73.0']);
DB::table('buyer_address')->insert(['buyer_id' => 7, 'latitude' => '19.0', 'longitude' => '73.0']);
refused(fn() => $run(['address_id' => 5]), 'hub whose city has no delivery price (not charged 0)');
DB::table('delivery_price')->insert(['city' => '2', 'price' => '25']);
ok($run(['address_id' => 5])['base']['delivery_charge'] === 25, 'once the price is set the order is accepted with that charge');
echo "double tap:\n";
DB::table('orders')->insert(['order_id' => 'ORD1', 'buyer_id' => 7, 'product_id' => 1, 'quantity' => '2', 'date_added' => '2026-11-15 11:59:50']);
refused(fn() => $run([]), 'same basket 10 seconds ago');
ok($run([], ['1'], ['3'])['base']['total_amount'] === '510', 'a different basket is fine');
DB::table('orders')->delete(); DB::table('orders')->insert(['order_id' => 'ORD1', 'buyer_id' => 7, 'product_id' => 1, 'quantity' => '2', 'date_added' => '2026-11-15 11:50:00']);
ok($run([])['base']['total_amount'] === '340', 'same basket 10 minutes ago is fine');
echo "\n$p passed, $f failed\n"; exit($f ? 1 : 0);
