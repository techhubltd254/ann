<?php

namespace App\Services;

use App\Models\Agency;
use App\Models\County;
use App\Models\CountyInstitution;
use App\Models\MediaAsset;
use App\Models\Ministry;
use App\Models\Sector;
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
class RecoveredSectorMediaResolver
{
    protected array $slotAliases = [
        'hotels' => 'hospitality',
        'farms' => 'agriculture',
        'products' => 'commerce',
        'institutions' => 'education',
        'transport' => 'industry',
    ];

    // Reverse alias: DB slug → upload form slug (admin uploads use form names for slot keys)
    protected array $uploadFormSlugs = [
        'agriculture' => 'farms',
        'manufacturing' => 'products',
        'creative' => 'culture',
        'commerce' => 'products',
        'hospitality' => 'hotels',
        'industry' => 'transport',
    ];

    /**
     * Resolve media for a county sector tile.
     */
    public function forCountySector(County $county, string $sectorSlug): array
    {
        $slug = $this->uploadFormSlugs[$sectorSlug] ?? $this->slotAliases[$sectorSlug] ?? $sectorSlug;

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
     * Batch-resolve tile media for ALL county sectors in 3 total queries.
     * Caches asset IDs only — URLs are resolved at render time so CDN
     * env var changes apply immediately without cache flush.
     */
    public function forAllCountySectors(County $county, array $sectorData): array
    {
        $version = \Illuminate\Support\Facades\Cache::get("tile_media_version_{$county->id}", 1);
        $cacheKey = "tile_media_ids_{$county->id}_v{$version}";
        $idMap = Cache::remember($cacheKey, config('kicc.cache_ttl.public', 21600), function () use ($county, $sectorData) {
            $slugs = array_map(fn($s) => $this->uploadFormSlugs[$s['sector_slug']] ?? $this->slotAliases[$s['sector_slug']] ?? $s['sector_slug'], $sectorData);

            $slotNames = array_map(fn($slug) => "sector_video_{$slug}", $slugs);
            $sectorAssets = MediaAsset::where('owner_type', County::class)
                ->where('owner_id', $county->id)
                ->whereIn('slot', $slotNames)
                ->ready()
                ->get()
                ->keyBy('slot');

            $countyFlag = MediaAsset::resolveSlot(County::class, $county->id, 'county_flag_video');
            $nationalFlag = MediaAsset::resolveSlot(County::class, 0, 'national_flag_video');

            $result = [];
            foreach ($sectorData as $name => $s) {
                $slug = $this->slotAliases[$s['sector_slug']] ?? $s['sector_slug'];
                $asset = $sectorAssets->get("sector_video_{$slug}");

                if (!$asset) {
                    $asset = $this->randomEntityVideo($county, $s['sector_slug']);
                }
                if (!$asset && $countyFlag) {
                    $asset = $countyFlag;
                }
                if (!$asset && $nationalFlag) {
                    $asset = $nationalFlag;
                }

                $result[$s['sector_slug']] = $asset ? ['id' => $asset->id, 'level' => 1] : null;
            }

            return $result;
        });

        // Resolve URLs from cached asset IDs (always uses current MEDIA_CDN_URL)
        $assetIds = array_values(array_filter(array_column($idMap, 'id')));
        $assets = [];
        if ($assetIds) {
            $assets = MediaAsset::with('derivatives')->whereIn('id', $assetIds)->get()->keyBy('id');
        }

        $result = [];
        foreach ($sectorData as $name => $s) {
            $entry = $idMap[$s['sector_slug']] ?? null;
            if ($entry && ($asset = $assets[$entry['id']] ?? null)) {
                $result[$s['sector_slug']] = $this->makeResult($asset, $entry['level']);
            } else {
                $result[$s['sector_slug']] = $this->emptyResult();
            }
        }

        return $result;
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
     * Resolve media for a specific entity (attraction, hotel, product).
     * Falls back to parent institution's hero video if no dedicated video exists.
     */
    public function forEntity(SectorEntity $se): array
    {
        $county = $se->county;

        // 1. Entity's own 4d_video
        $asset = $this->slotAsset(SectorEntity::class, $se->id, '4d_video');
        if ($asset) return $this->makeResult($asset, 1);

        // 2. Parent institution in same sector
        $inst = $this->resolveParentInstitution($se);
        if ($inst) {
            $asset = $this->slotAsset(CountyInstitution::class, $inst->id, 'hero_video');
            if ($asset) return $this->makeResult($asset, 2);
            $asset = $this->slotAsset(CountyInstitution::class, $inst->id, '4d_video');
            if ($asset) return $this->makeResult($asset, 2);
        }

        // 3. County flag
        if ($county) {
            $asset = $this->slotAsset(County::class, $county->id, 'county_flag_video');
            if ($asset) return $this->makeResult($asset, 4);
        }

        // 4. National flag
        $asset = $this->slotAsset(County::class, 0, 'national_flag_video');
        if ($asset) return $this->makeResult($asset, 5);

        return $this->emptyResult();
    }

    /**
     * Find the parent institution for an entity via SectorEntity.
     * Looks for a CountyInstitution-type SectorEntity in the same county + sector.
     */
    protected function resolveParentInstitution(SectorEntity $se): ?CountyInstitution
    {
        $instSE = SectorEntity::where('county_id', $se->county_id)
            ->where('sector_id', $se->sector_id)
            ->where('entity_type', CountyInstitution::class)
            ->where('entity_id', '!=', $se->entity_id)
            ->where('is_published', true)
            ->first();

        if (!$instSE) return null;

        return CountyInstitution::find($instSE->entity_id);
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
        $sector = Sector::where('slug', $sectorSlug)->first();
        if (!$sector) return null;

        $entities = SectorEntity::where('county_id', $county->id)
            ->where('sector_id', $sector->id)
            ->where(function ($q) { $q->where('is_published', true)->orWhere('isPublished', true); })
            ->get();

        if ($entities->isEmpty()) return null;

        // 1. 4d_video on SectorEntity (highest priority — explicitly placed media)
        $entityIds = $entities->pluck('id');
        $asset = MediaAsset::where('owner_type', SectorEntity::class)
            ->whereIn('owner_id', $entityIds)
            ->where('slot', '4d_video')
            ->ready()
            ->with('derivatives')
            ->inRandomOrder()
            ->first();
        if ($asset) return $asset;

        // 2. Institution hero videos for institutions with entities in this sector
        $instIds = $entities->where('entity_type', CountyInstitution::class)->pluck('entity_id')->unique();
        if ($instIds->isNotEmpty()) {
            $asset = MediaAsset::where('owner_type', CountyInstitution::class)
                ->whereIn('owner_id', $instIds)
                ->where('slot', 'hero_video')
                ->ready()
                ->with('derivatives')
                ->inRandomOrder()
                ->first();
            if ($asset) return $asset;
        }

        return null;
    }

    /**
     * Pick a random agency video under a ministry.
     */
    protected function randomAgencyVideo(Ministry $ministry): ?MediaAsset
    {
        $agencyIds = $ministry->agencies()->pluck('id');

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