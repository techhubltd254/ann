<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\County;
use App\Models\Sector;
use Illuminate\Http\Request;

class CountyController extends Controller
{
    public function index(Request $request)
    {
        $query = County::where('is_active', true);

        if ($request->filled('sector')) {
            $query->whereJsonContains('primary_sectors', $request->sector);
        }

        if ($request->filled('zone')) {
            $query->where('economic_zone', $request->zone);
        }

        if ($request->filled('search')) {
            $q = $request->search;
            $query->where(function ($qry) use ($q) {
                $qry->where('name', 'like', "%{$q}%")
                    ->orWhere('description', 'like', "%{$q}%")
                    ->orWhere('tagline', 'like', "%{$q}%");
            });
        }

        $counties = $query->with('sectors')->paginate(20);

        return response()->json($counties);
    }

    public function show(string $slug)
    {
        $county = County::where('slug', $slug)
            ->with(['sectors', 'seasonalCalendars'])
            ->firstOrFail();

        return response()->json([
            'county' => $county,
            'current_month' => $county->seasonalCalendars()
                ->where('month', now()->month)
                ->first(),
        ]);
    }

    public function sectors(string $slug)
    {
        $county = County::where('slug', $slug)->firstOrFail();
        return response()->json($county->sectors);
    }

    public function weather(string $slug)
    {
        $county = County::where('slug', $slug)->firstOrFail();
        $calendar = $county->seasonalCalendars()
            ->orderBy('month')
            ->get();

        return response()->json([
            'county' => $county->name,
            'annual' => $calendar,
            'now' => $calendar->firstWhere('month', now()->month),
        ]);
    }

    public function nationalHub()
    {
        $counties = County::where('is_active', true)->get();
        $sectors = Sector::whereNull('parent_id')->with('children')->get();

        return response()->json([
            'total_counties' => count($counties),
            'total_sectors' => count($sectors),
            'by_zone' => $counties->groupBy('economic_zone')->map->count(),
            'counties' => $counties,
            'sectors' => $sectors,
            'featured' => $counties->filter->isTourismDestination()->take(5)->values(),
        ]);
    }

    public function matchDestination(Request $request)
    {
        $request->validate([
            'query' => 'nullable|string|max:200',
            'temp' => 'nullable|numeric',
            'intent' => 'nullable|string|in:warm_escape,cool_retreat,adventure,cultural,business,family,general',
        ]);

        $temp = $request->float('temp', 20);
        $intent = $request->input('intent', 'general');
        $query = $request->input('query', '');

        $counties = County::where('is_active', true)->get();
        $scored = [];

        foreach ($counties as $county) {
            $warmest = filter_var($county->warmest_month, FILTER_SANITIZE_NUMBER_INT);
            $coolest = filter_var($county->coolest_month, FILTER_SANITIZE_NUMBER_INT);
            $avgTemp = ((int) $warmest + (int) $coolest) / 2;

            $score = 50;

            if ($temp <= 12 && $avgTemp >= 28) $score += 35;
            elseif ($temp >= 30 && $avgTemp <= 22) $score += 35;
            elseif (abs($temp - $avgTemp) <= 5) $score += 10;

            $score += $county->isTourismDestination() ? 15 : 5;

            $scored[] = [
                'county' => $county,
                'score' => min(100, $score),
            ];
        }

        usort($scored, fn($a, $b) => $b['score'] <=> $a['score']);

        return response()->json([
            'user_context' => [
                'detected_temp' => $temp,
                'inferred_intent' => $intent,
            ],
            'recommendations' => array_slice($scored, 0, 5),
        ]);
    }
}
