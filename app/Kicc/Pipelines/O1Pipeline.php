<?php

namespace App\Kicc\Pipelines;

/**
 * Creative & Livestream Economy
 * Sector: creative | Phase: 1 | Status: partial
 */
class O1Pipeline extends AbstractPipeline
{
    public const CODE = 'O1';
    public const SECTOR = 'creative';
    public const PHASE = '1';
    public const STATUS = 'partial';
    public const DESCRIPTION = 'Creative & Livestream Economy';

    protected array $economics = [
        'take_rate' => '10-15% commission + passes + tips',
        'holdback_rate' => 0.10,      // disputes/refunds reserve
        'equalisation_rate' => 0.005, // anchor-county earmark
    ];

    protected array $regulators = ["KFCB alignment"];

    protected array $tables = ["stream_sessions", "stream_passes", "stream_tips"];

    protected array $killCriteria = [
        'max_dispute_rate_pct' => 3.0,
        'min_fill_rate_pct' => 70.0,
        'consecutive_months' => 2,
    ];

    public function preFlight(): array
    {
        // Returns the blockers that must clear before this pipeline may earn.
        return ['Commission capture must be wired into the existing module'];
    }
}
