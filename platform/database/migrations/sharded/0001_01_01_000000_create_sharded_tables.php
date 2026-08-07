<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run on shard databases (kicc_shard_0 through kicc_shard_3).
     * The shard_partitions routing table lives on the primary (kicc) DB.
     */
    public function up(): void
    {
        Schema::create('shard_products', function (Blueprint $table) {
            $table->id();
            $table->integer('county_id')->index();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->decimal('price', 12, 2)->default(0);
            $table->decimal('compare_price', 12, 2)->nullable();
            $table->string('sku')->nullable();
            $table->integer('stock')->default(0);
            $table->string('status')->default('active');
            $table->integer('user_id')->nullable()->index();
            $table->integer('category_id')->nullable()->index();
            $table->string('image')->nullable();
            $table->json('images')->nullable();
            $table->json('attributes')->nullable();
            $table->integer('views')->default(0);
            $table->double('rating', 3, 2)->default(0);
            $table->integer('review_count')->default(0);
            $table->timestamps();
            $table->index(['county_id', 'status']);
            $table->index(['county_id', 'category_id']);
        });

        Schema::create('shard_orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();
            $table->integer('user_id')->index();
            $table->integer('county_id')->index();
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('shipping', 12, 2)->default(0);
            $table->decimal('tax', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->string('payment_status')->default('pending');
            $table->string('shipping_status')->default('pending');
            $table->string('payment_method')->nullable();
            $table->json('shipping_address')->nullable();
            $table->json('billing_address')->nullable();
            $table->string('currency', 3)->default('KES');
            $table->text('notes')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();
            $table->index(['county_id', 'payment_status']);
            $table->index(['county_id', 'created_at']);
        });

        Schema::create('shard_order_items', function (Blueprint $table) {
            $table->id();
            $table->integer('order_id')->index();
            $table->integer('product_id')->index();
            $table->string('product_name');
            $table->decimal('price', 12, 2);
            $table->integer('quantity');
            $table->decimal('subtotal', 12, 2);
            $table->json('options')->nullable();
            $table->timestamps();
        });

        Schema::create('shard_escrow_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->integer('buyer_id')->index();
            $table->integer('seller_id')->index();
            $table->integer('order_id')->nullable()->index();
            $table->integer('county_id')->index();
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3)->default('KES');
            $table->string('status')->default('held');
            $table->text('description')->nullable();
            $table->timestamp('released_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['county_id', 'status']);
        });

        Schema::create('shard_bookings', function (Blueprint $table) {
            $table->id();
            $table->string('booking_reference')->unique();
            $table->integer('user_id')->index();
            $table->integer('county_id')->index();
            $table->string('booking_type');
            $table->json('details');
            $table->decimal('total', 12, 2);
            $table->string('status')->default('pending');
            $table->string('payment_status')->default('pending');
            $table->timestamp('checked_in_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['county_id', 'status']);
            $table->index(['user_id', 'status']);
        });

        Schema::create('shard_cart_items', function (Blueprint $table) {
            $table->id();
            $table->string('session_id')->index();
            $table->integer('user_id')->nullable()->index();
            $table->integer('product_id')->index();
            $table->integer('county_id')->index();
            $table->string('name');
            $table->decimal('price', 12, 2);
            $table->integer('quantity')->default(1);
            $table->json('options')->nullable();
            $table->timestamps();
            $table->index(['session_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shard_cart_items');
        Schema::dropIfExists('shard_bookings');
        Schema::dropIfExists('shard_escrow_transactions');
        Schema::dropIfExists('shard_order_items');
        Schema::dropIfExists('shard_orders');
        Schema::dropIfExists('shard_products');
    }
};
