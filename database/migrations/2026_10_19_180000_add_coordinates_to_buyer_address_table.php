<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('buyer_address')) {
            return;
        }

        Schema::table('buyer_address', function (Blueprint $table) {
            if (!Schema::hasColumn('buyer_address', 'latitude')) {
                $table->decimal('latitude', 10, 7)->nullable();
            }
            if (!Schema::hasColumn('buyer_address', 'longitude')) {
                $table->decimal('longitude', 10, 7)->nullable();
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('buyer_address')) {
            return;
        }

        Schema::table('buyer_address', function (Blueprint $table) {
            $columns = [];
            if (Schema::hasColumn('buyer_address', 'latitude')) $columns[] = 'latitude';
            if (Schema::hasColumn('buyer_address', 'longitude')) $columns[] = 'longitude';
            if ($columns) $table->dropColumn($columns);
        });
    }
};
