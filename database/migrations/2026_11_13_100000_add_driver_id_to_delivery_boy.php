<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// orderlink-v1: a clean link from a delivery_boy row to the rider-app driver (driver_registration.id).
// regid is not used for this because older rows keep a phone push token in it.
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('delivery_boy', 'driver_id')) {
            Schema::table('delivery_boy', function (Blueprint $table) {
                $table->unsignedBigInteger('driver_id')->nullable()->index();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('delivery_boy', 'driver_id')) {
            Schema::table('delivery_boy', function (Blueprint $table) {
                $table->dropColumn('driver_id');
            });
        }
    }
};
