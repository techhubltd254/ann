<?php

namespace App\Kicc\Pipelines;

/**
 * County Procurement Rail
 * Sector: government | Phase: 3 | Status: licence_gated
 */
class G2Pipeline extends AbstractPipeline
{
    public const CODE = 'G2';
    public const SECTOR = 'government';
    public const PHASE = '3';
    public const STATUS = 'licence_gated';
    public const DESCRIPTION = 'County Procurement Rail';

    protected array $economics = [
        'take_rate' => '0.5-1% of escrowed PO value',
        'holdback_rate' => 0.10,      // disputes/refunds reserve
        'equalisation_rate' => 0.005, // anchor-county earmark
    ];

    protected array $regulators = ["IFMIS integration REQUIRED"];

    protected array $tables = ["county_procurement_pos", "county_procurement_escrows"];

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
