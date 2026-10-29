<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/* Freshful hub console: every stock change at a hub (inward accepted, stock count, adjustment). New table only. */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('hub_stock_moves')) {
            Schema::create('hub_stock_moves', function (Blueprint $t) {
                $t->bigIncrements('id');
                $t->unsignedInteger('hub_id')->index();
                $t->string('product_code', 40)->index();
                $t->string('type', 20);
                $t->decimal('before_qty', 12, 3)->default(0);
                $t->decimal('change_qty', 12, 3)->default(0);
                $t->decimal('after_qty', 12, 3)->default(0);
                $t->decimal('expected_qty', 12, 3)->nullable();
                $t->string('note', 250)->nullable();
                $t->unsignedInteger('by_id')->nullable();
                $t->string('by_name', 120)->nullable();
                $t->timestamp('created_at')->nullable()->index();
            });
        }
    }

    public function down(): void
    {
    }
};
