<?php

namespace App\Console\Commands;

use App\Kicc\Services\MotherPoolService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class KiccDryRunDistribution extends Command
{
    protected $signature = 'kicc:dry-run-distribution {--pool=} {--commit : Actually distribute (default is dry-run)}';
    protected $description = 'Compute Mother-Pool distribution (dry-run forces quality multiplier to 1.0)';

    public function handle(MotherPoolService $pools): int
    {
        $query = DB::table('pools')->where('distribution_status', 'open');
        if ($this->option('pool')) $query->where('id', (int) $this->option('pool'));
        foreach ($query->get() as $pool) {
            $plan = $pools->computeDistribution($pool->id, !$this->option('commit'));
            $this->info("Pool #{$pool->id} ({$pool->name}) — dry_run=" . ($plan['dry_run'] ?? 'n/a'));
            foreach ($plan['rows'] as $r) {
                $this->line(sprintf('  %s | county %s | share %.2f%% | KES %s',
                    $r['pipeline_code'], $r['county_id'] ?? '-', $r['share_pct'] ?? 0, number_format($r['amount'] ?? 0, 2)));
            }
            if ($this->option('commit')) {
                $pools->distribute($pool->id);
                $this->warn('  → DISTRIBUTED (multiplier enabled).');
            }
        }
        return self::SUCCESS;
    }
}
