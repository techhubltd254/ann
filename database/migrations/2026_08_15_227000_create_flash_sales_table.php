<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        // IDEMPOTENT_GUARD: table may already exist on TiDB (raw schema)
        if (Schema::hasTable('flash_sales')) {
            return;
        }

        Schema::create('flash_sales', function (Blueprint $t) {
            $t->id();
            $t->string('title');
            $t->text('description')->nullable();
            $t->decimal('discount_percent', 5, 2);
            $t->timestamp('starts_at');
            $t->timestamp('ends_at');
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });
        Schema::create('flash_sale_products', function (Blueprint $t) {
            $t->id();
            $t->foreignId('flash_sale_id')->constrained()->cascadeOnDelete();
            $t->foreignId('product_id')->constrained((new \App\Models\Marketplace\Product)->getTable())->cascadeOnDelete();
            $t->integer('max_qty')->default(0);
            $t->integer('sold_qty')->default(0);
            $t->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('flash_sale_products'); Schema::dropIfExists('flash_sales'); }
};