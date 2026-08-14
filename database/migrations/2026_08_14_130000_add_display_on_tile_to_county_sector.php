<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('county_sector', function (Blueprint $table) {
            $table->string('display_on_tile', 20)->default('no')->after('sub_sectors');
            $table->string('displayOnTile', 20)->default('no')->after('display_on_tile');
        });
    }

    public function down(): void
    {
        Schema::table('county_sector', function (Blueprint $table) {
            $table->dropColumn(['display_on_tile', 'displayOnTile']);
        });
    }
};