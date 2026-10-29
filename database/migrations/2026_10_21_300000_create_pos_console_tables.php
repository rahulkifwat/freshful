<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
| Freshful POS console tables (21 Oct 2026). NEW tables only - the orders table is not altered.
|   pos_order_events    who did what to an order (timeline + audit)
|   pos_order_weights   actual weight entered per order line and the money difference
|   pos_cash_handovers  money received at the hub (from a rider or the counter): cash / UPI / card, short or excess
|   pos_settlements     which orders each handover settled
|   pos_day_closes      day-end report submitted to head office
*/
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('pos_order_events')) {
            Schema::create('pos_order_events', function (Blueprint $t) {
                $t->bigIncrements('id');
                $t->string('order_id', 40)->index();
                $t->unsignedInteger('hub_id')->index();
                $t->string('action', 40);
                $t->text('detail')->nullable();
                $t->string('actor_guard', 30)->nullable();
                $t->unsignedInteger('actor_id')->nullable();
                $t->string('actor_name', 120)->nullable();
                $t->timestamp('created_at')->nullable()->index();
            });
        }
        if (!Schema::hasTable('pos_order_weights')) {
            Schema::create('pos_order_weights', function (Blueprint $t) {
                $t->bigIncrements('id');
                $t->string('order_id', 40)->index();
                $t->unsignedBigInteger('order_row_id')->unique();
                $t->unsignedInteger('hub_id');
                $t->unsignedInteger('product_id')->nullable();
                $t->decimal('qty', 10, 3)->default(0);
                $t->decimal('pack_g', 12, 2)->default(0);
                $t->decimal('expected_g', 12, 2)->default(0);
                $t->decimal('actual_g', 12, 2)->default(0);
                $t->decimal('pack_price', 12, 2)->default(0);
                $t->decimal('amount_diff', 12, 2)->default(0);
                $t->unsignedInteger('by_id')->nullable();
                $t->string('by_name', 120)->nullable();
                $t->timestamps();
            });
        }
        if (!Schema::hasTable('pos_cash_handovers')) {
            Schema::create('pos_cash_handovers', function (Blueprint $t) {
                $t->bigIncrements('id');
                $t->unsignedInteger('hub_id')->index();
                $t->unsignedInteger('rider_id')->nullable()->index();
                $t->unsignedInteger('orders_count')->default(0);
                $t->decimal('expected', 12, 2)->default(0);
                $t->decimal('cash_amount', 12, 2)->default(0);
                $t->decimal('upi_amount', 12, 2)->default(0);
                $t->decimal('card_amount', 12, 2)->default(0);
                $t->decimal('received', 12, 2)->default(0);
                $t->decimal('difference', 12, 2)->default(0);
                $t->string('reference', 120)->nullable();
                $t->string('note', 250)->nullable();
                $t->string('by_guard', 30)->nullable();
                $t->unsignedInteger('by_id')->nullable();
                $t->string('by_name', 120)->nullable();
                $t->timestamp('created_at')->nullable()->index();
            });
        }
        if (!Schema::hasTable('pos_settlements')) {
            Schema::create('pos_settlements', function (Blueprint $t) {
                $t->bigIncrements('id');
                $t->unsignedBigInteger('handover_id')->index();
                $t->string('order_id', 40)->unique();
                $t->unsignedInteger('hub_id')->index();
                $t->unsignedInteger('rider_id')->nullable();
                $t->string('payment_type', 30)->nullable();
                $t->decimal('bill', 12, 2)->default(0);
                $t->decimal('adjustment', 12, 2)->default(0);
                $t->decimal('expected', 12, 2)->default(0);
                $t->timestamp('created_at')->nullable();
            });
        }
        if (!Schema::hasTable('pos_day_closes')) {
            Schema::create('pos_day_closes', function (Blueprint $t) {
                $t->bigIncrements('id');
                $t->unsignedInteger('hub_id')->index();
                $t->date('business_date')->index();
                $t->longText('summary')->nullable();
                $t->decimal('cash_expected', 12, 2)->default(0);
                $t->decimal('cash_counted', 12, 2)->default(0);
                $t->decimal('difference', 12, 2)->default(0);
                $t->string('note', 500)->nullable();
                $t->string('by_guard', 30)->nullable();
                $t->unsignedInteger('by_id')->nullable();
                $t->string('by_name', 120)->nullable();
                $t->string('emailed_to', 250)->nullable();
                $t->string('email_status', 120)->nullable();
                $t->timestamps();
            });
        }
    }

    public function down(): void
    {
        // Kept on purpose: these tables hold money records. Drop by hand only if you are sure.
    }
};
