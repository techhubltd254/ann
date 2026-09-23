<?php

namespace App\Kicc\Pipelines;

/**
 * Escrow Float / Treasury Yield
 * Sector: financing | Phase: 3 | Status: licence_gated
 */
class F5Pipeline extends AbstractPipeline
{
    public const CODE = 'F5';
    public const SECTOR = 'financing';
    public const PHASE = '3';
    public const STATUS = 'licence_gated';
    public const DESCRIPTION = 'Escrow Float / Treasury Yield';

    protected array $economics = [
        'take_rate' => 'Interest spread (KES 0 until CBK confirms)',
        'holdback_rate' => 0.10,      // disputes/refunds reserve
        'equalisation_rate' => 0.005, // anchor-county earmark
    ];

    protected array $regulators = ["CBK ruling REQUIRED"];

    protected array $tables = ["escrow_float_accounts", "escrow_float_yields"];

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
