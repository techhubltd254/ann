<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The commerce `products` table on TiDB was created from the OLD county-products
 * schema (title/price/is_active) while the codebase expects the marketplace
 * schema (name/status/sku/deleted_at…). The table is empty, so we rebuild it.
 * Also creates the missing product_variants + product_images tables.
 */
return new class extends Migration {
    public function up(): void
    {
        $hasProducts = Schema::hasTable('products');

        if ($hasProducts && !Schema::hasColumn('products', 'name')) {
            \Illuminate\Support\Facades\DB::statement('SET FOREIGN_KEY_CHECKS = 0');
            Schema::dropIfExists('products');
            \Illuminate\Support\Facades\DB::statement('SET FOREIGN_KEY_CHECKS = 1');
            $hasProducts = false;
        }

        if (!$hasProducts) {
            Schema::create('products', function (Blueprint $t) {
                $t->id();
                $t->foreignId('user_id')->constrained()->cascadeOnDelete();
                $t->foreignId('county_id')->nullable()->constrained()->nullOnDelete();
                $t->foreignId('category_id')->nullable()->constrained('product_categories')->nullOnDelete();
                $t->string('name');
                $t->string('slug')->unique();
                $t->text('description')->nullable();
                $t->string('short_description', 500)->nullable();
                $t->string('sku', 100)->nullable();
                $t->string('barcode', 100)->nullable();
                $t->string('unit', 50)->default('piece');
                $t->decimal('weight_kg', 10, 2)->default(0);
                $t->decimal('length_cm', 10, 2)->default(0);
                $t->decimal('width_cm', 10, 2)->default(0);
                $t->decimal('height_cm', 10, 2)->default(0);
                $t->boolean('is_digital')->default(false);
                $t->string('status', 30)->default('draft')->index();
                $t->boolean('is_featured')->default(false);
                $t->string('meta_title')->nullable();
                $t->text('meta_description')->nullable();
                $t->json('tags')->nullable();
                $t->text('warranty_info')->nullable();
                $t->string('video_url')->nullable();
                $t->json('videos')->nullable();
                $t->text('video_description')->nullable();
                $t->string('model_url')->nullable();
                $t->integer('moq')->default(1);
                $t->decimal('fob_price', 12, 2)->nullable();
                $t->string('incoterm', 20)->nullable();
                $t->string('hs_code', 30)->nullable();
                $t->boolean('export_readiness')->default(false);
                $t->json('certifications')->nullable();
                $t->string('trade_enquiry_email')->nullable();
                $t->boolean('is_spotlight_product')->default(false);
                $t->decimal('price', 12, 2)->nullable();
                $t->decimal('compare_at_price', 12, 2)->nullable();
                $t->timestamps();
                $t->softDeletes();
                $t->index('county_id');
                $t->index('category_id');
            });
        }

        if (!Schema::hasTable('product_variants')) {
            Schema::create('product_variants', function (Blueprint $t) {
                $t->id();
                $t->foreignId('product_id')->constrained()->cascadeOnDelete();
                $t->string('name');
                $t->string('sku', 100)->nullable();
                $t->decimal('price', 12, 2);
                $t->decimal('compare_at_price', 12, 2)->nullable();
                $t->decimal('cost_price', 12, 2)->nullable();
                $t->integer('stock')->default(0);
                $t->integer('low_stock_threshold')->default(5);
                $t->decimal('weight_kg', 10, 2)->nullable();
                $t->boolean('is_active')->default(true);
                $t->integer('sort_order')->default(0);
                $t->json('attributes')->nullable();
                $t->string('image_url')->nullable();
                $t->timestamps();
            });
        }

        if (!Schema::hasTable('product_images')) {
            Schema::create('product_images', function (Blueprint $t) {
                $t->id();
                $t->foreignId('product_id')->constrained()->cascadeOnDelete();
                $t->foreignId('variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
                $t->string('url');
                $t->string('thumbnail_url')->nullable();
                $t->string('alt_text')->nullable();
                $t->integer('sort_order')->default(0);
                $t->boolean('is_primary')->default(false);
                $t->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('product_images');
        Schema::dropIfExists('product_variants');
    }
};