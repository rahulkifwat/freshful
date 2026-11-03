<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// New table only. No existing table is changed.
// One row = "tell this customer when this product is back at this store".
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('storefront_stock_alerts')) {
            return;
        }
        Schema::create('storefront_stock_alerts', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('buyer_id');
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('hub_id')->default(0);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('notified_at')->nullable();
            $table->unique(['buyer_id', 'product_id', 'hub_id'], 'sf_alert_unique');
            $table->index(['product_id', 'hub_id', 'notified_at'], 'sf_alert_lookup');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('storefront_stock_alerts');
    }
};
