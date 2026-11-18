<?php
// Tests for AdminHubs::clearArea (stage A). Run: APP_SRC=<app copy> OG_RIG=/home/claude/lab/bootstrap.php php run_hubs.php
require getenv('OG_RIG') ?: '/home/claude/lab/bootstrap.php';
use Illuminate\Support\Facades\DB; use Illuminate\Support\Facades\Schema;
$pass = 0; $fail = 0; $t = function ($n, $c) use (&$pass, &$fail) { if ($c) $pass++; else { $fail++; echo "FAIL: $n\n"; } };
Schema::create('hubs', function ($b) { $b->increments('id'); $b->string('hub'); $b->integer('city_id')->default(1); $b->string('type')->nullable(); $b->text('all_latlng')->nullable(); $b->string('hub_lat')->nullable(); $b->string('hub_lng')->nullable(); $b->string('status')->default('Active'); });
Schema::create('hub_area_meta', function ($b) { $b->unsignedInteger('hub_id')->primary(); $b->unsignedInteger('radius_m')->nullable(); $b->string('drawn_as', 12)->default(''); $b->timestamp('updated_at')->nullable(); });
$poly = json_encode([['lat' => 12.9, 'lng' => 77.5], ['lat' => 12.9, 'lng' => 77.6], ['lat' => 13.0, 'lng' => 77.6]]);
DB::table('hubs')->insert(['hub' => 'A', 'type' => 'polygon', 'all_latlng' => $poly, 'status' => 'Deactive']);
DB::table('hubs')->insert(['hub' => 'B', 'type' => 'polygon', 'all_latlng' => $poly, 'status' => 'Active']);
DB::table('hubs')->insert(['hub' => 'C', 'type' => '', 'all_latlng' => '', 'status' => 'Deactive']);
DB::table('hub_area_meta')->insert(['hub_id' => 1, 'radius_m' => 2000, 'drawn_as' => 'circle']);
$on = App\Services\AdminConsole\AdminRecords::statusValues('hubs')[0]; $off = App\Services\AdminConsole\AdminRecords::statusValues('hubs')[1];
DB::table('hubs')->where('id', 1)->update(['status' => $off]); DB::table('hubs')->where('id', 2)->update(['status' => $on]); DB::table('hubs')->where('id', 3)->update(['status' => $off]);
$S = 'App\Services\AdminConsole\AdminHubs'; $actor = ['id' => 1, 'name' => 'T', 'role' => 'admin'];
$r = $S::clearArea(99, $actor); $t('missing hub', !$r['ok']);
$r = $S::clearArea(2, $actor); $t('open hub refused', !$r['ok'] && stripos($r['message'], 'close the hub') !== false); $t('open hub untouched', DB::table('hubs')->where('id', 2)->value('all_latlng') === $poly);
$r = $S::clearArea(3, $actor); $t('hub without area refused', !$r['ok']);
$r = $S::clearArea(1, $actor); $t('closed hub cleared', $r['ok']);
$h = DB::table('hubs')->where('id', 1)->first(); $t('area emptied', $h->all_latlng === '' && $h->type === ''); $t('name/city/status kept', $h->hub === 'A' && $h->status === $off);
$m = DB::table('hub_area_meta')->where('hub_id', 1)->first(); $t('meta radius cleared', $m->radius_m === null && $m->drawn_as === '');
$t('shape now none', $S::shape($h->type, $h->all_latlng)['kind'] === null);
$r = $S::clearArea(1, $actor); $t('second remove refused', !$r['ok']);
$t('toggle on refused without area', !$S::toggle(1, $actor)['ok']);
echo "pass $pass, fail $fail\n"; exit($fail ? 1 : 0);
