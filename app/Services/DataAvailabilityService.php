<?php

namespace App\Services;

use App\Models\County;
use App\Models\CountyInstitution;
use App\Models\MediaAsset;
use App\Models\Sector;
use App\Models\SectorEntity;
use App\Models\Marketplace\Product;
use Illuminate\Support\Facades\Cache;

/**
 * Central data availability engine.
 *
 * For any county, sector, or institution, this service answers:
 *   "What data exists here?" — videos, products, reviews, entities.
 *
 * All queries are cached with a configurable TTL and busted via model events.
 */
class DataAvailabilityService
{
    // ── Public entry points ──

    public function forCounty(County $county): array
    {
        return Cache::remember(
            "dav:county:{$county->id}",
            $this->ttl(),
            fn () => $this->buildCounty($county),
        );
    }

    public function forSector(County $county, int $sectorId, int $page = 1): array
    {
        $version = Cache::get("dav:ver:{$county->id}:{$sectorId}", 1);
        return Cache::remember(
            "dav:sector:{$county->id}:{$sectorId}:{$version}:{$page}",
            $this->ttl(),
            fn () => $this->buildSector($county, $sectorId, $page),
        );
    }

    public function forInstitution(CountyInstitution $inst): array
    {
        return Cache::remember(
            "dav:inst:{$inst->id}",
            $this->ttl(),
            fn () => $this->buildInstitution($inst),
        );
    }

    // ── Cache busting ──

    public function bustCounty(int $countyId): void
    {
        Cache::forget("dav:county:{$countyId}");
    }

    public function bustSector(int $countyId, int $sectorId): void
    {
        $ver = (int) Cache::get("dav:ver:{$countyId}:{$sectorId}", 1);
        Cache::put("dav:ver:{$countyId}:{$sectorId}", $ver + 1, now()->addDay());
    }

    public function bustInstitution(int $instId): void
    {
        Cache::forget("dav:inst:{$instId}");
    }

    // ── Internal builders ──

    protected function buildCounty(County $county): array
    {
        $county->load('sectors');

        $sectors = $county->sectors->map(fn (Sector $s) => [
            'id' => $s->id,
            'name' => $s->name,
            'slug' => $s->slug,
            'entity_count' => SectorEntity::where('county_id', $county->id)
                ->where('sector_id', $s->id)
                ->where('is_published', true)
                ->count(),
        ])->filter(fn ($s) => $s['entity_count'] > 0)->values();

        $heroAsset = MediaAsset::resolveSlot(County::class, $county->id, 'hero_video');

        $featuredAttractions = $county->tourismAttractions()
            ->where('is_published', true)
            ->orderBy('name')->take(12)
            ->get(['id', 'name', 'slug', 'description', 'latitude', 'longitude', 'entry_fee', 'images']);

        $featuredHotels = $county->hotels()
            ->where('is_published', true)
            ->orderByDesc('star_rating')->take(8)
            ->get(['id', 'name', 'slug', 'star_rating', 'address', 'latitude', 'longitude', 'images']);

        $countyProducts = $county->products()
            ->where('is_published', true)
            ->whereNotNull('price')
            ->orderByDesc('price')->take(8)
            ->get(['id', 'name', 'slug', 'price', 'images']);

        $exhibitions = $county->exhibitions()
            ->where('status', 'published')
            ->orderByDesc('start_date')->take(3)
            ->get(['id', 'name', 'slug', 'start_date', 'end_date', 'cover_image']);

        return [
            'sectors' => $sectors,
            'hero' => $this->mediaUrls($heroAsset),
            'featured_attractions' => $featuredAttractions,
            'featured_hotels' => $featuredHotels,
            'featured_products' => $countyProducts,
            'exhibitions' => $exhibitions,
        ];
    }

    protected function buildSector(County $county, int $sectorId, int $page): array
    {
        $sector = Sector::find($sectorId);
        if (!$sector) return ['entities' => [], 'videos' => [], 'hero' => null];

        // Alias resolution
        $slugMap = [
            'agriculture' => ['agriculture', 'farms'],
            'hospitality' => ['hotels', 'hospitality'],
            'commerce' => ['products', 'commerce'],
            'education' => ['institutions', 'education'],
            'healthcare' => ['health', 'healthcare'],
        ];
        $aliases = $slugMap[$sector->slug] ?? [$sector->slug];

        $allSectorIds = Sector::whereIn('slug', function ($q) use ($aliases) {
            // match slugs that start with any alias
        })->pluck('id');

        $allSectorIds = collect([$sectorId]);
        foreach ($aliases as $alias) {
            $matched = Sector::where('slug', 'like', $alias . '%')
                ->where('id', '!=', $sectorId)
                ->pluck('id');
            $allSectorIds = $allSectorIds->merge($matched)->unique();
        }

        // Entities — exclude orphans whose parent was hard-deleted
        $instTypeClasses = [CountyInstitution::class, 'institution'];
        $liveInstIds = CountyInstitution::where('county_id', $county->id)
            ->where('is_published', true)
            ->pluck('id');

        $entities = SectorEntity::where('county_id', $county->id)
            ->whereIn('sector_id', $allSectorIds)
            ->where('is_published', true)
            ->where(function ($q) use ($instTypeClasses, $liveInstIds) {
                // Non-institution entities pass through; institution entities
                // must have a live parent CountyInstitution
                $q->whereNotIn('entity_type', $instTypeClasses)
                  ->orWhereIn('entity_id', $liveInstIds);
            })
            ->orderBy('name')
            ->paginate(12, ['*'], 'page', $page);

        $entityIds = $entities->pluck('id');

        // Batch-load ALL video assets for these entities
        $videoAssets = MediaAsset::where('owner_type', SectorEntity::class)
            ->whereIn('owner_id', $entityIds)
            ->whereIn('slot', ['4d_video', 'hero_video'])
            ->with('derivatives')
            ->get()
            ->groupBy('owner_id');

        // Institution IDs for hero + product videos
        $instEntities = $entities->filter(
            fn ($e) => in_array($e->entity_type, [CountyInstitution::class, 'institution'])
        );
        $instIds = $instEntities->pluck('entity_id')->unique();

        $instHeroAssets = collect();
        $instProductVideos = [];
        if ($instIds->isNotEmpty()) {
            $instHeroAssets = MediaAsset::where('owner_type', CountyInstitution::class)
                ->whereIn('owner_id', $instIds)
                ->where('slot', 'hero_video')
                ->with('derivatives')
                ->get()
                ->keyBy('owner_id');

            // Product videos (batch-loaded from all institution users)
            $userIds = CountyInstitution::whereIn('id', $instIds)
                ->pluck('user_id', 'id')
                ->filter();
            if ($userIds->isNotEmpty()) {
                $productRows = Product::whereIn('user_id', $userIds)
                    ->active()
                    ->whereNotNull('video_url')
                    ->get(['user_id', 'video_url']);
                foreach ($productRows as $pr) {
                    $instId = $userIds->search($pr->user_id);
                    if ($instId) {
                        $instProductVideos[$instId][] = $pr->video_url;
                    }
                }
            }
        }

        // Build entity response with video URLs
        $entitiesData = $entities->map(function ($e) use ($videoAssets, $instHeroAssets, $instProductVideos) {
            $assets = $videoAssets->get($e->id, collect());
            $fourD = $assets->firstWhere('slot', '4d_video');
            $hero = $assets->firstWhere('slot', 'hero_video');

            // For institution entities, also check institution-level hero
            $instHero = null;
            $prodVids = [];
            if (in_array($e->entity_type, [CountyInstitution::class, 'institution'])) {
                $instHeroAsset = $instHeroAssets->get($e->entity_id);
                if ($instHeroAsset) {
                    $instHero = $this->mediaUrls($instHeroAsset);
                }
                $prodVids = $instProductVideos[$e->entity_id] ?? [];
            }

            return [
                'id' => $e->id,
                'name' => $e->name,
                'entity_type' => $e->entity_type,
                'entity_id' => $e->entity_id,
                'description' => $e->description,
                'video_4d' => $fourD ? $this->mediaUrls($fourD) : null,
                'video_hero' => $hero ? $this->mediaUrls($hero) : null,
                'video_institution_hero' => $instHero,
                'video_product_fallback' => $prodVids[0] ?? null,
                'latitude' => $e->latitude,
                'longitude' => $e->longitude,
                'tags' => $e->tags,
            ];
        });

        // Sector-level hero video (from county sector_video_* slot)
        $sectorHeroAsset = MediaAsset::where('owner_type', County::class)
            ->where('owner_id', $county->id)
            ->where('slot', 'sector_video_' . $sector->slug)
            ->first();

        // Collect all available videos into a flat hero playlist
        $playlist = [];
        if ($sectorHeroAsset) {
            $url = $sectorHeroAsset->mp4Url() ?? $sectorHeroAsset->url();
            if ($url) $playlist[] = $url;
        }
        foreach ($entitiesData as $ed) {
            foreach (['video_4d', 'video_hero', 'video_institution_hero', 'video_product_fallback'] as $key) {
                $v = $ed[$key] ?? null;
                if ($v && is_string($v)) $playlist[] = $v;
            }
        }

        // County hero as last resort
        if (empty($playlist)) {
            $countyHero = MediaAsset::resolveSlot(County::class, $county->id, 'hero_video');
            $url = $countyHero?->mp4Url() ?? $countyHero?->url();
            if ($url) $playlist[] = $url;
        }

        return [
            'sector' => ['id' => $sector->id, 'name' => $sector->name, 'slug' => $sector->slug],
            'entities' => $entitiesData,
            'playlist' => array_values(array_unique(array_filter($playlist))),
            'hero_video' => $sectorHeroAsset ? $this->mediaUrls($sectorHeroAsset) : null,
            'total' => $entities->total(),
            'per_page' => $entities->perPage(),
            'current_page' => $entities->currentPage(),
            'last_page' => $entities->lastPage(),
        ];
    }

    protected function buildInstitution(CountyInstitution $inst): array
    {
        $heroAsset = MediaAsset::resolveSlot(CountyInstitution::class, $inst->id, 'hero_video');

        // Sector entities
        $sectorEntities = $inst->sectorEntities()->with('sector')->get();

        // Product videos
        $productVideos = [];
        if ($inst->user_id) {
            $productVideos = Product::where('user_id', $inst->user_id)
                ->active()
                ->whereNotNull('video_url')
                ->take(10)
                ->pluck('video_url')
                ->filter()
                ->values()
                ->all();
        }

        // Library videos (JSON)
        $libraryVideos = $inst->videos ?? [];

        // Reviews
        $reviews = \App\Models\Review::where('reviewable_type', CountyInstitution::class)
            ->where('reviewable_id', $inst->id)
            ->where('status', 'approved')
            ->with('user')
            ->latest()
            ->get();

        // Products
        $products = collect();
        if ($inst->user_id) {
            $products = Product::with(['county', 'category', 'variants' => fn ($q) => $q->where('is_active', true), 'images'])
                ->where('user_id', $inst->user_id)
                ->active()
                ->get();
        }

        // Build fallback playlist
        $playlist = [];
        if ($heroUrl = $heroAsset?->mp4Url() ?? $heroAsset?->url()) {
            $playlist[] = $heroUrl;
        }
        foreach ($productVideos as $pv) $playlist[] = $pv;
        foreach ($libraryVideos as $lv) {
            $url = $lv['path'] ?? $lv['url'] ?? null;
            if ($url) $playlist[] = $url;
        }
        // County hero as last resort
        if (empty($playlist) && $inst->county_id) {
            $countyHero = MediaAsset::resolveSlot(County::class, $inst->county_id, 'hero_video');
            $url = $countyHero?->mp4Url() ?? $countyHero?->url();
            if ($url) $playlist[] = $url;
        }

        return [
            'hero' => $this->mediaUrls($heroAsset),
            'hero_playlist' => array_values(array_unique(array_filter($playlist))),
            'sector_entities' => $sectorEntities,
            'products' => $products,
            'product_videos' => $productVideos,
            'library_videos' => $libraryVideos,
            'reviews' => $reviews,
        ];
    }

    protected function mediaUrls(?MediaAsset $asset): ?array
    {
        if (!$asset) return null;
        return [
            'mp4' => $asset->mp4Url() ?? $asset->url(),
            'hls' => $asset->derivativeUrl('hls_master'),
            'poster' => $asset->posterUrl(),
            'splat' => $asset->splatUrl(),
            'hover_loop' => $asset->hoverLoopUrl(),
        ];
    }

    protected function ttl(): int
    {
        return config('kicc.cache_ttl.public', 600);
    }
}