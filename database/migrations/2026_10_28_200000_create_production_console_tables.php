<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
| Freshful production console (production update 1, 28 Oct 2026). New tables only; no existing table is changed.
|   production_plan_lines   - how many packs the production manager plans for each hub and product on a date
|   production_batches      - one "send to hubs" (a dispatch note), numbered PB-YYMMDD-NNN
|   production_batch_lines  - each product and hub in a dispatch; the hub receives it in its console (inventory.stock -> live)
|   production_pending_clears - old waiting-at-hub quantities cleared by production (each can be put back)
*/
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('production_plan_lines')) {
            Schema::create('production_plan_lines', function (Blueprint $t) {
                $t->bigIncrements('id');
                $t->date('plan_date');
                $t->unsignedInteger('hub_id');
                $t->string('product_code', 40);
                $t->decimal('suggested_qty', 12, 3)->default(0);
                $t->decimal('planned_qty', 12, 3)->default(0);
                $t->string('note', 250)->nullable();
                $t->unsignedInteger('by_id')->nullable();
                $t->string('by_name', 120)->nullable();
                $t->timestamps();
                $t->unique(['plan_date', 'hub_id', 'product_code'], 'ppl_date_hub_code');
                $t->index(['plan_date']);
            });
        }
        if (!Schema::hasTable('production_batches')) {
            Schema::create('production_batches', function (Blueprint $t) {
                $t->bigIncrements('id');
                $t->string('batch_no', 30)->unique();
                $t->string('client_ref', 40)->nullable()->unique();   // the screen's one-time key, so a repeated click or retry never sends twice
                $t->date('plan_date')->nullable();
                $t->string('status', 20)->default('sent');          // sent | partly | received | cancelled
                $t->unsignedInteger('lines')->default(0);
                $t->unsignedInteger('hubs')->default(0);
                $t->decimal('packs', 14, 3)->default(0);
                $t->decimal('value', 14, 2)->default(0);
                $t->string('vehicle', 60)->nullable();
                $t->string('note', 250)->nullable();
                $t->unsignedInteger('by_id')->nullable();
                $t->string('by_name', 120)->nullable();
                $t->timestamps();
                $t->index(['created_at']);
                $t->index(['status']);
            });
        }
        if (!Schema::hasTable('production_batch_lines')) {
            Schema::create('production_batch_lines', function (Blueprint $t) {
                $t->bigIncrements('id');
                $t->unsignedBigInteger('batch_id');
                $t->unsignedInteger('hub_id');
                $t->string('product_code', 40);
                $t->decimal('qty', 12, 3);
                $t->decimal('price', 12, 2)->default(0);
                $t->decimal('pending_before', 12, 3)->default(0);    // what was already waiting at the hub when this was sent
                $t->string('status', 20)->default('sent');          // sent | received | cancelled
                $t->decimal('received_qty', 12, 3)->nullable();
                $t->timestamp('received_at')->nullable();
                $t->unsignedBigInteger('move_id')->nullable();       // hub_stock_moves row of the hub's receipt
                $t->unsignedBigInteger('moves_before')->default(0);  // last hub_stock_moves id when sent: only later receipts can match this line
                $t->string('cancel_note', 250)->nullable();
                $t->timestamp('cancelled_at')->nullable();
                $t->timestamps();
                $t->index(['batch_id']);
                $t->index(['hub_id', 'product_code', 'status'], 'pbl_hub_code_status');
            });
        }

        if (!Schema::hasTable('production_pending_clears')) {
            // old "waiting at hub" quantities (inventory.stock) cleared by production; kept so each can be put back
            Schema::create('production_pending_clears', function (Blueprint $t) {
                $t->bigIncrements('id');
                $t->unsignedInteger('inventory_id');
                $t->unsignedInteger('hub_id');
                $t->string('product_code', 40);
                $t->decimal('qty', 12, 3);
                $t->timestamp('waiting_since')->nullable();
                $t->string('note', 250);
                $t->unsignedInteger('by_id')->nullable();
                $t->string('by_name', 120)->nullable();
                $t->timestamp('restored_at')->nullable();
                $t->string('restored_by', 120)->nullable();
                $t->timestamps();
                $t->index(['hub_id', 'product_code']);
                $t->index(['created_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('production_pending_clears');
        Schema::dropIfExists('production_batch_lines');
        Schema::dropIfExists('production_batches');
        Schema::dropIfExists('production_plan_lines');
    }
};
