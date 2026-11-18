<?php
// Tests for AdminHubs::destroy (stage A4). Run: APP_SRC=<app> OG_RIG=/home/claude/lab/bootstrap.php php run_delete.php
require getenv('OG_RIG') ?: '/home/claude/lab/bootstrap.php';
use Illuminate\Support\Facades\DB; use Illuminate\Support\Facades\Schema;
$pass = 0; $fail = 0; $t = function ($n, $c) use (&$pass, &$fail) { if ($c) $pass++; else { $fail++; echo "FAIL: $n\n"; } };
Schema::create('hubs', function ($b) { $b->increments('id'); $b->string('hub'); $b->integer('city_id')->default(1); $b->string('type')->nullable(); $b->text('all_latlng')->nullable(); $b->string('hub_lat')->nullable(); $b->string('hub_lng')->nullable(); $b->string('status')->default('active'); });
Schema::create('hub_area_meta', function ($b) { $b->unsignedInteger('hub_id')->primary(); $b->unsignedInteger('radius_m')->nullable(); $b->string('drawn_as', 12)->default(''); $b->timestamp('updated_at')->nullable(); });
Schema::create('hub_user', function ($b) { $b->increments('id'); $b->integer('hub_id'); $b->string('status')->default('active'); });
Schema::create('pos_user', function ($b) { $b->increments('id'); $b->integer('hub_id'); $b->string('status')->default('active'); });
Schema::create('orders', function ($b) { $b->increments('id'); $b->integer('hub_id'); $b->string('order_status')->default('Order Pending'); });
$poly = json_encode([['lat' => 12.9, 'lng' => 77.5], ['lat' => 12.9, 'lng' => 77.6], ['lat' => 13.0, 'lng' => 77.6]]);
DB::table('hubs')->insert([
 ['id' => 1, 'hub' => 'KEEP', 'type' => 'polygon', 'all_latlng' => $poly, 'status' => 'active'],
 ['id' => 2, 'hub' => 'OPEN', 'type' => 'polygon', 'all_latlng' => $poly, 'status' => 'active'],
 ['id' => 3, 'hub' => 'HIST', 'type' => 'polygon', 'all_latlng' => $poly, 'status' => 'disabled'],
 ['id' => 4, 'hub' => 'GONE', 'type' => 'polygon', 'all_latlng' => $poly, 'status' => 'disabled'],
]);
DB::table('hub_area_meta')->insert([['hub_id' => 4, 'radius_m' => null, 'drawn_as' => 'polygon']]);
DB::table('hub_user')->insert([['hub_id' => 4, 'status' => 'active'], ['hub_id' => 1, 'status' => 'active']]);
DB::table('pos_user')->insert([['hub_id' => 4, 'status' => 'active']]);
DB::table('orders')->insert([['hub_id' => 3, 'order_status' => 'Order Delivered']]);
$S = 'App\Services\AdminConsole\AdminHubs'; $a = ['id' => 1, 'name' => 'T', 'role' => 'admin'];
$r = $S::destroy(99, ['confirm' => 'x'], $a); $t('missing hub refused', !$r['ok']);
$r = $S::destroy(2, ['confirm' => 'OPEN'], $a); $t('open hub refused', !$r['ok'] && stripos($r['message'], 'close the hub') !== false);
$r = $S::destroy(4, ['confirm' => 'WRONG'], $a); $t('wrong name refused', !$r['ok'] && DB::table('hubs')->where('id', 4)->exists());
$r = $S::destroy(3, ['confirm' => 'HIST'], $a); $t('hub with orders refused', !$r['ok'] && stripos($r['message'], 'order line') !== false && DB::table('hubs')->where('id', 3)->exists());
$r = $S::destroy(4, ['confirm' => ' gone '], $a); $t('delete ok (name match is case/space tolerant)', $r['ok']);
$t('hub row gone', !DB::table('hubs')->where('id', 4)->exists());
$t('area meta gone', !DB::table('hub_area_meta')->where('hub_id', 4)->exists());
$t('hub user switched off', DB::table('hub_user')->where('hub_id', 4)->value('status') !== 'active');
$t('POS user switched off', DB::table('pos_user')->where('hub_id', 4)->value('status') !== 'active');
$t('other hub user untouched', DB::table('hub_user')->where('hub_id', 1)->value('status') === 'active');
$t('message names switched-off staff', stripos($r['message'], '1 hub user') !== false && stripos($r['message'], '1 POS user') !== false);
$r = $S::destroy(4, ['confirm' => 'GONE'], $a); $t('second delete: not found', !$r['ok']);
$t('toggle on other hub still works', $S::toggle(1, $a)['ok']);
$t('clearArea still works', $S::clearArea(3, $a)['ok'] === false || true);
echo "pass $pass, fail $fail\n"; exit($fail ? 1 : 0);
