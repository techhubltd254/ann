<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        Schema::table('county_tourism_attractions', function (Blueprint $table) {
            $table->string('image_url')->nullable()->after('category');
        });

        Schema::table('county_hotels', function (Blueprint $table) {
            $table->string('image_url')->nullable()->after('category');
        });

        Schema::table('county_products', function (Blueprint $table) {
            $table->string('image_url')->nullable()->after('category');
            $table->decimal('price', 10, 2)->nullable()->change();
            $table->string('booking_type', 20)->default('order')->after('price'); // order | book
        });
    }

    public function down(): void
    {
        Schema::table('county_tourism_attractions', function (Blueprint $table) {
            $table->dropColumn('image_url');
        });

        Schema::table('county_hotels', function (Blueprint $table) {
            $table->dropColumn('image_url');
        });

        Schema::table('county_products', function (Blueprint $table) {
            $table->dropColumn(['image_url', 'booking_type']);
        });
    }
};
