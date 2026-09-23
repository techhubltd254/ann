<?php

namespace App\Kicc\Pipelines\Subsectors\Energy;

use App\Kicc\Pipelines\AbstractPipeline;

class M1_1 extends AbstractPipeline
{
    public const CODE = 'M1.1';
    public const PARENT = 'M1';
    public const SECTOR = 'energy';
    public const PHASE = '2';
    public const EARNING_LOCKED = false;

    public function code(): string { return self::CODE; }
    public function parent(): string { return self::PARENT; }
    public function sector(): string { return self::SECTOR; }
    public function phase(): string { return self::PHASE; }
    public function earningLocked(): bool { return self::EARNING_LOCKED; }
    public function preFlight(array $input = []): array { return ['ready' => !self::EARNING_LOCKED, 'reason' => self::EARNING_LOCKED ? 'Licence-gated' : 'Configured']; }
    public function process(array $input = []): array { return ['code' => self::CODE, 'status' => 'processed']; }
    public function killCriteria(): array { return ['max_dispute_rate_pct' => 3, 'min_fill_rate_pct' => 70, 'consecutive_months' => 2]; }
}
