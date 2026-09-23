<?php

namespace App\Kicc\Pipelines;

/**
 * University/Industry Attachment & IP Exchange
 * Sector: education | Phase: 1 | Status: absent
 */
class L4Pipeline extends AbstractPipeline
{
    public const CODE = 'L4';
    public const SECTOR = 'education';
    public const PHASE = '1';
    public const STATUS = 'absent';
    public const DESCRIPTION = 'University/Industry Attachment & IP Exchange';

    protected array $economics = [
        'take_rate' => 'Success fees',
        'holdback_rate' => 0.10,      // disputes/refunds reserve
        'equalisation_rate' => 0.005, // anchor-county earmark
    ];

    protected array $regulators = [];

    protected array $tables = ["attachment_opportunities", "attachment_placements"];

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
