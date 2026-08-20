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
            'Tourism' => ['count' => $tourismCount, 'icon' => '🏖️', 'route' => 'tourism'],
            'Hospitality' => ['count' => $hotelsCount, 'icon' => '🏨', 'route' => 'hotels'],
            'Agriculture' => ['count' => $farmsCount, 'icon' => '🌾', 'route' => 'farms'],
            'Commerce & End Products' => ['count' => $productsCount, 'icon' => '🛒', 'route' => 'products'],
            'Education' => ['count' => $institutionsCount, 'icon' => '🎓', 'route' => 'institutions'],
            'Transport' => ['count' => $transportCount, 'icon' => '🚢', 'route' => 'transport'],
            'Healthcare' => ['count' => $healthCount, 'icon' => '🏥', 'route' => 'health'],
            'Culture' => ['count' => $cultureCount, 'icon' => '🎭', 'route' => 'culture'],
        ];

        $featuredAttractions = $county->tourismAttractions()->where('is_published', true)->orderBy('name')->take(12)->get();
        $featuredHotels = $county->hotels()->where('is_published', true)->orderByDesc('star_rating')->take(8)->get();
        $countyProducts = $county->products()->where('is_published', true)->whereNotNull('price')->orderByDesc('price')->take(8)->get();
        $exhibitions = $county->exhibitions()->where('status', 'published')->orderBy('start_date', 'desc')->take(3)->get();
        $linkedSectors = $county->sectors()->orderBy('name')->get();

        $countyMedia = MediaAsset::resolveSlot(County::class, $county->id, 'hero_video');

        return view('counties.show', compact(
            'county', 'sectors', 'sectorData',
            'featuredAttractions', 'featuredHotels', 'countyProducts',
            'exhibitions', 'linkedSectors', 'countyMedia'
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

        return view('counties.sector', compact('county', 'items', 'sector', 'sectorInfo', 'sectorModel'));
    }
}
