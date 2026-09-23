<?php

namespace App\Kicc\Pipelines;

/**
 * Itinerary / Trip Planning
 * Sector: tourism | Phase: 1 | Status: partial
 */
class C2Pipeline extends AbstractPipeline
{
    public const CODE = 'C2';
    public const SECTOR = 'tourism';
    public const PHASE = '1';
    public const STATUS = 'partial';
    public const DESCRIPTION = 'Itinerary / Trip Planning';

    protected array $economics = [
        'take_rate' => 'Composition layer (no direct fee)',
        'holdback_rate' => 0.10,      // disputes/refunds reserve
        'equalisation_rate' => 0.005, // anchor-county earmark
    ];

    protected array $regulators = [];

    protected array $tables = ["trip_itineraries", "trip_itinerary_items"];

    protected array $killCriteria = [
        'max_dispute_rate_pct' => 3.0,
        'min_fill_rate_pct' => 70.0,
        'consecutive_months' => 2,
    ];

    public function preFlight(): array
    {
        // Returns the blockers that must clear before this pipeline may earn.
        return ['Commission capture must be wired into the existing module'];
    }
}
