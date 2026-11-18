<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use Illuminate\Support\Facades\Schema; use Illuminate\Database\Schema\Blueprint;
if (Schema::hasTable('app_home_blocks') && ! Schema::hasColumn('app_home_blocks', 'after_row')) { Schema::table('app_home_blocks', function (Blueprint $t) { $t->string('after_row', 60)->nullable(); }); echo "column after_row added\n"; }
if (! Schema::hasTable('app_stock_alerts')) { Schema::create('app_stock_alerts', function (Blueprint $t) { $t->id(); $t->unsignedBigInteger('buyer_id'); $t->unsignedBigInteger('product_id'); $t->unsignedBigInteger('hub_id')->nullable(); $t->timestamp('notified_at')->nullable(); $t->timestamps(); $t->unique(['buyer_id', 'product_id']); }); echo "table app_stock_alerts created\n"; }
echo "database ready\n";
