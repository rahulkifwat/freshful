<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Freshful hub console phase 2 (22 Oct 2026): inter-hub transfers and requests, hub cash book.
 * Only NEW tables, plus a "ref" column on hub_stock_moves (created in phase 1). Existing tables are not touched.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('hub_stock_moves') && !Schema::hasColumn('hub_stock_moves', 'ref')) {
            Schema::table('hub_stock_moves', function (Blueprint $t) {
                $t->string('ref', 40)->nullable()->index();
            });
        }

        if (!Schema::hasTable('hub_transfers')) {
            Schema::create('hub_transfers', function (Blueprint $t) {
                $t->bigIncrements('id');
                $t->string('ref', 30)->unique();
                $t->string('kind', 10)->default('send');          // send | request
                $t->unsignedInteger('from_hub_id')->index();       // the hub that gives the stock
                $t->unsignedInteger('to_hub_id')->index();         // the hub that receives it
                $t->string('status', 20)->index();                 // requested | declined | in_transit | received | cancelled
                $t->string('reason', 30)->nullable();              // balance | older | request | other
                $t->string('instruction', 500)->nullable();
                $t->unsignedInteger('rider_id')->nullable();
                $t->string('rider_name', 120)->nullable();
                $t->unsignedInteger('created_by_id')->nullable();
                $t->string('created_by', 120)->nullable();
                $t->unsignedInteger('sent_by_id')->nullable();
                $t->string('sent_by', 120)->nullable();
                $t->timestamp('sent_at')->nullable();
                $t->unsignedInteger('received_by_id')->nullable();
                $t->string('received_by', 120)->nullable();
                $t->timestamp('received_at')->nullable();
                $t->string('receive_note', 500)->nullable();
                $t->string('close_note', 500)->nullable();        // decline / cancel reason
                $t->timestamp('closed_at')->nullable();
                $t->timestamps();
            });
        }

        if (!Schema::hasTable('hub_transfer_items')) {
            Schema::create('hub_transfer_items', function (Blueprint $t) {
                $t->bigIncrements('id');
                $t->unsignedBigInteger('transfer_id')->index();
                $t->string('product_code', 40);
                $t->string('product_name', 160)->nullable();
                $t->string('pack', 40)->nullable();
                $t->decimal('requested_qty', 12, 3)->nullable();
                $t->decimal('sent_qty', 12, 3)->nullable();
                $t->decimal('received_qty', 12, 3)->nullable();
            });
        }

        if (!Schema::hasTable('hub_cash_entries')) {
            Schema::create('hub_cash_entries', function (Blueprint $t) {
                $t->bigIncrements('id');
                $t->unsignedInteger('hub_id')->index();
                $t->date('business_date')->index();
                $t->string('type', 20);                           // bank | cms | adjustment | opening
                $t->decimal('amount', 12, 2);                     // deposits positive; adjustment signed
                $t->string('reference', 120)->nullable();
                $t->string('receipt_path', 250)->nullable();
                $t->string('receipt_name', 160)->nullable();
                $t->string('note', 500)->nullable();
                $t->unsignedInteger('by_id')->nullable();
                $t->string('by_name', 120)->nullable();
                $t->timestamp('voided_at')->nullable();
                $t->string('void_note', 250)->nullable();
                $t->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('hub_cash_entries');
        Schema::dropIfExists('hub_transfer_items');
        Schema::dropIfExists('hub_transfers');
    }
};
