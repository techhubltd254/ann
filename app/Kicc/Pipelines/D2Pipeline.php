<?php

namespace App\Kicc\Pipelines;

/**
 * SEZ Workforce Matching
 * Sector: investment | Phase: 2 | Status: absent
 */
class D2Pipeline extends AbstractPipeline
{
    public const CODE = 'D2';
    public const SECTOR = 'investment';
    public const PHASE = '2';
    public const STATUS = 'absent';
    public const DESCRIPTION = 'SEZ Workforce Matching';

    protected array $economics = [
        'take_rate' => 'Per-hire fees + skills-tier subscriptions',
        'holdback_rate' => 0.10,      // disputes/refunds reserve
        'equalisation_rate' => 0.005, // anchor-county earmark
    ];

    protected array $regulators = [];

    protected array $tables = ["sez_workforce_requests", "sez_placements"];

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
