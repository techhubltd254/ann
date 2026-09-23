<?php

namespace App\Kicc\Pipelines;

/**
 * TVET Equipment & Consumables Trade
 * Sector: education | Phase: 1 | Status: absent
 */
class L5Pipeline extends AbstractPipeline
{
    public const CODE = 'L5';
    public const SECTOR = 'education';
    public const PHASE = '1';
    public const STATUS = 'absent';
    public const DESCRIPTION = 'TVET Equipment & Consumables Trade';

    protected array $economics = [
        'take_rate' => '2-4% of trade value',
        'holdback_rate' => 0.10,      // disputes/refunds reserve
        'equalisation_rate' => 0.005, // anchor-county earmark
    ];

    protected array $regulators = [];

    protected array $tables = ["tvet_equipment_listings", "tvet_equipment_orders"];

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
