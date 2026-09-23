<?php

namespace App\Kicc\Pipelines;

/**
 * Mining Value-Chain Services
 * Sector: investment | Phase: 2 | Status: absent
 */
class D5Pipeline extends AbstractPipeline
{
    public const CODE = 'D5';
    public const SECTOR = 'investment';
    public const PHASE = '2';
    public const STATUS = 'absent';
    public const DESCRIPTION = 'Mining Value-Chain Services';

    protected array $economics = [
        'take_rate' => '2-5% trade commission + royalty split',
        'holdback_rate' => 0.10,      // disputes/refunds reserve
        'equalisation_rate' => 0.005, // anchor-county earmark
    ];

    protected array $regulators = ["Mining Act licensing"];

    protected array $tables = ["mining_listings", "mining_deals"];

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
