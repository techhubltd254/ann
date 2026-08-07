<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('media_assets', function (Blueprint $table) {
            $table->string('slot', 32)->nullable()->after('alt_text');
            $table->index(['owner_type', 'owner_id', 'slot']);
        });
    }

    public function down(): void
    {
        Schema::table('media_assets', function (Blueprint $table) {
            $table->dropIndex(['owner_type', 'owner_id', 'slot']);
            $table->dropColumn('slot');
        });
    }
};
