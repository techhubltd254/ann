<?php

namespace App\Kicc\Pipelines;

/**
 * Real Estate (title-verified)
 * Sector: investment | Phase: 2 | Status: blocked
 */
class D3Pipeline extends AbstractPipeline
{
    public const CODE = 'D3';
    public const SECTOR = 'investment';
    public const PHASE = '2';
    public const STATUS = 'blocked';
    public const DESCRIPTION = 'Real Estate (title-verified)';

    protected array $economics = [
        'take_rate' => '2-5% facilitation fee',
        'holdback_rate' => 0.10,      // disputes/refunds reserve
        'equalisation_rate' => 0.005, // anchor-county earmark
    ];

    protected array $regulators = ["Ardhisasa/MoLands partnership REQUIRED"];

    protected array $tables = ["real_estate_listings", "real_estate_deals"];

    protected array $killCriteria = [
        'max_dispute_rate_pct' => 3.0,
        'min_fill_rate_pct' => 70.0,
        'consecutive_months' => 2,
    ];

    public function preFlight(): array
    {
        // Returns the blockers that must clear before this pipeline may earn.
        return ['External dependency unconfirmed (partner/integration/legal) — see regulators list', 'General Ledger migration must be live'];
    }
}
