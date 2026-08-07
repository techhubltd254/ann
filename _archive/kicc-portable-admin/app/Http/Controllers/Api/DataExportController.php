<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
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
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DataExportController extends Controller
{
    public function exportCounty($slug, $format = 'json')
    {
        $county = County::where('slug', $slug)->firstOrFail();
        $data = [
            'county' => $county->toArray(),
            'sectors' => $county->sectors()->orderBy('name')->get()->toArray(),
            'entities' => SectorEntity::where('county_id', $county->id)->orderBy('name')->get()->toArray(),
            'attractions' => CountyTourismAttraction::where('county_id', $county->id)->orderBy('name')->get()->toArray(),
            'hotels' => CountyHotel::where('county_id', $county->id)->orderBy('name')->get()->toArray(),
            'farms' => CountyFarm::where('county_id', $county->id)->orderBy('name')->get()->toArray(),
            'health' => CountyHealthFacility::where('county_id', $county->id)->orderBy('name')->get()->toArray(),
            'institutions' => CountyInstitution::where('county_id', $county->id)->orderBy('name')->get()->toArray(),
            'transport' => CountyTransport::where('county_id', $county->id)->orderBy('name')->get()->toArray(),
            'culture' => CountyCultureSite::where('county_id', $county->id)->orderBy('name')->get()->toArray(),
            'products' => CountyProduct::where('county_id', $county->id)->orderBy('name')->get()->toArray(),
            'weather' => DB::table('seasonal_calendars')->where('county_id', $county->id)->orderBy('month')->get()->toArray(),
            'exported_at' => now()->toIso8601String(),
        ];

        if ($format === 'csv') {
            return $this->toCsv($data, $county->slug);
        }
        return response()->json($data)->header('Content-Disposition', "attachment; filename=\"{$slug}-county-data.json\"");
    }

    public function exportAll($format = 'json')
    {
        $counties = County::orderBy('name')->pluck('slug');
        $all = [];
        foreach ($counties as $slug) {
            $c = County::where('slug', $slug)->first();
            $all[$slug] = [
                'county' => $c->toArray(),
                'data_points' => 
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
        }
        return response()->json([
            'exported_at' => now()->toIso8601String(),
            'total_counties' => count($all),
            'counties' => $all,
        ])->header('Content-Disposition', 'attachment; filename="all-counties-export.json"');
    }

    public function exportSummary()
    {
        $counties = County::orderBy('name')->get()->map(function ($c) {
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
            return [
                'name' => $c->name,
                'slug' => $c->slug,
                'capital' => $c->capital,
                'region' => $c->region,
                'population' => $c->population_2024,
                'area_km2' => $c->area_km2,
                'total_data_points' => $total,
                'sectors' => $c->sectors()->count(),
                'entities' => SectorEntity::where('county_id', $c->id)->count(),
                'attractions' => CountyTourismAttraction::where('county_id', $c->id)->count(),
                'hotels' => CountyHotel::where('county_id', $c->id)->count(),
                'farms' => CountyFarm::where('county_id', $c->id)->count(),
                'health' => CountyHealthFacility::where('county_id', $c->id)->count(),
                'institutions' => CountyInstitution::where('county_id', $c->id)->count(),
                'transport' => CountyTransport::where('county_id', $c->id)->count(),
                'culture' => CountyCultureSite::where('county_id', $c->id)->count(),
                'products' => CountyProduct::where('county_id', $c->id)->count(),
            ];
        });

        $csv = "Name,Slug,Capital,Region,Population,Area km²,Data Points,Sectors,Entities,Attractions,Hotels,Farms,Health,Institutions,Transport,Culture,Products\n";
        foreach ($counties as $c) {
            $csv .= implode(',', [
                "\"{$c['name']}\"", $c['slug'], "\"{$c['capital']}\"", "\"{$c['region']}\"",
                $c['population'] ?? 0, $c['area_km2'] ?? 0,
                $c['total_data_points'], $c['sectors'], $c['entities'],
                $c['attractions'], $c['hotels'], $c['farms'], $c['health'],
                $c['institutions'], $c['transport'], $c['culture'], $c['products'],
            ]) . "\n";
        }
        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="county-summary.csv"',
        ]);
    }

    private function toCsv($data, $slug)
    {
        $csv = '';
        foreach ($data as $section => $rows) {
            if (is_array($rows) && isset($rows[0])) {
                $csv .= "\n=== {$section} ===\n";
                $csv .= implode(',', array_keys((array)$rows[0])) . "\n";
                foreach ($rows as $row) {
                    $csv .= implode(',', array_map(fn($v) => '"' . str_replace('"', '""', $v ?? '') . '"', (array)$row)) . "\n";
                }
            }
        }
        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$slug}-county-data.csv\"",
        ]);
    }
}
