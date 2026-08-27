<?php

namespace App\Observers;

use App\Jobs\GenerateHlsJob;
use App\Jobs\MediaDerivativesJob;
use App\Models\MediaAsset;

class MediaAssetObserver
{
    /**
     * Whenever a video asset is uploaded:
     *  1. Generate Tier-1 poster + Tier-2 hover loop (lightweight derivatives)
     *  2. Generate Tier-3 adaptive HLS stream
     * This guarantees every new video gets the full 3-tier delivery automatically.
     */
    public function created(MediaAsset $asset): void
    {
        if ($asset->kind !== 'video') {
            return;
        }

        try {
            MediaDerivativesJob::dispatch($asset->id);
            GenerateHlsJob::dispatch($asset->id);
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /**
     * If the asset was replaced with a different video, regenerate all tiers.
     */
    public function updated(MediaAsset $asset): void
    {
        if ($asset->kind !== 'video' || !$asset->wasChanged('path')) {
            return;
        }

        try {
            MediaDerivativesJob::dispatch($asset->id, true);
            GenerateHlsJob::dispatch($asset->id, true);
        } catch (\Throwable $e) {
            report($e);
        }
    }
}