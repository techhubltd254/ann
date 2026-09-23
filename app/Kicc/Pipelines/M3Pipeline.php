<?php

namespace App\Kicc\Pipelines;

/**
 * Water Infrastructure Matchmaking
 * Sector: energy | Phase: 2 | Status: absent
 */
class M3Pipeline extends AbstractPipeline
{
    public const CODE = 'M3';
    public const SECTOR = 'energy';
    public const PHASE = '2';
    public const STATUS = 'absent';
    public const DESCRIPTION = 'Water Infrastructure Matchmaking';

    protected array $economics = [
        'take_rate' => '0.5-1% success fee on committed capex',
        'holdback_rate' => 0.10,      // disputes/refunds reserve
        'equalisation_rate' => 0.005, // anchor-county earmark
    ];

    protected array $regulators = ["WASREB/county"];

    protected array $tables = ["water_capex_projects", "water_project_deals"];

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
