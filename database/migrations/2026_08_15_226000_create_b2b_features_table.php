<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // IDEMPOTENT_GUARD: table may already exist on TiDB (raw schema)
        if (Schema::hasTable('product_variants')) {
            return;
        }

        if (Schema::hasTable('product_variants')) {
            Schema::table('product_variants', function (Blueprint $t) {
                $t->integer('moq')->default(1)->after('stock');
                $t->json('tier_prices')->nullable()->after('moq'); // [{"qty":10,"price":450},{"qty":50,"price":400}]
            });
        }
        Schema::create('rfqs', function (Blueprint $t) {
            $t->id();
            $t->string('rfq_number')->unique();
            $t->foreignId('buyer_id')->constrained('users')->cascadeOnDelete();
            $t->string('product_name');
            $t->integer('quantity');
            $t->text('specifications')->nullable();
            $t->decimal('budget_min', 12, 2)->nullable();
            $t->decimal('budget_max', 12, 2)->nullable();
            $t->date('deadline')->nullable();
            $t->string('status')->default('open'); // open/quoted/closed/cancelled
            $t->timestamps();
        });
        Schema::create('rfq_quotes', function (Blueprint $t) {
            $t->id();
            $t->foreignId('rfq_id')->constrained()->cascadeOnDelete();
            $t->foreignId('seller_id')->constrained('users')->cascadeOnDelete();
            $t->decimal('price', 12, 2);
            $t->text('notes')->nullable();
            $t->string('status')->default('pending'); // pending/accepted/rejected
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rfq_quotes');
        Schema::dropIfExists('rfqs');
        if (Schema::hasTable('product_variants')) {
            Schema::table('product_variants', fn (Blueprint $t) => $t->dropColumn(['moq', 'tier_prices']));
        }
    }
};
