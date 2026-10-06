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
            // Only dispatch HLS job — it generates poster + hover loop + HLS
            // in one pass from a single R2 download. No duplicate 1GB transfers.
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

        // Generic resolution keys
        if ($asset->owner_type && $asset->owner_id) {
            $keys[] = "resolve:{$asset->owner_type}_{$asset->owner_id}_id";
            $keys[] = "resolve:{$asset->owner_type}_{$asset->owner_id}";
        }

        // Resolve county_id from owner (County or Institution)
        $countyId = null;
        if (($asset->owner_type === 'App\Models\County' || $asset->owner_type === 'county') && $asset->owner_id) {
            $countyId = $asset->owner_id;
        } elseif (in_array($asset->owner_type, ['App\Models\CountyInstitution', 'institution'])) {
            try {
                $inst = \App\Models\CountyInstitution::find($asset->owner_id);
                if ($inst?->county_id) $countyId = $inst->county_id;
            } catch (\Throwable) {}
        }

        if ($asset->slot) {
            if (str_contains($asset->slot, 'sector_video_')) {
                $slug = str_replace('sector_video_', '', $asset->slot);
                $keys[] = "resolve:county_sector_video_{$slug}";
                $keys[] = "resolve:county_sector_video_url_{$slug}";
            }
            if (str_contains($asset->slot, 'hero')) {
                if ($asset->owner_type && $asset->owner_id) {
                    $keys[] = "resolve:{$asset->owner_type}_{$asset->owner_id}_slot_hero";
                    $keys[] = "resolve:{$asset->owner_type}_{$asset->owner_id}_slot_hero_video";
                }
                if ($countyId) {
                    $keys[] = "kicc_county_sector_hero_{$countyId}";
                }
            }
        }

        // CountyController hero video cache keys — resolve:county_hero_id_{id}
        if ($countyId) {
            $keys[] = "resolve:county_hero_id_{$countyId}";
            $keys[] = "resolve:county_hero_url_{$countyId}";
            $keys[] = "tile_media_ids_{$countyId}";
            $keys[] = "county_pins_{$countyId}";
            $keys[] = "kicc_county_sector_counts_{$countyId}";
            Cache::increment("tile_media_version_{$countyId}");
            Cache::increment("kicc_sector_version_{$countyId}_");
        }

        // CountyController institution hero video — resolve:inst_hero_id_{id}
        if ($asset->owner_type === 'App\Models\CountyInstitution' && $asset->owner_id) {
            $keys[] = "resolve:inst_hero_id_{$asset->owner_id}";
        }

        // National government hero cache — ng_hero_{buster}
        if (($asset->owner_type === 'App\Models\Ministry' || $asset->owner_type === 'ministry')
             || ($asset->slot && str_contains($asset->slot, 'national'))) {
            $buster = Cache::get('ng_version', 1);
            $keys[] = "ng_hero_{$buster}";
            Cache::increment('ng_version');
        }

        // National sector aggregated caches
        $keys[] = 'kicc_national_sector_counts';

        // Bust ALL national sector detail caches (version bump)
        Cache::increment('kicc_nat_sector_global_version');

        foreach ($keys as $key) {
            try {
                Cache::forget($key);
            } catch (\Throwable $e) {
                Log::warning("media-bust: forgot {$key}");
            }
        }
    }
}