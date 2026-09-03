<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Exhibition;
use App\Models\LiveStream;
use App\Models\Venue;

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

        return view('exhibitions.index', compact('exhibitions', 'liveStreams'));
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

    public function venues()
    {
        $venues = Venue::where('is_active', true)
            ->orderBy('name')
            ->paginate(12);

        return view('venues.index', compact('venues'));
    }
}
