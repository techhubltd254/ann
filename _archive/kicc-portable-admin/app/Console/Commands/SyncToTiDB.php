<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SyncToTiDB extends Command
{
    protected $signature = 'sync:to-tidb {--tables=all : Comma-separated list of tables to sync}';
    protected $description = 'Push local SQLite changes to TiDB Cloud';

    protected $tables = [
        'counties', 'sectors', 'county_sector',
        'users', 'model_has_roles',
        'ministries', 'agencies',
        'products', 'product_variants',
        'exhibitions', 'venues', 'booths', 'bookings',
        'subscription_plans',
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
        $this->info('=== Syncing LOCAL SQLite → TiDB Cloud ===');
        $this->newLine();

        $tables = $this->option('tables') === 'all' ? $this->tables : explode(',', $this->option('tables'));

        try {
            DB::connection('mysql')->getPdo();
            $this->info('✅ Connected to TiDB Cloud');
        } catch (\Exception $e) {
            $this->error('❌ Cannot connect to TiDB: ' . $e->getMessage());
            return 1;
        }

        $this->newLine();

        foreach ($tables as $table) {
            $table = trim($table);
            if (!Schema::hasTable($table)) {
                continue;
            }

            $this->info("  → Pushing $table...");

            try {
                $rows = DB::table($table)->get();
                if ($rows->isEmpty()) {
                    $this->line("    No data, skipped");
                    continue;
                }

                $data = $rows->map(fn($r) => (array) $r)->toArray();
                $inserted = 0;
                $updated = 0;

                foreach ($data as $row) {
                    $id = $row['id'] ?? null;
                    if (!$id) continue;

                    $existing = DB::connection('mysql')->table($table)->where('id', $id)->first();
                    if ($existing) {
                        DB::connection('mysql')->table($table)->where('id', $id)->update($row);
                        $updated++;
                    } else {
                        DB::connection('mysql')->table($table)->insert($row);
                        $inserted++;
                    }
                }

                $this->line("    $inserted new, $updated updated");
            } catch (\Exception $e) {
                $this->error("    ✗ Error pushing '$table': " . $e->getMessage());
            }
        }

        $this->newLine();
        $this->info('✅ Sync to TiDB complete!');
    }
}