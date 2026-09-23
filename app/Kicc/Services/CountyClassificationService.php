<?php

namespace App\Kicc\Services;

use Illuminate\Support\Facades\DB;

class CountyClassificationService
{
    public function classify(int $countyId, array $indicators): array
    {
        $w = config('kicc-county-classification.weights');
        $rps = 0.0;
        foreach ($w['rps'] ?? [] as $key => $weight) {
            $rps += ($indicators[$key] ?? 0) * $weight;
        }
        $fns = 0.0;
        foreach ($w['fns'] ?? [] as $key => $weight) {
            $fns += ($indicators[$key] ?? 0) * $weight;
        }
        $quadrant = $this->quadrant($rps, $fns);
        DB::table('counties')->where('id', $countyId)->update([
            'classification_rps' => round($rps, 2),
            'classification_fns' => round($fns, 2),
            'classification_quadrant' => $quadrant,
        ]);
        return ['rps' => round($rps, 2), 'fns' => round($fns, 2), 'quadrant' => $quadrant];
    }

    public function quadrant(float $rps, float $fns): string
    {
        $t = config('kicc-county-classification.thresholds');
        if ($rps >= ($t['rps_high'] ?? 0.5)) return $fns >= ($t['fns_high'] ?? 0.5) ? 'engine' : 'growth';
        return $fns >= ($t['fns_high'] ?? 0.5) ? 'priority' : 'anchor';
    }

    public function activationTierFor(string $quadrant): array
    {
        return config('kicc-county-classification.activation_tiers.' . $quadrant, []);
    }
}
