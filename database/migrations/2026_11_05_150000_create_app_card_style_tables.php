<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// New tables only. Admin > Marketing > App control: product card style and per-tag card colours. Nothing existing is changed.
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('app_card_style')) {
            Schema::create('app_card_style', function (Blueprint $t) {
                $t->id();
                $t->string('skey', 40)->unique();
                $t->string('svalue', 120)->nullable();
                $t->timestamps();
            });
        }
        if (! Schema::hasTable('app_card_tags')) {
            Schema::create('app_card_tags', function (Blueprint $t) {
                $t->id();
                $t->string('tag', 60)->unique();
                $t->string('color_from', 9);
                $t->string('color_to', 9);
                $t->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('app_card_style');
        Schema::dropIfExists('app_card_tags');
    }
};
