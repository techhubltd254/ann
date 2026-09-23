<?php

namespace App\Kicc\Pipelines;

/**
 * PSV / Matatu SACCO Operations
 * Sector: mobility | Phase: 2 | Status: absent
 */
class N1Pipeline extends AbstractPipeline
{
    public const CODE = 'N1';
    public const SECTOR = 'mobility';
    public const PHASE = '2';
    public const STATUS = 'absent';
    public const DESCRIPTION = 'PSV / Matatu SACCO Operations';

    protected array $economics = [
        'take_rate' => '0.5-1% collection fee + insurance/parts commissions',
        'holdback_rate' => 0.10,      // disputes/refunds reserve
        'equalisation_rate' => 0.005, // anchor-county earmark
    ];

    protected array $regulators = ["NTSA/county bylaws"];

    protected array $tables = ["sacco_vehicles", "sacco_daily_collections"];

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
