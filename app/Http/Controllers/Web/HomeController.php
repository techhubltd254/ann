<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\County;
use App\Models\Exhibition;
use App\Models\Marketplace\Product;
use App\Models\Venue;

class HomeController extends Controller
{
    public function __invoke()
    {
        $featuredExhibitions = Exhibition::with('county')
            ->where('status', 'published')
            ->where('is_featured', true)
            ->orderBy('start_date')
            ->take(3)
            ->get();

        $counties = County::orderBy('name')->get();
        $products = Product::with(['county', 'variants'])->active()->latest()->take(8)->get();
        $venues = Venue::where('is_active', true)->orderBy('name')->take(4)->get();
        $ministries = \App\Models\Ministry::with('agencies')->where('is_active', true)->orderBy('name')->get();

        // Canonical major sector groups with department counts (junk/dupes cleaned 2026-08).
        $groupMeta = \App\Models\Sector::GROUP_META;
        $counts = \App\Models\Sector::where('is_active', true)
            ->whereNotNull('sector_group')
            ->selectRaw('sector_group, COUNT(*) as n')
            ->groupBy('sector_group')
            ->pluck('n', 'sector_group');
        $sectorGroups = collect($groupMeta)->map(fn ($meta, $key) => [
            'key' => $key,
            'name' => $meta[0],
            'icon' => $meta[1],
            'count' => (int) ($counts[$key] ?? 0),
        ])->where('count', '>', 0)->values();

        return view('home', compact('featuredExhibitions', 'counties', 'products', 'venues', 'ministries', 'sectorGroups'));
    }
}