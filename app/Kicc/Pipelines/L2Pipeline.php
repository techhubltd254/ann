<?php

namespace App\Kicc\Pipelines;

/**
 * Labour Migration Desk
 * Sector: education | Phase: 2 | Status: absent
 */
class L2Pipeline extends AbstractPipeline
{
    public const CODE = 'L2';
    public const SECTOR = 'education';
    public const PHASE = '2';
    public const STATUS = 'absent';
    public const DESCRIPTION = 'Labour Migration Desk';

    protected array $economics = [
        'take_rate' => 'Per-worker verification + remittance loop',
        'holdback_rate' => 0.10,      // disputes/refunds reserve
        'equalisation_rate' => 0.005, // anchor-county earmark
    ];

    protected array $regulators = ["NEA/NITA"];

    protected array $tables = ["labour_migration_applications", "labour_migration_verifications"];

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
