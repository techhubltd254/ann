<?php

namespace App\Kicc\Pipelines;

/**
 * Horticulture Export Compliance Desk
 * Sector: agriculture | Phase: 2 | Status: absent
 */
class B5Pipeline extends AbstractPipeline
{
    public const CODE = 'B5';
    public const SECTOR = 'agriculture';
    public const PHASE = '2';
    public const STATUS = 'absent';
    public const DESCRIPTION = 'Horticulture Export Compliance Desk';

    protected array $economics = [
        'take_rate' => 'Per-document fees + bank FX spread',
        'holdback_rate' => 0.10,      // disputes/refunds reserve
        'equalisation_rate' => 0.005, // anchor-county earmark
    ];

    protected array $regulators = ["KEPHIS", "Plant protection conventions"];

    protected array $tables = ["hort_export_permits", "hort_compliance_documents"];

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
