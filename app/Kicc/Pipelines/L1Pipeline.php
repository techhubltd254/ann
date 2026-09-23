<?php

namespace App\Kicc\Pipelines;

/**
 * Skills-to-Jobs (TVET)
 * Sector: education | Phase: 1 | Status: absent
 */
class L1Pipeline extends AbstractPipeline
{
    public const CODE = 'L1';
    public const SECTOR = 'education';
    public const PHASE = '1';
    public const STATUS = 'absent';
    public const DESCRIPTION = 'Skills-to-Jobs (TVET)';

    protected array $economics = [
        'take_rate' => 'Employer placement fee + cert revenue share',
        'holdback_rate' => 0.10,      // disputes/refunds reserve
        'equalisation_rate' => 0.005, // anchor-county earmark
    ];

    protected array $regulators = ["NITA/TVETA"];

    protected array $tables = ["skills_job_vacancies", "skills_placements"];

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
