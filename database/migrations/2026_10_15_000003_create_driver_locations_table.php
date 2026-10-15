<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Latest known location per driver (one row per driver, updated in place).
 * Kept out of driver_profile because the app writes it every few seconds.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('driver_locations')) {
            return;
        }

        Schema::create('driver_locations', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('regid')->unique(); // driver_registration.id
            $table->string('address', 500)->nullable();
            $table->decimal('lat', 10, 7);
            $table->decimal('lng', 10, 7);
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('driver_locations');
    }
};
