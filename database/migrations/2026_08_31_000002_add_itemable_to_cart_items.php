<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // IDEMPOTENT_GUARD: skip when table missing / changes already applied
        if (!Schema::hasTable('cart_items')) {
            return;
        }
        try {
        Schema::table('cart_items', function (Blueprint $table) {
            $table->nullableMorphs('itemable');
            $table->unsignedBigInteger('variant_id')->nullable()->change();
        });
    
        } catch (\Throwable $e) {
            // already applied — ignore
        }
}

    public function down(): void
    {
        Schema::table('cart_items', function (Blueprint $table) {
            $table->dropMorphs('itemable');
            $table->unsignedBigInteger('variant_id')->nullable(false)->change();
        });
    }
};