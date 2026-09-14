<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add slug/lat/lng to county_institutions — the raw schema file lacked them
 * but the model + MapPinService expect them.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('county_institutions', function (Blueprint $t) {
            if (!Schema::hasColumn('county_institutions', 'slug')) {
                $t->string('slug')->nullable()->after('name');
            }
            if (!Schema::hasColumn('county_institutions', 'lat')) {
                $t->decimal('lat', 10, 6)->nullable();
            }
            if (!Schema::hasColumn('county_institutions', 'lng')) {
                $t->decimal('lng', 10, 6)->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('county_institutions', function (Blueprint $t) {
            $t->dropColumn(['slug', 'lat', 'lng']);
        });
    }
};