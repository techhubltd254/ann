<?php

namespace App\Observers;

use App\Jobs\GenerateHlsJob;
use App\Models\MediaAsset;

class MediaAssetObserver
{
    /**
     * Whenever a video asset is uploaded, queue adaptive HLS generation.
     * This guarantees every new video (county hero, sector, institution,
     * product, 4d) gets the low-start adaptive stream automatically.
     */
    public function created(MediaAsset $asset): void
    {
        if ($asset->kind !== 'video') {
            return;
        }

        try {
            GenerateHlsJob::dispatch($asset->id);
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /**
     * If the asset was replaced with a different video, regenerate HLS.
     */
    public function updated(MediaAsset $asset): void
    {
        if ($asset->kind !== 'video' || !$asset->wasChanged('path')) {
            return;
        }

        try {
            GenerateHlsJob::dispatch($asset->id, true);
        } catch (\Throwable $e) {
            report($e);
        }
    }
}