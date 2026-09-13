<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void { Schema::table('media_derivatives', fn(Blueprint $t) => $t->string('original_name', 255)->nullable()->after('variant')); }
    public function down(): void { Schema::table('media_derivatives', fn(Blueprint $t) => $t->dropColumn('original_name')); }
};