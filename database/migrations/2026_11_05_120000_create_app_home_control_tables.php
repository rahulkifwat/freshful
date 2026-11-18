<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// New tables only. Admin > Marketing > App home blocks / App header colour. Nothing existing is changed.
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('app_home_blocks')) {
            Schema::create('app_home_blocks', function (Blueprint $t) {
                $t->id();
                $t->string('type', 30);
                $t->string('title', 120)->nullable();
                $t->string('subtitle', 160)->nullable();
                $t->string('link', 120)->nullable();
                $t->string('block_key', 60)->nullable();
                $t->text('items_text')->nullable();
                $t->unsignedInteger('position')->default(0);
                $t->string('starts_on', 10)->nullable();
                $t->string('ends_on', 10)->nullable();
                $t->enum('status', ['active', 'inactive'])->default('active');
                $t->timestamps();
            });
        }
        if (! Schema::hasTable('app_home_theme')) {
            Schema::create('app_home_theme', function (Blueprint $t) {
                $t->id();
                $t->string('title', 120)->nullable();
                $t->string('color_from', 9)->nullable();
                $t->string('color_to', 9)->nullable();
                $t->string('text_color', 10)->default('light');
                $t->string('starts_on', 10)->nullable();
                $t->string('ends_on', 10)->nullable();
                $t->enum('status', ['active', 'inactive'])->default('active');
                $t->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('app_home_blocks');
        Schema::dropIfExists('app_home_theme');
    }
};
