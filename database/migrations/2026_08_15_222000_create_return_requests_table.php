<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('return_requests', function (Blueprint $t) {
            $t->id();
            $t->string('return_number')->unique();
            $t->foreignId('order_id')->constrained((new \App\Models\Marketplace\Order)->getTable())->cascadeOnDelete();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->foreignId('order_item_id')->constrained((new \App\Models\Marketplace\OrderItem)->getTable())->cascadeOnDelete();
            $t->text('reason');
            $t->string('status')->default('pending'); // pending/approved/rejected/refunded
            $t->text('admin_notes')->nullable();
            $t->timestamp('approved_at')->nullable();
            $t->timestamp('refunded_at')->nullable();
            $t->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('return_requests'); }
};