<?php

namespace App\Kicc\Pipelines;

/**
 * MICE / Venue & Booths
 * Sector: tourism | Phase: 1 | Status: partial
 */
class C5Pipeline extends AbstractPipeline
{
    public const CODE = 'C5';
    public const SECTOR = 'tourism';
    public const PHASE = '1';
    public const STATUS = 'partial';
    public const DESCRIPTION = 'MICE / Venue & Booths';

    protected array $economics = [
        'take_rate' => 'Booking fees + dynamic pricing + booths',
        'holdback_rate' => 0.10,      // disputes/refunds reserve
        'equalisation_rate' => 0.005, // anchor-county earmark
    ];

    protected array $regulators = [];

    protected array $tables = ["venue_listings", "venue_bookings", "booth_inventory"];

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
