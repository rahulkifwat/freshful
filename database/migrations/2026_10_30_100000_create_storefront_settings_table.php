<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// New table only. Home page settings changed from the admin (category background, HD category images).
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('storefront_settings')) {
            return;
        }
        Schema::create('storefront_settings', function (Blueprint $table) {
            $table->string('name', 100)->primary();
            $table->text('value')->nullable();
            $table->timestamp('updated_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('storefront_settings');
    }
};
