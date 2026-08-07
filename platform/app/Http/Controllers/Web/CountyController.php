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
            'Tourism' => ['count' => $tourismCount, 'icon' => '🏖️', 'route' => 'tourism', 'desc' => 'Attractions, natural wonders and visitor experiences.'],
            'Hospitality' => ['count' => $hotelsCount, 'icon' => '🏨', 'route' => 'hotels', 'desc' => 'Hotels, lodges, camps and places to stay.'],
            'Agriculture' => ['count' => $farmsCount, 'icon' => '🌾', 'route' => 'farms', 'desc' => 'Farms, cooperatives and agricultural production.'],
            'Trade & Products' => ['count' => $productsCount, 'icon' => '🛍️', 'route' => 'products', 'desc' => 'Local products, manufacturers and trade.'],
            'Education' => ['count' => $institutionsCount, 'icon' => '🎓', 'route' => 'institutions', 'desc' => 'Schools, colleges, universities and training.'],
            'Transport' => ['count' => $transportCount, 'icon' => '🚢', 'route' => 'transport', 'desc' => 'Roads, transport and logistics services.'],
            'Healthcare' => ['count' => $healthCount, 'icon' => '🏥', 'route' => 'health', 'desc' => 'Hospitals, clinics and health facilities.'],
            'Culture' => ['count' => $cultureCount, 'icon' => '🎭', 'route' => 'culture', 'desc' => 'Heritage, cultural sites and community traditions.'],
        ];

        $featuredAttractions = $county->tourismAttractions()->where('is_published', true)->take(4)->get();
        $featuredHotels = $county->hotels()->where('is_published', true)->take(4)->get();
        $exhibitions = $county->exhibitions()->where('status', 'published')->orderBy('start_date', 'desc')->take(3)->get();
        $linkedSectors = $county->sectors()->orderBy('name')->get();

        $countyMedia = MediaAsset::resolveSlot(County::class, $county->id, 'hero_video');

        return view('counties.show', compact(
            'county', 'sectors', 'sectorData',
            'featuredAttractions', 'featuredHotels',
            'exhibitions', 'linkedSectors', 'countyMedia'
        ));
    }

    public function sector(County $county, string $sector)
    {
        // The 8 public sector routes map to the county entity tables — NOT to
        // linked sector slugs (those are scraped department names and vary per county).
        $map = [
            'tourism'      => ['title' => 'Tourism & Attractions',  'icon' => '🏖️', 'relation' => 'tourismAttractions'],
            'hotels'       => ['title' => 'Hospitality & Hotels',   'icon' => '🏨', 'relation' => 'hotels'],
            'farms'        => ['title' => 'Agriculture & Farms',    'icon' => '🌾', 'relation' => 'farms'],
            'products'     => ['title' => 'Trade & Products',       'icon' => '🛍️', 'relation' => 'products'],
            'institutions' => ['title' => 'Education & Institutions','icon' => '🎓', 'relation' => 'institutions'],
            'transport'    => ['title' => 'Transport & Logistics',  'icon' => '🚢', 'relation' => 'transport'],
            'health'       => ['title' => 'Healthcare',             'icon' => '🏥', 'relation' => 'healthFacilities'],
            'culture'      => ['title' => 'Culture & Heritage',     'icon' => '🎭', 'relation' => 'cultureSites'],
        ];

        $def = $map[$sector] ?? abort(404, "Unknown sector '$sector'");
        $items = $county->{$def['relation']}()
            ->where('is_published', true)
            ->orderBy('name')
            ->paginate(12);

        $sectorInfo = $def + ['desc' => "{$def['title']} in {$county->name} County."];

        return view('counties.sector', compact('county', 'items', 'sector', 'sectorInfo'));
    }
}
