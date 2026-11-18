<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Customer app: the admin chooses the background colour behind each round category picture on the app home screen.
 * One nullable text column (like #FFE3D6); nothing existing changes, and an empty value means "use the default tint".
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('category', 'bg_color')) {
            Schema::table('category', function (Blueprint $table) {
                $table->string('bg_color', 9)->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('category', 'bg_color')) {
            Schema::table('category', function (Blueprint $table) {
                $table->dropColumn('bg_color');
            });
        }
    }
};
