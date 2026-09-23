<?php

namespace App\Kicc\Pipelines;

/**
 * Livestock & Leather
 * Sector: agriculture | Phase: 1 | Status: absent
 */
class B3Pipeline extends AbstractPipeline
{
    public const CODE = 'B3';
    public const SECTOR = 'agriculture';
    public const PHASE = '1';
    public const STATUS = 'absent';
    public const DESCRIPTION = 'Livestock & Leather';

    protected array $economics = [
        'take_rate' => '3-5% on vet-certified sales',
        'holdback_rate' => 0.10,      // disputes/refunds reserve
        'equalisation_rate' => 0.005, // anchor-county earmark
    ];

    protected array $regulators = ["Veterinary certification"];

    protected array $tables = ["livestock_listings", "livestock_sales"];

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
