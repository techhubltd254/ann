<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\County;
use App\Models\Exhibition;
use App\Models\Marketplace\Product;
use App\Models\MediaAsset;
use App\Models\TradeAgreement;
use App\Models\Venue;
use Illuminate\Support\Facades\Cache;

class HomeController extends Controller
{
    public function __invoke()
    {
        $cacheKey = 'kicc_home_page_data_v2';

        $data = Cache::remember($cacheKey, 600, function () {
            $featuredExhibitions = Exhibition::with('county')
                ->where('status', 'published')
                ->where('is_featured', true)
                ->orderBy('start_date')
                ->take(3)
                ->get();

            $counties = County::orderBy('name')->get();
            $countyIds = $counties->pluck('id');

            // Resolve hero videos for all counties — includes mp4Url + economic_zone for overlay
            $heroAssets = MediaAsset::where('owner_type', County::class)
                ->whereIn('owner_id', $countyIds)
                ->where('slot', 'hero_video')
                ->where('status', 'ready')
                ->with('derivatives')
                ->get()
                ->keyBy('owner_id');

            $countyHeroVideos = [];
            foreach ($counties as $c) {
                $asset = $heroAssets->get($c->id);
                $countyHeroVideos[$c->slug] = $asset?->mp4Url();
            }

            $products = Product::with(['county', 'category', 'variants'])->active()->latest()->take(8)->get();
            $venues = Venue::where('is_active', true)->orderBy('name')->take(4)->get();
            $tradeAgreementsHome = TradeAgreement::with('bloc')->featured()->active()->latest()->take(3)->get();

            // Resolve the pipeline-managed hero video (fall back to hardcoded path).
            $heroAsset = MediaAsset::resolveSlot('landing_page', 1, 'hero_video');
            $heroVideo = $heroAsset?->bestVideoUrl();
            $heroWebm = $heroAsset?->webmUrl();
            $heroPoster = $heroAsset?->posterUrl();

            return [
                'featuredExhibitions' => $featuredExhibitions,
                'counties' => $counties,
                'products' => $products,
                'venues' => $venues,
                'tradeAgreementsHome' => $tradeAgreementsHome,
                'heroVideo' => $heroVideo,
                'heroWebm' => $heroWebm,
                'heroPoster' => $heroPoster,
                'countyHeroVideos' => $countyHeroVideos,
            ];
        });

        return view('home', $data);
    }
}
