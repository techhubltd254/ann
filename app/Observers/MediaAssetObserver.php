<?php

namespace App\Observers;

use App\Jobs\GenerateHlsJob;
use App\Jobs\MediaDerivativesJob;
use App\Jobs\RunPipelineJob;
use App\Jobs\SyncInstitutionJob;
use App\Models\CountyInstitution;
use App\Models\MediaAsset;
use App\Models\PipelineJob;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

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

        // Auto-dispatch 3D depth map generation if the engine is configured for auto-run
        try {
            $engineConfig = config('pipeline.engines.video_to_3d');
            if ($engineConfig && ($engineConfig['enabled'] ?? false) && ($engineConfig['auto_run'] ?? false)) {
                $job = PipelineJob::create([
                    'uuid' => (string) Str::uuid(),
                    'media_asset_id' => $asset->id,
                    'pipeline' => 'video_to_3d',
                    'engine' => 'video_to_3d',
                    'status' => 'queued',
                    'options' => [],
                ]);
                RunPipelineJob::dispatch($job->id)->onQueue('pipeline');
                Log::info("MediaAssetObserver: dispatched video_to_3d pipeline for asset {$asset->id}");
            }
        } catch (\Throwable $e) {
            Log::warning("MediaAssetObserver: failed to auto-dispatch 3d pipeline for {$asset->id}: " . $e->getMessage());
        }

        $this->bustCache($asset);

        // Trigger institution sync when a video is uploaded for an institution
        if ($asset->owner_type === CountyInstitution::class && $asset->owner_id) {
            SyncInstitutionJob::dispatch($asset->owner_id)->onQueue('sync');
        }
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
            $keys[] = "resolve:{$asset->owner_type}_{$asset->owner_id}_id";
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

        // Bust county-level caches that include media
        if ($asset->owner_type === 'App\Models\County' && $asset->owner_id) {
            $keys[] = "tile_media_ids_{$asset->owner_id}";
            $keys[] = "county_pins_{$asset->owner_id}";
            $keys[] = "kicc_county_sector_counts_{$asset->owner_id}";
        } elseif (in_array($asset->owner_type, ['App\Models\CountyInstitution', 'institution'])) {
            // Try to resolve county via institution
            try {
                $inst = \App\Models\CountyInstitution::find($asset->owner_id);
                if ($inst && $inst->county_id) {
                    $keys[] = "tile_media_ids_{$inst->county_id}";
                    $keys[] = "county_pins_{$inst->county_id}";
                }
            } catch (\Throwable) {}
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