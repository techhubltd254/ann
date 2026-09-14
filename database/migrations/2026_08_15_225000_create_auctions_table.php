<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        // IDEMPOTENT_GUARD: table may already exist on TiDB (raw schema)
        if (Schema::hasTable('auctions')) {
            return;
        }

        Schema::create('auctions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('product_id')->constrained((new \App\Models\Marketplace\Product)->getTable())->cascadeOnDelete();
            $t->foreignId('seller_id')->constrained('users')->cascadeOnDelete();
            $t->decimal('starting_bid', 12, 2);
            $t->decimal('reserve_price', 12, 2)->nullable();
            $t->decimal('current_bid', 12, 2)->default(0);
            $t->decimal('increment', 10, 2)->default(100);
            $t->timestamp('starts_at');
            $t->timestamp('ends_at');
            $t->string('status')->default('pending'); // pending/active/ended/cancelled
            $t->foreignId('winner_id')->nullable()->constrained('users')->nullOnDelete();
            $t->decimal('winning_bid', 12, 2)->nullable();
            $t->timestamps();
        });
        Schema::create('auction_bids', function (Blueprint $t) {
            $t->id();
            $t->foreignId('auction_id')->constrained()->cascadeOnDelete();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->decimal('amount', 12, 2);
            $t->boolean('is_auto')->default(false);
            $t->timestamps();
            $t->index(['auction_id', 'amount']);
        });
    }
    public function down(): void { Schema::dropIfExists('auction_bids'); Schema::dropIfExists('auctions'); }
};