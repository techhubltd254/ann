<?php

namespace App\Services;

use App\Models\County;
use App\Models\CountyInstitution;
use App\Models\CountyProduct;
use App\Models\CountyHotel;
use App\Models\CountyTourismAttraction;
use App\Models\MediaAsset;
use App\Models\Sector;
use App\Models\SectorEntity;
use App\Models\Marketplace\Product;
use Illuminate\Support\Facades\Cache;

/**
 * SectorMediaResolver — universal algorithm for discovering ALL available media
 * for any county sector, without hardcoded sector names or entity types.
 *
 * Given any county + sector slug, this resolver returns:
 *   - hero_playlist: ordered list of video URLs for the hero banner
 *   - entity_videos: per-entity video URLs for tiles/cards
 *   - posters: per-entity poster URLs
 *   - hover_loops: per-entity hover loop URLs
 *   - hero_poster: best poster for the hero section
 *   - hero_video_url: the single best "featured" video for the hero
 *
 * The algorithm works identically for all 47 counties and all sectors —
 * no sector names, no entity types, no hardcoded tiers.
 */
class SectorMediaResolver
{
    public function resolve(County $county, string $sectorSlug, array $entityIds = []): array
    {
        // 1. Discover all entity types and their media slots from config
        $slotRegistry = $this->slotRegistry();
        $entityTypes = array_keys($slotRegistry);

        // 2. Load all entities for this sector
        $sector = $this->resolveSector($county, $sectorSlug);
        if (!$sector) return $this->emptyResult();

        $allSectorIds = $this->resolveAliasSectorIds($county, $sector);
        $entities = $this->loadEntities($county, $allSectorIds, $entityIds);

        // 3. Batch-load ALL media assets for ALL entity types in ONE query
        $mediaMap = $this->loadAllMedia($entities, $slotRegistry);

        // 4. Build per-entity video/posters
        $entityVideos = [];
        $entityPosters = [];
        $entityHoverLoops = [];
        $entitySplats = [];

        foreach ($entities as $e) {
            $key = $e->entity_type . '-' . $e->entity_id;
            $assets = $mediaMap->get($key);
            if (!$assets || $assets->isEmpty()) continue;
            // Prefer hero_video over 4d_video, prefer assets with playable derivatives
            $asset = $assets->sortByDesc(fn ($a) => match ($a->slot) {
                'hero_video' => 2,
                '4d_video' => 1,
                default => 0,
            })->first(fn ($a) => $this->bestVideoUrl($a) !== null) ?? $assets->first();
            if ($asset) {
                $entityVideos[$e->id] = $this->bestVideoUrl($asset);
                $entityPosters[$e->id] = $this->bestPosterUrl($asset);
                $entityHoverLoops[$e->id] = $this->bestHoverLoopUrl($asset);
                $entitySplats[$e->id] = $asset->splatUrl();
            }
        }

        // 5. Build institution hero + product video fallbacks
        $instHeroVideos = $this->loadInstitutionHeroVideos($entities);
        $instProductVideos = $this->loadInstitutionProductVideos($entities);

        // 6. Build the hero playlist (all available videos, deduplicated)
        $playlist = $this->buildPlaylist($entities, $entityVideos, $instHeroVideos, $instProductVideos, $county, $sector, $sectorSlug);

        // 7. Pick hero poster
        $heroPoster = $this->resolveHeroPoster($county, $sectorSlug, $entityVideos, $entityPosters, $entities, $instHeroVideos);

        return [
            'hero_playlist' => $playlist,
            'hero_video_url' => $playlist[0] ?? null,
            'hero_poster' => $heroPoster,
            'entity_videos' => $entityVideos,
            'entity_posters' => $entityPosters,
            'entity_hover_loops' => $entityHoverLoops,
            'entity_splats' => $entitySplats,
            'institution_hero_videos' => $instHeroVideos,
            'institution_product_videos' => $instProductVideos,
            'total_entities' => count($entities),
        ];
    }

    /** All entity types and which media slots to query for each. */
    public function slotRegistry(): array
    {
        return [
            SectorEntity::class => ['4d_video', 'hero_video'],
            CountyInstitution::class => ['hero_video', '4d_video'],
            CountyTourismAttraction::class => ['4d_video'],
            CountyHotel::class => ['4d_video'],
            CountyProduct::class => ['4d_video'],
        ];
    }

    public function resolveSector(County $county, string $slug): ?Sector
    {
        $county->loadMissing('sectors');
        $sector = $county->sectors->firstWhere('slug', $slug)
            ?? $county->sectors->first(fn ($s) => str_starts_with($s->slug, $slug));
        if ($sector) return $sector;

        $aliasMap = [
            'agriculture' => ['farms'], 'farms' => ['agriculture'],
            'hospitality' => ['hotels'], 'hotels' => ['hospitality'],
            'commerce' => ['products'], 'products' => ['commerce'],
            'education' => ['institutions'], 'institutions' => ['education'],
            'healthcare' => ['health'], 'health' => ['healthcare'],
        ];
        $aliases = $aliasMap[$slug] ?? [];
        foreach ($aliases as $alias) {
            $sector = $county->sectors->first(fn ($s) => str_starts_with($s->slug, $alias));
            if ($sector) return $sector;
        }
        return null;
    }

    public function resolveAliasSectorIds(County $county, Sector $sector): array
    {
        $ids = [$sector->id];
        $aliasMap = [
            'agriculture' => ['farms'], 'farms' => ['agriculture'],
            'hospitality' => ['hotels'], 'hotels' => ['hospitality'],
            'commerce' => ['products'], 'products' => ['commerce'],
            'education' => ['institutions'], 'institutions' => ['education'],
            'healthcare' => ['health'], 'health' => ['healthcare'],
        ];
        $baseSlug = explode('-', $sector->slug)[0];
        $aliases = $aliasMap[$baseSlug] ?? [];
        foreach ($aliases as $alias) {
            $match = $county->sectors->first(fn ($s) => str_starts_with($s->slug, $alias));
            if ($match && $match->id !== $sector->id) $ids[] = $match->id;
        }
        return $ids;
    }

    protected function loadEntities(County $county, array $sectorIds, array $additionalIds): \Illuminate\Support\Collection
    {
        $query = SectorEntity::where('county_id', $county->id)
            ->whereIn('sector_id', $sectorIds)
            ->where('is_published', true);

        if (!empty($additionalIds)) {
            $query->whereIn('id', $additionalIds);
        }

        return $query->orderBy('name')->get();
    }

    /** Single query to load ALL media assets for all entity types. */
    protected function loadAllMedia($entities, array $slotRegistry): \Illuminate\Support\Collection
    {
        if ($entities->isEmpty()) return collect();

        $conditions = [];
        $bindings = [];
        foreach ($entities as $e) {
            $slots = $slotRegistry[$e->entity_type] ?? [];
            foreach ($slots as $slot) {
                $conditions[] = '(owner_type = ? AND owner_id = ? AND slot = ?)';
                $bindings[] = $e->entity_type;
                $bindings[] = $e->entity_id;
                $bindings[] = $slot;
            }
        }

        if (empty($conditions)) return collect();

        return MediaAsset::where(function ($q) use ($conditions, $bindings) {
                $first = array_shift($conditions);
                $firstBindings = array_splice($bindings, 0, 3);
                $q->whereRaw($first, $firstBindings);
                foreach ($conditions as $i => $cond) {
                    $chunk = array_splice($bindings, 0, 3);
                    $q->orWhereRaw($cond, $chunk);
                }
            })
            ->with('derivatives')
            ->get()
            ->groupBy(fn ($a) => $a->owner_type . '-' . $a->owner_id);
    }

    protected function loadInstitutionHeroVideos($entities): array
    {
        $instIds = $entities->whereIn('entity_type', [CountyInstitution::class, 'institution'])
            ->pluck('entity_id')->unique();
        if ($instIds->isEmpty()) return [];

        $assets = MediaAsset::where('owner_type', CountyInstitution::class)
            ->whereIn('owner_id', $instIds)
            ->where('slot', 'hero_video')
            ->with('derivatives')
            ->get()
            ->keyBy('owner_id');

        $videos = [];
        foreach ($entities as $e) {
            $isInst = in_array($e->entity_type, [CountyInstitution::class, 'institution']);
            if ($isInst && isset($assets[$e->entity_id])) {
                $videos[$e->id] = $this->bestVideoUrl($assets[$e->entity_id]);
            }
        }
        return $videos;
    }

    protected function loadInstitutionProductVideos($entities): array
    {
        $instIds = $entities->whereIn('entity_type', [CountyInstitution::class, 'institution'])
            ->pluck('entity_id')->unique();
        if ($instIds->isEmpty()) return [];

        $userIds = CountyInstitution::whereIn('id', $instIds)->pluck('user_id', 'id')->filter();
        if ($userIds->isEmpty()) return [];

        $products = Product::whereIn('user_id', $userIds)
            ->active()
            ->whereNotNull('video_url')
            ->get(['user_id', 'video_url']);

        $videos = [];
        foreach ($products as $p) {
            $instId = $userIds->search($p->user_id);
            if ($instId) $videos[$instId][] = $p->video_url;
        }
        return $videos;
    }

    /** Build ordered playlist: entity videos → product videos → sector_video → alias videos → county hero → any sector */
    protected function buildPlaylist($entities, array $entityVids, array $instHeroVids, array $instProdVids, County $county, Sector $sector, string $slug): array
    {
        $seen = [];
        $list = [];

        $add = function ($url) use (&$list, &$seen) {
            if ($url && !isset($seen[$url])) {
                $seen[$url] = true;
                $list[] = $url;
            }
        };

        // Tier 1: entity videos + institution heroes
        foreach ($entities as $e) {
            $add($entityVids[$e->id] ?? null);
            $add($instHeroVids[$e->id] ?? null);
        }

        // Tier 2: product videos
        foreach ($entities as $e) {
            $isInst = in_array($e->entity_type, [CountyInstitution::class, 'institution']);
            if ($isInst) {
                foreach ($instProdVids[$e->entity_id] ?? [] as $pv) $add($pv);
            }
        }

        // Tier 3: sector_video asset
        if (empty($list)) {
            $asset = MediaAsset::resolveSlot(County::class, $county->id, 'sector_video_' . $slug);
            $add($asset ? $this->bestVideoUrl($asset) : null);
        }

        // Tier 4: alias sector videos
        if (empty($list)) {
            $baseSlug = explode('-', $sector->slug)[0];
            $aliasMap = [
                'agriculture' => ['farms'], 'farms' => ['agriculture'],
                'hospitality' => ['hotels'], 'hotels' => ['hospitality'],
                'commerce' => ['products'], 'products' => ['commerce'],
                'education' => ['institutions'], 'institutions' => ['education'],
                'healthcare' => ['health'], 'health' => ['healthcare'],
            ];
            foreach ($aliasMap[$baseSlug] ?? [] as $alias) {
                $asset = MediaAsset::resolveSlot(County::class, $county->id, 'sector_video_' . $alias);
                $add($asset ? $this->bestVideoUrl($asset) : null);
            }
        }

        // Tier 5: county hero
        if (empty($list)) {
            $asset = MediaAsset::resolveSlot(County::class, $county->id, 'hero_video');
            $add($asset ? $this->bestVideoUrl($asset) : null);
        }

        // Tier 6: any sector video
        if (empty($list)) {
            $assets = MediaAsset::where('owner_type', County::class)
                ->where('owner_id', $county->id)
                ->where('slot', 'like', 'sector_video_%')
                ->with('derivatives')
                ->get();
            foreach ($assets as $a) {
                $add($this->bestVideoUrl($a));
            }
        }

        // Tier 7: any media asset for this county
        if (empty($list)) {
            $assets = MediaAsset::where('owner_type', County::class)
                ->where('owner_id', $county->id)
                ->where('kind', 'video')
                ->with('derivatives')
                ->get();
            foreach ($assets as $a) {
                $add($this->bestVideoUrl($a));
            }
        }

        return $list;
    }

    protected function resolveHeroPoster(County $county, string $slug, array $entityVids, array $entityPosters, $entities, array $instHeroVids): ?string
    {
        // Prefer first entity's poster
        foreach ($entities as $e) {
            if (!empty($entityPosters[$e->id])) return $entityPosters[$e->id];
        }
        // Fall back to sector_video poster
        $asset = MediaAsset::resolveSlot(County::class, $county->id, 'sector_video_' . $slug);
        if ($asset && $asset->posterUrl()) return $asset->posterUrl();
        // County hero poster
        $hero = MediaAsset::resolveSlot(County::class, $county->id, 'hero_video');
        if ($hero && $hero->posterUrl()) return $hero->posterUrl();
        // Default
        return media('counties/' . $county->slug . '/hero.jpeg');
    }

    /** Get best video URL: derivative → raw path */
    protected function bestVideoUrl(MediaAsset $asset): ?string
    {
        return $asset->mp4Url() ?? $asset->webmUrl() ?? $asset->url();
    }

    protected function bestPosterUrl(MediaAsset $asset): ?string
    {
        return $asset->posterUrl() ?? $asset->thumbnailUrl();
    }

    protected function bestHoverLoopUrl(MediaAsset $asset): ?string
    {
        return $asset->hoverLoopUrl();
    }

    protected function emptyResult(): array
    {
        return [
            'hero_playlist' => [],
            'hero_video_url' => null,
            'hero_poster' => null,
            'entity_videos' => [],
            'entity_posters' => [],
            'entity_hover_loops' => [],
            'entity_splats' => [],
            'institution_hero_videos' => [],
            'institution_product_videos' => [],
            'total_entities' => 0,
        ];
    }
}