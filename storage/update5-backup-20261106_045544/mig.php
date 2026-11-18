<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use Illuminate\Support\Facades\Schema; use Illuminate\Database\Schema\Blueprint;
if (! Schema::hasTable('app_stock_alerts')) { Schema::create('app_stock_alerts', function (Blueprint $t) { $t->id(); $t->unsignedBigInteger('buyer_id'); $t->unsignedBigInteger('product_id'); $t->unsignedBigInteger('hub_id')->nullable(); $t->timestamp('notified_at')->nullable(); $t->timestamps(); $t->unique(['buyer_id', 'product_id']); }); echo "table app_stock_alerts created\n"; }
if (! Schema::hasTable('app_push_tokens')) { Schema::create('app_push_tokens', function (Blueprint $t) { $t->id(); $t->unsignedBigInteger('buyer_id')->index(); $t->string('token', 190)->unique(); $t->string('platform', 12)->nullable(); $t->timestamps(); }); echo "table app_push_tokens created\n"; }
if (! Schema::hasTable('app_notifications')) { Schema::create('app_notifications', function (Blueprint $t) { $t->id(); $t->unsignedBigInteger('buyer_id')->index(); $t->string('kind', 20)->default('info'); $t->string('title', 120); $t->string('body', 240); $t->unsignedBigInteger('item_id')->nullable(); $t->timestamp('read_at')->nullable(); $t->timestamp('pushed_at')->nullable(); $t->timestamps(); }); echo "table app_notifications created\n"; }
echo "database ready\n";
