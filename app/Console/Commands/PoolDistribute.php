<?php

namespace App\Console\Commands;

use App\Models\Pool\Pool;
use App\Models\Pool\PoolContribution;
use App\Services\AlgorithmsClient;
use App\Services\Pool\PoolEngine;
use Illuminate\Console\Command;

class PoolDistribute extends Command
{
    protected $signature = 'pool:distribute {period? : YYYY-MM period (default: current month)}';
    protected $description = 'Run monthly pool distribution via Python algorithms service';

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

        $contributions = PoolContribution::where('pool_id', $pool->id)
            ->where('period_id', $period)->get(['entity_id', 'pool_share']);

        if ($contributions->isEmpty()) {
            $this->warn('No contributions found for this period.');
            return 0;
        }

        $this->info("Distributing {$contributions->count()} contributions via Python service...");

        $client = app(AlgorithmsClient::class);
        $contribData = $contributions->map(fn($c) => [
            'entity_id' => $c->entity_id, 'pool_share' => (float) $c->pool_share,
        ])->toArray();

        $rows = $client->distributePool($contribData);

        if (empty($rows)) {
            $this->warn('Python service returned empty. Falling back to PHP PoolEngine...');
            $rows = app(PoolEngine::class)->distribute($pool->id, $period);
            if (empty($rows)) {
                $this->warn('No distribution rows generated.');
                return 0;
            }
        }

        $totalAmount = array_sum(array_column($rows, 'amount'));
        $this->info("Distributed {$period}: " . count($rows) . " beneficiaries, total KES {$totalAmount}");
        $this->info("Status: pending — approve via Mother Admin → Selling Pool tab.");

        return 0;
    }
}