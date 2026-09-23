<?php

namespace App\Kicc\Pipelines;

/**
 * Tourism & Experiences
 * Sector: tourism | Phase: 1 | Status: partial
 */
class C1Pipeline extends AbstractPipeline
{
    public const CODE = 'C1';
    public const SECTOR = 'tourism';
    public const PHASE = '1';
    public const STATUS = 'partial';
    public const DESCRIPTION = 'Tourism & Experiences';

    protected array $economics = [
        'take_rate' => '15-18% OTA-style commission',
        'holdback_rate' => 0.10,      // disputes/refunds reserve
        'equalisation_rate' => 0.005, // anchor-county earmark
    ];

    protected array $regulators = ["TRA-adjacent"];

    protected array $tables = ["experience_bookings", "experience_availability"];

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
