<?php

namespace App\Kicc\Pipelines;

/**
 * Supplier / Invoice Financing
 * Sector: financing | Phase: 3 | Status: licence_gated
 */
class F2Pipeline extends AbstractPipeline
{
    public const CODE = 'F2';
    public const SECTOR = 'financing';
    public const PHASE = '3';
    public const STATUS = 'licence_gated';
    public const DESCRIPTION = 'Supplier / Invoice Financing';

    protected array $economics = [
        'take_rate' => '2-3%/month on PO-backed advances',
        'holdback_rate' => 0.10,      // disputes/refunds reserve
        'equalisation_rate' => 0.005, // anchor-county earmark
    ];

    protected array $regulators = ["CBK licence REQUIRED"];

    protected array $tables = ["invoice_financing_requests", "invoice_financing_deals"];

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
