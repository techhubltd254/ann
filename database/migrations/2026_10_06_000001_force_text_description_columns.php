<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tables = [
            'county_tourism_attractions' => ['description'],
            'county_hotels' => ['description'],
            'county_products' => ['description'],
            'county_farms' => ['description'],
            'county_institutions' => ['description'],
            'county_transport' => ['description'],
            'county_health_facilities' => ['description'],
            'county_culture_sites' => ['description'],
        ];

        foreach ($tables as $table => $columns) {
            if (!Schema::hasTable($table)) continue;
            foreach ($columns as $col) {
                try {
                    // Check if already TEXT — if so, skip
                    $info = DB::select("SHOW COLUMNS FROM `{$table}` LIKE '{$col}'");
                    if (!empty($info) && strtoupper($info[0]->Type) !== 'TEXT') {
                        DB::statement("ALTER TABLE `{$table}` MODIFY COLUMN `{$col}` TEXT NULL");
                    }
                } catch (\Throwable $e) {
                    report($e);
                }
            }
        }
    }

    public function down(): void {}
};