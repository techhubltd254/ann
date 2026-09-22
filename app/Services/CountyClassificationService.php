<?php

namespace App\Services;

use App\Models\County;

/**
 * County classification — Algorithm 15 from kicc-algorithms.
 * RPS: revenue potential from pipeline data (gmv, tourism, agri, SEZ, procurement)
 * FNS: foundational need (CRA-style marginalisation: water, health, roads, power, security, education)
 * The two scores are NEVER combined — the quadrant IS the output.
 */
class CountyClassificationService
{
    private array $rpsFields = ['gmv', 'tourism_bookings', 'agri_exports', 'sez_pipeline', 'procurement_flow'];
    private array $fnsFields = ['water', 'health', 'roads', 'power', 'security', 'education'];

    public function classify(County $county, float $rpsThreshold = 0.5, float $fnsThreshold = 0.5): array
    {
        $r = $this->rps($county);
        $f = $this->fns($county);

        if ($r >= $rpsThreshold && $f < $fnsThreshold) {
            $quadrant = 'engine';
        } elseif ($r < $rpsThreshold && $f < $fnsThreshold) {
            $quadrant = 'growth';
        } elseif ($r >= $rpsThreshold && $f >= $fnsThreshold) {
            $quadrant = 'priority_development';
        } else {
            $quadrant = 'foundational_anchor';
        }

        // Persist to the county record
        $county->forceFill([
            'classification_rps'      => $r,
            'classification_fns'      => $f,
            'classification_quadrant' => $quadrant,
        ])->save();

        return [
            'county'   => $county->name,
            'slug'     => $county->slug,
            'rps'      => $r,
            'fns'      => $f,
            'quadrant'  => $quadrant,
        ];
    }

    public function classifyAll(): void
    {
        County::chunk(50, function ($counties) {
            foreach ($counties as $county) {
                $this->classify($county);
            }
        });
    }

    public function rps(County $county): float
    {
        $vals = array_map(fn($f) => (float) ($county->$f ?? 0), $this->rpsFields);
        $mx = max($vals) ?: 1;
        return count($vals) ? round(array_sum($vals) / $mx / count($vals), 4) : 0;
    }

    public function fns(County $county): float
    {
        $vals = array_map(fn($f) => min(1.0, (float) ($county->$f ?? 0)), $this->fnsFields);
        return count($vals) ? round(array_sum($vals) / count($vals), 4) : 0;
    }
}