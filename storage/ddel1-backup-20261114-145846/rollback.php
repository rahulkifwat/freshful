<?php
chdir('/home/u182416408/domains/freshful.in/laravelapp');
require 'vendor/autoload.php'; $app = require 'bootstrap/app.php'; $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$d = json_decode(file_get_contents('/home/u182416408/domains/freshful.in/laravelapp/storage/ddel1-backup-20261114-145846/deleted_drivers.json'), true); $n = 0;
foreach ($d as $p) { $DB = 'Illuminate\\Support\\Facades\\DB';
  if (!$DB::table('driver_registration')->where('id', $p['row']['id'])->exists()) { $DB::table('driver_registration')->insert($p['row']); $n++; }
  foreach ($p['children'] as $t => $ch) { foreach ($ch['rows'] as $row) { try { $DB::table($t)->insert($row); } catch (\Throwable $e) { echo 'Could not put back a row in ' . $t . ': ' . $e->getMessage() . "\n"; } } } }
echo "Put back $n driver(s) with their rows.\n";
