<?php

namespace App\Services\Pool;

/**
 * QualityScorer — weighted composite of delivery, disputes, trust,
 * completeness, reviews, and media presence.
 * Weights come from config/kicc.php pool.quality_weights.
 * This implements Algorithm 12 from the kicc-algorithms reference.
 */
class QualityScorer
{
    private array $weights;

    public function __construct()
    {
        $this->weights = config('kicc.pool.quality_weights', [
            'delivery' => 0.25, 'disputes' => 0.20, 'trust' => 0.15,
            'completeness' => 0.15, 'reviews' => 0.15, 'media' => 0.10,
        ]);
    }

    public function score(
        float $deliveryRate,
        float $adverseRate,
        string $trustGrade,
        float $completeness,
        float $avgReview,
        int $mediaTier
    ): array {
        $trustMap = ['A' => 1.0, 'B' => 0.8, 'C' => 0.6, 'D' => 0.4, 'F' => 0.2];

        $components = [
            'delivery'     => max(0.0, min(1.0, $deliveryRate)),
            'disputes'     => max(0.0, min(1.0, 1.0 - $adverseRate)),
            'trust'        => $trustMap[strtoupper($trustGrade)] ?? 0.2,
            'completeness' => max(0.0, min(1.0, $completeness)),
            'reviews'      => max(0.0, min(1.0, $avgReview / 5.0)),
            'media'        => max(0.0, min(1.0, $mediaTier / 3.0)),
        ];

        $total = 0.0;
        foreach ($this->weights as $key => $w) {
            $total += $w * ($components[$key] ?? 0);
        }

        return [
            'score'       => round($total, 4),
            'version'     => 'quality_weights@kicc.php-' . now()->format('Y-m-d'),
            'components'  => $components,
        ];
    }
}