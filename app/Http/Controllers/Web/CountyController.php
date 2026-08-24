<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\County;
use App\Models\MediaAsset;
use App\Models\SectorEntity;

class CountyController extends Controller
{
    public function index()
    {
        $counties = County::withCount('sectors')->orderBy('name')->get();
        return view('counties.index', compact('counties'));
    }

    public function show(County $county)
    {
        $county->load('sectors');
        $sectors = $county->sectors;

        $tourismCount = $county->tourismAttractions()->count();
        $hotelsCount = $county->hotels()->count();
        $productsCount = $county->products()->count();
        $institutionsCount = $county->institutions()->count();
        $farmsCount = $county->farms()->count();
        $transportCount = $county->transport()->count();
        $healthCount = $county->healthFacilities()->count();
        $cultureCount = $county->cultureSites()->count();

        $sectorData = [
            'Tourism' => ['count' => $tourismCount, 'icon' => '🏖️', 'route' => 'tourism', 'sector_slug' => 'tourism'],
            'Hospitality' => ['count' => $hotelsCount, 'icon' => '🏨', 'route' => 'hotels', 'sector_slug' => 'hospitality'],
            'Agriculture' => ['count' => $farmsCount, 'icon' => '🌾', 'route' => 'farms', 'sector_slug' => 'agriculture'],
            'Commerce & End Products' => ['count' => $productsCount, 'icon' => '🛒', 'route' => 'products', 'sector_slug' => 'commerce'],
            'Education' => ['count' => $institutionsCount, 'icon' => '🎓', 'route' => 'institutions', 'sector_slug' => 'education'],
            'Transport' => ['count' => $transportCount, 'icon' => '🚢', 'route' => 'transport', 'sector_slug' => 'transport'],
            'Healthcare' => ['count' => $healthCount, 'icon' => '🏥', 'route' => 'health', 'sector_slug' => 'healthcare'],
            'Culture' => ['count' => $cultureCount, 'icon' => '🎭', 'route' => 'culture', 'sector_slug' => 'culture'],
        ];

        $featuredAttractions = $county->tourismAttractions()->where('is_published', true)->orderBy('name')->take(12)->get();
        $featuredHotels = $county->hotels()->where('is_published', true)->orderByDesc('star_rating')->take(8)->get();
        $countyProducts = $county->products()->where('is_published', true)->whereNotNull('price')->orderByDesc('price')->take(8)->get();
        $exhibitions = $county->exhibitions()->where('status', 'published')->orderBy('start_date', 'desc')->take(3)->get();
        $linkedSectors = $county->sectors()->orderBy('name')->get();

        $countyMedia = MediaAsset::resolveSlot(County::class, $county->id, 'hero_video');

        // Resolve sector video clips for tile background playback
        $sectorVideos = [];
        $sectorWebmVideos = [];
        foreach ($sectorData as $name => $s) {
            $asset = MediaAsset::resolveSlot(County::class, $county->id, 'sector_video_' . $s['sector_slug']);
            $sectorVideos[$s['sector_slug']] = $asset?->mp4Url();
            $sectorWebmVideos[$s['sector_slug']] = $asset?->webmUrl();
        }

        return view('counties.show', compact(
            'county', 'sectors', 'sectorData',
            'featuredAttractions', 'featuredHotels', 'countyProducts',
            'exhibitions', 'linkedSectors', 'countyMedia', 'sectorVideos', 'sectorWebmVideos'
        ));
    }

    public function sector(County $county, string $sector)
    {
        $sectorModel = $county->sectors()->where('slug', $sector)->first();
        if (!$sectorModel) abort(404, "Sector not found for {$county->name}");

        $items = SectorEntity::where('county_id', $county->id)
            ->where('sector_id', $sectorModel->id)
            ->where('is_published', true)
            ->orderBy('name')
            ->paginate(12);

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
        $institutionIds = $items->where('entity_type', \App\Models\CountyInstitution::class)->pluck('entity_id')->unique();
        $institutionHeroVideos = [];
        if ($institutionIds->isNotEmpty()) {
            $heroAssets = MediaAsset::where('owner_type', \App\Models\CountyInstitution::class)
                ->whereIn('owner_id', $institutionIds)
                ->where('slot', 'hero_video')
                ->get()
                ->keyBy('owner_id');
            foreach ($items as $e) {
                if ($e->entity_type === \App\Models\CountyInstitution::class && isset($heroAssets[$e->entity_id])) {
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

        $services = collect();

        return view('counties.sector', compact(
            'county', 'items', 'sector', 'sectorInfo', 'sectorModel',
            'fourDVideo', 'entityVideos', 'entityPosters', 'institutionHeroVideos', 'services'
        ));
    }
}
