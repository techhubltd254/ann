<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\County;
use App\Models\MediaAsset;
use App\Models\CountyInstitution;
use App\Models\Marketplace\Product;
use App\Models\SectorEntity;
use App\Services\InstitutionSyncService;
use App\Services\SectorPitchService;
use App\Services\CorrelationService;
use Illuminate\Support\Facades\Cache;

class CountyController extends Controller
{
    public function index()
    {
        $countyIds = Cache::remember('kicc_counties_index', config('kicc.cache_ttl.public', 21600), fn () => County::orderBy('name')->pluck('id')->all());
        $counties = County::withCount('sectors')->whereIn('id', $countyIds)->orderBy('name')->get();

        // Hero media per county card — YouTube-style play on hover/in-view
        $countyHeroes = [];
        $heroAssets = MediaAsset::where('owner_type', County::class)
            ->whereIn('owner_id', $countyIds)
            ->where('slot', 'hero_video')
            ->where('status', 'ready')
            ->with('derivatives')
            ->get()
            ->keyBy('owner_id');
        foreach ($counties as $c) {
            $a = $heroAssets->get($c->id);
            $countyHeroes[$c->slug] = [
                'video' => $a?->mp4Url() ?? $a?->url(),
                'hover' => $a?->hoverLoopUrl(),
                'poster' => $a?->posterUrl() ?? media('counties/' . $c->slug . '/hero.jpeg'),
            ];
        }

        return view('counties.index', compact('counties', 'countyHeroes'));
    }

    public function show(County $county)
    {
        $county->load('sectors');
        $sectors = $county->sectors;

        // Dynamic sector data — count entities per sector across all entity types
        $sectorEntityCounts = Cache::remember("kicc_county_sector_counts_{$county->id}", config('kicc.cache_ttl.public', 21600), function () use ($county) {
            return \App\Models\SectorEntity::where('county_id', $county->id)
                ->where('is_published', true)
                ->selectRaw('sector_id, count(*) as total')
                ->groupBy('sector_id')
                ->pluck('total', 'sector_id')
                ->toArray();
        });

        $sectorNames = [
            'tourism' => 'Tourism', 'hospitality' => 'Hospitality',
            'farms' => 'Agriculture', 'agriculture' => 'Agriculture',
            'products' => 'Commerce & End Products', 'commerce' => 'Commerce & End Products',
            'education' => 'Education', 'institutions' => 'Education',
            'transport' => 'Transport', 'health' => 'Healthcare',
            'healthcare' => 'Healthcare', 'culture' => 'Culture',
            'industries' => 'Industries', 'energy' => 'Energy',
        ];

        $routeMap = [
            'tourism' => 'tourism', 'hospitality' => 'hotels',
            'farms' => 'farms', 'agriculture' => 'farms',
            'products' => 'products', 'commerce' => 'products',
            'education' => 'education', 'institutions' => 'education',
            'transport' => 'transport', 'health' => 'health',
            'healthcare' => 'health', 'culture' => 'culture',
            'industries' => 'industries', 'energy' => 'energy',
        ];

        $sectorIcons = [
            'tourism' => '🏛', 'hospitality' => '🏨',
            'farms' => '🌾', 'agriculture' => '🌱',
            'products' => '📦', 'commerce' => '📦',
            'education' => '📚', 'institutions' => '📚',
            'transport' => '🚢', 'health' => '🏥',
            'healthcare' => '🏥', 'culture' => '🎭',
            'industries' => '🏭', 'energy' => '⚡',
        ];

        $sectorData = [];
        foreach ($sectors as $s) {
            $baseSlug = explode('-', $s->slug)[0];
            $name = $sectorNames[$baseSlug] ?? $s->name;
            $route = $routeMap[$baseSlug] ?? $baseSlug;
            $count = $sectorEntityCounts[$s->id] ?? 0;
            if ($count > 0 && !isset($sectorData[$name])) {
                $sectorData[$name] = ['count' => $count, 'route' => $route, 'sector_slug' => $baseSlug, 'icon' => $sectorIcons[$baseSlug] ?? '📋'];
            } elseif ($count > 0 && isset($sectorData[$name])) {
                $sectorData[$name]['count'] += $count;
            }
        }

        $featuredAttractionIds = Cache::remember("kicc_county_attractions_{$county->id}", config('kicc.cache_ttl.public', 21600), function () use ($county) {
            return $county->tourismAttractions()
                ->leftJoin('reviews', function ($j) {
                    $j->on('reviews.reviewable_id', '=', 'county_tourism_attractions.id')
                      ->where('reviews.reviewable_type', '=', \App\Models\CountyTourismAttraction::class)
                      ->where('reviews.status', 'approved');
                })
                ->where('is_published', true)
                ->selectRaw('county_tourism_attractions.id, AVG(COALESCE(reviews.rating,0)) as avg_rating')
                ->groupBy('county_tourism_attractions.id')
                ->orderByDesc('avg_rating')
                ->orderBy('county_tourism_attractions.name')
                ->take(12)
                ->pluck('county_tourism_attractions.id')
                ->all();
        });
        $featuredHotelIds = Cache::remember("kicc_county_hotels_{$county->id}", config('kicc.cache_ttl.public', 21600), fn () => $county->hotels()->where('is_published', true)->orderByDesc('star_rating')->take(8)->pluck('id')->all());
        $countyProductIds = Cache::remember("kicc_county_products_{$county->id}", config('kicc.cache_ttl.public', 21600), fn () => $county->products()->where('is_published', true)->whereNotNull('price')->orderByDesc('price')->take(8)->pluck('id')->all());
        $exhibitionIds = Cache::remember("kicc_county_exhibitions_{$county->id}", config('kicc.cache_ttl.public', 21600), fn () => $county->exhibitions()->where('status', 'published')->orderBy('start_date', 'desc')->take(3)->pluck('id')->all());
        $linkedSectorIds = Cache::remember("kicc_county_linked_sectors_{$county->id}", config('kicc.cache_ttl.public', 21600), fn () => $county->sectors()->orderBy('name')->pluck('sectors.id')->all());

        $featuredAttractions = $featuredAttractionIds ? $county->tourismAttractions()->whereIn('id', $featuredAttractionIds)->orderBy('name')->get() : collect();
        $featuredHotels = $featuredHotelIds ? $county->hotels()->whereIn('id', $featuredHotelIds)->orderByDesc('star_rating')->get() : collect();
        $countyProducts = $countyProductIds ? $county->products()->whereIn('id', $countyProductIds)->orderByDesc('price')->get() : collect();
        $exhibitions = $exhibitionIds ? $county->exhibitions()->whereIn('id', $exhibitionIds)->orderBy('start_date', 'desc')->get() : collect();
        $linkedSectors = $linkedSectorIds ? $county->sectors()->whereIn('sectors.id', $linkedSectorIds)->orderBy('name')->get() : collect();

        // Resolve accurate thumbnails for every card (video poster → category fallback → branded placeholder)
        $attractionThumbs = $featuredAttractions->mapWithKeys(fn($a) => [$a->id => \App\Services\ThumbnailService::for($a, $county->slug)]);
        $hotelThumbs = $featuredHotels->mapWithKeys(fn($h) => [$h->id => \App\Services\ThumbnailService::for($h, $county->slug)]);
        $productThumbs = $countyProducts->mapWithKeys(fn($p) => [$p->id => \App\Services\ThumbnailService::for($p, $county->slug)]);

        $countyMediaId = Cache::remember("resolve:county_hero_id_" . $county->id, config('kicc.cache_ttl.public', 21600), fn() => MediaAsset::resolveSlot(County::class, $county->id, 'hero_video')?->id);
        $countyMedia = $countyMediaId ? MediaAsset::with('derivatives')->find($countyMediaId) : null;

        // Sector tile media — unified 5-level fallback via TileMediaResolver (batched: 3 queries total)
        $tileResolver = app(\App\Services\TileMediaResolver::class);
        $tileMedia = $tileResolver->forAllCountySectors($county, $sectorData);
        $sectorPitches = [];
        foreach ($sectorData as $name => $s) {
            $cacheKey = "sector_pitch_{$county->id}_{$s['sector_slug']}";
            $sectorPitches[$s['sector_slug']] = Cache::remember($cacheKey, 86400, fn() =>
                SectorPitchService::generate($county, $s['sector_slug'], $s)
            );
        }

        // ═══ HERO FALLBACK ═══
        $countyHeroFallback = [];
        if (!$countyMedia || !($countyMedia->mp4Url() ?? $countyMedia->url())) {
            $fallback = array_values(array_filter(array_map(fn($m) => $m['videoUrl'], $tileMedia)));
            $countyHeroFallback = $fallback;
        }

        // Entity media — video pipeline for attractions, hotels, products (batched)
        $entityMedia = [];
        $resolver = app(\App\Services\TileMediaResolver::class);

        // Batch load all SectorEntity records for attractions, hotels, products
        $attractionIds = $featuredAttractions->pluck('id');
        $hotelIds = $featuredHotels->pluck('id');
        $productIds = $countyProducts->pluck('id');

        $seMap = \App\Models\SectorEntity::whereIn('entity_type', [
                \App\Models\CountyTourismAttraction::class,
                \App\Models\CountyHotel::class,
                \App\Models\CountyProduct::class,
            ])
            ->whereIn('entity_id', $attractionIds->merge($hotelIds)->merge($productIds))
            ->get()
            ->groupBy(fn($se) => $se->entity_type . '_' . $se->entity_id);

        foreach ($featuredAttractions as $a) {
            $key = \App\Models\CountyTourismAttraction::class . '_' . $a->id;
            $se = $seMap->get($key)?->first();
            if ($se) $entityMedia['attraction_' . $a->id] = $resolver->forEntity($se);
        }
        foreach ($featuredHotels as $h) {
            $key = \App\Models\CountyHotel::class . '_' . $h->id;
            $se = $seMap->get($key)?->first();
            if ($se) $entityMedia['hotel_' . $h->id] = $resolver->forEntity($se);
        }
        foreach ($countyProducts as $p) {
            $key = \App\Models\CountyProduct::class . '_' . $p->id;
            $se = $seMap->get($key)?->first();
            if ($se) $entityMedia['product_' . $p->id] = $resolver->forEntity($se);
        }

        $mapPins = app(\App\Services\MapPinService::class)->countyPins($county);

        // County flag data for 3D waving flag
        $countyFlagUri = app(\App\Services\CountyFlagService::class)->forCounty($county)['flag_data_uri'];

        // Build sector→pins mapping: which institutions belong to which sector
        $sectorPins = Cache::remember("county_pins_{$county->id}", config('kicc.cache_ttl.public', 21600), function () use ($county, $sectorData, $mapPins) {
            $result = [];
            foreach ($sectorData as $name => $s) {
                $sectorModel = $county->sectors()->where('slug', 'like', $s['sector_slug'] . '%')->first();
                if (!$sectorModel) continue;
                $instIds = \App\Models\SectorEntity::where('county_id', $county->id)
                    ->where('sector_id', $sectorModel->id)
                    ->whereIn('entity_type', [\App\Models\CountyInstitution::class, \App\Services\InstitutionSyncService::ENTITY_TYPE])
                    ->where('is_published', true)
                    ->pluck('entity_id')
                    ->unique();
                $result[$s['sector_slug']] = collect($mapPins)->whereIn('id', $instIds)->values()->all();
            }
            return $result;
        });

        // ── Pipeline earnings for this county ──
        $countyPipelines = \Illuminate\Support\Facades\DB::table('escrow_transactions')
            ->join('users', 'escrow_transactions.seller_id', '=', 'users.id')
            ->where('users.county_id', $county->id)
            ->where('escrow_transactions.status', 'released')
            ->selectRaw('escrow_transactions.reference_type as code, COUNT(*) as trades, SUM(escrow_transactions.amount) as gmv')
            ->groupBy('code')
            ->orderByDesc('gmv')
            ->limit(15)
            ->get();

        return view('counties.show', compact(
            'county', 'sectors', 'sectorData',
            'featuredAttractions', 'featuredHotels', 'countyProducts',
            'exhibitions', 'linkedSectors', 'countyMedia', 'countyHeroFallback', 'tileMedia',
            'sectorPitches', 'attractionThumbs', 'hotelThumbs', 'productThumbs',
            'mapPins', 'sectorPins', 'countyFlagUri', 'entityMedia',
            'countyPipelines',
        ));
    }

        public function sector(County $county, string $sector)
    {
        $page = request()->get('page', 1);
        $county->load('sectors');

        $resolver = app(\App\Services\SectorMediaResolver::class);
        $sectorModel = $resolver->resolveSector($county, $sector);
        if (!$sectorModel) abort(404, "Sector not found for {$county->name}");

        $allSectorIds = $resolver->resolveAliasSectorIds($county, $sectorModel);

        $liveInstIds = \App\Models\CountyInstitution::where('county_id', $county->id)
            ->where('is_published', true)->pluck('id');
        $instTypes = [\App\Models\CountyInstitution::class, \App\Services\InstitutionSyncService::ENTITY_TYPE];

        $listVersion = Cache::get("kicc_sector_version_{$county->id}_{$sectorModel->id}", 1);
        $entityIdCache = Cache::remember("kicc_county_sector_items_{$county->id}_{$sectorModel->id}_{$listVersion}_{$page}", config('kicc.cache_ttl.public', 21600), function () use ($county, $allSectorIds, $instTypes, $liveInstIds) {
            return \App\Models\SectorEntity::where('county_id', $county->id)
                ->whereIn('sector_id', $allSectorIds)
                ->where('is_published', true)
                ->where(function ($q) use ($instTypes, $liveInstIds) {
                    $q->whereNotIn('entity_type', $instTypes)
                      ->orWhereIn('entity_id', $liveInstIds);
                })
                ->orderBy('name')
                ->pluck('id')
                ->all();
        });

        $allItems = \App\Models\SectorEntity::whereIn('id', $entityIdCache ?? [])->get()->keyBy('id');
        $items = new \Illuminate\Pagination\LengthAwarePaginator(
            collect($entityIdCache ?? [])->map(fn ($id) => $allItems->get($id))->filter(),
            count($entityIdCache ?? []),
            12,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );

        $instIdsForCount = $items->whereIn('entity_type', $instTypes)->pluck('entity_id')->unique();
        $productCounts = [];
        if ($instIdsForCount->isNotEmpty()) {
            $productCounts = \App\Models\Marketplace\Product::whereIn('user_id', \App\Models\CountyInstitution::whereIn('id', $instIdsForCount)->pluck('user_id'))
                ->active()
                ->selectRaw('user_id, count(*) as count')
                ->groupBy('user_id')
                ->get()
                ->keyBy('user_id')
                ->map(fn($r) => $r->count)
                ->toArray();
        }

        $media = $resolver->resolve($county, $sector, $entityIdCache ?? []);
        $entityVideos = $media['entity_videos'];
        $entityPosters = $media['entity_posters'];
        $entityHoverLoops = $media['entity_hover_loops'];
        $entitySplats = $media['entity_splats'];
        $institutionHeroVideos = $media['institution_hero_videos'];
        $sectorHeroVideos = $media['hero_playlist'];
        $sectorHeroPoster = $media['hero_poster'];
        $fourDVideo = $media['hero_video_url'];
        $institutionProductVideos = $media['institution_product_videos'];

        // Fallback: assign product video to entity tiles that have no hero/4d video
        foreach ($items as $e) {
            if (!empty($entityVideos[$e->id]) || !empty($institutionHeroVideos[$e->id])) continue;
            $isInst = in_array($e->entity_type, [\App\Models\CountyInstitution::class, \App\Services\InstitutionSyncService::ENTITY_TYPE]);
            if (!$isInst) continue;
            $pVids = $institutionProductVideos[$e->entity_id] ?? [];
            if (!empty($pVids)) {
                $entityVideos[$e->id] = $pVids[0];
            }
        }

        $fallbackResolver = app(\App\Services\MediaFallbackResolver::class);
        foreach ($items as $e) {
            if (empty($entityPosters[$e->id]) && !empty($entityVideos[$e->id])) {
                $frame = $fallbackResolver->extractFrame($entityVideos[$e->id]);
                if ($frame) $entityPosters[$e->id] = $frame;
            }
        }
        foreach ($items as $e) {
            if (empty($institutionHeroVideos[$e->id]) && !empty($entityVideos[$e->id])) {
                $institutionHeroVideos[$e->id] = $entityVideos[$e->id];
            }
        }

        $info = [
            'tourism' => ['title' => 'Tourism & Attractions', 'icon' => '', 'desc' => 'Discover attractions and cultural sites.'],
            'hotels' => ['title' => 'Hospitality & Hotels', 'icon' => '', 'desc' => 'Hotels and accommodation.'],
            'products' => ['title' => 'Commerce & End Products', 'icon' => '', 'desc' => 'Bookable county end products and marketplace goods.'],
            'institutions' => ['title' => 'Education & Institutions', 'icon' => '', 'desc' => 'Schools and training centers.'],
            'farms' => ['title' => 'Agriculture & Farms', 'icon' => '', 'desc' => 'Farms and agribusiness.'],
            'transport' => ['title' => 'Transport & Logistics', 'icon' => '', 'desc' => 'Transport hubs and logistics.'],
            'health' => ['title' => 'Healthcare', 'icon' => '', 'desc' => 'Hospitals and clinics.'],
            'culture' => ['title' => 'Culture & Heritage', 'icon' => '', 'desc' => 'Cultural sites and traditions.'],
            'agriculture' => ['title' => 'Agriculture', 'icon' => '', 'desc' => 'Agriculture and farming.'],
        ];
        $sectorInfo = $info[$sector] ?? ['title' => $sectorModel->name, 'icon' => '', 'desc' => "{$sectorModel->name} in {$county->name} County."];

        $entityIdsList = $items->pluck('id');
        $entityReviewScores = [];
        if ($entityIdsList->isNotEmpty()) {
            $seeds = \App\Models\ReviewSeed::where('owner_type', \App\Models\SectorEntity::class)
                ->whereIn('owner_id', $entityIdsList)->get()->keyBy('owner_id');
            $real = \App\Models\SectorEntityReview::whereIn('sector_entity_id', $entityIdsList)
                ->selectRaw('sector_entity_id, AVG(rating) as avg_r, COUNT(*) as cnt')
                ->groupBy('sector_entity_id')->get()->keyBy('sector_entity_id');
            $instEntityIds = $items->whereIn('entity_type', [CountyInstitution::class, InstitutionSyncService::ENTITY_TYPE])
                ->pluck('entity_id')->unique();
            $instSeeds = $instEntityIds->isNotEmpty()
                ? \App\Models\ReviewSeed::where('owner_type', CountyInstitution::class)->whereIn('owner_id', $instEntityIds)->get()->keyBy('owner_id')
                : collect();
            foreach ($items as $e) {
                $seed = $seeds->get($e->id);
                if (!$seed && $instSeeds->isNotEmpty() && in_array($e->entity_type, [CountyInstitution::class, InstitutionSyncService::ENTITY_TYPE])) {
                    $seed = $instSeeds->get($e->entity_id);
                }
                $r = $real->get($e->id);
                $avg = $r?->avg_r ?? 0;
                $cnt = (int) ($r?->cnt ?? 0);
                if ($seed && $seed->review_count > 0) {
                    $cnt += (int) $seed->review_count;
                    $avg = $avg > 0
                        ? (($avg * (int) ($r?->cnt ?? 0)) + ((float) $seed->rating * (int) $seed->review_count)) / max(1, $cnt)
                        : (float) $seed->rating;
                }
                $entityReviewScores[$e->id] = [
                    'avg' => round((float) $avg, 1),
                    'count' => $cnt,
                    'source' => $seed?->sourceLabel(),
                ];
            }
        }

        $services = collect();

        return view('counties.sector', compact(
            'county', 'items', 'sector', 'sectorInfo', 'sectorModel',
            'fourDVideo', 'entityVideos', 'entityPosters', 'entityHoverLoops', 'entitySplats',
            'institutionHeroVideos', 'productCounts',
            'sectorHeroVideos', 'sectorHeroPoster', 'entityReviewScores', 'services'
        ))->with(['institutionHeroPosters' => $entityPosters, 'institutionHeroLoops' => $entityHoverLoops]);
    }

    public function institution(string $slug)
    {
        $institution = CountyInstitution::where('slug', $slug)
            ->where('is_published', true)
            ->with('county', 'sectorEntities.sector')
            ->firstOrFail();

        $county = $institution->county;

        $slotAliases = ['hotels' => 'hospitality', 'farms' => 'agriculture', 'products' => 'commerce', 'institutions' => 'education', 'transport' => 'industry'];

        // Hero video (Tier 3: HLS adaptive preferred, mp4 fallback)
        $heroAssetId = Cache::remember("resolve:inst_hero_id_" . $institution->id, config('kicc.cache_ttl.public', 21600), fn() => MediaAsset::resolveSlot(CountyInstitution::class, $institution->id, 'hero_video')?->id);
        $heroAsset = $heroAssetId ? MediaAsset::with('derivatives')->find($heroAssetId) : null;
        $heroVideo = $heroAsset?->mp4Url() ?? $heroAsset?->url();
        $heroHls = $heroAsset?->derivativeUrl('hls_master');
        $heroPoster = $heroAsset?->posterUrl();
        $heroSplat = $heroAsset?->splatUrl();

        // Institution fallback video algorithm: tree hierarchy
        // Tier 1: Institution's own hero video (already checked above — $heroVideo)
        // Tier 2: Sector videos (4D + peer heroes + sector_video assets)
        // Tier 3: Institution's marketplace product videos
        // Tier 4: Institution's own library videos (videos JSON)
        // Tier 5: County hero video
        $institutionFallbackVideos = [];
        if (!$heroVideo) {
            $fallback = [];

            // Tier 2: Sector videos — collect all videos from this institution's sector
            if ($institution->county_id) {
                $sectorSlugs = $institution->sectorEntities
                    ->pluck('sector.slug')
                    ->map(fn ($slug) => explode('-', $slug)[0])
                    ->unique();

                // 2a: 4D videos from this institution's sector entities
                $sectorEntityIds = $institution->sectorEntities->pluck('id');
                if ($sectorEntityIds->isNotEmpty()) {
                    $fourDAssets = MediaAsset::where('owner_type', SectorEntity::class)
                        ->whereIn('owner_id', $sectorEntityIds)
                        ->where('slot', '4d_video')
                        ->get();
                    foreach ($fourDAssets as $a) {
                        if ($url = $a->mp4Url() ?? $a->url()) $fallback[] = $url;
                    }
                }

                // 2b: Hero videos of other institutions in the same sector
                $sectorIds = $institution->sectorEntities->pluck('sector_id');
                if ($sectorIds->isNotEmpty()) {
                    $peerInstIds = SectorEntity::where('county_id', $institution->county_id)
                        ->whereIn('sector_id', $sectorIds)
                        ->whereIn('entity_type', [CountyInstitution::class, InstitutionSyncService::ENTITY_TYPE])
                        ->where('entity_id', '!=', $institution->id)
                        ->pluck('entity_id')
                        ->unique();
                    if ($peerInstIds->isNotEmpty()) {
                        $peerAssets = MediaAsset::where('owner_type', CountyInstitution::class)
                            ->whereIn('owner_id', $peerInstIds)
                            ->where('slot', 'hero_video')
                            ->get();
                        foreach ($peerAssets as $a) {
                            if ($url = $a->mp4Url() ?? $a->url()) $fallback[] = $url;
                        }
                    }
                }

                // 2c: County-level sector_video assets (mother tile)
                foreach ($sectorSlugs as $slug) {
                    $asset = MediaAsset::where('owner_type', County::class)
                        ->where('owner_id', $institution->county_id)
                        ->where('slot', 'sector_video_' . ($slotAliases[$slug] ?? $slug))
                        ->first();
                    if ($asset && ($url = $asset->mp4Url() ?? $asset->url())) {
                        $fallback[] = $url;
                    }
                }
            }

            // Tier 3: Institution's marketplace product videos (cached 6h)
            if ($institution->user_id) {
                $cacheKey = 'inst_product_videos_' . $institution->user_id;
                $productVids = \Illuminate\Support\Facades\Cache::remember($cacheKey, config('kicc.cache_ttl.public', 21600), function () use ($institution) {
                    return \App\Models\Marketplace\Product::where('user_id', $institution->user_id)
                        ->active()
                        ->whereNotNull('video_url')
                        ->take(10)
                        ->pluck('video_url')
                        ->filter()
                        ->values()
                        ->all();
                });
                foreach ($productVids as $pv) {
                    $fallback[] = $pv;
                }
            }

            // Tier 4: Institution's own library videos (videos JSON)
            $libVids = $institution->videos ?? [];
            foreach ($libVids as $v) {
                $url = $v['path'] ?? $v['url'] ?? null;
                if ($url) $fallback[] = $url;
            }

            // Tier 5: County hero video
            if ($institution->county_id) {
                $countyHeroUrl2 = Cache::remember("resolve:county_hero_url_" . $institution->county_id, config('kicc.cache_ttl.public', 21600), function () use ($institution) {
                    $a = MediaAsset::resolveSlot(County::class, $institution->county_id, 'hero_video');
                    return $a ? ($a->mp4Url() ?? $a->url()) : null;
                });
                if ($countyHeroUrl2) $fallback[] = $countyHeroUrl2;
            }

            // Deduplicate and limit
            $institutionFallbackVideos = array_values(array_unique(array_filter($fallback)));
        }

        // If no hero poster exists, use the first fallback video URL as poster
        // The browser will show the first video frame for both img and video elements
        if (!$heroPoster && !empty($institutionFallbackVideos)) {
            $heroPoster = $institutionFallbackVideos[0];
        }

        // Marketplace products owned by this institution
        $products = collect();
        if ($institution->user_id) {
            $products = Product::with(['county', 'category', 'variants' => fn ($q) => $q->where('is_active', true), 'images'])
                ->where('user_id', $institution->user_id)
                ->active()
                ->get();
        }

        // Sector entities (mappings) — already eager-loaded
        $sectorEntities = $institution->sectorEntities;

        // Additional videos (institution videos JSON)
        $libraryVideos = $institution->videos ?? [];

        // Institution reviews (polymorphic)
        $institutionReviews = \App\Models\Review::where('reviewable_type', CountyInstitution::class)
            ->where('reviewable_id', $institution->id)
            ->where('status', 'approved')
            ->with('user')
            ->latest()
            ->get();
        $institutionReviewSeed = \App\Models\ReviewSeed::where('owner_type', CountyInstitution::class)
            ->where('owner_id', $institution->id)
            ->first();
        $institutionReviewAvg = (float) $institutionReviews->avg('rating');
        $institutionReviewCount = $institutionReviews->count();
        if ($institutionReviewSeed) {
            $seedCount = (int) $institutionReviewSeed->review_count;
            $institutionReviewCount += $seedCount;
            if ($seedCount > 0 && $institutionReviewAvg > 0) {
                $institutionReviewAvg = (($institutionReviews->avg('rating') * $institutionReviews->count()) + ((float) $institutionReviewSeed->rating * $seedCount)) / max(1, $institutionReviews->count() + $seedCount);
            } elseif ($institutionReviewAvg === 0.0 && $seedCount > 0) {
                $institutionReviewAvg = (float) $institutionReviewSeed->rating;
            }
        }

        // Trip correlation: nearby places to visit, places to stay, transport
        $tripRecommendations = [];
        try {
            $tripRecommendations = app(CorrelationService::class)->forInstitution($institution, 6);
        } catch (\Throwable $e) {
        }

        // ── Institution pipeline earnings ──
        $institutionEarnings = collect();
        if ($institution->user_id) {
            $institutionEarnings = \Illuminate\Support\Facades\DB::table('escrow_transactions')
                ->where('seller_id', $institution->user_id)
                ->where('status', 'released')
                ->selectRaw('reference_type as code, COUNT(*) as trades, SUM(amount) as gmv')
                ->groupBy('reference_type')
                ->orderByDesc('gmv')
                ->limit(10)
                ->get();
        }

        return view('counties.institution', compact(
            'institution', 'county', 'heroAsset', 'heroVideo', 'heroHls', 'heroPoster', 'heroSplat', 'products', 'sectorEntities', 'libraryVideos',
            'institutionReviews', 'institutionReviewSeed', 'institutionReviewAvg', 'institutionReviewCount',
            'tripRecommendations', 'institutionFallbackVideos',
            'institutionEarnings',
        ));
    }
}
