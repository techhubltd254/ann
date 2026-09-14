<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // IDEMPOTENT_GUARD: skip when table missing / changes already applied
        if (!Schema::hasTable('products')) {
            return;
        }
        try {
        if (!Schema::hasColumn('products', 'video_description')) {
            Schema::table('products', function (Blueprint $table) {
                $table->text('video_description')->nullable()->after('videos');
            });
        }
        if (!Schema::hasColumn('county_products', 'video_description')) {
            Schema::table('county_products', function (Blueprint $table) {
                $table->text('video_description')->nullable()->after('videos');
            });
        }
    
        } catch (\Throwable $e) {
            // already applied — ignore
        }
}

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('video_description');
        });
        Schema::table('county_products', function (Blueprint $table) {
            $table->dropColumn('video_description');
        });
    }
};