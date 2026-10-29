<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/* Freshful admin console (admin update 1, 22 Oct 2026): every change made in the new admin is written here (who, what, before, after). */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('admin_audit_log')) return;
        Schema::create('admin_audit_log', function (Blueprint $t) {
            $t->bigIncrements('id');
            $t->string('actor', 120)->default('');
            $t->string('actor_role', 30)->default('');
            $t->unsignedBigInteger('actor_id')->default(0);
            $t->string('action', 40);
            $t->string('module', 60)->default('');
            $t->string('table_name', 60)->default('');
            $t->string('record_id', 60)->default('');
            $t->string('summary', 255)->default('');
            $t->mediumText('before_json')->nullable();
            $t->mediumText('after_json')->nullable();
            $t->string('ip', 45)->default('');
            $t->timestamp('created_at')->useCurrent();
            $t->index(['table_name', 'record_id']);
            $t->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_audit_log');
    }
};
