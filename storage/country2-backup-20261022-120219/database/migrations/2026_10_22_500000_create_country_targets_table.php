<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Freshful country console (22 Oct 2026): monthly targets for All India, a state, a city or a hub.
 * One NEW table. Nothing existing is changed.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('country_targets')) {
            Schema::create('country_targets', function (Blueprint $t) {
                $t->bigIncrements('id');
                $t->string('scope', 10);                       // india | state | city | hub
                $t->unsignedInteger('scope_id')->default(0);   // 0 for india
                $t->string('month', 7);                        // YYYY-MM
                $t->decimal('sales_target', 14, 2)->default(0);
                $t->unsignedInteger('orders_target')->default(0);
                $t->string('note', 250)->nullable();
                $t->unsignedInteger('created_by_id')->nullable();
                $t->string('created_by', 120)->nullable();
                $t->timestamps();
                $t->unique(['scope', 'scope_id', 'month']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('country_targets');
    }
};
