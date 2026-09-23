<?php

namespace App\Kicc\Pipelines;

/**
 * SME Acquisition / Business-for-Sale
 * Sector: trade | Phase: 3 | Status: absent
 */
class A6Pipeline extends AbstractPipeline
{
    public const CODE = 'A6';
    public const SECTOR = 'trade';
    public const PHASE = '3';
    public const STATUS = 'absent';
    public const DESCRIPTION = 'SME Acquisition / Business-for-Sale';

    protected array $economics = [
        'take_rate' => '1-2% success fee on business transfer',
        'holdback_rate' => 0.10,      // disputes/refunds reserve
        'equalisation_rate' => 0.005, // anchor-county earmark
    ];

    protected array $regulators = [];

    protected array $tables = ["sme_business_listings", "sme_acquisition_deals"];

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
