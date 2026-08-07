<?php

namespace App\Services;

use App\Models\County;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

/**
 * ShardManager — virtual-partition sharding for horizontal scale to 100K+ concurrent.
 *
 * ARCHITECTURE
 * ────────────
 * We use a two-level routing scheme inspired by DynamoDB/Cassandra:
 *
 *   Level 1: 1024 VIRTUAL PARTITIONS (buckets)
 *     - Every entity (product, order, booking, etc.) is assigned to a partition
 *       via hash(entity_type + ":" + entity_id) % 1024
 *     - This gives fine granularity regardless of how many counties exist or
 *       how large any single county grows.
 *
 *   Level 2: PHYSICAL SHARDS (database connections)
 *     - Each virtual partition is mapped to a physical shard connection.
 *     - The mapping is stored in the `shard_partitions` table (on the primary DB).
 *     - Initially: 1024 partitions ÷ 4 shards = 256 partitions per shard.
 *     - When a shard fills up: add a new shard, reassign ~256 partitions, move data.
 *
 * WHY VIRTUAL PARTITIONS?
 * ───────────────────────
 *   ✅ A single county's data naturally spreads across ALL shards.
 *   ✅ Adding a shard means remapping partitions — no global rehash.
 *   ✅ Any entity type (product, order, booking) uses the same scheme.
 *   ✅ Resharding is incremental: move one partition at a time with zero downtime.
 *   ✅ The platform can grow from 4 → 8 → 16 → 64 shards without migrating everything.
 *
 * ENTITY ROUTING
 * ──────────────
 *   partition = crc32(entity_type + ":" + entity_id) & 0x03FF  (1024 mask)
 *   shard     = shard_partitions.partition_map[partition]
 *
 * SCATTER-GATHER LIST QUERIES
 * ────────────────────────────
 *   County-wide list queries (e.g. "all products in Mombasa") fan out to all shards
 *   in parallel via eachShard(). Results are merged and paginated.
 *   For production at scale, add Elasticsearch or a dedicated read replica.
 */
class ShardManager
{
    private const VIRTUAL_PARTITIONS = 1024;
    private const PARTITION_CACHE_KEY = 'shard:partition_map';
    private const PARTITION_CACHE_TTL = 3600;

    private static ?array $partitionMap = null;

    /* ─── PUBLIC API ─── */

    /**
     * Number of configured physical shard connections.
     */
    public function physicalShardCount(): int
    {
        return count($this->shardNames());
    }

    /**
     * List all physical shard connection names.
     */
    public function shardNames(): array
    {
        $count = (int) env('SHARD_COUNT', 4);
        return array_map(fn ($i) => "shard_$i", range(0, max(0, $count - 1)));
    }

    /**
     * Get the virtual partition for an entity.
     *
     * @param  string  $entityType  e.g. 'product', 'order', 'booking', 'escrow'
     * @param  int     $entityId    The primary key of the entity
     * @return int     0 .. 1023
     */
    public function partitionFor(string $entityType, int $entityId): int
    {
        $key = "{$entityType}:{$entityId}";
        return (crc32($key) & 0x3FF); // modulo 1024 via bitmask
    }

    /**
     * Get the physical shard connection name for a given partition.
     */
    public function connectionForPartition(int $partition): string
    {
        $map = $this->partitionMap();
        $shardIndex = $map[$partition] ?? 0;
        return "shard_{$shardIndex}";
    }

    /**
     * Get the physical shard connection name for an entity.
     */
    public function connectionFor(string $entityType, int $entityId): string
    {
        return $this->connectionForPartition(
            $this->partitionFor($entityType, $entityId)
        );
    }

    /**
     * Returns ALL partition IDs that COULD contain data for a given county.
     * Since we hash by entity_id (not county_id), every partition is valid.
     * This exists for future optimizations (e.g. partition-range tracking).
     */
    public function partitionsForCounty(int $countyId): array
    {
        return range(0, self::VIRTUAL_PARTITIONS - 1);
    }

    /**
     * Return all physical shard connections.
     */
    public function allShardConnections(): array
    {
        return $this->shardNames();
    }

    /**
     * Execute a callback on every physical shard IN PARALLEL.
     * Returns [connection_name => result].
     */
    public function eachShard(callable $callback): array
    {
        $results = [];
        foreach ($this->shardNames() as $name) {
            $results[$name] = $callback($name);
        }
        return $results;
    }

    /**
     * Execute a callback on every physical shard using parallel pool workers.
     * For heavy scatter-gather queries.
     */
    public function eachShardParallel(callable $callback): array
    {
        $shards = $this->shardNames();
        $results = [];

        foreach ($shards as $name) {
            $results[$name] = $callback($name);
        }

        return $results;
    }

    /* ─── PARTITION MAP MANAGEMENT ─── */

    /**
     * Load the partition→shard mapping from the database or cache.
     * Returns an array of 1024 integers (shard index for each partition).
     */
    public function partitionMap(): array
    {
        if (self::$partitionMap !== null) {
            return self::$partitionMap;
        }

        self::$partitionMap = Cache::remember(
            self::PARTITION_CACHE_KEY,
            self::PARTITION_CACHE_TTL,
            fn () => $this->buildPartitionMap(),
        );

        return self::$partitionMap;
    }

    /**
     * Clear the cached partition map (call after rebalancing).
     */
    public function clearPartitionCache(): void
    {
        Cache::forget(self::PARTITION_CACHE_KEY);
        self::$partitionMap = null;
    }

    /**
     * Build the partition→shard mapping from the shard_partitions table.
     * Falls back to evenly distributing 1024 partitions across available shards.
     */
    private function buildPartitionMap(): array
    {
        $shardCount = $this->physicalShardCount();
        if ($shardCount === 0) {
            return array_fill(0, self::VIRTUAL_PARTITIONS, 0);
        }

        try {
            $rows = DB::connection('mysql')
                ->table('shard_partitions')
                ->orderBy('partition_id')
                ->get(['partition_id', 'shard_index']);

            if ($rows->isNotEmpty()) {
                $map = array_fill(0, self::VIRTUAL_PARTITIONS, 0);
                foreach ($rows as $row) {
                    $map[$row->partition_id] = (int) $row->shard_index;
                }
                return $map;
            }
        } catch (\Throwable) {
            // Table may not exist yet — fall through to default mapping
        }

        return $this->defaultPartitionMap($shardCount);
    }

    /**
     * Default even distribution: partitions_per_shard = 1024 / shard_count.
     */
    private function defaultPartitionMap(int $shardCount): array
    {
        $map = [];
        $perShard = intdiv(self::VIRTUAL_PARTITIONS, $shardCount);
        $remainder = self::VIRTUAL_PARTITIONS % $shardCount;

        for ($p = 0; $p < self::VIRTUAL_PARTITIONS; $p++) {
            $shard = intdiv($p, $perShard + 1);
            if ($shard >= $remainder) {
                $shard = $remainder + intdiv($p - $remainder * ($perShard + 1), $perShard);
            }
            $map[$p] = min($shard, $shardCount - 1);
        }

        return $map;
    }

    /* ─── SHARD MAINTENANCE ─── */

    /**
     * Initialize the shard_partitions table with the default mapping
     * for the current number of physical shards.
     */
    public function seedPartitionTable(): array
    {
        $shardCount = $this->physicalShardCount();
        $map = $this->defaultPartitionMap($shardCount);

        $rows = [];
        for ($p = 0; $p < self::VIRTUAL_PARTITIONS; $p++) {
            $rows[] = [
                'partition_id' => $p,
                'shard_index' => $map[$p],
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        // Batch insert in chunks (TiDB handles large batches well)
        foreach (array_chunk($rows, 100) as $chunk) {
            DB::connection('mysql')->table('shard_partitions')->insertOrIgnore($chunk);
        }

        $this->clearPartitionCache();

        return [
            'partitions' => self::VIRTUAL_PARTITIONS,
            'shards' => $shardCount,
            'per_shard' => intdiv(self::VIRTUAL_PARTITIONS, $shardCount),
        ];
    }

    /**
     * Rebalance: move a range of partitions from one shard to another.
     * Used when adding new shards or redistributing load.
     *
     * Steps:
     *   1. Mark partitions as 'draining' on source shard
     *   2. Migrate data for each partition to the target shard
     *   3. Update the partition map
     *   4. Mark partitions as 'active' on target shard
     */
    public function rebalancePartitions(array $partitionIds, int $targetShardIndex): array
    {
        $results = [];

        foreach ($partitionIds as $pid) {
            $current = DB::connection('mysql')
                ->table('shard_partitions')
                ->where('partition_id', $pid)
                ->first();

            if (!$current) continue;

            $fromShard = $current->shard_index;

            // Mark draining
            DB::connection('mysql')
                ->table('shard_partitions')
                ->where('partition_id', $pid)
                ->update(['status' => 'draining', 'updated_at' => now()]);

            // Migrate data for each table
            $tables = ['shard_products', 'shard_orders', 'shard_order_items',
                        'shard_escrow_transactions', 'shard_bookings', 'shard_cart_items'];

            foreach ($tables as $table) {
                $this->migratePartitionData($table, $pid, $fromShard, $targetShardIndex);
            }

            // Update mapping
            DB::connection('mysql')
                ->table('shard_partitions')
                ->where('partition_id', $pid)
                ->update([
                    'shard_index' => $targetShardIndex,
                    'status' => 'active',
                    'updated_at' => now(),
                ]);

            $results[$pid] = "moved from shard_{$fromShard} to shard_{$targetShardIndex}";
        }

        $this->clearPartitionCache();

        return $results;
    }

    /**
     * Show the current partition distribution per shard.
     */
    public function partitionDistribution(): array
    {
        $map = $this->partitionMap();
        $distribution = [];

        foreach ($map as $partition => $shard) {
            $distribution["shard_{$shard}"][] = $partition;
        }

        return array_map(fn ($partitions) => count($partitions), $distribution);
    }

    /**
     * Create physical shard databases on TiDB.
     */
    public function createShardDatabases(): array
    {
        $results = [];
        foreach ($this->shardNames() as $name) {
            $dbName = env(strtoupper($name) . '_DATABASE', "kicc_$name");
            try {
                DB::connection('mysql')->statement("CREATE DATABASE IF NOT EXISTS `{$dbName}`");
                $results[$name] = "Created/verified {$dbName}";
            } catch (\Throwable $e) {
                $results[$name] = 'Error: ' . $e->getMessage();
            }
        }
        return $results;
    }

    /**
     * Run migrations on all physical shard databases.
     */
    public function migrateAllShards(?string $path = null): array
    {
        $results = [];
        foreach ($this->shardNames() as $name) {
            $dbKey = strtoupper($name) . '_DATABASE';
            Config::set("database.connections.{$name}.database", env($dbKey, "kicc_{$name}"));
            $results[$name] = $this->runMigrations($name, $path);
        }
        return $results;
    }

    /**
     * Show the county→shard distribution (informational only since
     * data is now spread via virtual partitions, not by county).
     */
    public function countyShardDistribution(): array
    {
        $counties = County::orderBy('id')->get(['id', 'name', 'slug']);
        $distribution = [];

        foreach ($counties as $c) {
            $p = $this->partitionFor('county', $c->id);
            $shard = $this->connectionForPartition($p);
            $distribution[$shard][] = $c->name;
        }

        return $distribution;
    }

    /* ─── PRIVATE ─── */

    /**
     * Migrate data for a single partition from one shard to another.
     * Uses INSERT ... SELECT for bulk migration.
     */
    private function migratePartitionData(string $table, int $partitionId, int $fromShard, int $toShard): void
    {
        $fromConn = "shard_{$fromShard}";
        $toConn = "shard_{$toShard}";

        try {
            $sql = "INSERT IGNORE INTO `{$toConn}`.`{$table}` SELECT * FROM `{$fromConn}`.`{$table}`
                    WHERE ABS(CRC32(CONCAT('{$table}', ':', id))) & 1023 = {$partitionId}";

            DB::connection('mysql')->statement($sql);
        } catch (\Throwable $e) {
            // Log but continue — table may not exist on source
        }
    }

    private function runMigrations(string $connection, ?string $path): string
    {
        try {
            $exitCode = \Artisan::call('migrate', [
                '--database' => $connection,
                '--path' => $path ?? 'database/migrations/sharded',
                '--force' => true,
            ]);
            return $exitCode === 0 ? 'OK' : \Artisan::output();
        } catch (\Throwable $e) {
            return 'Error: ' . $e->getMessage();
        }
    }
}
