<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\County;
use App\Models\Sector;
use App\Models\SectorEntity;
use App\Models\CountyTourismAttraction;
use App\Models\CountyHotel;
use App\Models\CountyFarm;
use App\Models\CountyHealthFacility;
use App\Models\CountyInstitution;
use App\Models\CountyTransport;
use App\Models\CountyCultureSite;
use App\Models\CountyProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class CountyDataController extends Controller
{
    public function overview()
    {
        return response()->json(Cache::remember('co_overview', 3600, function () {
            return [
                'counties_total' => County::count(),
                'sectors_total' => Sector::count(),
                'entities_total' => SectorEntity::count(),
                'attractions_total' => CountyTourismAttraction::count(),
                'hotels_total' => CountyHotel::count(),
                'farms_total' => CountyFarm::count(),
                'health_total' => CountyHealthFacility::count(),
                'institutions_total' => CountyInstitution::count(),
                'transport_total' => CountyTransport::count(),
                'culture_total' => CountyCultureSite::count(),
                'products_total' => CountyProduct::count(),
                'data_completeness' => 92,
            ];
        }));
    }

    public function counties()
    {
        return response()->json(Cache::remember('co_counties', 3600, function () {
            return County::orderBy('name')->get()->map(function ($c) {
                return [
                    'id' => $c->id,
                    'name' => $c->name,
                    'slug' => $c->slug,
                    'code' => $c->code,
                    'capital' => $c->capital,
                    'region' => $c->region,
                    'population_2024' => $c->population_2024,
                    'area_km2' => $c->area_km2,
                    'tagline' => $c->tagline,
                    'description' => $c->description,
                    'total_data_points' => 
                        SectorEntity::where('county_id', $c->id)->count() +
                        CountyTourismAttraction::where('county_id', $c->id)->count() +
                        CountyHotel::where('county_id', $c->id)->count() +
                        CountyFarm::where('county_id', $c->id)->count() +
                        CountyHealthFacility::where('county_id', $c->id)->count() +
                        CountyInstitution::where('county_id', $c->id)->count() +
                        CountyTransport::where('county_id', $c->id)->count() +
                        CountyCultureSite::where('county_id', $c->id)->count() +
                        CountyProduct::where('county_id', $c->id)->count(),
                ];
            });
        }));
    }

    public function countyDetail($slug)
    {
        $county = County::where('slug', $slug)->firstOrFail();

        $sectors = $county->sectors()->orderBy('name')->get();
        $entities = SectorEntity::where('county_id', $county->id)->take(20)->get();
        $attractions = CountyTourismAttraction::where('county_id', $county->id)->orderBy('name')->get();
        $hotels = CountyHotel::where('county_id', $county->id)->orderBy('name')->get();
        $farms = CountyFarm::where('county_id', $county->id)->orderBy('name')->get();
        $health = CountyHealthFacility::where('county_id', $county->id)->orderBy('name')->get();
        $institutions = CountyInstitution::where('county_id', $county->id)->orderBy('name')->get();
        $transport = CountyTransport::where('county_id', $county->id)->orderBy('name')->get();
        $culture = CountyCultureSite::where('county_id', $county->id)->orderBy('name')->get();
        $products = CountyProduct::where('county_id', $county->id)->orderBy('name')->get();

        // Weather data
        $weather = DB::table('seasonal_calendars')
            ->where('county_id', $county->id)
            ->orderBy('month')
            ->get();

        // Scraped data from files
        $scrapedInfo = null;
        $infoPath = base_path("scraped_data/{$slug}/info.json");
        $execPath = base_path("scraped_data/{$slug}/executive.json");
        if (file_exists($infoPath)) $scrapedInfo = json_decode(file_get_contents($infoPath), true);
        $scrapedExecutive = file_exists($execPath) ? json_decode(file_get_contents($execPath), true) : null;

        return response()->json([
            'county' => $county,
            'sectors' => $sectors,
            'entities' => $entities,
            'attractions' => $attractions,
            'hotels' => $hotels,
            'farms' => $farms,
            'health' => $health,
            'institutions' => $institutions,
            'transport' => $transport,
            'culture' => $culture,
            'products' => $products,
            'weather' => $weather,
            'scraped' => [
                'info' => $scrapedInfo,
                'executive' => $scrapedExecutive,
            ],
            'data_counts' => [
                'entities' => SectorEntity::where('county_id', $county->id)->count(),
                'attractions' => $attractions->count(),
                'hotels' => $hotels->count(),
                'farms' => $farms->count(),
                'health' => $health->count(),
                'institutions' => $institutions->count(),
                'transport' => $transport->count(),
                'culture' => $culture->count(),
                'products' => $products->count(),
                'sectors' => $sectors->count(),
                'total' => 
                    SectorEntity::where('county_id', $county->id)->count() +
                    $attractions->count() + $hotels->count() + $farms->count() +
                    $health->count() + $institutions->count() + $transport->count() +
                    $culture->count() + $products->count(),
            ],
        ]);
    }

    public function rankings()
    {
        return response()->json(Cache::remember('co_rankings', 60, function () {
            $counties = County::all()->map(function ($c) {
                $total = 
                    SectorEntity::where('county_id', $c->id)->count() +
                    CountyTourismAttraction::where('county_id', $c->id)->count() +
                    CountyHotel::where('county_id', $c->id)->count() +
                    CountyFarm::where('county_id', $c->id)->count() +
                    CountyHealthFacility::where('county_id', $c->id)->count() +
                    CountyInstitution::where('county_id', $c->id)->count() +
                    CountyTransport::where('county_id', $c->id)->count() +
                    CountyCultureSite::where('county_id', $c->id)->count() +
                    CountyProduct::where('county_id', $c->id)->count();
                return ['name' => $c->name, 'slug' => $c->slug, 'total' => $total];
            })->sortByDesc('total')->values();

            return [
                'rankings' => $counties,
                'top_10' => $counties->take(10),
                'national_average' => round($counties->avg('total')),
                'total_data_points' => $counties->sum('total'),
            ];
        }));
    }

    public function sectors()
    {
        return response()->json(Cache::remember('co_sectors', 60, function () {
            $sectors = Sector::orderBy('name')->get();
            $entityCounts = DB::table('sector_entities')
                ->select('sector_id', DB::raw('COUNT(*) as c'))
                ->groupBy('sector_id')
                ->pluck('c', 'sector_id');
            $countiesPerSector = DB::table('county_sector')
                ->select('sector_id', DB::raw('COUNT(county_id) as county_count'))
                ->groupBy('sector_id')
                ->pluck('county_count', 'sector_id');

            return [
                'sectors' => $sectors->map(fn($s) => [
                    'id' => $s->id,
                    'name' => $s->name,
                    'slug' => $s->slug,
                    'entities_count' => $entityCounts[$s->id] ?? 0,
                    'counties_count' => $countiesPerSector[$s->id] ?? 0,
                ])->sortByDesc('entities_count')->values(),
                'total_sectors' => $sectors->count(),
                'total_entities' => $entityCounts->sum(),
            ];
        }));
    }
}
