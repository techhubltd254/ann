<?php

namespace App\Services\Pipeline;

use App\Models\MediaAsset;
use App\Models\PipelineJob;

interface PipelineEngineContract
{
    /**
     * Run the engine for this job. Implementations must report progress
     * via $job->setProgress() and finish with $job->complete($outputAssetId).
     */
    public function run(PipelineJob $job): void;

    /**
     * Verify the engine is available on this server / API key is set.
     */
    public function available(): bool;

    /**
     * Human-readable reason when unavailable (null = available).
     */
    public function availabilityNote(): ?string;

    public function supportedPipelines(): array;
}
