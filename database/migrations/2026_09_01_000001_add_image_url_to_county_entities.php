<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('county_farms', function (Blueprint $table) {
            $table->string('image_url', 500)->nullable()->after('products');
        });
        Schema::table('county_transport', function (Blueprint $table) {
            $table->string('image_url', 500)->nullable()->after('contact');
        });
        Schema::table('county_health_facilities', function (Blueprint $table) {
            $table->string('image_url', 500)->nullable()->after('services');
        });
        Schema::table('county_culture_sites', function (Blueprint $table) {
            $table->string('image_url', 500)->nullable()->after('contact');
        });
    }

    public function down(): void
    {
        Schema::table('county_farms', fn (Blueprint $t) => $t->dropColumn('image_url'));
        Schema::table('county_transport', fn (Blueprint $t) => $t->dropColumn('image_url'));
        Schema::table('county_health_facilities', fn (Blueprint $t) => $t->dropColumn('image_url'));
        Schema::table('county_culture_sites', fn (Blueprint $t) => $t->dropColumn('image_url'));
    }
};