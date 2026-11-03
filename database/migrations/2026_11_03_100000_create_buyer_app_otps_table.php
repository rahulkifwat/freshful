<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('buyer_app_otps')) {
            return;
        }

        Schema::create('buyer_app_otps', function (Blueprint $table) {
            $table->id();
            $table->string('phone', 15);
            $table->string('purpose', 20)->default('login');
            $table->string('code_hash', 64);          // HMAC of phone|code, never the code
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->unsignedBigInteger('expires_ts');
            $table->unsignedBigInteger('consumed_ts')->nullable();
            $table->unsignedBigInteger('created_ts');
            $table->string('ip', 45)->nullable();

            $table->index(['phone', 'consumed_ts'], 'buyer_app_otps_phone_open');
            $table->index('created_ts', 'buyer_app_otps_created');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('buyer_app_otps');
    }
};
