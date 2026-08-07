<?php

namespace App\Console\Commands;

use App\Services\ShardManager;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ShardSetupCommand extends Command
{
    protected $signature = 'shard:setup
        {--create : Create shard databases on TiDB}
        {--migrate : Run migrations on all shards}
        {--seed-map : Seed the shard_partitions routing table}
        {--status : Show virtual partition distribution across shards}
        {--rebalance= : Rebalance partitions e.g. --rebalance="0-255:3" moves partitions 0-255 to shard_3}
        {--all : Run all setup steps}';

    protected $description = 'Manage the 1024-virtual-partition sharding system';

    public function handle(ShardManager $shardManager): int
    {
        $all = $this->option('all');

        if ($all || $this->option('create')) {
            $this->createShards($shardManager);
        }

        if ($all || $this->option('status')) {
            $this->showStatus($shardManager);
        }

        if ($all || $this->option('seed-map')) {
            $this->seedPartitionMap($shardManager);
        }

        if ($all || $this->option('migrate')) {
            $this->migrateShards($shardManager);
        }

        if ($rebalance = $this->option('rebalance')) {
            $this->rebalance($shardManager, $rebalance);
        }

        $this->newLine();
        $this->info('Shard system ready. 1024 virtual partitions × ' . $shardManager->physicalShardCount() . ' physical shards.');
        $this->warn('Long-term: add more shards and rebalance partitions — no global rehash needed.');

        return Command::SUCCESS;
    }

    private function createShards(ShardManager $sm): void
    {
        $this->info('Creating physical shard databases on TiDB...');
        $results = $sm->createShardDatabases();

        foreach ($results as $shard => $result) {
            $icon = str_starts_with($result, 'Error') ? '✗' : '✓';
            $this->line("  {$icon} {$shard}: {$result}");
        }
    }

    private function showStatus(ShardManager $sm): void
    {
        $distribution = $sm->partitionDistribution();

        $this->info('Virtual Partition Distribution (1024 total):');
        foreach ($distribution as $shard => $count) {
            $pct = round($count / 1024 * 100, 1);
            $this->line("  {$shard}: {$count} partitions ({$pct}%)");
        }

        $this->newLine();
        $this->info('County→Shard (informational — data spreads via hash):');
        $countyDist = $sm->countyShardDistribution();
        foreach ($countyDist as $shard => $counties) {
            $this->line("  {$shard}: " . implode(', ', $counties));
        }
    }

    private function seedPartitionMap(ShardManager $sm): void
    {
        $this->info('Seeding shard_partitions routing table...');

        if (!Schema::connection('mysql')->hasTable('shard_partitions')) {
            $this->warn('shard_partitions table does not exist. Run migrations on the primary DB first.');
            $this->line('Creating table now...');

            Schema::connection('mysql')->create('shard_partitions', function ($table) {
                $table->smallInteger('partition_id')->unsigned()->primary();
                $table->tinyInteger('shard_index')->unsigned();
                $table->string('status', 20)->default('active');
                $table->timestamps();
                $table->index('shard_index');
            });
        }

        $result = $sm->seedPartitionTable();
        $this->line("  ✓ {$result['partitions']} partitions mapped to {$result['shards']} shards");
        $this->line("  ✓ ~{$result['per_shard']} partitions per shard");
    }

    private function migrateShards(ShardManager $sm): void
    {
        $this->info('Running migrations on all physical shards...');
        $results = $sm->migrateAllShards();

        foreach ($results as $shard => $result) {
            $icon = str_starts_with((string) $result, 'Error') ? '✗' : '✓';
            $this->line("  {$icon} {$shard}: {$result}");
        }
    }

    private function rebalance(ShardManager $sm, string $spec): void
    {
        if (!preg_match('/^(\d+)-(\d+):(\d+)$/', $spec, $m)) {
            $this->error('Invalid rebalance spec. Use format: start-end:target_shard  e.g. 0-255:3');
            return;
        }

        $start = (int) $m[1];
        $end = (int) $m[2];
        $targetShard = (int) $m[3];

        $partitionIds = range($start, $end);
        $shardNames = $sm->shardNames();

        if (!isset($shardNames[$targetShard])) {
            $this->error("Target shard {$targetShard} does not exist. Available: " . implode(', ', array_keys($shardNames)));
            return;
        }

        $this->info("Moving partitions {$start}-{$end} to {$shardNames[$targetShard]}...");

        $bar = $this->output->createProgressBar(count($partitionIds));
        $bar->start();

        foreach (array_chunk($partitionIds, 10) as $chunk) {
            $sm->rebalancePartitions($chunk, $targetShard);
            $bar->advance(count($chunk));
        }

        $bar->finish();
        $this->newLine();
        $this->info('Rebalance complete. Partition cache cleared.');
    }
}
