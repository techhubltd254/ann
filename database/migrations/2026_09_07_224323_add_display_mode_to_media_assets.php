<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('media_assets', function (Blueprint $table) {
            $table->string('display_mode', 32)->nullable()->after('slot');
            $table->index(['owner_type', 'owner_id', 'slot', 'display_mode'], 'media_assets_owner_slot_display');
        });
    }

    public function down(): void
    {
        Schema::table('media_assets', function (Blueprint $table) {
            $table->dropIndex('media_assets_owner_slot_display');
            $table->dropColumn('display_mode');
        });
    }
};
