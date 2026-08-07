<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use App\Models\County;
use App\Models\Sector;
use App\Models\SectorEntity;
use App\Models\CountyProduct;
use App\Models\CountyTourismAttraction;
use App\Models\CountyHotel;
use App\Models\CountyFarm;
use App\Models\CountyHealthFacility;
use App\Models\CountyInstitution;
use App\Models\CountyTransport;
use App\Models\CountyCultureSite;
use Illuminate\Support\Facades\DB;

class WarmCache extends Command
{
    protected $signature = 'cache:warm';
    protected $description = 'Pre-load all county data into cache';

    public function handle()
    {
        $this->info('Warming cache for all 47 counties...');

        County::all()->each(function ($county) {
            $key = "county_dash_{$county->slug}";
            $data = [
                'sectors' => $county->sectors()->orderBy('name')->get(),
                'entities' => SectorEntity::where('county_id', $county->id)->orderBy('name')->get(),
                'products' => CountyProduct::where('county_id', $county->id)->get(),
                'attractions' => CountyTourismAttraction::where('county_id', $county->id)->get(),
                'hotels' => CountyHotel::where('county_id', $county->id)->get(),
                'farms' => CountyFarm::where('county_id', $county->id)->get(),
                'health' => CountyHealthFacility::where('county_id', $county->id)->get(),
                'institutions' => CountyInstitution::where('county_id', $county->id)->get(),
                'transport' => CountyTransport::where('county_id', $county->id)->get(),
                'culture' => CountyCultureSite::where('county_id', $county->id)->get(),
                'exhibitions' => [],
                'allSectors' => Sector::orderBy('name')->get(),
            ];
            Cache::put($key, $data, 3600);
            $this->line("  ✅ {$county->name}");
        });

        // Warm API caches
        Cache::put('co_overview', [
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
        ], 3600);

        $this->info('Cache warmed successfully!');
    }
}
