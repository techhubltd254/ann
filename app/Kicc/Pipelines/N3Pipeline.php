<?php

namespace App\Kicc\Pipelines;

/**
 * Fuel & Fleet Inputs
 * Sector: mobility | Phase: 2 | Status: absent
 */
class N3Pipeline extends AbstractPipeline
{
    public const CODE = 'N3';
    public const SECTOR = 'mobility';
    public const PHASE = '2';
    public const STATUS = 'absent';
    public const DESCRIPTION = 'Fuel & Fleet Inputs';

    protected array $economics = [
        'take_rate' => '1-2% commission',
        'holdback_rate' => 0.10,      // disputes/refunds reserve
        'equalisation_rate' => 0.005, // anchor-county earmark
    ];

    protected array $regulators = ["EPRA"];

    protected array $tables = ["fleet_fuel_orders"];

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
