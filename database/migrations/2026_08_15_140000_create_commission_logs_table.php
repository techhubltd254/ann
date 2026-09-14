<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('commission_logs')) {
            return;
        }
        Schema::create('commission_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->foreignId('order_item_id')->nullable()->constrained('order_items')->nullOnDelete();
            $table->decimal('item_total', 14, 2);
            $table->decimal('commission_rate', 5, 2);
            $table->decimal('commission_amount', 14, 2);
            $table->string('commission_type', 30)->default('marketplace'); // marketplace, licensing
            $table->string('status', 20)->default('pending'); // pending, settled, refunded
            $table->timestamp('settled_at')->nullable();
            $table->timestamps();
        });

        Schema::table('agents', function (Blueprint $table) {
            $table->decimal('licensing_fee', 10, 2)->default(0)->after('commission_rate');
            $table->date('licensing_fee_due_at')->nullable()->after('licensing_fee');
            $table->date('licensing_fee_paid_at')->nullable()->after('licensing_fee_due_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commission_logs');
        Schema::table('agents', function (Blueprint $table) {
            $table->dropColumn(['licensing_fee', 'licensing_fee_due_at', 'licensing_fee_paid_at']);
        });
    }
};