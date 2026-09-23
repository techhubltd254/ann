<?php

namespace App\Kicc\Pipelines;

/**
 * Sports Talent & Events
 * Sector: creative | Phase: 3 | Status: absent
 */
class O2Pipeline extends AbstractPipeline
{
    public const CODE = 'O2';
    public const SECTOR = 'creative';
    public const PHASE = '3';
    public const STATUS = 'absent';
    public const DESCRIPTION = 'Sports Talent & Events';

    protected array $economics = [
        'take_rate' => 'Success fees on talent deals',
        'holdback_rate' => 0.10,      // disputes/refunds reserve
        'equalisation_rate' => 0.005, // anchor-county earmark
    ];

    protected array $regulators = ["FIFA/agent licensing where relevant"];

    protected array $tables = ["sports_talent_profiles", "sports_deals"];

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
