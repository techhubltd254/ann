<?php

namespace App\Observers;

use App\Models\CountyInstitution;
use App\Models\MediaAsset;
use App\Models\SectorEntity;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class PlatformCacheObserver
{
    public function created(CountyInstitution|MediaAsset|SectorEntity $model): void
    {
        $this->bust($model);
    }

    public function updated(CountyInstitution|MediaAsset|SectorEntity $model): void
    {
        $this->bust($model);
    }

    public function deleted(CountyInstitution|MediaAsset|SectorEntity $model): void
    {
        $this->bust($model);
    }

    protected function bust(CountyInstitution|MediaAsset|SectorEntity $model): void
    {
        $countyId = $model->county_id ?? null;
        $sectorId = $model->sector_id ?? null;

        if (method_exists($model, 'county') && $model->county_id) {
            $countyId = $model->county_id;
        }

        $keys = [];

        // County-level keys
        if ($countyId) {
            $keys[] = "kicc_county_sector_counts_{$countyId}";
            $keys[] = "kicc_county_sector_hero_{$countyId}";
            $keys[] = "resolve:county_hero_{$countyId}";
            $keys[] = "resolve:county_hero_url_{$countyId}";
            $keys[] = "resolve:county_hero_post_{$countyId}";
            $keys[] = "kicc_county_linked_sectors_{$countyId}";
            $keys[] = "kicc_county_attractions_{$countyId}";
            $keys[] = "kicc_county_hotels_{$countyId}";
            $keys[] = "kicc_county_products_{$countyId}";
            $keys[] = "kicc_county_exhibitions_{$countyId}";

            // Institution-specific
            if ($model instanceof CountyInstitution) {
                $keys[] = "resolve:inst_hero_{$model->id}";
                if ($model->user_id) {
                    $keys[] = "inst_product_videos_{$model->user_id}";
                }
                // Bust sector item caches for all sectors this institution belongs to
                if ($model->relationLoaded('sectorEntities')) {
                    foreach ($model->sectorEntities as $se) {
                        for ($pg = 1; $pg <= 10; $pg++) {
                            $keys[] = "kicc_county_sector_items_{$countyId}_{$se->sector_id}_1_{$pg}";
                        }
                    }
                }
            }

            // SectorEntity-specific
            if ($model instanceof SectorEntity && $sectorId) {
                for ($pg = 1; $pg <= 10; $pg++) {
                    $keys[] = "kicc_county_sector_items_{$countyId}_{$sectorId}_1_{$pg}";
                }
                $keys[] = "resolve:county_sector_video_{$countyId}_{$sectorId}";
            }
        }

        // MediaAsset-specific busting
        if ($model instanceof MediaAsset) {
            $owner = $model->owner_type;
            $ownerId = $model->owner_id;
            if ($owner && $ownerId) {
                $keys[] = "resolve:{$owner}_{$ownerId}";
                $keys[] = "resolve:{$owner}_{$ownerId}_slot_{$model->slot}";
            }
            // If this is a sector_video or hero_video, bust sector level too
            if ($model->slot && str_contains($model->slot, 'sector_video')) {
                $slug = str_replace('sector_video_', '', $model->slot);
                $keys[] = "resolve:county_sector_video_{$slug}";
                $keys[] = "resolve:county_sector_video_url_{$slug}";
            }
        }

        foreach ($keys as $key) {
            try {
                Cache::forget($key);
            } catch (\Throwable $e) {
                Log::warning("cache-bust: failed to forget {$key}", ['error' => $e->getMessage()]);
            }
        }
    }
}