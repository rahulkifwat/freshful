<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/* Freshful Control Centre update 2 (15 Nov 2026). Additive: one NEW table, nothing existing is changed.
   Rollback: php artisan migrate:rollback --path=database/migrations/2026_11_15_110000_create_cc_ai_log_table.php */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('cc_ai_log')) return;
        Schema::create('cc_ai_log', function (Blueprint $t) {
            $t->bigIncrements('id');
            $t->string('actor', 120)->default('');
            $t->unsignedBigInteger('actor_id')->default(0);
            $t->string('actor_role', 30)->default('');
            $t->string('question', 500);
            $t->string('mode', 10)->default('numbers');      // numbers | ai
            $t->string('provider', 20)->default('');
            $t->string('status', 10)->default('ok');         // ok | fallback | refused | error
            $t->string('note', 300)->nullable();             // safe error text only, never a key or a raw response
            $t->date('period_from')->nullable();
            $t->date('period_to')->nullable();
            $t->string('scope', 200)->nullable();
            $t->string('confidence', 10)->nullable();
            $t->unsignedSmallInteger('sources')->default(0);
            $t->mediumText('answer')->nullable();
            $t->timestamp('created_at')->useCurrent();
            $t->index(['actor_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cc_ai_log');
    }
};
