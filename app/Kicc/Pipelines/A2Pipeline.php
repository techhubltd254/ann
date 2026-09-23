<?php

namespace App\Kicc\Pipelines;

/**
 * Cross-Border Logistics
 * Sector: trade | Phase: 1 | Status: absent
 */
class A2Pipeline extends AbstractPipeline
{
    public const CODE = 'A2';
    public const SECTOR = 'trade';
    public const PHASE = '1';
    public const STATUS = 'absent';
    public const DESCRIPTION = 'Cross-Border Logistics';

    protected array $economics = [
        'take_rate' => 'Landed-cost margin + FX spread 1-1.5% + carrier referral',
        'holdback_rate' => 0.10,      // disputes/refunds reserve
        'equalisation_rate' => 0.005, // anchor-county earmark
    ];

    protected array $regulators = [];

    protected array $tables = ["logistics_shipments", "logistics_carrier_quotes"];

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
