<?php

namespace App\Kicc\Services;

use Illuminate\Support\Facades\DB;

/** Quality multiplier from pool_contributions.quality_score. Default 1.0 (neutral). */
class QualityScoreService
{
    public function multiplierFor(string $pipelineCode, ?int $countyId, string $period): float
    {
        $q = DB::table('pool_contributions')
            ->where('period_id', $period)
            ->whereNotNull('quality_score')
            ->when($countyId, fn ($qq) => $qq->where('county_id', $countyId))
            ->orderByDesc('id')->first();
        if (!$q || !$q->quality_score) return 1.0;
        $score = 0.6 + ($q->quality_score * 0.6);
        return max(0.8, min(1.2, round($score, 2)));
    }
}