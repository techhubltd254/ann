<?php

namespace App\Kicc\Pipelines;

/**
 * e-Government Services Facilitation
 * Sector: government | Phase: 2 | Status: absent
 */
class G3Pipeline extends AbstractPipeline
{
    public const CODE = 'G3';
    public const SECTOR = 'government';
    public const PHASE = '2';
    public const STATUS = 'absent';
    public const DESCRIPTION = 'e-Government Services Facilitation';

    protected array $economics = [
        'take_rate' => 'KES 50-100 convenience fee per transaction',
        'holdback_rate' => 0.10,      // disputes/refunds reserve
        'equalisation_rate' => 0.005, // anchor-county earmark
    ];

    protected array $regulators = ["eCitizen alignment"];

    protected array $tables = ["egov_service_catalog", "egov_transactions"];

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
