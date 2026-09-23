<?php

namespace App\Kicc\Pipelines;

/**
 * County Own-Source Revenue Rail
 * Sector: government | Phase: 1 | Status: absent
 */
class G1Pipeline extends AbstractPipeline
{
    public const CODE = 'G1';
    public const SECTOR = 'government';
    public const PHASE = '1';
    public const STATUS = 'absent';
    public const DESCRIPTION = 'County Own-Source Revenue Rail';

    protected array $economics = [
        'take_rate' => '1.5-2% collection fee',
        'holdback_rate' => 0.10,      // disputes/refunds reserve
        'equalisation_rate' => 0.005, // anchor-county earmark
    ];

    protected array $regulators = ["County assembly approvals"];

    protected array $tables = ["county_revenue_heads", "county_revenue_collections"];

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
