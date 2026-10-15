<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * driver_registration.status held 'Activate' / 'active' / '1' / 'Deactivate' / '0'.
 * From now on it is only 'active' or 'deactive' (admin-controlled account status;
 * on/off duty lives in driver_profile.working_status).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('driver_registration')) {
            return;
        }

        DB::table('driver_registration')
            ->whereIn(DB::raw("LOWER(COALESCE(status, ''))"), ['deactivate', 'deactive', '0', 'inactive', ''])
            ->update(['status' => 'deactive']);
        DB::table('driver_registration')
            ->where('status', '!=', 'deactive')
            ->update(['status' => 'active']);

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE driver_registration ALTER status SET DEFAULT 'deactive'");
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('driver_registration')) {
            return;
        }

        DB::table('driver_registration')->where('status', 'active')->update(['status' => 'Activate']);
        DB::table('driver_registration')->where('status', 'deactive')->update(['status' => 'Deactivate']);

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE driver_registration ALTER status SET DEFAULT 'Deactivate'");
        }
    }
};
