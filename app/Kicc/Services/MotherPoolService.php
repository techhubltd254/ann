<?php

namespace App\Kicc\Services;

use App\Kicc\Support\Economics;
use Illuminate\Support\Facades\DB;

/**
 * Mother-Pool: collects every pipeline contribution and distributes by
 * contribution × quality, with 10% holdback + 0.5% equalisation earmark.
 */
class MotherPoolService
{
    public function __construct(private LedgerService $ledger) {}

    public function openPool(string $name, string $periodStart, string $periodEnd, ?int $countyId = null, ?int $sectorId = null): int
    {
        return DB::table('pools')->insertGetId([
            'name' => $name, 'county_id' => $countyId, 'sector_id' => $sectorId,
            'period_start' => $periodStart, 'period_end' => $periodEnd,
            'distribution_status' => 'open',
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function recordContribution(int $poolId, string $pipelineCode, float $gmv, float $fee, ?int $countyId = null, ?int $sectorId = null, ?string $period = null): int
    {
        $multiplier = app(QualityScoreService::class)->multiplierFor($pipelineCode, $countyId, $period ?? now()->format('Y-m'));
        $data = Economics::contribution($poolId, $pipelineCode, $gmv, $fee, $countyId, $sectorId, $multiplier);
        $id = DB::table('pool_contributions')->insertGetId($data + ['status' => 'recorded', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('pools')->where('id', $poolId)->increment('balance', $data['kicc_net']);
        DB::table('pools')->where('id', $poolId)->increment('holdback_pct', $data['holdback']);
        DB::table('pools')->where('id', $poolId)->increment('equalisation_amount', $data['equalisation']);
        return $id;
    }

    /**
     * Compute distribution for a pool. In dry-run mode the quality
     * multiplier is FORCED to 1.0 (per the strategy doc's governance rule).
     */
    public function computeDistribution(int $poolId, bool $dryRun = true): array
    {
        $contribs = DB::table('pool_contributions')->where('pool_id', $poolId)->get();
        $rows = [];
        foreach ($contribs as $c) {
            $m = $dryRun ? 1.0 : (float) $c->quality_multiplier;
            $rows[] = [
                'county_id' => $c->county_id, 'pipeline_code' => $c->pipeline_code,
                'basis_amount' => round($c->kicc_net * $m, 2),
            ];
        }
        $total = array_sum(array_column($rows, 'basis_amount'));
        if ($total <= 0) return ['rows' => [], 'total' => 0.0];
        foreach ($rows as &$r) {
            $r['share_pct'] = round($r['basis_amount'] / $total * 100, 4);
            $r['amount'] = round($r['basis_amount'] / $total * (float) DB::table('pools')->where('id', $poolId)->value('balance'), 2);
        }
        return ['rows' => $rows, 'total' => $total, 'dry_run' => $dryRun];
    }

    public function distribute(int $poolId): void
    {
        $plan = $this->computeDistribution($poolId, false);
        DB::transaction(function () use ($poolId, $plan) {
            foreach ($plan['rows'] as $r) {
                DB::table('pool_distributions')->insert([
                    'pool_id' => $poolId,
                    'beneficiary_type' => 'county_pipeline',
                    'beneficiary_id' => $r['county_id'] ?? 0,
                    'amount' => $r['amount'],
                    'basis' => 'contribution_x_quality',
                    'status' => 'pending',
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            }
            DB::table('pools')->where('id', $poolId)->update(['distribution_status' => 'distributed', 'distributed_at' => now(), 'updated_at' => now()]);
        });
    }
}
