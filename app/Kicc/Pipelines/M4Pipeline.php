<?php

namespace App\Kicc\Pipelines;

/**
 * Waste / Recycling Exchange
 * Sector: energy | Phase: 1 | Status: absent
 */
class M4Pipeline extends AbstractPipeline
{
    public const CODE = 'M4';
    public const SECTOR = 'energy';
    public const PHASE = '1';
    public const STATUS = 'absent';
    public const DESCRIPTION = 'Waste / Recycling Exchange';

    protected array $economics = [
        'take_rate' => 'Trade commission',
        'holdback_rate' => 0.10,      // disputes/refunds reserve
        'equalisation_rate' => 0.005, // anchor-county earmark
    ];

    protected array $regulators = ["NEMA alignment"];

    protected array $tables = ["waste_listings", "waste_transactions"];

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
