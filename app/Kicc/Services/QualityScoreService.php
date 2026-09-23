<?php

namespace App\Kicc\Services;

use Illuminate\Support\Facades\DB;

/** Quality multiplier from fill/dispute/completeness/reliability — clipped to [0.8, 1.2]. */
class QualityScoreService
{
    public function multiplierFor(string $pipelineCode, ?int $countyId, string $period): float
    {
        $q = DB::table('quality_metrics')
            ->where('pipeline_code', $pipelineCode)
            ->where('period', $period)
            ->when($countyId, fn ($qq) => $qq->where('county_id', $countyId))
            ->orderByDesc('id')->first();
        if (!$q) return 1.0;
        $score = 1.0
            + (($q->fill_rate - 90) / 100)          // above/below 90% fill
            - ($q->dispute_rate / 50)               // penalty
            + (($q->data_completeness - 90) / 200)
            + (($q->delivery_reliability - 90) / 200);
        return max(0.8, min(1.2, round($score, 2)));
    }
}
