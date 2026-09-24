<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('product_categories') && ! Schema::hasColumn('product_categories', 'pipeline_code')) {
            Schema::table('product_categories', function (Blueprint $table) {
                $table->string('pipeline_code', 20)->nullable()->after('sector')->index();
            });
        }

        if (Schema::hasTable('products') && ! Schema::hasColumn('products', 'pipeline_code')) {
            Schema::table('products', function (Blueprint $table) {
                $table->string('pipeline_code', 20)->nullable()->after('county_id')->index();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('product_categories', 'pipeline_code')) {
            Schema::table('product_categories', fn (Blueprint $t) => $t->dropColumn('pipeline_code'));
        }
        if (Schema::hasColumn('products', 'pipeline_code')) {
            Schema::table('products', fn (Blueprint $t) => $t->dropColumn('pipeline_code'));
        }
    }
};