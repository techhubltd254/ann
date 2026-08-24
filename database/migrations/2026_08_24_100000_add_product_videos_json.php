<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add videos JSON column to marketplace products (stores array of video URLs)
        if (!Schema::hasColumn('products', 'videos')) {
            Schema::table('products', function (Blueprint $table) {
                $table->json('videos')->nullable()->after('video_url');
            });
        }

        // Add videos JSON column to county products
        if (!Schema::hasColumn('county_products', 'videos')) {
            Schema::table('county_products', function (Blueprint $table) {
                $table->json('videos')->nullable()->after('video_url');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('products', 'videos')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropColumn('videos');
            });
        }
        if (Schema::hasColumn('county_products', 'videos')) {
            Schema::table('county_products', function (Blueprint $table) {
                $table->dropColumn('videos');
            });
        }
    }
};