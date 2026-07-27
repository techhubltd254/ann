<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\County;
use App\Models\CountyCultureSite;
use App\Models\CountyFarm;
use App\Models\CountyHealthFacility;
use App\Models\CountyHotel;
use App\Models\CountyInstitution;
use App\Models\CountyProduct;
use App\Models\CountyTourismAttraction;
use App\Models\CountyTransport;
use App\Models\SectorEntity;
use Illuminate\Http\Request;

class CountyController extends Controller
{
    public function index()
    {
        $counties = County::withCount('sectors')
            ->orderBy('name')
            ->get();

        return view('counties.index', compact('counties'));
    }

    public function show(County $county)
    {
        $county->load(['sectors' => function ($q) {
            $q->orderBy('sort_order');
        }]);

        $linkedSectors = $county->sectors()->orderBy('name')->get();
        $exhibitions = $county->exhibitions()
            ->where('status', 'published')
            ->orderBy('start_date', 'desc')
            ->take(3)
            ->get();

        return view('counties.show', compact(
            'county', 'linkedSectors', 'exhibitions'
        ));
    }

    public function sector(County $county, string $sector)
    {
        $sectorModel = $county->sectors()->where('slug', $sector)->first();
        if (!$sectorModel) {
            abort(404, "Sector '{$sector}' not found for {$county->name}");
        }

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

        if (isset($models[$sector])) {
            $modelClass = $models[$sector];
            $items = $modelClass::where('county_id', $county->id)
                ->where('is_published', true)
                ->paginate(12);
        } else {
            $items = SectorEntity::where('county_id', $county->id)
                ->where('sector_id', $sectorModel->id)
                ->where('is_published', true)
                ->orderBy('name')
                ->paginate(12);
        }

        $infoKey = isset($info[$sector]) ? $sector : 'generic';
        if ($infoKey === 'generic') {
            $info['generic'] = [
                'title' => $sectorModel->name,
                'icon' => '📋',
                'desc' => "{$sectorModel->name} entities and resources in {$county->name} County.",
            ];
        }

        return view('counties.sector', compact('county', 'items', 'sector', 'info', 'sectorModel'));
    }
}
