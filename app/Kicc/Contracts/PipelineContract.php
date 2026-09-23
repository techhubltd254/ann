<?php

namespace App\Kicc\Contracts;

interface PipelineContract
{
    public function code(): string;
    public function economics(): array;
    public function regulators(): array;
    public function tables(): array;
    public function killCriteria(): array;
    /** @return string[] things that must be true before this pipeline may earn */
    public function preFlight(): array;
    public function isEarningReady(): bool;
}
