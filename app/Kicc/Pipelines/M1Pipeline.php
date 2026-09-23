<?php

namespace App\Kicc\Pipelines;

/**
 * Clean-Energy Commerce + PAYG escrow
 * Sector: energy | Phase: 2 | Status: absent
 */
class M1Pipeline extends AbstractPipeline
{
    public const CODE = 'M1';
    public const SECTOR = 'energy';
    public const PHASE = '2';
    public const STATUS = 'absent';
    public const DESCRIPTION = 'Clean-Energy Commerce + PAYG escrow';

    protected array $economics = [
        'take_rate' => '5-10% + installer referral + insurance attach',
        'holdback_rate' => 0.10,      // disputes/refunds reserve
        'equalisation_rate' => 0.005, // anchor-county earmark
    ];

    protected array $regulators = ["EPRA alignment"];

    protected array $tables = ["energy_products", "energy_payg_contracts"];

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
