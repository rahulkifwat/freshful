<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Customer app online payments. One row per Razorpay order the app started. The order is saved only after the payment is
 * verified; this row remembers what the customer chose (address, coupon, delivery time) in between, and what became of it:
 *   created -> placed (order saved) | refunded_wallet (paid but the order could no longer be placed: money added to the wallet).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('app_payment_intents')) {
            Schema::create('app_payment_intents', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('buyer_id')->index();
                $table->string('razorpay_order_id', 64)->unique();
                $table->string('razorpay_payment_id', 64)->nullable()->unique();
                $table->unsignedInteger('amount_paise');
                $table->unsignedBigInteger('address_id');
                $table->string('coupon_code', 40)->nullable();
                $table->string('delivery_type', 12);
                $table->string('schedule_date', 12)->default('');
                $table->string('schedule_time', 30)->default('');
                $table->string('status', 20)->default('created');
                $table->string('order_id', 40)->nullable();
                $table->string('note', 255)->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('app_payment_intents');
    }
};
