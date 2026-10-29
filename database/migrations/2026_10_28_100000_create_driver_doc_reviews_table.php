<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
| Freshful drivers update 1 (28 Oct 2026): the head office's check of each item a driver sends
| (photo, personal information, Aadhaar, licence, PAN, vehicle, bank). New table only; no existing
| table is changed. sig = fingerprint of what was checked, so a later re-upload shows "changed since checked".
*/
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('driver_doc_reviews')) return;
        Schema::create('driver_doc_reviews', function (Blueprint $t) {
            $t->bigIncrements('id');
            $t->unsignedInteger('regid');
            $t->string('doc', 30);
            $t->string('decision', 10);
            $t->string('note', 255)->default('');
            $t->string('sig', 32)->default('');
            $t->unsignedInteger('actor_id')->default(0);
            $t->string('actor_name', 120)->default('');
            $t->timestamp('created_at')->nullable();
            $t->timestamp('updated_at')->nullable();
            $t->unique(['regid', 'doc']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('driver_doc_reviews');
    }
};
