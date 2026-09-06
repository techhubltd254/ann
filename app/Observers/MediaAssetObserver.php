<?php

namespace App\Observers;

use App\Jobs\GenerateHlsJob;
use App\Jobs\MediaDerivativesJob;
use App\Models\MediaAsset;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class MediaAssetObserver
{
    public function created(MediaAsset $asset): void
    {
        if ($asset->kind !== 'video') {
            $this->bustCache($asset);
            return;
        }

        try {
            MediaDerivativesJob::dispatch($asset->id);
            GenerateHlsJob::dispatch($asset->id);
        } catch (\Throwable $e) {
            report($e);
        }

        $this->bustCache($asset);
    }

    public function updated(MediaAsset $asset): void
    {
        if ($asset->kind === 'video' && $asset->wasChanged('path')) {
            try {
                MediaDerivativesJob::dispatch($asset->id, true);
                GenerateHlsJob::dispatch($asset->id, true);
            } catch (\Throwable $e) {
                report($e);
            }
        }

        $this->bustCache($asset);
    }

    public function deleted(MediaAsset $asset): void
    {
        $this->bustCache($asset);
    }

    protected function bustCache(MediaAsset $asset): void
    {
        $keys = [];

        if ($asset->owner_type && $asset->owner_id) {
            $keys[] = "resolve:{$asset->owner_type}_{$asset->owner_id}";
        }

        if ($asset->slot) {
            // Sector video bust
            if (str_contains($asset->slot, 'sector_video_')) {
                $slug = str_replace('sector_video_', '', $asset->slot);
                $keys[] = "resolve:county_sector_video_{$slug}";
                $keys[] = "resolve:county_sector_video_url_{$slug}";
            }
            // Hero video bust
            if (str_contains($asset->slot, 'hero')) {
                if ($asset->owner_type) {
                    $keys[] = "resolve:{$asset->owner_type}_{$asset->owner_id}_slot_hero";
                    $keys[] = "resolve:{$asset->owner_type}_{$asset->owner_id}_slot_hero_video";
                }
                if ($asset->owner_type === 'App\Models\County' || $asset->owner_type === 'county') {
                    $keys[] = "kicc_county_sector_hero_{$asset->owner_id}";
                }
            }
        }

        foreach ($keys as $key) {
            try {
                Cache::forget($key);
            } catch (\Throwable $e) {
                Log::warning("media-bust: forgot {$key}");
            }
        }
    }
}