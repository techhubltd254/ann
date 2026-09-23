<?php

namespace App\Kicc\Pipelines;

/**
 * Edu Commerce & Fee Rails
 * Sector: education | Phase: 1 | Status: absent
 */
class L3Pipeline extends AbstractPipeline
{
    public const CODE = 'L3';
    public const SECTOR = 'education';
    public const PHASE = '1';
    public const STATUS = 'absent';
    public const DESCRIPTION = 'Edu Commerce & Fee Rails';

    protected array $economics = [
        'take_rate' => '0.5-1% fee handling + supplies margin',
        'holdback_rate' => 0.10,      // disputes/refunds reserve
        'equalisation_rate' => 0.005, // anchor-county earmark
    ];

    protected array $regulators = ["MoE alignment"];

    protected array $tables = ["edu_institution_fees", "edu_fee_transactions"];

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
