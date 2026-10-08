<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\CarRental;
use App\Models\EventOrganizer;
use App\Models\Restaurant;
use App\Models\TourGuide;
use Illuminate\Http\Request;

class TourismEntityController extends Controller
{
    public function guides(Request $request)
    {
        $query = TourGuide::with('county')->where('is_published', true);
        if ($q = $request->get('q')) $query->where('name', 'like', "%{$q}%");
        if ($c = $request->get('county')) $query->where('county_id', $c);
        $guides = $query->paginate(20);
        return view('experience.pages.tourism.guides', compact('guides'));
    }

    public function rentals(Request $request)
    {
        $query = CarRental::with('county')->where('is_published', true);
        if ($q = $request->get('q')) $query->where('company_name', 'like', "%{$q}%")->orWhere('vehicle_type', 'like', "%{$q}%");
        if ($c = $request->get('county')) $query->where('county_id', $c);
        $rentals = $query->paginate(20);
        return view('experience.pages.tourism.rentals', compact('rentals'));
    }

    public function restaurants(Request $request)
    {
        $query = Restaurant::with('county')->where('is_published', true);
        if ($q = $request->get('q')) $query->where('name', 'like', "%{$q}%")->orWhere('cuisine_type', 'like', "%{$q}%");
        if ($c = $request->get('county')) $query->where('county_id', $c);
        $restaurants = $query->paginate(20);
        return view('experience.pages.tourism.restaurants', compact('restaurants'));
    }

    public function organizers(Request $request)
    {
        $organizers = EventOrganizer::where('is_active', true)->paginate(20);
        return view('experience.pages.tourism.organizers', compact('organizers'));
    }
}