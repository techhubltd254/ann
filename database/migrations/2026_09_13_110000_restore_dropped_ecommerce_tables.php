<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration {
    public function up(): void
    {
        // Restore tables that were accidentally dropped by cleanup migration
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
        ];

        foreach ($tables as $table => $blueprint) {
            if (!Schema::hasTable($table)) {
                Schema::create($table, $blueprint);
            }
        }
    }

    public function down(): void
    {
        // Non-reversible — data is critical
    }
};