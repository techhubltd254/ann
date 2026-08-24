<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add video_url to marketplace products
        if (!Schema::hasColumn('products', 'video_url')) {
            Schema::table('products', function (Blueprint $table) {
                $table->string('video_url')->nullable()->after('description');
            });
        }

        // Add video_url to county products
        if (!Schema::hasColumn('county_products', 'video_url')) {
            Schema::table('county_products', function (Blueprint $table) {
                $table->string('video_url')->nullable()->after('image_url');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('products', 'video_url')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropColumn('video_url');
            });
        }
        if (Schema::hasColumn('county_products', 'video_url')) {
            Schema::table('county_products', function (Blueprint $table) {
                $table->dropColumn('video_url');
            });
        }
    }
};