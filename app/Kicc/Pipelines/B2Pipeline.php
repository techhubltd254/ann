<?php

namespace App\Kicc\Pipelines;

/**
 * Cooperative Rail
 * Sector: agriculture | Phase: 1 | Status: absent
 */
class B2Pipeline extends AbstractPipeline
{
    public const CODE = 'B2';
    public const SECTOR = 'agriculture';
    public const PHASE = '1';
    public const STATUS = 'absent';
    public const DESCRIPTION = 'Cooperative Rail';

    protected array $economics = [
        'take_rate' => '2-5% on pooled member lots',
        'holdback_rate' => 0.10,      // disputes/refunds reserve
        'equalisation_rate' => 0.005, // anchor-county earmark
    ];

    protected array $regulators = ["Societies Act"];

    protected array $tables = ["coop_pooling_batches", "coop_lot_consignments"];

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
