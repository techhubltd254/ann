<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\County;
use App\Models\Exhibition;
use App\Models\Marketplace\Product;
use App\Models\TradeAgreement;
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
        $tradeAgreementsHome = TradeAgreement::with('bloc')->featured()->active()->latest()->take(3)->get();

        return view('home', compact('featuredExhibitions', 'counties', 'products', 'venues', 'tradeAgreementsHome'));
    }
}
