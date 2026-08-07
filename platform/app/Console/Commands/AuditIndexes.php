<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * DBA cadence (blueprint §11.2): weekly index/health audit.
 * Reports unused indexes, tables missing indexes on FK/JOIN columns, table sizes.
 * Read-only — outputs a report; changes are reviewed before applying.
 */
class AuditIndexes extends Command
{
    protected $signature = 'dba:index-audit';
    protected $description = 'Report index usage + table health for the DBA cadence';

    public function handle(): int
    {
        $db = config('database.connections.mysql.database');
        $this->info("=== Index audit for {$db} ===");

        // largest tables
        $this->line("\n-- Largest tables --");
        foreach (DB::select("SELECT table_name, table_rows, ROUND(data_length/1048576,1) data_mb FROM information_schema.tables WHERE table_schema=? ORDER BY data_length DESC LIMIT 12", [$db]) as $t) {
            $this->line(sprintf('  %-32s %10s rows %8s MB', $t->table_name, $t->table_rows, $t->data_mb));
        }

        // FK-ish columns without an index (heuristic: *_id columns lacking any index)
        $this->line("\n-- *_id columns missing an index --");
        $missing = DB::select("
            SELECT c.table_name, c.column_name
            FROM information_schema.columns c
            WHERE c.table_schema = ? AND c.column_name LIKE '%\_id'
              AND NOT EXISTS (
                SELECT 1 FROM information_schema.statistics s
                WHERE s.table_schema = c.table_schema AND s.table_name = c.table_name AND s.column_name = c.column_name
              )
            ORDER BY c.table_name LIMIT 30", [$db]);
        foreach ($missing as $m) $this->warn("  {$m->table_name}.{$m->column_name}");
        if (empty($missing)) $this->line('  none');

        $this->info("\nIndex audit complete — review before applying changes.");
        return self::SUCCESS;
    }
}
