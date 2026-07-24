<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\County;
use App\Models\Travel\Attraction;
use App\Models\Travel\Hotel;
use Illuminate\Http\Request;

class TravelController extends Controller
{
    public function index(Request $request)
    {
        $attractions = Attraction::with('county')->where('is_active', true)->get();
        $hotels = Hotel::with('county')->where('is_active', true)->get();
        $counties = County::orderBy('name')->get(['id', 'name', 'slug']);

        return view('travel.index', compact('attractions', 'hotels', 'counties'));
    }
}
