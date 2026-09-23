<?php

namespace App\Kicc\Pipelines;

/**
 * Bundled Tourism (Merchant of Record)
 * Sector: tourism | Phase: 2 | Status: absent
 */
class C3Pipeline extends AbstractPipeline
{
    public const CODE = 'C3';
    public const SECTOR = 'tourism';
    public const PHASE = '2';
    public const STATUS = 'absent';
    public const DESCRIPTION = 'Bundled Tourism (Merchant of Record)';

    protected array $economics = [
        'take_rate' => '20-30% of total trip spend (merchant of record)',
        'holdback_rate' => 0.10,      // disputes/refunds reserve
        'equalisation_rate' => 0.005, // anchor-county earmark
    ];

    protected array $regulators = ["IATA/TALA", "ODPC"];

    protected array $tables = ["tour_packages", "tour_package_bookings"];

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
