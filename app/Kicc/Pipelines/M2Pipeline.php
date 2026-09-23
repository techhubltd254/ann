<?php

namespace App\Kicc\Pipelines;

/**
 * Carbon & Climate Assets
 * Sector: energy | Phase: 2 | Status: absent
 */
class M2Pipeline extends AbstractPipeline
{
    public const CODE = 'M2';
    public const SECTOR = 'energy';
    public const PHASE = '2';
    public const STATUS = 'absent';
    public const DESCRIPTION = 'Carbon & Climate Assets';

    protected array $economics = [
        'take_rate' => '10-15% brokerage + verification fees',
        'holdback_rate' => 0.10,      // disputes/refunds reserve
        'equalisation_rate' => 0.005, // anchor-county earmark
    ];

    protected array $regulators = ["Carbon registries"];

    protected array $tables = ["carbon_projects", "carbon_credit_trades"];

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
