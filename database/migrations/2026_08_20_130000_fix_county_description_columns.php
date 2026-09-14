<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
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
            if (! Schema::hasTable($table)) {
                continue;
            }
            try {
                Schema::table($table, function (Blueprint $t) use ($columns) {
                    foreach ($columns as $col) {
                        $t->text($col)->change();
                    }
                });
            } catch (\Throwable $e) {
                // already applied — ignore
            }
        }
    }

    public function down(): void
    {
        // Irreversible — would need original varchar length
    }
};
