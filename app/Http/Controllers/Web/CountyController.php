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
        $countyIds = Cache::remember('kicc_counties_index', 21600, fn () => County::orderBy('name')->pluck('id')->all());
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
        $sectorEntityCounts = Cache::remember("kicc_county_sector_counts_{$county->id}", 21600, function () use ($county) {
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

        $featuredAttractionIds = Cache::remember("kicc_county_attractions_{$county->id}", 21600, fn () => $county->tourismAttractions()->where('is_published', true)->orderBy('name')->take(12)->pluck('id')->all());
        $featuredHotelIds = Cache::remember("kicc_county_hotels_{$county->id}", 21600, fn () => $county->hotels()->where('is_published', true)->orderByDesc('star_rating')->take(8)->pluck('id')->all());
        $countyProductIds = Cache::remember("kicc_county_products_{$county->id}", 21600, fn () => $county->products()->where('is_published', true)->whereNotNull('price')->orderByDesc('price')->take(8)->pluck('id')->all());
        $exhibitionIds = Cache::remember("kicc_county_exhibitions_{$county->id}", 21600, fn () => $county->exhibitions()->where('status', 'published')->orderBy('start_date', 'desc')->take(3)->pluck('id')->all());
        $linkedSectorIds = Cache::remember("kicc_county_linked_sectors_{$county->id}", 21600, fn () => $county->sectors()->orderBy('name')->pluck('sectors.id')->all());

        $featuredAttractions = $featuredAttractionIds ? $county->tourismAttractions()->whereIn('id', $featuredAttractionIds)->orderBy('name')->get() : collect();
        $featuredHotels = $featuredHotelIds ? $county->hotels()->whereIn('id', $featuredHotelIds)->orderByDesc('star_rating')->get() : collect();
        $countyProducts = $countyProductIds ? $county->products()->whereIn('id', $countyProductIds)->orderByDesc('price')->get() : collect();
        $exhibitions = $exhibitionIds ? $county->exhibitions()->whereIn('id', $exhibitionIds)->orderBy('start_date', 'desc')->get() : collect();
        $linkedSectors = $linkedSectorIds ? $county->sectors()->whereIn('sectors.id', $linkedSectorIds)->orderBy('name')->get() : collect();

        // Resolve accurate thumbnails for every card (video poster → category fallback → branded placeholder)
        $attractionThumbs = $featuredAttractions->mapWithKeys(fn($a) => [$a->id => \App\Services\ThumbnailService::for($a, $county->slug)]);
        $hotelThumbs = $featuredHotels->mapWithKeys(fn($h) => [$h->id => \App\Services\ThumbnailService::for($h, $county->slug)]);
        $productThumbs = $countyProducts->mapWithKeys(fn($p) => [$p->id => \App\Services\ThumbnailService::for($p, $county->slug)]);

        $countyMedia = MediaAsset::resolveSlot(County::class, $county->id, 'hero_video');

        // Batched sector video loading — single queries instead of per-sector
        $sectorSlugs = collect($sectorData)->pluck('sector_slug')->unique();
        $sectorVideos = [];
        $sectorWebmVideos = [];
        $sectorEntityVideos = [];
        $sectorPitches = [];

        $cacheKey = "kicc_county_sectors_{$county->id}_v2";

        $cached = Cache::remember($cacheKey, 21600, function () use ($county, $sectorSlugs, $sectorData, &$sectorVideos, &$sectorWebmVideos, &$sectorEntityVideos, &$sectorPitches) {
            // Batch load sector video assets
            $slots = $sectorSlugs->map(fn($slug) => 'sector_video_' . $slug);
            $assets = MediaAsset::where('owner_type', County::class)
                ->where('owner_id', $county->id)
                ->whereIn('slot', $slots)
                ->ready()
                ->with('derivatives')
                ->get()
                ->keyBy('slot');

            foreach ($sectorData as $name => $s) {
                $slot = 'sector_video_' . $s['sector_slug'];
                $asset = $assets->get($slot);
                $sectorVideos[$s['sector_slug']] = $asset?->mp4Url();
                $sectorWebmVideos[$s['sector_slug']] = $asset?->webmUrl();
            }

            // Batch load all sector entities + their videos
            $sectorModels = $county->sectors()->where(function ($q) use ($sectorSlugs) {
                foreach ($sectorSlugs as $slug) {
                    $q->orWhere('slug', 'like', $slug . '%');
                }
            })->get()->keyBy(fn($s) => explode('-', $s->slug)[0]);

            foreach ($sectorData as $name => $s) {
                $sectorModel = $sectorModels->get($s['sector_slug']);
                if (!$sectorModel) { $sectorEntityVideos[$s['sector_slug']] = []; continue; }

                $entities = SectorEntity::where('county_id', $county->id)
                    ->where('sector_id', $sectorModel->id)
                    ->where('is_published', true)
                    ->get();

                $entityIds = $entities->pluck('id');
                $vids = [];

                if ($entityIds->isNotEmpty()) {
                    $fourDAssets = MediaAsset::where('owner_type', SectorEntity::class)
                        ->whereIn('owner_id', $entityIds)
                        ->where('slot', '4d_video')
                        ->get();
                    foreach ($fourDAssets as $a) {
                        if ($url = $a->mp4Url() ?? $a->url()) $vids[] = $url;
                    }
                }

                $instIds = $entities->whereIn('entity_type', [CountyInstitution::class, InstitutionSyncService::ENTITY_TYPE])->pluck('entity_id')->unique();
                if ($instIds->isNotEmpty()) {
                    $heroAssets = MediaAsset::where('owner_type', CountyInstitution::class)
                        ->whereIn('owner_id', $instIds)
                        ->where('slot', 'hero_video')
                        ->get();
                    foreach ($heroAssets as $a) {
                        if ($url = $a->mp4Url() ?? $a->url()) $vids[] = $url;
                    }
                }

                // Deduplicate: remove any video URL already assigned to a previous sector
                static $usedVideos = [];
                $unique = array_values(array_filter($vids, fn($v) => !in_array($v, $usedVideos)));
                $usedVideos = array_merge($usedVideos, $unique);
                $sectorEntityVideos[$s['sector_slug']] = $unique;
            }

            // Generate pitches
            foreach ($sectorData as $name => $s) {
                $sectorPitches[$s['sector_slug']] = SectorPitchService::generate($county, $s['sector_slug'], $s);
            }

            return compact('sectorVideos', 'sectorWebmVideos', 'sectorEntityVideos', 'sectorPitches');
        });

        $sectorVideos = $cached['sectorVideos'];
        $sectorWebmVideos = $cached['sectorWebmVideos'];
        $sectorEntityVideos = $cached['sectorEntityVideos'];
        $sectorPitches = $cached['sectorPitches'];

        // ═══ HERO FALLBACK ALGORITHM ═══
        // If the county has no hero video uploaded, build a hero playlist from the
        // sector videos + entity videos so the county hero still plays motion.
        $countyHeroFallback = [];
        if (!$countyMedia || !($countyMedia->mp4Url() ?? $countyMedia->url())) {
            $fallback = [];
            foreach ($sectorVideos as $url) {
                if ($url) $fallback[] = $url;
            }
            foreach ($sectorEntityVideos as $vids) {
                foreach ($vids as $url) {
                    $fallback[] = $url;
                }
            }
            // Cycle limit — a handful is plenty for a looping hero
            $countyHeroFallback = array_values(array_unique(array_filter($fallback)));
        }

        $mapPins = app(\App\Services\MapPinService::class)->countyPins($county);

        // Build sector→pins mapping: which institutions belong to which sector
        $sectorPins = [];
        foreach ($sectorData as $name => $s) {
            $sectorModel = $county->sectors()->where('slug', 'like', $s['sector_slug'] . '%')->first();
            if (!$sectorModel) continue;
            $instIds = \App\Models\SectorEntity::where('county_id', $county->id)
                ->where('sector_id', $sectorModel->id)
                ->whereIn('entity_type', [\App\Models\CountyInstitution::class, \App\Services\InstitutionSyncService::ENTITY_TYPE])
                ->where('is_published', true)
                ->pluck('entity_id')
                ->unique();
            $sectorPins[$s['sector_slug']] = collect($mapPins)->whereIn('id', $instIds)->values()->all();
        }

        return view('counties.show', compact(
            'county', 'sectors', 'sectorData',
            'featuredAttractions', 'featuredHotels', 'countyProducts',
            'exhibitions', 'linkedSectors', 'countyMedia', 'countyHeroFallback', 'sectorVideos', 'sectorWebmVideos',
            'sectorEntityVideos', 'sectorPitches', 'attractionThumbs', 'hotelThumbs', 'productThumbs',
            'mapPins', 'sectorPins'
        ));
    }

    public function sector(County $county, string $sector)
    {
        $page = request()->get('page', 1);
        $sectorModel = $county->sectors()->where('slug', $sector)->first();
        if (!$sectorModel) {
            // Try fuzzy match by partial slug or name
            $sectorModel = $county->sectors()->where('slug', 'like', $sector . '%')->first();
        }
        if (!$sectorModel) {
            // Try matching the sector_slug map
            $slugMap = [
                'agriculture' => ['agriculture', 'farms', 'Agriculture'],
                'tourism' => ['tourism', 'Tourism'],
                'hospitality' => ['hotels', 'hospitality', 'Hospitality'],
                'commerce' => ['products', 'commerce', 'Commerce'],
                'education' => ['institutions', 'education', 'Education'],
                'transport' => ['transport', 'Transport'],
                'healthcare' => ['health', 'healthcare', 'Healthcare'],
                'culture' => ['culture', 'Culture'],
            ];
            $aliases = $slugMap[$sector] ?? [];
            foreach ($aliases as $alias) {
                $sectorModel = $county->sectors()->where('slug', 'like', $alias . '%')->first();
                if ($sectorModel) break;
            }
        }
        if (!$sectorModel) abort(404, "Sector not found for {$county->name}");

        // Collect entities from this sector and any alias sectors
        $sectorIds = collect([$sectorModel->id]);
        $slugMap = [
            'farms' => ['farms', 'agriculture', 'Agriculture'],
            'agriculture' => ['farms', 'agriculture', 'Agriculture'],
            'tourism' => ['tourism', 'Tourism'],
            'hotels' => ['hotels', 'hospitality', 'Hospitality'],
            'hospitality' => ['hotels', 'hospitality', 'Hospitality'],
            'products' => ['products', 'commerce', 'Commerce'],
            'commerce' => ['products', 'commerce', 'Commerce'],
            'education' => ['education', 'institutions', 'Education'],
            'institutions' => ['education', 'institutions', 'Education'],
            'transport' => ['transport', 'Transport'],
            'health' => ['health', 'healthcare', 'Healthcare'],
            'healthcare' => ['health', 'healthcare', 'Healthcare'],
            'culture' => ['culture', 'Culture'],
            'industries' => ['industries'],
            'energy' => ['energy'],
        ];
        $aliases = $slugMap[$sector] ?? [];
        foreach ($aliases as $alias) {
            $aliasSector = $county->sectors()->where('slug', 'like', $alias . '%')->first();
            if ($aliasSector && $aliasSector->id !== $sectorModel->id) {
                $sectorIds->push($aliasSector->id);
            }
        }

        $listVersion = Cache::get("kicc_sector_version_{$county->id}_{$sectorModel->id}", 1);
        $entityIdCache = Cache::remember("kicc_county_sector_items_{$county->id}_{$sectorModel->id}_{$listVersion}_{$page}", 21600, function () use ($county, $sectorIds) {
            return SectorEntity::where('county_id', $county->id)
                ->whereIn('sector_id', $sectorIds)
                ->where('is_published', true)
                ->orderBy('name')
                ->pluck('id')
                ->all();
        });

        $items = new \Illuminate\Pagination\LengthAwarePaginator(
            collect($entityIdCache ?? [])->map(fn ($id) => SectorEntity::find($id))->filter(),
            count($entityIdCache ?? []),
            12,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );

        // Count marketplace products for institution entities
        $institutionIds = $items->whereIn('entity_type', [\App\Models\CountyInstitution::class, \App\Services\InstitutionSyncService::ENTITY_TYPE])->pluck('entity_id')->unique();
        $productCounts = [];
        if ($institutionIds->isNotEmpty()) {
            $productCounts = \App\Models\Marketplace\Product::whereIn('user_id', \App\Models\CountyInstitution::whereIn('id', $institutionIds)->pluck('user_id'))
                ->active()
                ->selectRaw('user_id, count(*) as count')
                ->groupBy('user_id')
                ->get()
                ->keyBy('user_id')
                ->map(fn($r) => $r->count)
                ->toArray();
        }

        // Sector background video (institution sync sets this slot)
        $bgAsset = MediaAsset::resolveSlot(County::class, $county->id, 'sector_video_' . $sector);
        $fourDVideo = $bgAsset?->mp4Url() ?? $bgAsset?->url();

        // Per-entity 4D videos: owner_type=SectorEntity, slot=4d_video
        $entityIds = $items->pluck('id');
        $entityVideos = [];
        $entityPosters = [];
        $entityHoverLoops = [];
        $entitySplats = [];
        if ($entityIds->isNotEmpty()) {
            $assets = MediaAsset::where('owner_type', SectorEntity::class)
                ->whereIn('owner_id', $entityIds)
                ->where('slot', '4d_video')
                ->with('derivatives')
                ->get()
                ->groupBy('owner_id');
            foreach ($assets as $ownerId => $list) {
                $a = $list->first();
                $entityVideos[$ownerId] = $a->mp4Url() ?? $a->url();
                $entityPosters[$ownerId] = $a->posterUrl();
                $entityHoverLoops[$ownerId] = $a->hoverLoopUrl();
                $entitySplats[$ownerId] = $a->splatUrl();
            }
        }

        // Institution hero videos: load for SectorEntity items that are institutions
        $institutionIds = $items->whereIn('entity_type', [\App\Models\CountyInstitution::class, \App\Services\InstitutionSyncService::ENTITY_TYPE])->pluck('entity_id')->unique();
        $institutionHeroVideos = [];
        $institutionHeroPosters = [];
        $institutionHeroLoops = [];
        if ($institutionIds->isNotEmpty()) {
            $heroAssets = MediaAsset::where('owner_type', \App\Models\CountyInstitution::class)
                ->whereIn('owner_id', $institutionIds)
                ->where('slot', 'hero_video')
                ->with('derivatives')
                ->get()
                ->keyBy('owner_id');
            foreach ($items as $e) {
                $isInst = in_array($e->entity_type, [\App\Models\CountyInstitution::class, \App\Services\InstitutionSyncService::ENTITY_TYPE]);
                if ($isInst && isset($heroAssets[$e->entity_id])) {
                    $a = $heroAssets[$e->entity_id];
                    $institutionHeroVideos[$e->id] = $a->mp4Url() ?? $a->url();
                    $institutionHeroPosters[$e->id] = $a->posterUrl();
                    $institutionHeroLoops[$e->id] = $a->hoverLoopUrl();
                }
            }
        }

        // Product video fallback: for institutions without hero video, use marketplace product videos
        foreach ($items as $e) {
            $isInst = in_array($e->entity_type, [\App\Models\CountyInstitution::class, \App\Services\InstitutionSyncService::ENTITY_TYPE]);
            if (!$isInst || !empty($institutionHeroVideos[$e->id])) continue;
            $inst = \App\Models\CountyInstitution::find($e->entity_id);
            if (!$inst || !$inst->user_id) continue;
            $productVids = \App\Models\Marketplace\Product::where('user_id', $inst->user_id)
                ->active()
                ->where(function ($q) { $q->whereNotNull('video_url')->orWhereNotNull('videos'); })
                ->take(5)->get()
                ->map(fn ($p) => $p->video_url ?? (is_array($p->videos) ? ($p->videos[0] ?? null) : null))
                ->filter()
                ->values();
            if ($productVids->isNotEmpty()) {
                $institutionHeroVideos[$e->id] = $productVids[0];
            }
        }

        $info = [
            'tourism' => ['title' => 'Tourism & Attractions', 'icon' => '🏖️', 'desc' => 'Discover attractions and cultural sites.'],
            'hotels' => ['title' => 'Hospitality & Hotels', 'icon' => '🏨', 'desc' => 'Hotels and accommodation.'],
            'products' => ['title' => 'Commerce & End Products', 'icon' => '🛒', 'desc' => 'Bookable county end products and marketplace goods.'],
            'institutions' => ['title' => 'Education & Institutions', 'icon' => '🎓', 'desc' => 'Schools and training centers.'],
            'farms' => ['title' => 'Agriculture & Farms', 'icon' => '🌾', 'desc' => 'Farms and agribusiness.'],
            'transport' => ['title' => 'Transport & Logistics', 'icon' => '🚢', 'desc' => 'Transport hubs and logistics.'],
            'health' => ['title' => 'Healthcare', 'icon' => '🏥', 'desc' => 'Hospitals and clinics.'],
            'culture' => ['title' => 'Culture & Heritage', 'icon' => '🎭', 'desc' => 'Cultural sites and traditions.'],
            'agriculture' => ['title' => 'Agriculture', 'icon' => '🌱', 'desc' => 'Agriculture and farming.'],
        ];

        $sectorInfo = $info[$sector] ?? ['title' => $sectorModel->name, 'icon' => '📋', 'desc' => "{$sectorModel->name} in {$county->name} County."];

        // Collect all entity videos into a flat playlist for the hero cycling
        $sectorHeroVideos = [];
        $seen = [];
        foreach ($items as $e) {
            $vid = $entityVideos[$e->id] ?? $institutionHeroVideos[$e->id] ?? null;
            if ($vid && !in_array($vid, $seen)) {
                $sectorHeroVideos[] = $vid;
                $seen[] = $vid;
            }
        }

        // Tier 2: Add product videos from institutions in this sector
        foreach ($items as $e) {
            $isInst = in_array($e->entity_type, [\App\Models\CountyInstitution::class, \App\Services\InstitutionSyncService::ENTITY_TYPE]);
            if (!$isInst) continue;
            $inst = \App\Models\CountyInstitution::find($e->entity_id);
            if (!$inst || !$inst->user_id) continue;
            $productVids = \App\Models\Marketplace\Product::where('user_id', $inst->user_id)
                ->active()
                ->whereNotNull('video_url')
                ->take(5)->get()
                ->pluck('video_url')
                ->filter();
            foreach ($productVids as $pv) {
                if (!in_array($pv, $seen)) {
                    $sectorHeroVideos[] = $pv;
                    $seen[] = $pv;
                }
            }
        }

        // Tier 3: This sector's own sector_video asset (mother tile — uploaded in admin)
        if (count($sectorHeroVideos) === 0) {
            $thisSectorAsset = MediaAsset::resolveSlot(County::class, $county->id, 'sector_video_' . $sector);
            if ($thisSectorAsset && ($url = $thisSectorAsset->mp4Url() ?? $thisSectorAsset->url())) {
                $sectorHeroVideos[] = $url;
            }
        }

        // Tier 4: County hero video
        if (count($sectorHeroVideos) === 0) {
            $countyHeroAsset = MediaAsset::resolveSlot(County::class, $county->id, 'hero_video');
            if ($countyHeroAsset?->mp4Url() ?? $countyHeroAsset?->url()) {
                $sectorHeroVideos[] = $countyHeroAsset->mp4Url() ?? $countyHeroAsset->url();
            }
        }

        // Tier 5: Other sector videos (only when absolutely nothing else exists)
        if (count($sectorHeroVideos) === 0) {
            $otherSectorVideos = MediaAsset::where('owner_type', County::class)
                ->where('owner_id', $county->id)
                ->where('slot', 'like', 'sector_video_%')
                ->get();
            foreach ($otherSectorVideos as $sv) {
                $url = $sv->mp4Url() ?? $sv->url();
                if ($url && !in_array($url, $sectorHeroVideos)) {
                    $sectorHeroVideos[] = $url;
                }
            }
        }

        $services = collect();

        // Review scores for entity cards (seeded online reviews + real entity reviews)
        $entityIdsList = $items->pluck('id');
        $entityReviewScores = [];
        if ($entityIdsList->isNotEmpty()) {
            $seeds = \App\Models\ReviewSeed::where('owner_type', SectorEntity::class)
                ->whereIn('owner_id', $entityIdsList)->get()->keyBy('owner_id');
            $real = \App\Models\SectorEntityReview::whereIn('sector_entity_id', $entityIdsList)
                ->selectRaw('sector_entity_id, AVG(rating) as avg_r, COUNT(*) as cnt')
                ->groupBy('sector_entity_id')->get()->keyBy('sector_entity_id');
            // Institution seeds also count — entities that are institutions inherit their seed
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

        // Poster for the sector hero (county hero poster / default county image)
        $sectorHeroPoster = \App\Models\MediaAsset::resolveSlot(County::class, $county->id, 'hero_video')?->posterUrl()
            ?? media('counties/' . $county->slug . '/hero.jpeg');

        return view('counties.sector', compact(
            'county', 'items', 'sector', 'sectorInfo', 'sectorModel',
            'fourDVideo', 'entityVideos', 'entityPosters', 'entityHoverLoops', 'entitySplats',
            'institutionHeroVideos', 'institutionHeroPosters', 'institutionHeroLoops', 'productCounts',
            'sectorHeroVideos', 'sectorHeroPoster', 'services', 'entityReviewScores'
        ));
    }

    public function institution(string $slug)
    {
        $institution = CountyInstitution::where('slug', $slug)
            ->where('is_published', true)
            ->with('county', 'sectorEntities.sector')
            ->firstOrFail();

        $county = $institution->county;

        // Hero video (Tier 3: HLS adaptive preferred, mp4 fallback)
        $heroAsset = MediaAsset::resolveSlot(CountyInstitution::class, $institution->id, 'hero_video');
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
                $sectorSlugs = $institution->sectorEntities()
                    ->with('sector')
                    ->get()
                    ->pluck('sector.slug')
                    ->map(fn ($slug) => explode('-', $slug)[0])
                    ->unique();

                // 2a: 4D videos from this institution's sector entities
                $sectorEntityIds = $institution->sectorEntities()->pluck('sector_entities.id');
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
                $sectorIds = $institution->sectorEntities()->pluck('sector_id');
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
                        ->where('slot', 'sector_video_' . $slug)
                        ->first();
                    if ($asset && ($url = $asset->mp4Url() ?? $asset->url())) {
                        $fallback[] = $url;
                    }
                }
            }

            // Tier 3: Institution's marketplace product videos
            if ($institution->user_id) {
                $productVids = \App\Models\Marketplace\Product::where('user_id', $institution->user_id)
                    ->active()
                    ->where(function ($q) { $q->whereNotNull('video_url')->orWhereNotNull('videos'); })
                    ->take(10)->get()
                    ->flatMap(fn ($p) => array_merge(
                        $p->video_url ? [$p->video_url] : [],
                        is_array($p->videos) ? $p->videos : []
                    ))
                    ->filter()
                    ->values()
                    ->all();
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
                $countyHero = MediaAsset::resolveSlot(County::class, $institution->county_id, 'hero_video');
                if ($countyHero && ($url = $countyHero->mp4Url() ?? $countyHero->url())) {
                    $fallback[] = $url;
                }
            }

            // Deduplicate and limit
            $institutionFallbackVideos = array_values(array_unique(array_filter($fallback)));
        }

        // If no hero poster exists, use the first fallback video URL as poster
// The browser will show the first video frame for both img and video elements
if (!$heroPoster && !empty($institutionFallbackVideos)) {
    $heroPoster = $institutionFallbackVideos[0];
    // Try to extract a real frame asynchronously (already exists? use it)
    try {
        $frame = app(\App\Services\MediaFallbackResolver::class)->extractFrame($institutionFallbackVideos[0]);
        if ($frame) $heroPoster = $frame;
    } catch (\Throwable $e) {}
}

        // Marketplace products owned by this institution
        $products = collect();
        if ($institution->user_id) {
            $products = Product::with(['county', 'category', 'variants' => fn ($q) => $q->where('is_active', true), 'images'])
                ->where('user_id', $institution->user_id)
                ->active()
                ->get();
        }

        // Sector entities (mappings)
        $sectorEntities = $institution->sectorEntities()->with('sector')->get();

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

        return view('counties.institution', compact(
            'institution', 'county', 'heroAsset', 'heroVideo', 'heroHls', 'heroPoster', 'heroSplat', 'products', 'sectorEntities', 'libraryVideos',
            'institutionReviews', 'institutionReviewSeed', 'institutionReviewAvg', 'institutionReviewCount',
            'tripRecommendations', 'institutionFallbackVideos'
        ));
    }
}
