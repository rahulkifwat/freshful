<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('buyers')) {
            Schema::table('buyers', function (Blueprint $table) {
                if (!Schema::hasColumn('buyers', 'wallet_amount')) $table->decimal('wallet_amount', 12, 2)->default(0);
                if (!Schema::hasColumn('buyers', 'referral_code')) $table->string('referral_code', 24)->nullable()->unique();
                if (!Schema::hasColumn('buyers', 'referred_by_buyer_id')) $table->unsignedBigInteger('referred_by_buyer_id')->nullable()->index();
            });
        }

        if (!Schema::hasTable('buyer_wallet_topups')) {
            Schema::create('buyer_wallet_topups', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('buyer_id')->index();
                $table->unsignedInteger('amount_paise');
                $table->string('currency', 3)->default('INR');
                $table->string('razorpay_order_id')->unique();
                $table->string('razorpay_payment_id')->nullable()->unique();
                $table->string('status', 20)->default('pending')->index();
                $table->timestamp('paid_at')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('buyer_wallet_ledger')) {
            Schema::create('buyer_wallet_ledger', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('buyer_id')->index();
                $table->string('event_type', 32);
                $table->decimal('amount', 12, 2);
                $table->decimal('balance_after', 12, 2);
                $table->string('description', 180);
                $table->string('reference', 100)->nullable();
                $table->string('idempotency_key', 140)->unique();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('buyer_referrals')) {
            Schema::create('buyer_referrals', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('referrer_buyer_id')->index();
                $table->unsignedBigInteger('referred_buyer_id')->unique();
                $table->string('referral_code', 24);
                $table->decimal('reward_amount', 12, 2)->default(150);
                $table->string('status', 20)->default('pending')->index();
                $table->string('qualifying_order_id')->nullable();
                $table->timestamp('rewarded_at')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('buyer_referrals');
        Schema::dropIfExists('buyer_wallet_ledger');
        Schema::dropIfExists('buyer_wallet_topups');
        // Keep buyer data/columns on rollback; dropping referral or wallet balances is destructive.
    }
};
