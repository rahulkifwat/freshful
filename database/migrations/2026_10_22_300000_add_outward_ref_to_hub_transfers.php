<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Hub console phase 3 (22 Oct 2026): optional paper outward / challan number on an inter-hub transfer
 * (the old InterHub Orders form had an "Outward Id" field). Additive only: one nullable column.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('hub_transfers') && !Schema::hasColumn('hub_transfers', 'outward_ref')) {
            Schema::table('hub_transfers', function (Blueprint $t) {
                $t->string('outward_ref', 60)->nullable()->after('ref');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('hub_transfers') && Schema::hasColumn('hub_transfers', 'outward_ref')) {
            Schema::table('hub_transfers', function (Blueprint $t) { $t->dropColumn('outward_ref'); });
        }
    }
};
