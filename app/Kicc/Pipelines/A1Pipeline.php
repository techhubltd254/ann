<?php

namespace App\Kicc\Pipelines;

/**
 * Domestic Marketplace (ALREADY BUILT in ann — this kit wires it into the ledger)
 * Sector: trade | Phase: 1 | Status: built
 */
class A1Pipeline extends AbstractPipeline
{
    public const CODE = 'A1';
    public const SECTOR = 'trade';
    public const PHASE = '1';
    public const STATUS = 'built';
    public const DESCRIPTION = 'Domestic Marketplace (ALREADY BUILT in ann — this kit wires it into the ledger)';

    protected array $economics = [
        'take_rate' => '2-5% commission + subs/booths/ads',
        'holdback_rate' => 0.10,      // disputes/refunds reserve
        'equalisation_rate' => 0.005, // anchor-county earmark
    ];

    protected array $regulators = [];

    protected array $tables = ["products", "orders"];

    protected array $killCriteria = [
        'max_dispute_rate_pct' => 3.0,
        'min_fill_rate_pct' => 70.0,
        'consecutive_months' => 2,
    ];

    public function preFlight(): array
    {
        // Returns the blockers that must clear before this pipeline may earn.
        return ['Wire existing checkout into LedgerService for fee capture'];
    }
}
