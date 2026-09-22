<?php

namespace App\Services\Pool;

use App\Models\Pool\PoolContribution;
use App\Models\Pool\PoolDistribution;
use Illuminate\Support\Facades\DB;

/**
 * PoolEngine — distribution of pool proceeds by contribution × quality.
 * weight = contribution^alpha × quality^beta.
 * alpha=1, beta=0 degrades to pure proportional (launch-month safe mode).
 */
class PoolEngine
{
    private float $alpha;
    private float $beta;
    private float $holdbackPct;
    private float $equalisationPct;
    private float $defaultQuality;

    public function __construct()
    {
        $cfg = config('kicc.pool');
        $this->alpha = $cfg['alpha'] ?? 0.7;
        $this->beta = $cfg['beta'] ?? 0.3;
        $this->holdbackPct = ($cfg['holdback_pct'] ?? 10.0) / 100;
        $this->equalisationPct = ($cfg['equalisation_pct'] ?? 0.5) / 100;
        $this->defaultQuality = $cfg['default_quality'] ?? 0.5;
    }

    public function distribute(int $poolId, string $periodId): array
    {
        $contributions = PoolContribution::where('pool_id', $poolId)
            ->where('period_id', $periodId)->get();
        if ($contributions->isEmpty()) return [];

        $inflow = $contributions->sum('pool_share');
        $reserves = $inflow * ($this->holdbackPct + $this->equalisationPct);
        $distributable = $inflow - $reserves;
        if ($distributable <= 0) return [];

        if ($distributable < 0.01) return [];

        $byEntity = $contributions->groupBy(fn($c) => $c->entity_id ?? $c->entity_type . '_' . $c->entity_id);
        $entityShares = $byEntity->map->sum('pool_share');
        $totalShare = $entityShares->sum();
        if ($totalShare <= 0) return [];

        $qualities = [];
        $entityToEntityId = [];
        $entityToEntityType = [];

        foreach ($byEntity as $key => $group) {
            $first = $group->first();
            $entityToEntityId[$key] = $first->entity_id;
            $entityToEntityType[$key] = $first->entity_type;
            $qsRecord = PoolContribution::select('quality_score')
                ->where('pool_id', $poolId)
                ->where('period_id', $periodId)
                ->where('entity_id', $first->entity_id)
                ->where('entity_type', $first->entity_type)
                ->whereNotNull('quality_score')
                ->first();
            $qualities[$key] = $qsRecord ? (float) $qsRecord->quality_score : $this->defaultQuality;
        }

        $rows = [];
        $weights = [];
        $rawContribFracs = [];
        foreach ($entityShares as $key => $share) {
            $cf = $share / $totalShare;
            $rawContribFracs[$key] = $cf;
            $q = $qualities[$key];
            $weights[$key] = pow($cf, $this->alpha) * pow($q, $this->beta);
        }

        $weightSum = array_sum($weights) ?: 1;
        $totalDistributed = 0;
        $now = now();

        foreach ($weights as $key => $weight) {
            $amount = round($distributable * $weight / $weightSum, 2);
            $rows[] = [
                'pool_id' => $poolId,
                'period_id' => $periodId,
                'beneficiary_type' => $entityToEntityType[$key] ?? 'seller',
                'beneficiary_id' => $entityToEntityId[$key],
                'contribution_weight' => round($rawContribFracs[$key], 6),
                'quality_weight' => round($qualities[$key], 6),
                'final_weight' => round($weight, 6),
                'amount' => $amount,
                'breakdown' => json_encode([
                    'inflow' => $inflow,
                    'reserves' => $reserves,
                    'distributable' => $distributable,
                    'alpha' => $this->alpha,
                    'beta' => $this->beta,
                    'entity_count' => $byEntity->count(),
                ]),
                'status' => 'pending',
                'created_at' => $now,
                'updated_at' => $now,
            ];
            $totalDistributed += $amount;
        }

        $drift = round($distributable - $totalDistributed, 2);
        if (abs($drift) > 0.001 && !empty($rows)) {
            $rows[count($rows) - 1]['amount'] = round($rows[count($rows) - 1]['amount'] + $drift, 2);
        }

        PoolDistribution::insert($rows);

        app(\App\Services\CacheSyncService::class)->kicc();

        return $rows;
    }
}