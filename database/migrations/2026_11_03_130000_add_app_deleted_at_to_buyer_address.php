<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Customer app: deleting a saved address hides it from the customer instead of removing the row,
 * because past orders and invoices still show the address they were delivered to.
 * Adds one nullable column; nothing existing changes.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('buyer_address', 'app_deleted_at')) {
            Schema::table('buyer_address', function (Blueprint $table) {
                $table->timestamp('app_deleted_at')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('buyer_address', 'app_deleted_at')) {
            Schema::table('buyer_address', function (Blueprint $table) {
                $table->dropColumn('app_deleted_at');
            });
        }
    }
};
