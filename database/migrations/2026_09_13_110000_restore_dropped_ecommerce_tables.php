<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration {
    public function up(): void
    {
        $tables = [
            'auctions' => function (Blueprint $t) {
                $t->id();
                $t->foreignId('product_id')->constrained()->cascadeOnDelete();
                $t->foreignId('seller_id')->constrained('users')->cascadeOnDelete();
                $t->string('title');
                $t->decimal('starting_bid', 12, 2);
                $t->decimal('current_bid', 12, 2)->default(0);
                $t->decimal('reserve_price', 12, 2)->nullable();
                $t->decimal('increment', 12, 2)->default(100);
                $t->timestamp('starts_at')->nullable();
                $t->timestamp('ends_at')->nullable();
                $t->string('status', 20)->default('draft');
                $t->timestamps();
            },
            'auction_bids' => function (Blueprint $t) {
                $t->id();
                $t->foreignId('auction_id')->constrained()->cascadeOnDelete();
                $t->foreignId('user_id')->constrained()->cascadeOnDelete();
                $t->decimal('amount', 12, 2);
                $t->timestamps();
            },
            'flash_sales' => function (Blueprint $t) {
                $t->id();
                $t->string('title');
                $t->text('description')->nullable();
                $t->timestamp('starts_at')->nullable();
                $t->timestamp('ends_at')->nullable();
                $t->boolean('is_active')->default(false);
                $t->timestamps();
            },
            'flash_sale_products' => function (Blueprint $t) {
                $t->id();
                $t->foreignId('flash_sale_id')->constrained()->cascadeOnDelete();
                $t->foreignId('product_id')->constrained()->cascadeOnDelete();
                $t->decimal('discount_percent', 5, 2);
                $t->integer('quantity_limit')->default(0);
                $t->timestamps();
            },
            'gift_cards' => function (Blueprint $t) {
                $t->id();
                $t->string('code', 50)->unique();
                $t->decimal('initial_balance', 12, 2);
                $t->decimal('current_balance', 12, 2);
                $t->foreignId('purchaser_id')->constrained('users')->cascadeOnDelete();
                $t->string('recipient_email')->nullable();
                $t->text('message')->nullable();
                $t->timestamp('expires_at')->nullable();
                $t->string('status', 20)->default('active');
                $t->timestamps();
            },
            'wishlists' => function (Blueprint $t) {
                $t->id();
                $t->foreignId('user_id')->constrained()->cascadeOnDelete();
                $t->morphs('wishlistable');
                $t->unique(['user_id', 'wishlistable_id', 'wishlistable_type']);
                $t->timestamps();
            },
            'order_status_history' => function (Blueprint $t) {
                $t->id();
                $t->foreignId('order_id')->constrained()->cascadeOnDelete();
                $t->string('status_from', 50)->nullable();
                $t->string('status_to', 50);
                $t->string('notes')->nullable();
                $t->foreignId('changed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
                $t->timestamps();
            },
            'return_requests' => function (Blueprint $t) {
                $t->id();
                $t->string('return_number', 50)->unique();
                $t->foreignId('order_id')->constrained()->cascadeOnDelete();
                $t->foreignId('user_id')->constrained()->cascadeOnDelete();
                $t->foreignId('order_item_id')->constrained('order_items')->cascadeOnDelete();
                $t->text('reason');
                $t->string('status', 20)->default('pending');
                $t->text('admin_notes')->nullable();
                $t->timestamp('approved_at')->nullable();
                $t->timestamp('refunded_at')->nullable();
                $t->timestamps();
            },
            'product_questions' => function (Blueprint $t) {
                $t->id();
                $t->foreignId('product_id')->constrained()->cascadeOnDelete();
                $t->foreignId('user_id')->constrained()->cascadeOnDelete();
                $t->text('question');
                $t->text('answer')->nullable();
                $t->timestamp('answered_at')->nullable();
                $t->timestamps();
            },
            'recently_viewed' => function (Blueprint $t) {
                $t->id();
                $t->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
                $t->string('session_id')->nullable()->index();
                $t->morphs('viewable');
                $t->timestamp('viewed_at')->useCurrent();
                $t->index(['user_id', 'viewable_id', 'viewable_type']);
            },
            'rfqs' => function (Blueprint $t) {
                $t->id();
                $t->foreignId('buyer_id')->constrained('users')->cascadeOnDelete();
                $t->string('title');
                $t->text('description')->nullable();
                $t->string('status', 20)->default('open');
                $t->timestamp('closes_at')->nullable();
                $t->timestamps();
            },
            'rfq_quotes' => function (Blueprint $t) {
                $t->id();
                $t->foreignId('rfq_id')->constrained()->cascadeOnDelete();
                $t->foreignId('seller_id')->constrained('users')->cascadeOnDelete();
                $t->decimal('amount', 12, 2);
                $t->text('notes')->nullable();
                $t->string('status', 20)->default('pending');
                $t->timestamps();
            },
            'shopping_carts' => function (Blueprint $t) {
                $t->id();
                $t->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
                $t->string('session_id')->nullable()->index();
                $t->string('coupon_code', 50)->nullable();
                $t->decimal('discount_amount', 12, 2)->default(0);
                $t->text('notes')->nullable();
                $t->timestamp('expires_at')->nullable();
                $t->timestamps();
            },
            'cart_items' => function (Blueprint $t) {
                $t->id();
                $t->foreignId('cart_id')->constrained('shopping_carts')->cascadeOnDelete();
                $t->unsignedBigInteger('variant_id')->nullable()->index();
                $t->integer('quantity')->default(1);
                $t->decimal('unit_price', 12, 2);
                $t->morphs('itemable');
                $t->timestamps();
            },
        ];

        foreach ($tables as $table => $blueprint) {
            if (!Schema::hasTable($table)) {
                Schema::create($table, $blueprint);
            }
        }
    }

    public function down(): void {}
};