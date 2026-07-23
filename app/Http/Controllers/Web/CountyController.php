<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\County;
use App\Models\CountyTourismAttraction;
use App\Models\CountyHotel;
use App\Models\CountyProduct;
use App\Models\CountyInstitution;
use App\Models\CountyFarm;
use App\Models\CountyTransport;
use App\Models\CountyHealthFacility;
use App\Models\CountyCultureSite;

class CountyController extends Controller
{
    public function index()
    {
        $counties = County::withCount('sectors')
            ->orderBy('name')
            ->paginate(24);

        return view('counties.index', compact('counties'));
    }

    public function show(County $county)
    {
        $county->load(['sectors' => function ($q) {
            $q->orderBy('sort_order');
        }]);

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
            'Tourism' => ['count' => $tourismCount, 'icon' => '🏖️', 'color' => 'amber', 'route' => 'tourism'],
            'Hospitality' => ['count' => $hotelsCount, 'icon' => '🏨', 'color' => 'rose', 'route' => 'hotels'],
            'Trade & Products' => ['count' => $productsCount, 'icon' => '🛍️', 'color' => 'purple', 'route' => 'products'],
            'Education' => ['count' => $institutionsCount, 'icon' => '🎓', 'color' => 'blue', 'route' => 'institutions'],
            'Agriculture' => ['count' => $farmsCount, 'icon' => '🌾', 'color' => 'green', 'route' => 'farms'],
            'Transport' => ['count' => $transportCount, 'icon' => '🚢', 'color' => 'cyan', 'route' => 'transport'],
            'Healthcare' => ['count' => $healthCount, 'icon' => '🏥', 'color' => 'red', 'route' => 'health'],
            'Culture' => ['count' => $cultureCount, 'icon' => '🎭', 'color' => 'orange', 'route' => 'culture'],
        ];

        $featuredAttractions = $county->tourismAttractions()->where('is_published', true)->take(4)->get();
        $featuredHotels = $county->hotels()->where('is_published', true)->take(4)->get();

        $exhibitions = $county->exhibitions()
            ->where('status', 'published')
            ->orderBy('start_date', 'desc')
            ->take(3)
            ->get();

        return view('counties.show', compact(
            'county', 'sectors', 'sectorData',
            'featuredAttractions', 'featuredHotels',
            'exhibitions'
        ));
    }

    public function sector(County $county, string $sector)
    {
        $models = [
            'tourism' => CountyTourismAttraction::class,
            'hotels' => CountyHotel::class,
            'products' => CountyProduct::class,
            'institutions' => CountyInstitution::class,
            'farms' => CountyFarm::class,
            'transport' => CountyTransport::class,
            'health' => CountyHealthFacility::class,
            'culture' => CountyCultureSite::class,
        ];

        $info = [
            'tourism' => ['title' => 'Tourism & Attractions', 'icon' => '🏖️', 'desc' => 'Discover tourist attractions, parks, and cultural sites.'],
            'hotels' => ['title' => 'Hospitality & Hotels', 'icon' => '🏨', 'desc' => 'Hotels, resorts, and accommodation options.'],
            'products' => ['title' => 'Trade & Products', 'icon' => '🛍️', 'desc' => 'Local products, markets, and trade opportunities.'],
            'institutions' => ['title' => 'Education & Institutions', 'icon' => '🎓', 'desc' => 'Schools, colleges, universities, and training centers.'],
            'farms' => ['title' => 'Agriculture & Farms', 'icon' => '🌾', 'desc' => 'Farms, agricultural produce, and agribusiness.'],
            'transport' => ['title' => 'Transport & Logistics', 'icon' => '🚢', 'desc' => 'Transport hubs, ports, airports, and logistics.'],
            'health' => ['title' => 'Healthcare', 'icon' => '🏥', 'desc' => 'Hospitals, clinics, and health facilities.'],
            'culture' => ['title' => 'Culture & Heritage', 'icon' => '🎭', 'desc' => 'Cultural sites, traditions, and heritage.'],
        ];

        if (!isset($models[$sector])) {
            abort(404);
        }

        $modelClass = $models[$sector];
        $items = $modelClass::where('county_id', $county->id)
            ->where('is_published', true)
            ->paginate(12);

        return view('counties.sector', compact('county', 'items', 'sector', 'info'));
    }
}
