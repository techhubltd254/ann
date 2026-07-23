<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Venue;

class VenueController extends Controller
{
    public function show(Venue $venue)
    {
        $venue->load('exhibitions');

        $upcomingExhibitions = $venue->exhibitions()
            ->where('status', 'published')
            ->where('start_date', '>=', now())
            ->orderBy('start_date')
            ->take(6)
            ->get();

        return view('venues.show', compact('venue', 'upcomingExhibitions'));
    }
}
