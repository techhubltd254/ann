<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // IDEMPOTENT_GUARD: skip when table missing / changes already applied
        if (!Schema::hasTable('products')) {
            return;
        }
        try {
        Schema::table('products', function (Blueprint $table) {
            $table->string('model_url', 500)->nullable()->after('video_description');
        });
    
        } catch (\Throwable $e) {
            // already applied — ignore
        }
}

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('model_url');
        });
    }
};