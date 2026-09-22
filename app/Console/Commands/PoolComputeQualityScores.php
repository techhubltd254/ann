<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\EscrowTransaction;
use App\Models\Pool\PoolContribution;
use App\Models\Marketplace\Order;
use App\Services\Pool\QualityScorer;
use Illuminate\Console\Command;

class PoolComputeQualityScores extends Command
{
    protected $signature = 'pool:quality-scores';
    protected $description = 'Compute quality scores for every entity with pool contributions';

    public function handle(QualityScorer $scorer): int
    {
        $entityIds = PoolContribution::whereNull('quality_score')
            ->distinct()->pluck('entity_id');

        if ($entityIds->isEmpty()) {
            $entityIds = User::where('account_type', 'seller')->pluck('id');
        }

        $count = 0;
        foreach ($entityIds as $entityId) {
            $deliveryRate = $this->deliveryRate($entityId);
            $adverseRate = $this->adverseRate($entityId);
            $trustGrade = $this->trustGrade($deliveryRate);
            $completeness = $this->completeness($entityId);
            $avgReview = $this->avgReview($entityId);
            $mediaTier = $this->mediaTier($entityId);

            $result = $scorer->score($deliveryRate, $adverseRate, $trustGrade, $completeness, $avgReview, $mediaTier);

            // Upsert quality score into pool_contributions
            PoolContribution::where('entity_id', $entityId)
                ->update(['quality_score' => $result['score'], 'updated_at' => now()]);

            $count++;
        }

        $this->info("Computed quality scores for {$count} entities.");
        return 0;
    }

    protected function deliveryRate(int $entityId): float
    {
        $total = EscrowTransaction::where('seller_id', $entityId)->count();
        if ($total === 0) return 0.5;
        $delivered = EscrowTransaction::where('seller_id', $entityId)
            ->where('status', 'released')->count();
        return round($delivered / $total, 4);
    }

    protected function adverseRate(int $entityId): float
    {
        $cases = \App\Models\DisputeCase::where('seller_id', $entityId)->count();
        if ($cases === 0) return 0.0;
        $adverse = \App\Models\DisputeCase::where('seller_id', $entityId)
            ->whereIn('status', ['open', 'lost'])->count();
        return round($adverse / $cases, 4);
    }

    protected function trustGrade(float $deliveryRate): string
    {
        return match (true) {
            $deliveryRate >= 0.98 => 'A',
            $deliveryRate >= 0.90 => 'B',
            $deliveryRate >= 0.75 => 'C',
            $deliveryRate >= 0.50 => 'D',
            default => 'F',
        };
    }

    protected function completeness(int $entityId): float
    {
        // Use existing CorrelationService completenessScore if available
        if (\Illuminate\Support\Facades\Cache::has("completeness:{$entityId}")) {
            return min(1.0, (float) \Illuminate\Support\Facades\Cache::get("completeness:{$entityId}"));
        }
        return 0.5;
    }

    protected function avgReview(int $entityId): float
    {
        // Average review rating out of 5, default to 3.0
        $reviews = \App\Models\Review::where('user_id', $entityId)
            ->whereNotNull('rating')->avg('rating');
        return $reviews ?? 3.0;
    }

    protected function mediaTier(int $entityId): int
    {
        // 3 = hero video, 2 = poster/image, 1 = text only, 0 = minimal
        $assetCount = \App\Models\MediaAsset::where('owner_type', 'App\\Models\\User')
            ->where('owner_id', $entityId)->count();
        return $assetCount > 5 ? 3 : ($assetCount > 2 ? 2 : ($assetCount > 0 ? 1 : 0));
    }
}