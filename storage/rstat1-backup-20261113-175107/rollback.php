<?php
chdir('/home/u182416408/domains/freshful.in/laravelapp');
require 'vendor/autoload.php'; $app = require 'bootstrap/app.php'; $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
foreach (array (
  9 => 'active',
  10 => 'active',
  11 => 'active',
  12 => 'active',
  14 => 'active',
) as $id => $st) { Illuminate\Support\Facades\DB::table('delivery_boy')->where('id', $id)->update(['status' => $st]); }
echo "Switched the copies back on.\n";
