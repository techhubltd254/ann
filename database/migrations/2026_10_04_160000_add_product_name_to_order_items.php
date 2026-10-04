<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('order_items') || Schema::hasColumn('order_items', 'product_name')) {
            return;
        }

        Schema::table('order_items', function (Blueprint $table) {
            $table->string('product_name', 255)->nullable()->after('product_id');
        });

        DB::statement("
            UPDATE order_items
            INNER JOIN products ON order_items.product_id = products.id
            SET order_items.product_name = products.name
            WHERE order_items.product_name IS NULL
        ");
    }

    public function down(): void
    {
        if (Schema::hasColumn('order_items', 'product_name')) {
            Schema::table('order_items', fn ($t) => $t->dropColumn('product_name'));
        }
    }
};