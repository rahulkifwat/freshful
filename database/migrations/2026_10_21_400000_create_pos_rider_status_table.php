<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/* Freshful POS: rider duty and "back at hub" marks for smart assignment (21 Oct 2026). New table only. */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('pos_rider_status')) {
            Schema::create('pos_rider_status', function (Blueprint $t) {
                $t->unsignedInteger('rider_id')->primary();
                $t->unsignedInteger('hub_id')->index();
                $t->boolean('on_duty')->default(true);
                $t->timestamp('back_at')->nullable();
                $t->timestamp('updated_at')->nullable();
            });
        }
    }

    public function down(): void
    {
    }
};
