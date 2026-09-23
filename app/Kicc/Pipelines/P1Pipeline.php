<?php

namespace App\Kicc\Pipelines;

/**
 * KYB-as-a-Service
 * Sector: identity | Phase: 1 | Status: absent
 */
class P1Pipeline extends AbstractPipeline
{
    public const CODE = 'P1';
    public const SECTOR = 'identity';
    public const PHASE = '1';
    public const STATUS = 'absent';
    public const DESCRIPTION = 'KYB-as-a-Service';

    protected array $economics = [
        'take_rate' => 'Per-check API fees',
        'holdback_rate' => 0.10,      // disputes/refunds reserve
        'equalisation_rate' => 0.005, // anchor-county earmark
    ];

    protected array $regulators = ["ODPC"];

    protected array $tables = ["kyb_verification_requests"];

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
