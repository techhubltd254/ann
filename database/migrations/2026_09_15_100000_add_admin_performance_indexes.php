<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        $tables = [
            'county_institutions' => ['is_published'],
            'county_tourism_attractions' => ['is_published'],
            'county_hotels' => ['is_published'],
            'county_products' => ['is_published'],
            'sector_entities' => ['entity_type', 'is_published'],
            'county_sector' => ['display_on_tile'],
        ];
        foreach ($tables as $table => $cols) {
            if (!Schema::hasTable($table)) continue;
            Schema::table($table, function (Blueprint $t) use ($table, $cols) {
                foreach ($cols as $col) {
                    $idx = "{$table}_{$col}_index";
                    try { $t->index($col, $idx); } catch (\Throwable $e) {}
                }
            });
        }
        if (Schema::hasTable('county_sector')) {
            try { Schema::table('county_sector', fn (Blueprint $t) => $t->index(['county_id', 'sector_id'], 'county_sector_county_sector_index')); } catch (\Throwable $e) {}
        }
    }
    public function down(): void {}
};