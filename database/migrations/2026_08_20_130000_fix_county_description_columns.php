<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $cols = [
            'county_tourism_attractions' => ['description'],
            'county_hotels' => ['description'],
            'county_products' => ['description'],
            'county_farms' => ['description'],
            'county_institutions' => ['description'],
            'county_transport' => ['description'],
            'county_health_facilities' => ['description'],
            'county_culture_sites' => ['description'],
        ];

        foreach ($cols as $table => $columns) {
            if (!Schema::hasTable($table)) continue;
            foreach ($columns as $col) {
                try {
                    DB::statement("ALTER TABLE `{$table}` MODIFY COLUMN `{$col}` TEXT NULL");
                } catch (\Throwable $e) {
                    // already TEXT — ignore
                }
            }
        }
    }

    public function down(): void
    {
        // Irreversible — would need original varchar length
    }
};