<?php

namespace App\Services;

use App\Models\County;
use Illuminate\Support\Facades\DB;

/**
 * County classification using real data available across all 47 counties.
 * RPS (revenue potential): population, economic zone, entity count
 * FNS (foundational need): area, density, inverse of economic zone
 * The two scores are NEVER combined — the quadrant IS the output.
 */
class CountyClassificationService
{
    public function classify(County $county): array
    {
        $stats = $this->countyStats($county->id);
        $r = $this->rps($county, $stats);
        $f = $this->fns($county, $stats);

        if ($r >= 0.5 && $f < 0.5) $quadrant = 'engine';
        elseif ($r < 0.5 && $f < 0.5) $quadrant = 'growth';
        elseif ($r >= 0.5 && $f >= 0.5) $quadrant = 'priority_development';
        else $quadrant = 'foundational_anchor';

        $county->forceFill([
            'classification_rps'      => $r,
            'classification_fns'      => $f,
            'classification_quadrant' => $quadrant,
        ])->save();

        return ['county' => $county->name, 'slug' => $county->slug, 'rps' => $r, 'fns' => $f, 'quadrant' => $quadrant];
    }

    public function classifyAll(): void
    {
        County::chunk(50, fn($c) => $c->each(fn($c) => $this->classify($c)));
    }

    private function countyStats(int $id): object
    {
        return DB::table('counties as c')
            ->leftJoin('county_tourism_attractions as a', 'c.id', '=', 'a.county_id')
            ->leftJoin('county_hotels as h', 'c.id', '=', 'h.county_id')
            ->leftJoin('county_products as p', 'c.id', '=', 'p.county_id')
            ->leftJoin('county_institutions as i', 'c.id', '=', 'i.county_id')
            ->selectRaw('
                c.population_2024, c.area_km2, c.economic_zone, c.former_province,
                COUNT(DISTINCT a.id) as attractions,
                COUNT(DISTINCT h.id) as hotels,
                COUNT(DISTINCT p.id) as products,
                COUNT(DISTINCT i.id) as institutions')
            ->where('c.id', $id)->groupBy('c.id')->first() ?? (object) [
                'population_2024' => null, 'area_km2' => null, 'economic_zone' => null,
                'former_province' => null, 'attractions' => 0, 'hotels' => 0,
                'products' => 0, 'institutions' => 0,
            ];
    }

    public function rps(County $county, object $stats): float
    {
        $maxPop = 5544000; $minPop = 143000;
        $popScore = $stats->population_2024
            ? ($stats->population_2024 - $minPop) / max(1, ($maxPop - $minPop))
            : 0;
        $ecoBonus = in_array($stats->economic_zone, ['Coast','Nairobi Metro','Central Highlands']) ? 0.2 : 0;
        $entityScore = min(1, ($stats->attractions + $stats->hotels + $stats->products + $stats->institutions) / 50);
        return round(min(1, ($popScore * 0.6) + $entityScore * 0.2 + $ecoBonus), 4);
    }

    public function fns(County $county, object $stats): float
    {
        $pop = max(1, $stats->population_2024 ?: 0);
        $area = max(1, $stats->area_km2 ?: 1);
        $density = $pop / $area;
        $maxDensity = 5485;
        $densityScore = 1 - min(1, $density / $maxDensity);
        $areaScore = min(1, $area / 70000);
        $ecoPenalty = in_array($stats->economic_zone, ['Arid','North Eastern','Upper Eastern']) ? 0.3 : 0;
        return round(min(1, $densityScore * 0.4 + $areaScore * 0.3 + $ecoPenalty), 4);
    }
}