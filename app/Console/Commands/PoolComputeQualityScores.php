<?php

namespace App\Console\Commands;

use App\Models\Pool\PoolContribution;
use App\Services\AlgorithmsClient;
use App\Services\Pool\QualityScorer;
use Illuminate\Console\Command;

class PoolComputeQualityScores extends Command
{
    protected $signature = 'pool:quality-scores';
    protected $description = 'Compute quality scores via Python algorithms service';

    public function handle(AlgorithmsClient $client): int
    {
        $entityIds = PoolContribution::whereNull('quality_score')
            ->distinct()->pluck('entity_id');
        if ($entityIds->isEmpty()) {
            $entityIds = collect([1]);
        }

        $count = 0;
        foreach ($entityIds as $entityId) {
            $data = $this->computeInputs($entityId);
            $result = $client->quality(
                $data['delivery_rate'],
                $data['adverse_rate'],
                $data['trust_grade'],
                $data['completeness'],
                $data['avg_review'],
                $data['media_tier']
            );

            // Fallback to local PHP QualityScorer if Python fails
            if (empty($result['score'])) {
                $result = app(QualityScorer::class)->score(
                    $data['delivery_rate'], $data['adverse_rate'], $data['trust_grade'],
                    $data['completeness'], $data['avg_review'], $data['media_tier']
                );
            }

            PoolContribution::where('entity_id', $entityId)
                ->update(['quality_score' => $result['score'] ?? 0.5, 'updated_at' => now()]);
            $count++;
        }

        $this->info("Computed quality scores for {$count} entities.");
        return 0;
    }

    protected function computeInputs(int $entityId): array
    {
        $total = \App\Models\EscrowTransaction::where('seller_id', $entityId)->count();
        $delivered = \App\Models\EscrowTransaction::where('seller_id', $entityId)->where('status', 'released')->count();
        $deliveryRate = $total > 0 ? round($delivered / $total, 4) : 0.5;

        $cases = \App\Models\DisputeCase::where('seller_id', $entityId)->count();
        $adverse = \App\Models\DisputeCase::where('seller_id', $entityId)->whereIn('status', ['open', 'lost'])->count();
        $adverseRate = $cases > 0 ? round($adverse / $cases, 4) : 0.0;

        $trustGrade = match (true) { $deliveryRate >= 0.98 => 'A', $deliveryRate >= 0.90 => 'B',
            $deliveryRate >= 0.75 => 'C', $deliveryRate >= 0.50 => 'D', default => 'F' };

        $completeness = (float) (\Illuminate\Support\Facades\Cache::get("completeness:{$entityId}", 0.5));
        $avgReview = (float) (\App\Models\Review::where('user_id', $entityId)->whereNotNull('rating')->avg('rating') ?? 3.0);
        $mediaTier = \App\Models\MediaAsset::where('owner_type', 'App\\Models\\User')
            ->where('owner_id', $entityId)->count();
        $mediaTier = $mediaTier > 5 ? 3 : ($mediaTier > 2 ? 2 : ($mediaTier > 0 ? 1 : 0));

        return compact('delivery_rate', 'adverse_rate', 'trust_grade', 'completeness', 'avg_review', 'media_tier');
    }
}