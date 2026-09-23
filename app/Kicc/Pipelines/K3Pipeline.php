<?php

namespace App\Kicc\Pipelines;

/**
 * Telehealth / Health-Data layer
 * Sector: health | Phase: 2 | Status: blocked
 */
class K3Pipeline extends AbstractPipeline
{
    public const CODE = 'K3';
    public const SECTOR = 'health';
    public const PHASE = '2';
    public const STATUS = 'blocked';
    public const DESCRIPTION = 'Telehealth / Health-Data layer';

    protected array $economics = [
        'take_rate' => 'Undefined (Digital Health Act struck down)',
        'holdback_rate' => 0.10,      // disputes/refunds reserve
        'equalisation_rate' => 0.005, // anchor-county earmark
    ];

    protected array $regulators = ["Legal status unresolved"];

    protected array $tables = ["telehealth_sessions"];

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
