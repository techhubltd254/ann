<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\County;
use App\Models\Exhibition;
use App\Models\LiveStream;
use App\Models\Venue;
use Illuminate\Http\Request;

class ExhibitionController extends Controller
{
    public function index()
    {
        $exhibitions = Exhibition::with('county')
            ->withCount('booths')
            ->where('status', 'published')
            ->orderBy('start_date')
            ->paginate(12);

        $liveStreams = LiveStream::where('status', 'live')
            ->pluck('exhibition_id')
            ->filter()
            ->values()
            ->all();

        return view('experience.exhibitions', compact('exhibitions', 'liveStreams'));
    }

    public function show(string $slug)
    {
        $exhibition = Exhibition::where('slug', $slug)
            ->where('status', 'published')
            ->with([
                'county',
                'venue',
                'sessions' => fn($q) => $q->orderBy('start_time'),
                'booths' => fn($q) => $q->where('status', 'available'),
                'ticketTypes' => fn($q) => $q->where('is_active', true),
            ])
            ->firstOrFail();

        $liveStream = LiveStream::with('user')
            ->where('exhibition_id', $exhibition->id)
            ->latest()
            ->first();

        return view('exhibitions.show', compact('exhibition', 'liveStream'));
    }

    public function venues(Request $request)
    {
        $q = Venue::with('institution')
            ->where('is_active', true);

        $countyId = $request->get('county_id');
        if ($countyId) $q->where('county_id', (int) $countyId);

        $venueType = $request->get('type');
        if ($venueType) $q->where('venue_type', $venueType);

        $minCapacity = $request->get('min_capacity');
        if ($minCapacity) $q->where('capacity', '>=', (int) $minCapacity);

        $venues = $q->orderBy('name')->paginate(24);
        $counties = County::orderBy('name')->get(['id', 'name', 'slug']);
        $venueTypes = Venue::selectRaw('DISTINCT venue_type')
            ->whereNotNull('venue_type')
            ->pluck('venue_type')->toArray();

        return view('experience.venues', compact('venues', 'counties', 'venueTypes'));
    }
}
