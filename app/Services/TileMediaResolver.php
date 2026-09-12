<?php

namespace App\Services;

use App\Models\Agency;
use App\Models\County;
use App\Models\CountyInstitution;
use App\Models\MediaAsset;
use App\Models\Ministry;
use App\Models\SectorEntity;
use Illuminate\Support\Facades\Cache;

/**
 * TileMediaResolver — Universal 5-level fallback pipeline for tile hover videos.
 *
 * Every tile (county sector, ministry, institution) follows the same chain:
 *   1. Dedicated slot video  (sector_video_{slug} / ministry_video_{slug})
 *   2. Entity / agency videos (4d_video on entities under this context)
 *   3. Institution / ministry emblem flag
 *   4. County animated flag
 *   5. National animated flag
 *   ∅  Null (gradient fallback in view, no color blocks)
 */
class TileMediaResolver
{
    protected array $slotAliases = [
        'hotels' => 'hospitality',
        'farms' => 'agriculture',
        'products' => 'commerce',
        'institutions' => 'education',
        'transport' => 'industry',
    ];

    /**
     * Resolve media for a county sector tile.
     */
    public function forCountySector(County $county, string $sectorSlug): array
    {
        $slug = $this->slotAliases[$sectorSlug] ?? $sectorSlug;

        // 1. Dedicated sector video
        $asset = $this->slotAsset(County::class, $county->id, "sector_video_{$slug}");
        if ($asset) return $this->makeResult($asset, 1);

        // 2. Entity videos from SectorEntity records in this sector
        $entityAsset = $this->randomEntityVideo($county, $sectorSlug);
        if ($entityAsset) return $this->makeResult($entityAsset, 2);

        // 3. (Skip institution emblem — no single institution context for sector tiles)
        // 4. County animated flag
        $asset = $this->slotAsset(County::class, $county->id, 'county_flag_video');
        if ($asset) return $this->makeResult($asset, 4);

        // 5. National animated flag
        $asset = $this->slotAsset(County::class, 0, 'national_flag_video');
        if ($asset) return $this->makeResult($asset, 5);

        return $this->emptyResult();
    }

    /**
     * Resolve media for a ministry tile (National Government page).
     */
    public function forMinistry(Ministry $ministry): array
    {
        // 1. Dedicated ministry video
        $asset = $this->slotAsset(Ministry::class, $ministry->id, "ministry_video_{$ministry->slug}");
        if ($asset) return $this->makeResult($asset, 1);

        // 2. Agency videos under this ministry
        $agencyAsset = $this->randomAgencyVideo($ministry);
        if ($agencyAsset) return $this->makeResult($agencyAsset, 2);

        // 3. Ministry animated flag
        $asset = $this->slotAsset(Ministry::class, $ministry->id, 'ministry_flag_video');
        if ($asset) return $this->makeResult($asset, 3);

        // 4. (Skip county flag — ministries belong to national, not a county)
        // 5. National animated flag
        $asset = $this->slotAsset(County::class, 0, 'national_flag_video');
        if ($asset) return $this->makeResult($asset, 5);

        return $this->emptyResult();
    }

    /**
     * Resolve media for an institution tile.
     */
    public function forInstitution(CountyInstitution $institution, ?string $sectorSlug = null): array
    {
        $county = $institution->county;

        // 1. Dedicated sector video for this institution
        if ($sectorSlug) {
            $slug = $this->slotAliases[$sectorSlug] ?? $sectorSlug;
            $asset = $this->slotAsset(CountyInstitution::class, $institution->id, "sector_video_{$slug}");
            if ($asset) return $this->makeResult($asset, 1);
        }

        // 2. Institution's own hero_video or 4d_video
        $asset = $this->slotAsset(CountyInstitution::class, $institution->id, 'hero_video');
        if ($asset) return $this->makeResult($asset, 2);
        $asset = $this->slotAsset(CountyInstitution::class, $institution->id, '4d_video');
        if ($asset) return $this->makeResult($asset, 2);

        // 3. Institution animated emblem
        $asset = $this->slotAsset(CountyInstitution::class, $institution->id, 'institution_flag_video');
        if ($asset) return $this->makeResult($asset, 3);

        // 4. Parent county animated flag
        if ($county) {
            $asset = $this->slotAsset(County::class, $county->id, 'county_flag_video');
            if ($asset) return $this->makeResult($asset, 4);
        }

        // 5. National animated flag
        $asset = $this->slotAsset(County::class, 0, 'national_flag_video');
        if ($asset) return $this->makeResult($asset, 5);

        return $this->emptyResult();
    }

    /**
     * Generic resolver for any owner type.
     */
    public function forOwner(string $ownerType, int $ownerId, ?string $slot = null): array
    {
        if ($slot) {
            $asset = $this->slotAsset($ownerType, $ownerId, $slot);
            if ($asset) return $this->makeResult($asset, 1);
        }
        return $this->emptyResult();
    }

    // ---------------------------------------------------------------

    protected function slotAsset(string $ownerType, int $ownerId, string $slot): ?MediaAsset
    {
        return MediaAsset::resolveSlot($ownerType, $ownerId, $slot);
    }

    protected function makeResult(MediaAsset $asset, int $level): array
    {
        return [
            'videoUrl' => $asset->mp4Url(),
            'hoverLoopUrl' => $asset->hoverLoopUrl(),
            'posterUrl' => $asset->posterUrl(),
            'fallback_level' => $level,
        ];
    }

    protected function emptyResult(): array
    {
        return [
            'videoUrl' => null,
            'hoverLoopUrl' => null,
            'posterUrl' => null,
            'fallback_level' => null,
        ];
    }

    /**
     * Pick a random entity video from SectorEntity records in this sector.
     */
    protected function randomEntityVideo(County $county, string $sectorSlug): ?MediaAsset
    {
        $sector = \App\Models\Sector::where('slug', $sectorSlug)->first();
        if (!$sector) return null;

        $entityIds = Cache::remember("tile_entity_ids_{$county->id}_{$sector->id}", 300, function () use ($county, $sector) {
            return SectorEntity::where('county_id', $county->id)
                ->where('sector_id', $sector->id)
                ->pluck('id');
        });

        if ($entityIds->isEmpty()) return null;

        $asset = MediaAsset::where('owner_type', SectorEntity::class)
            ->whereIn('owner_id', $entityIds)
            ->where('slot', '4d_video')
            ->ready()
            ->with('derivatives')
            ->inRandomOrder()
            ->first();

        return $asset;
    }

    /**
     * Pick a random agency video under a ministry.
     */
    protected function randomAgencyVideo(Ministry $ministry): ?MediaAsset
    {
        $agencyIds = Cache::remember("tile_agency_ids_{$ministry->id}", 300, function () use ($ministry) {
            return $ministry->agencies()->pluck('id');
        });

        if ($agencyIds->isEmpty()) return null;

        $asset = MediaAsset::where('owner_type', Agency::class)
            ->whereIn('owner_id', $agencyIds)
            ->whereIn('slot', ['agency_video', '4d_video', 'hero_video'])
            ->ready()
            ->with('derivatives')
            ->inRandomOrder()
            ->first();

        return $asset;
    }
}