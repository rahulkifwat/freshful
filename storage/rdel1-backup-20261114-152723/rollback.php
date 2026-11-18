<?php
chdir('/home/u182416408/domains/freshful.in/laravelapp');
require 'vendor/autoload.php'; $app = require 'bootstrap/app.php'; $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$rows = json_decode(file_get_contents('/home/u182416408/domains/freshful.in/laravelapp/storage/rdel1-backup-20261114-152723/deleted_riders.json'), true); $n = 0;
foreach ($rows as $r) { if (!Illuminate\Support\Facades\DB::table('delivery_boy')->where('id', $r['id'])->exists()) { Illuminate\Support\Facades\DB::table('delivery_boy')->insert($r); $n++; } }
echo "Put back $n rider record(s).\n";
