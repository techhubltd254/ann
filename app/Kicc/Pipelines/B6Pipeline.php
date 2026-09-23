<?php

namespace App\Kicc\Pipelines;

/**
 * Agri-Input Financing Feeder
 * Sector: agriculture | Phase: 3 | Status: licence_gated
 */
class B6Pipeline extends AbstractPipeline
{
    public const CODE = 'B6';
    public const SECTOR = 'agriculture';
    public const PHASE = '3';
    public const STATUS = 'licence_gated';
    public const DESCRIPTION = 'Agri-Input Financing Feeder';

    protected array $economics = [
        'take_rate' => '2-3%/month on input advances',
        'holdback_rate' => 0.10,      // disputes/refunds reserve
        'equalisation_rate' => 0.005, // anchor-county earmark
    ];

    protected array $regulators = ["CBK licence REQUIRED"];

    protected array $tables = ["input_financing_advances", "input_financing_repayments"];

    protected array $killCriteria = [
        'max_dispute_rate_pct' => 3.0,
        'min_fill_rate_pct' => 70.0,
        'consecutive_months' => 2,
    ];

    public function preFlight(): array
    {
        // Returns the blockers that must clear before this pipeline may earn.
        return ['CBK licence (or equivalent) not yet obtained — pipeline must NOT process money until cleared', 'General Ledger migration must be live'];
    }
}
