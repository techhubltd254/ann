<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('shopping_carts')) {
            Schema::create('shopping_carts', function (Blueprint $t) {
                $t->id();
                $t->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
                $t->string('session_id')->nullable()->index();
                $t->string('coupon_code', 50)->nullable();
                $t->decimal('discount_amount', 12, 2)->default(0);
                $t->text('notes')->nullable();
                $t->timestamp('expires_at')->nullable();
                $t->timestamps();
            });
        }

        if (!Schema::hasTable('cart_items')) {
            Schema::create('cart_items', function (Blueprint $t) {
                $t->id();
                $t->foreignId('cart_id')->constrained('shopping_carts')->cascadeOnDelete();
                $t->unsignedBigInteger('variant_id')->nullable()->index();
                $t->integer('quantity')->default(1);
                $t->decimal('unit_price', 12, 2);
                $t->morphs('itemable');
                $t->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('cart_items');
        Schema::dropIfExists('shopping_carts');
    }
};