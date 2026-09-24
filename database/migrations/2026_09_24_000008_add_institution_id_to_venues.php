<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add institution_id so hotels/institutions can offer conference/event spaces
        if (! Schema::hasColumn('venues', 'institution_id')) {
            Schema::table('venues', function (Blueprint $table) {
                $table->foreignId('institution_id')->nullable()->after('id')
                    ->constrained('county_institutions')->nullOnDelete();
                $table->string('pipeline_code', 20)->nullable()->after('institution_id')->index();
                $table->index('institution_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('venues', 'institution_id')) {
            Schema::table('venues', function (Blueprint $table) {
                $table->dropForeign(['institution_id']);
                $table->dropIndex(['institution_id']);
                $table->dropColumn(['institution_id', 'pipeline_code']);
            });
        }
    }
};