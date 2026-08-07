<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('counties', function (Blueprint $table) {
            $table->float('map_x')->nullable()->after('longitude');
            $table->float('map_y')->nullable()->after('map_x');
            $table->float('map_z')->nullable()->after('map_y');
            $table->string('scene_type', 30)->default('coast')->after('map_z');
            $table->string('region', 50)->nullable()->after('scene_type');
        });
    }

    public function down(): void
    {
        Schema::table('counties', function (Blueprint $table) {
            $table->dropColumn(['map_x', 'map_y', 'map_z', 'scene_type', 'region']);
        });
    }
};
