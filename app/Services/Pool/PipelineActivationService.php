<?php

namespace App\Services\Pool;

use App\Models\County;
use Illuminate\Support\Facades\DB;

/**
 * PipelineActivationService — decides which of the 202 pipelines a county
 * may operate based on its classification quadrant and the activation tiers
 * defined in config/kicc-county-classification.php.
 *
 * Tier markers are resolved to concrete pipeline_registrations.code values.
 */
class PipelineActivationService
{
    private array $config;

    public function __construct()
    {
        $this->config = config('kicc-county-classification');
    }

    /** All pipeline codes permitted for a given county's quadrant. */
    public function codesForCounty(County $county): array
    {
        return $this->codesForQuadrant($county->classification_quadrant);
    }

    /** Resolve marker → concrete pipeline codes for a quadrant. */
    public function codesForQuadrant(?string $quadrant): array
    {
        $tiers = $this->config['activation_tiers'] ?? [];
        $markers = $tiers[$quadrant ?? 'anchor'] ?? ['__SUBSIDY__'];
        return $this->resolveMarkers($markers);
    }

    /** Resolve marker names to actual pipeline_registrations codes. */
    public function resolveMarkers(array $markers): array
    {
        $markerMap = $this->config['markers'] ?? [];

        if (in_array('__ALL__', $markers)) {
            return DB::table('pipeline_registrations')->pluck('code')->all();
        }

        $codes = [];
        foreach ($markers as $m) {
            if (str_starts_with($m, '__')) {
                $parentCodes = $markerMap[$m] ?? [];
                foreach ($parentCodes as $pc) {
                    // Add the parent itself
                    $codes[] = $pc;
                    // Add all subsector children (e.g. A1.1, A1.2 from A1)
                    $children = DB::table('pipeline_registrations')
                        ->where('code', 'like', $pc . '.%')->pluck('code')->all();
                    $codes = array_merge($codes, $children);
                }
            }
        }
        return array_unique($codes);
    }

    /** Activate (insert) allowed pipelines for a county. */
    public function syncForCounty(County $county): int
    {
        $codes = $this->codesForCounty($county);
        $inserted = 0;
        foreach ($codes as $code) {
            DB::table('pipeline_activations')->updateOrInsert(
                ['county_id' => $county->id, 'pipeline_code' => $code],
                ['activated_at' => now(), 'updated_at' => now(), 'created_at' => now()]
            );
            $inserted++;
        }
        return $inserted;
    }

    /** Sync all 47 counties. */
    public function syncAll(): array
    {
        $results = [];
        County::chunk(50, function ($counties) use (&$results) {
            foreach ($counties as $county) {
                $count = $this->syncForCounty($county);
                $results[$county->slug] = $count;
            }
        });
        return $results;
    }
}