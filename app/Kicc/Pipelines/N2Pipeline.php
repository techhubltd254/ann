<?php

namespace App\Kicc\Pipelines;

/**
 * Freight & Loads Marketplace
 * Sector: mobility | Phase: 1 | Status: absent
 */
class N2Pipeline extends AbstractPipeline
{
    public const CODE = 'N2';
    public const SECTOR = 'mobility';
    public const PHASE = '1';
    public const STATUS = 'absent';
    public const DESCRIPTION = 'Freight & Loads Marketplace';

    protected array $economics = [
        'take_rate' => '5-10% of load value',
        'holdback_rate' => 0.10,      // disputes/refunds reserve
        'equalisation_rate' => 0.005, // anchor-county earmark
    ];

    protected array $regulators = [];

    protected array $tables = ["freight_loads", "freight_bids"];

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
