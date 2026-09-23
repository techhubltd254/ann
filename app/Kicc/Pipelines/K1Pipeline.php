<?php

namespace App\Kicc\Pipelines;

/**
 * Health Facility Supply Marketplace
 * Sector: health | Phase: 2 | Status: absent
 */
class K1Pipeline extends AbstractPipeline
{
    public const CODE = 'K1';
    public const SECTOR = 'health';
    public const PHASE = '2';
    public const STATUS = 'absent';
    public const DESCRIPTION = 'Health Facility Supply Marketplace';

    protected array $economics = [
        'take_rate' => '1.5-3% + group-purchasing fee',
        'holdback_rate' => 0.10,      // disputes/refunds reserve
        'equalisation_rate' => 0.005, // anchor-county earmark
    ];

    protected array $regulators = ["PPB/KEMSA alignment"];

    protected array $tables = ["health_supply_catalog", "health_supply_orders"];

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
