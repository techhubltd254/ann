<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('counties', 'website')) {
            Schema::table('counties', function (Blueprint $table) {
                $table->string('website')->nullable()->after('slug');
            });
        }
    }

    public function down(): void
    {
        Schema::table('counties', function (Blueprint $table) {
            $table->dropColumn('website');
        });
    }
};