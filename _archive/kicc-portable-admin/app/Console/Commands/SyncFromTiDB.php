<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SyncFromTiDB extends Command
{
    protected $signature = 'sync:from-tidb {--tables=all : Comma-separated list of tables to sync}';
    protected $description = 'Pull data from TiDB Cloud into local SQLite';

    protected $tables = [
        'counties', 'sectors', 'county_sector',
        'users', 'roles', 'permissions', 'model_has_roles', 'role_has_permissions',
        'ministries', 'agencies',
        'products', 'product_categories', 'product_variants',
        'exhibitions', 'venues', 'booths', 'bookings',
        'subscription_plans', 'user_subscriptions',
        'sector_entities', 'entity_media',
        'screens', 'screen_images',
        'room3ds',
        // County data
        'county_products',
        'county_tourism_attractions', 'county_hotels', 'county_farms',
        'county_health_facilities', 'county_institutions',
        'county_transport', 'county_culture_sites',
    ];

    public function handle()
    {
        $this->info('=== Syncing from TiDB Cloud ===');
        $this->newLine();

        $tables = $this->option('tables') === 'all' ? $this->tables : explode(',', $this->option('tables'));

        // Test TiDB connection
        try {
            DB::connection('mysql')->getPdo();
            $this->info('✅ Connected to TiDB Cloud');
        } catch (\Exception $e) {
            $this->error('❌ Cannot connect to TiDB: ' . $e->getMessage());
            $this->info('   Check your internet connection and TiDB credentials in .env');
            return 1;
        }

        $this->newLine();

        foreach ($tables as $table) {
            $table = trim($table);
            if (!Schema::connection('mysql')->hasTable($table)) {
                $this->warn("  ⚠ Table '$table' not found in TiDB, skipping");
                continue;
            }

            $this->info("  → Syncing $table...");

            try {
                // Get count before
                $before = DB::table($table)->count();

                // Fetch all data from TiDB
                $rows = DB::connection('mysql')->table($table)->get();

                if ($rows->isEmpty()) {
                    $this->line("    No data in TiDB, skipped");
                    continue;
                }

                // Convert to arrays
                $data = $rows->map(fn($r) => (array) $r)->toArray();

                // Get primary key
                $pk = 'id';

                // Upsert into local SQLite
                $inserted = 0;
                $updated = 0;
                foreach ($data as $row) {
                    $id = $row[$pk] ?? null;
                    if (!$id) continue;

                    $existing = DB::table($table)->where($pk, $id)->first();
                    if ($existing) {
                        DB::table($table)->where($pk, $id)->update($row);
                        $updated++;
                    } else {
                        DB::table($table)->insert($row);
                        $inserted++;
                    }
                }

                $after = DB::table($table)->count();
                $this->line("    +$inserted new, ~$updated updated ($before → $after rows)");

            } catch (\Exception $e) {
                $this->error("    ✗ Error syncing '$table': " . $e->getMessage());
            }
        }

        $this->newLine();
        $this->info('✅ Sync complete!');
    }
}