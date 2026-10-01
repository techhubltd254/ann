<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('room3ds', 'entity_type')) {
            Schema::table('room3ds', function (Blueprint $table) {
                $table->string('entity_type', 100)->nullable()->index();
                $table->unsignedBigInteger('entity_id')->nullable()->index();
                $table->index(['entity_type', 'entity_id'], 'room3ds_entity_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::table('room3ds', function (Blueprint $table) {
            $table->dropIndex('room3ds_entity_idx');
            $table->dropColumn('entity_type');
            $table->dropColumn('entity_id');
        });
    }
};