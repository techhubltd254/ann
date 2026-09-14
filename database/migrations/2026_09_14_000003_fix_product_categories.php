<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add missing columns to product_categories (parent_id, icon, image_url,
 * sort_order) — the TiDB table was created from the old county-products
 * schema and lacked the marketplace category fields.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('product_categories', function (Blueprint $t) {
            if (!Schema::hasColumn('product_categories', 'parent_id')) {
                $t->foreignId('parent_id')->nullable()->constrained('product_categories')->nullOnDelete();
            }
            if (!Schema::hasColumn('product_categories', 'icon')) {
                $t->string('icon', 50)->nullable();
            }
            if (!Schema::hasColumn('product_categories', 'image_url')) {
                $t->string('image_url')->nullable();
            }
            if (!Schema::hasColumn('product_categories', 'sort_order')) {
                $t->integer('sort_order')->default(0);
            }
        });
    }

    public function down(): void
    {
        Schema::table('product_categories', function (Blueprint $t) {
            $t->dropColumn(['parent_id', 'icon', 'image_url', 'sort_order']);
        });
    }
};