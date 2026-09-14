<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('orders')) {
            Schema::table('orders', function (Blueprint $t) {
                if (!Schema::hasColumn('orders', 'tracking_number')) $t->string('tracking_number', 255)->nullable();
                if (!Schema::hasColumn('orders', 'shipping_carrier')) $t->string('shipping_carrier', 100)->nullable();
                if (!Schema::hasColumn('orders', 'estimated_delivery')) $t->timestamp('estimated_delivery')->nullable();
                if (!Schema::hasColumn('orders', 'admin_notes')) $t->text('admin_notes')->nullable();
            });
        }
        if (Schema::hasTable('products')) {
            Schema::table('products', function (Blueprint $t) {
                if (!Schema::hasColumn('products', 'views_count')) $t->integer('views_count')->default(0);
                if (!Schema::hasColumn('products', 'sold_count')) $t->integer('sold_count')->default(0);
            });
        }
        if (Schema::hasTable('payment_intents')) {
            Schema::table('payment_intents', function (Blueprint $t) {
                if (!Schema::hasColumn('payment_intents', 'mpesa_receipt')) $t->string('mpesa_receipt', 50)->nullable();
                if (!Schema::hasColumn('payment_intents', 'phone_number')) $t->string('phone_number', 20)->nullable();
            });
        }
    }

    public function down(): void {}
};