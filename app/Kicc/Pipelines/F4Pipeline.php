<?php

namespace App\Kicc\Pipelines;

/**
 * Embedded Insurance + KYB-as-a-Service
 * Sector: financing | Phase: 2 | Status: absent
 */
class F4Pipeline extends AbstractPipeline
{
    public const CODE = 'F4';
    public const SECTOR = 'financing';
    public const PHASE = '2';
    public const STATUS = 'absent';
    public const DESCRIPTION = 'Embedded Insurance + KYB-as-a-Service';

    protected array $economics = [
        'take_rate' => '10-20% commission / per-check API fees',
        'holdback_rate' => 0.10,      // disputes/refunds reserve
        'equalisation_rate' => 0.005, // anchor-county earmark
    ];

    protected array $regulators = ["IRA intermediary"];

    protected array $tables = ["embedded_insurance_policies", "kyb_checks"];

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
