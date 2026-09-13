<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        foreach (['contentType', 'createdAt', 'originalName', 'sizeBytes', 'storageKey', 'uploadedByUserId'] as $col) {
            if (Schema::hasColumn('media_assets', $col)) {
                Schema::table('media_assets', fn(Blueprint $t) => $t->dropColumn($col));
            }
        }
    }
    public function down(): void { /* no restore — data lives in snake_case columns */ }
};