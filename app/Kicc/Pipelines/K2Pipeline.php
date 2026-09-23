<?php

namespace App\Kicc\Pipelines;

/**
 * Clinic/Hospital Back-office SaaS
 * Sector: health | Phase: 2 | Status: absent
 */
class K2Pipeline extends AbstractPipeline
{
    public const CODE = 'K2';
    public const SECTOR = 'health';
    public const PHASE = '2';
    public const STATUS = 'absent';
    public const DESCRIPTION = 'Clinic/Hospital Back-office SaaS';

    protected array $economics = [
        'take_rate' => 'SaaS subscriptions',
        'holdback_rate' => 0.10,      // disputes/refunds reserve
        'equalisation_rate' => 0.005, // anchor-county earmark
    ];

    protected array $regulators = ["ODPC"];

    protected array $tables = ["health_facility_subscriptions"];

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
