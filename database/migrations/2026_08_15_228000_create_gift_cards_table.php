<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('gift_cards', function (Blueprint $t) {
            $t->id();
            $t->string('code', 20)->unique();
            $t->decimal('initial_balance', 12, 2);
            $t->decimal('balance', 12, 2);
            $t->foreignId('issued_by_user_id')->constrained('users')->cascadeOnDelete();
            $t->foreignId('redeemed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('expires_at')->nullable();
            $t->timestamp('redeemed_at')->nullable();
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('gift_cards'); }
};