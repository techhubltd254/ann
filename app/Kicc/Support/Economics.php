<?php

namespace App\Kicc\Support;

class Economics
{
    public const HOLDBACK_RATE = 0.10;       // disputes & refunds reserve
    public const EQUALISATION_RATE = 0.005;  // anchor-county earmark

    /**
     * Fee for a GMV value given a rate spec:
     *  - ['pct' => [min, max]] percentage of GMV (uses max unless $conservative)
     *  - ['flat' => amount]
     */
    public static function feeFor(float $gmv, array $spec, bool $conservative = false): float
    {
        if (isset($spec['flat'])) {
            return round($spec['flat'], 2);
        }
        if (isset($spec['pct']) && is_array($spec['pct'])) {
            $rate = $conservative ? $spec['pct'][0] : $spec['pct'][1];
            return round($gmv * ($rate / 100), 2);
        }
        return 0.0;
    }

    /**
     * Split a fee event into the Mother-Pool structure:
     * holdback 10% + equalisation 0.5% + KICC net + vendor payout.
     */
    public static function contribution(int $motherPoolId, string $pipelineCode, float $gmv, float $fee, ?int $countyId, ?int $sectorId, float $qualityMultiplier = 1.0): array
    {
        $weightedFee = round($fee * $qualityMultiplier, 2);
        $holdback = round($weightedFee * self::HOLDBACK_RATE, 2);
        $equalisation = round($weightedFee * self::EQUALISATION_RATE, 2);
        $kiccNet = round($weightedFee - $holdback - $equalisation, 2);
        return [
            'mother_pool_id' => $motherPoolId,
            'pipeline_code' => $pipelineCode,
            'county_id' => $countyId,
            'sector_id' => $sectorId,
            'gmv' => round($gmv, 2),
            'fee_charged' => round($fee, 2),
            'quality_multiplier' => $qualityMultiplier,
            'vendor_payout' => round($gmv - $fee, 2),
            'kicc_net' => $kiccNet,
            'holdback' => $holdback,
            'equalisation' => $equalisation,
        ];
    }
}
