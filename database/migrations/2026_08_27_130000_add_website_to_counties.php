<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // IDEMPOTENT_GUARD: skip when table missing / changes already applied
        if (!Schema::hasTable('counties')) {
            return;
        }
        try {
        if (!Schema::hasColumn('counties', 'website')) {
            Schema::table('counties', function (Blueprint $table) {
                $table->string('website')->nullable()->after('slug');
            });
        }
    
        } catch (\Throwable $e) {
            // already applied — ignore
        }
}

    public function down(): void
    {
        Schema::table('counties', function (Blueprint $table) {
            $table->dropColumn('website');
        });
    }
};