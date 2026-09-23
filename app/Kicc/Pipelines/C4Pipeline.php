<?php

namespace App\Kicc\Pipelines;

/**
 * County Homestay & Community Tourism
 * Sector: tourism | Phase: 1 | Status: absent
 */
class C4Pipeline extends AbstractPipeline
{
    public const CODE = 'C4';
    public const SECTOR = 'tourism';
    public const PHASE = '1';
    public const STATUS = 'absent';
    public const DESCRIPTION = 'County Homestay & Community Tourism';

    protected array $economics = [
        'take_rate' => '15% commission + curation fees',
        'holdback_rate' => 0.10,      // disputes/refunds reserve
        'equalisation_rate' => 0.005, // anchor-county earmark
    ];

    protected array $regulators = ["County tourism"];

    protected array $tables = ["homestay_listings", "homestay_bookings"];

    protected array $killCriteria = [
        'max_dispute_rate_pct' => 3.0,
        'min_fill_rate_pct' => 70.0,
        'consecutive_months' => 2,
    ];

    public function preFlight(): array
    {
        // Returns the blockers that must clear before this pipeline may earn.
        return ['General Ledger migration must be live', 'Payment rails must be wired to checkout'];
    }
}
