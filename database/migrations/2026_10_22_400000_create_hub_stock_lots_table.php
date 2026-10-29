<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Hub console phase 5 (22 Oct 2026): stock age (fresh / danger / expired) and the daily 11:00 danger-stock plan.
 *
 * hub_stock_lots  - every batch of stock in a hub with the day it arrived. inventory.live_stock stays the total;
 *                   lots only say how old that stock is. Day 0 = fresh, day 1 = danger (sell today), day 2+ = expired.
 * hub_stock_moves.lots, hub_transfer_items.lots - which batches a sale / transfer used, so a cancelled order or a
 *                   transfer keeps its real age (danger stock sent to another hub is still danger stock there).
 * hub_transfers.meet_* - the meeting point where the two hubs' riders exchange stock.
 * hub_daily_runs  - makes the 11:00 plan run once per hub per day.
 * Additive only: new tables and new nullable columns. Nothing existing is changed or removed.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('hub_stock_lots')) {
            Schema::create('hub_stock_lots', function (Blueprint $t) {
                $t->bigIncrements('id');
                $t->unsignedInteger('hub_id');
                $t->string('product_code', 40);
                $t->date('received_on');
                $t->decimal('qty_in', 12, 3)->default(0);
                $t->decimal('qty_left', 12, 3)->default(0);
                $t->string('source', 20)->nullable();          // inward | transfer_in | return | count | opening | untracked
                $t->string('ref', 40)->nullable();
                $t->timestamps();
                $t->index(['hub_id', 'product_code', 'received_on']);
            });
        }
        if (Schema::hasTable('hub_stock_moves') && !Schema::hasColumn('hub_stock_moves', 'lots')) {
            Schema::table('hub_stock_moves', function (Blueprint $t) { $t->text('lots')->nullable(); });
        }
        if (Schema::hasTable('hub_transfer_items') && !Schema::hasColumn('hub_transfer_items', 'lots')) {
            Schema::table('hub_transfer_items', function (Blueprint $t) { $t->text('lots')->nullable(); });
        }
        if (Schema::hasTable('hub_transfers') && !Schema::hasColumn('hub_transfers', 'meet_point')) {
            Schema::table('hub_transfers', function (Blueprint $t) {
                $t->string('meet_point', 160)->nullable();
                $t->decimal('meet_lat', 10, 7)->nullable();
                $t->decimal('meet_lng', 10, 7)->nullable();
            });
        }
        if (!Schema::hasTable('hub_daily_runs')) {
            Schema::create('hub_daily_runs', function (Blueprint $t) {
                $t->bigIncrements('id');
                $t->unsignedInteger('hub_id');
                $t->date('run_date');
                $t->string('kind', 30);
                $t->text('summary')->nullable();
                $t->timestamp('created_at')->nullable();
                $t->unique(['hub_id', 'run_date', 'kind']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('hub_daily_runs');
        Schema::dropIfExists('hub_stock_lots');
    }
};
