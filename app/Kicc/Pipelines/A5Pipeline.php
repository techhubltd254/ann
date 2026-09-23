<?php

namespace App\Kicc\Pipelines;

/**
 * Franchise & Dealership Matchmaking
 * Sector: trade | Phase: 1 | Status: absent
 */
class A5Pipeline extends AbstractPipeline
{
    public const CODE = 'A5';
    public const SECTOR = 'trade';
    public const PHASE = '1';
    public const STATUS = 'absent';
    public const DESCRIPTION = 'Franchise & Dealership Matchmaking';

    protected array $economics = [
        'take_rate' => '1-3% success fee per signed dealership',
        'holdback_rate' => 0.10,      // disputes/refunds reserve
        'equalisation_rate' => 0.005, // anchor-county earmark
    ];

    protected array $regulators = [];

    protected array $tables = ["franchise_listings", "franchise_matches"];

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
