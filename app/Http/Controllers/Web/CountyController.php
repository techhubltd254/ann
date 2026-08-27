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
use Illuminate\Support\Facades\Cache;

class CountyController extends Controller
{
    public function index()
    {
        $countyIds = Cache::remember('kicc_counties_index', 21600, fn () => County::orderBy('name')->pluck('id')->all());
        $counties = County::withCount('sectors')->whereIn('id', $countyIds)->orderBy('name')->get();
        return view('counties.index', compact('counties'));
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

        $sectorData = [];
        foreach ($sectors as $s) {
            $baseSlug = explode('-', $s->slug)[0];
            $name = $sectorNames[$baseSlug] ?? $s->name;
            $route = $routeMap[$baseSlug] ?? $baseSlug;
            $count = $sectorEntityCounts[$s->id] ?? 0;
            if ($count > 0 && !isset($sectorData[$name])) {
                $sectorData[$name] = ['count' => $count, 'route' => $route, 'sector_slug' => $baseSlug];
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

        return view('counties.show', compact(
            'county', 'sectors', 'sectorData',
            'featuredAttractions', 'featuredHotels', 'countyProducts',
            'exhibitions', 'linkedSectors', 'countyMedia', 'sectorVideos', 'sectorWebmVideos',
            'sectorEntityVideos', 'sectorPitches', 'attractionThumbs', 'hotelThumbs', 'productThumbs'
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

        $entityIdCache = Cache::remember("kicc_county_sector_items_{$county->id}_{$sectorModel->id}_{$page}", 21600, function () use ($county, $sectorIds) {
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
        if ($entityIds->isNotEmpty()) {
            $assets = MediaAsset::where('owner_type', SectorEntity::class)
                ->whereIn('owner_id', $entityIds)
                ->where('slot', '4d_video')
                ->get()
                ->groupBy('owner_id');
            foreach ($assets as $ownerId => $list) {
                $a = $list->first();
                $entityVideos[$ownerId] = $a->mp4Url() ?? $a->url();
                $entityPosters[$ownerId] = $a->posterUrl();
            }
        }

        // Institution hero videos: load for SectorEntity items that are institutions
        $institutionIds = $items->whereIn('entity_type', [\App\Models\CountyInstitution::class, \App\Services\InstitutionSyncService::ENTITY_TYPE])->pluck('entity_id')->unique();
        $institutionHeroVideos = [];
        if ($institutionIds->isNotEmpty()) {
            $heroAssets = MediaAsset::where('owner_type', \App\Models\CountyInstitution::class)
                ->whereIn('owner_id', $institutionIds)
                ->where('slot', 'hero_video')
                ->get()
                ->keyBy('owner_id');
            foreach ($items as $e) {
                $isInst = in_array($e->entity_type, [\App\Models\CountyInstitution::class, \App\Services\InstitutionSyncService::ENTITY_TYPE]);
                if ($isInst && isset($heroAssets[$e->entity_id])) {
                    $a = $heroAssets[$e->entity_id];
                    $institutionHeroVideos[$e->id] = $a->mp4Url() ?? $a->url();
                }
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

        $services = collect();

        return view('counties.sector', compact(
            'county', 'items', 'sector', 'sectorInfo', 'sectorModel',
            'fourDVideo', 'entityVideos', 'entityPosters', 'institutionHeroVideos', 'productCounts',
            'sectorHeroVideos', 'services'
        ));
    }

    public function institution(string $slug)
    {
        $institution = CountyInstitution::where('slug', $slug)
            ->where('is_published', true)
            ->with('county', 'sectorEntities.sector')
            ->firstOrFail();

        $county = $institution->county;

        // Hero video
        $heroAsset = MediaAsset::resolveSlot(CountyInstitution::class, $institution->id, 'hero_video');
        $heroVideo = $heroAsset?->mp4Url() ?? $heroAsset?->url();
        $heroPoster = $heroAsset?->posterUrl() ?? $institution->logo_url;

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

        return view('counties.institution', compact(
            'institution', 'county', 'heroVideo', 'heroPoster', 'products', 'sectorEntities', 'libraryVideos'
        ));
    }
}
