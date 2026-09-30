<?php

namespace App\Console\Commands;

use App\Models\Pool\Pool;
use App\Models\Pool\PoolContribution;
use App\Kicc\Services\MotherPoolService;
use App\Services\AlgorithmsClient;
use Illuminate\Console\Command;

class PoolDistribute extends Command
{
    protected $signature = 'pool:distribute {period? : YYYY-MM period (default: current month)}';
    protected $description = 'Run monthly pool distribution via MotherPoolService (kit)';

    public function handle(): int
    {
        $period = $this->argument('period') ?? now()->format('Y-m');
        $pool = Pool::where('scope', 'global')->where('is_active', true)->first();

        if (!$pool) {
            $this->warn('No active global pool found. Creating one...');
            $pool = Pool::create([
                'name' => 'KICC Mother Pool',
                'scope' => 'global',
                'holdback_pct' => config('kicc.pool.holdback_pct', 10.0),
                'is_active' => true,
            ]);
        }

        $this->info("Distributing pool #{$pool->id} for period {$period} via kit MotherPoolService...");

        try {
            $plan = app(MotherPoolService::class)->computeDistribution($pool->id, true);
            $totalAmount = $plan['total'] ?? 0;
            $rowCount = count($plan['rows'] ?? []);
            $this->info("Dry-run distribution: {$rowCount} rows, total KES {$totalAmount}");
            $this->info("Run with --commit to settle.");
            return 0;
        } catch (\Throwable $e) {
            $this->warn('Kit MotherPoolService failed: ' . $e->getMessage());
        }

        // Fallback: Python service
        $client = app(AlgorithmsClient::class);
        $contributions = PoolContribution::where('pool_id', $pool->id)
            ->where('period_id', $period)->get(['entity_id', 'pool_share']);
        if ($contributions->isNotEmpty()) {
            $contribData = $contributions->map(fn($c) => [
                'entity_id' => $c->entity_id, 'pool_share' => (float) $c->pool_share,
            ])->toArray();
            $rows = $client->distributePool($contribData);
            if (!empty($rows)) {
                $totalAmount = array_sum(array_column($rows, 'amount'));
                $this->info("Distributed via Python: " . count($rows) . " beneficiaries, KES {$totalAmount}");
                $this->info("Status: pending — approve via Mother Admin → Selling Pool tab.");
                return 0;
            }
        }

        $this->warn('No distribution completed.');
        return 0;
    }
}