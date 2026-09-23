<?php

namespace App\Kicc\Pipelines;

/**
 * Diaspora Investment Corridor
 * Sector: investment | Phase: 2 | Status: absent
 */
class D4Pipeline extends AbstractPipeline
{
    public const CODE = 'D4';
    public const SECTOR = 'investment';
    public const PHASE = '2';
    public const STATUS = 'absent';
    public const DESCRIPTION = 'Diaspora Investment Corridor';

    protected array $economics = [
        'take_rate' => 'FX spread + directed-investment commission',
        'holdback_rate' => 0.10,      // disputes/refunds reserve
        'equalisation_rate' => 0.005, // anchor-county earmark
    ];

    protected array $regulators = ["CBK remittance partners"];

    protected array $tables = ["diaspora_investment_projects", "diaspora_investments"];

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
