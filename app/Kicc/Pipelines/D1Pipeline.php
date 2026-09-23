<?php

namespace App\Kicc\Pipelines;

/**
 * Industrial / SEZ Facilitation
 * Sector: investment | Phase: 2 | Status: absent
 */
class D1Pipeline extends AbstractPipeline
{
    public const CODE = 'D1';
    public const SECTOR = 'investment';
    public const PHASE = '2';
    public const STATUS = 'absent';
    public const DESCRIPTION = 'Industrial / SEZ Facilitation';

    protected array $economics = [
        'take_rate' => '0.1-0.5% success fee on committed capex',
        'holdback_rate' => 0.10,      // disputes/refunds reserve
        'equalisation_rate' => 0.005, // anchor-county earmark
    ];

    protected array $regulators = ["SEZA", "KenInvest"];

    protected array $tables = ["sez_investor_leads", "sez_deals"];

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
