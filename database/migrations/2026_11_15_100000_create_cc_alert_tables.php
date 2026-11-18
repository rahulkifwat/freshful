<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/* Freshful Control Centre update 1 (15 Nov 2026). Additive only: two NEW tables, nothing existing is changed.
   Rollback: php artisan migrate:rollback --path=database/migrations/2026_11_15_100000_create_cc_alert_tables.php */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('cc_alerts')) {
            Schema::create('cc_alerts', function (Blueprint $t) {
                $t->bigIncrements('id');
                $t->string('fingerprint', 120)->unique();
                $t->string('rule', 40);
                $t->string('severity', 10)->default('warn');       // bad | warn | info
                $t->string('title', 200);
                $t->text('detail')->nullable();
                $t->decimal('impact_value', 14, 2)->nullable();    // rupees, when it can be measured
                $t->string('impact_label', 120)->nullable();
                $t->string('confidence', 10)->default('medium');   // high | medium | low
                $t->date('period_from')->nullable();
                $t->date('period_to')->nullable();
                $t->unsignedInteger('hub_id')->nullable();
                $t->unsignedInteger('records')->default(0);
                $t->string('link', 60)->nullable();                // console screen key that lists the records
                $t->string('next_step', 255)->nullable();
                $t->string('status', 15)->default('open');         // open | acknowledged | dismissed | resolved
                $t->unsignedBigInteger('assigned_to')->nullable();
                $t->string('assigned_name', 120)->nullable();
                $t->timestamp('first_seen')->nullable();
                $t->timestamp('last_seen')->nullable();
                $t->timestamp('updated_at')->nullable();
                $t->index(['status', 'severity']);
                $t->index('hub_id');
            });
        }
        if (!Schema::hasTable('cc_alert_events')) {
            Schema::create('cc_alert_events', function (Blueprint $t) {
                $t->bigIncrements('id');
                $t->unsignedBigInteger('alert_id');
                $t->string('action', 20);                          // raised | reopened | resolved | acknowledge | assign | dismiss | reopen
                $t->string('actor', 120)->default('system');
                $t->string('actor_role', 30)->default('');
                $t->string('note', 300)->nullable();
                $t->timestamp('created_at')->useCurrent();
                $t->index('alert_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('cc_alert_events');
        Schema::dropIfExists('cc_alerts');
    }
};
