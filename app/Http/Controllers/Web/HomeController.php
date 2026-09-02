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
        $cacheKey = 'kicc_home_page_data_v4_' . cache_buster();

        $ids = Cache::remember($cacheKey, 60, function () {
            // Display-priority products: sector representation + review score,
            // restricted to real-data counties (auto-detected by product count, not hardcoded).
            $priority = app(\App\Services\DisplayPriorityService::class);
            $productIds = $priority->marketplaceProductIds();
            $productIds = array_slice($productIds, 0, 8);

            return [
                'countyIds' => County::orderBy('name')->pluck('id')->all(),
                'exhibitionIds' => Exhibition::where('status', 'published')->where('is_featured', true)
                    ->orderBy('start_date')->take(3)->pluck('id')->all(),
                'productIds' => $productIds,
                'venueIds' => Venue::where('is_active', true)->orderBy('name')->take(4)->pluck('id')->all(),
                'tradeAgreementIds' => TradeAgreement::featured()->active()->latest()->take(3)->pluck('id')->all(),
            ];
        });

        // Hydrate models after cache read (never cache Eloquent collections in Redis)
        $counties = County::whereIn('id', $ids['countyIds'] ?? [])->orderBy('name')->get(['id', 'name', 'slug', 'economic_zone', 'former_province']);
        $featuredExhibitions = Exhibition::with('county')->whereIn('id', $ids['exhibitionIds'] ?? [])->orderBy('start_date')->get();
        $products = Product::with(['county', 'category', 'variants', 'images'])->whereIn('id', $ids['productIds'] ?? [])->latest()->get();
        $venues = Venue::whereIn('id', $ids['venueIds'] ?? [])->orderBy('name')->get();
        $tradeAgreementsHome = TradeAgreement::with('bloc')->whereIn('id', $ids['tradeAgreementIds'] ?? [])->latest()->get();

        // Resolve hero videos for all counties
        $heroAssets = MediaAsset::where('owner_type', County::class)
            ->whereIn('owner_id', $counties->pluck('id'))
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

        // Resolve the pipeline-managed hero video (fall back to hardcoded path).
        $heroAsset = MediaAsset::resolveSlot('landing_page', 1, 'hero_video');
        $heroVideo = $heroAsset?->bestVideoUrl();
        $heroWebm = $heroAsset?->webmUrl();
        $heroPoster = $heroAsset?->posterUrl();

        return view('home', compact(
            'featuredExhibitions', 'counties', 'products', 'venues',
            'tradeAgreementsHome', 'heroVideo', 'heroWebm', 'heroPoster',
            'countyHeroVideos',
        ));
    }
}