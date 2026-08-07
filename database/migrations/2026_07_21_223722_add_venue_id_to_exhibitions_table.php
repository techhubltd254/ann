<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exhibitions', function (Blueprint $table) {
            $table->foreignId('venue_id')->nullable()->after('county_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('exhibitions', function (Blueprint $table) {
            $table->dropForeign(['venue_id']);
            $table->dropColumn('venue_id');
        });
    }
};
