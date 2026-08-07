<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\County;
use App\Models\Exhibition;
use Illuminate\Support\Facades\Cache;

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

        return view('home', compact('featuredExhibitions', 'counties'));
    }
}
