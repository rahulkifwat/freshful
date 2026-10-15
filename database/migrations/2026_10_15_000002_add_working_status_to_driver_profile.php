<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Driver on/off duty, set by the driver app (POST /api/driver/driver/working-status). */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('driver_profile') || Schema::hasColumn('driver_profile', 'working_status')) {
            return;
        }

        Schema::table('driver_profile', function (Blueprint $table) {
            $table->string('working_status', 20)->default('off_duty')->after('language');
            $table->dateTime('working_status_at')->nullable()->after('working_status');
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('driver_profile') && Schema::hasColumn('driver_profile', 'working_status')) {
            Schema::table('driver_profile', function (Blueprint $table) {
                $table->dropColumn(['working_status', 'working_status_at']);
            });
        }
    }
};
