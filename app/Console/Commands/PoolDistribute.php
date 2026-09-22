<?php

namespace App\Console\Commands;

use App\Models\Pool\Pool;
use App\Services\Pool\PoolEngine;
use Illuminate\Console\Command;

class PoolDistribute extends Command
{
    protected $signature = 'pool:distribute {period? : YYYY-MM period (default: current month)}';
    protected $description = 'Run monthly pool distribution: contribution × quality → pending payouts';

    public function handle(PoolEngine $engine): int
    {
        $period = $this->argument('period') ?? now()->format('Y-m');
        $pool = Pool::where('scope', 'global')->where('is_active', true)->first();

        if (!$pool) {
            $this->warn('No active global pool found. Creating one...');
            $pool = Pool::create([
                'name' => 'KICC Mother Pool',
                'scope' => 'global',
                'holdback_pct' => config('kicc.pool.holdback_pct', 10.0),
                'equalisation_pct' => config('kicc.pool.equalisation_pct', 0.5),
                'is_active' => true,
            ]);
        }

        $this->info("Distributing pool #{$pool->id} for period {$period}...");

        $rows = $engine->distribute($pool->id, $period);

        if (empty($rows)) {
            $this->warn('No contributions found for this period.');
            return 0;
        }

        $totalAmount = array_sum(array_column($rows, 'amount'));
        $entitledCount = count($rows);

        $this->info("Distributed {$period}: {$entitledCount} beneficiaries, total KES {$totalAmount}");
        $this->info("Status: pending — approve via Mother Admin → Selling Pool tab.");

        return 0;
    }
}