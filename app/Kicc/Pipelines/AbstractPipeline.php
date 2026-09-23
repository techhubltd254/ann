<?php

namespace App\Kicc\Pipelines;

use App\Kicc\Support\Economics;

abstract class AbstractPipeline implements \App\Kicc\Contracts\PipelineContract
{
    abstract public function preFlight(): array;

    public function code(): string { return static::CODE; }

    public function economics(): array { return $this->economics; }

    public function regulators(): array { return $this->regulators; }

    public function tables(): array { return $this->tables; }

    public function killCriteria(): array { return $this->killCriteria; }

    public function isEarningReady(): bool
    {
        return in_array(static::STATUS, ['built', 'partial', 'absent'], true)
            && static::PHASE === '1';
    }

    /**
     * Build a Mother-Pool contribution from a settled transaction.
     * $gmv = gross value captured; $fee = KICC fee on it.
     */
    public function makeContribution(int $motherPoolId, float $gmv, float $fee, ?int $countyId = null, ?int $sectorId = null, float $qualityMultiplier = 1.0): array
    {
        return Economics::contribution($motherPoolId, static::CODE, $gmv, $fee, $countyId, $sectorId, $qualityMultiplier);
    }
}
