<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\County;
use App\Models\Exhibition;
use App\Models\LiveStream;
use App\Models\Marketplace\Product;
use App\Models\MediaAsset;
use App\Models\TradeAgreement;
use App\Models\Venue;
use Illuminate\Support\Facades\Cache;

class HomeController extends Controller
{
    public function __invoke()
    {
        $cacheKey = 'kicc_home_page_data_live20261007_' . cache_buster();

        $ids = Cache::remember($cacheKey, config('kicc.cache_ttl.public', 21600), function () {
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
                'venueIds' => Venue::where('is_active', true)->orderBy('name')->take(10)->pluck('id')->all(),
                'tradeAgreementIds' => TradeAgreement::featured()->active()->latest()->take(3)->pluck('id')->all(),
            ];
        });

        // Hydrate models after cache read (never cache Eloquent collections in Redis)
        $counties = County::whereIn('id', $ids['countyIds'] ?? [])->orderBy('name')->get(['id', 'name', 'slug', 'economic_zone', 'former_province', 'code', 'primary_sectors']);
        $featuredExhibitions = Exhibition::with('county')->whereIn('id', $ids['exhibitionIds'] ?? [])->orderBy('start_date')->get();
        $products = Product::with(['county', 'category', 'variants', 'images'])->whereIn('id', $ids['productIds'] ?? [])->latest()->get();
        $venues = Venue::whereIn('id', $ids['venueIds'] ?? [])->orderBy('name')->get();
        $tradeAgreementsHome = TradeAgreement::with('bloc')->whereIn('id', $ids['tradeAgreementIds'] ?? [])->latest()->get();

        // Resolve hero videos for all counties through the strict mapping rule:
        // a county only renders footage whose R2 key carries its own slug. A
        // shared stand-in is withheld (null) so the tile shows its own fallback
        // instead of another place's film.
        $countyHeroVideos = [];
        $countyHeroStates = [];
        foreach ($counties as $c) {
            $hero = \App\Support\MediaMapping::countyHero($c);
            $countyHeroVideos[$c->slug] = $hero['video'];
            $countyHeroStates[$c->slug] = $hero['state'];
        }

        // Resolve the pipeline-managed hero video (fall back to hardcoded path).
        $heroAsset = MediaAsset::resolveSlot('landing_page', 1, 'hero_video');
        $heroVideo = $heroAsset?->bestVideoUrl();
        $heroWebm = $heroAsset?->webmUrl();

        // The poster derivative row can outlive its R2 object — the seedance
        // poster was purged from the bucket, so publishing that URL served a
        // 404 and the hero reported itself unavailable. Verify the object first.
        $heroPoster = null;
        try {
            $posterDerivative = $heroAsset?->derivatives->firstWhere('kind', 'poster');
            if ($posterDerivative && \Illuminate\Support\Facades\Storage::disk('r2')->exists($posterDerivative->path)) {
                $heroPoster = $heroAsset->posterUrl();
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('hero poster check: ' . $e->getMessage());
        }

        // A real still to sit under the film, so the hero keeps a frame even
        // where the film cannot be decoded. Prefer a published poster, else the
        // first ready image in the store.
        $heroStill = $heroPoster;
        if (! $heroStill) {
            try {
                $stillAsset = MediaAsset::query()
                    ->where('status', 'ready')->where('kind', 'image')
                    ->whereNotNull('path')->orderBy('id')->first();
                if ($stillAsset) {
                    $heroStill = $stillAsset->url();
                }
            } catch (\Throwable $e) {
            }
        }

        // Live streams for the "Live Now" carousel
        $liveStreams = LiveStream::with('exhibition')
            ->where('status', 'live')
            ->latest()
            ->take(6)
            ->get();

        // ── The Archive strip ────────────────────────────────────────────
        // "No image reaches this page unfiltered": the strip renders only
        // media that passed the pipeline (status=ready) and is attached to a
        // named owner, read live from the store — never a hardcoded list.
        $archive = collect();
        try {
            // Read a pool, then interleave by owner so the strip shows a spread
            // of the store (counties, institutions, sectors, KICC) instead of
            // the twenty-four newest rows, which all share one owner.
            $pool = MediaAsset::query()
                ->where('status', 'ready')
                ->whereNotNull('owner_type')
                ->orderByDesc('id')
                ->take(160)
                ->get();

            $buckets = $pool->groupBy('owner_type')->map(fn ($g) => $g->values())->values();
            $picked = collect();
            for ($i = 0; $picked->count() < 24 && $i < 60; $i++) {
                $added = false;
                foreach ($buckets as $bucket) {
                    if (isset($bucket[$i])) {
                        $picked->push($bucket[$i]);
                        $added = true;
                    }
                    if ($picked->count() >= 24) {
                        break 2;
                    }
                }
                if (! $added) {
                    break;
                }
            }

            $archive = $picked
                ->map(function (MediaAsset $a) {
                    $ownerName = 'KICC';
                    $ownerType = 'National';
                    try {
                        $ot = (string) $a->owner_type;
                        if ($ot !== '' && class_exists($ot)) {
                            $ownerType = class_basename($ot);
                            $owner = $a->owner;
                            if ($owner && ! empty($owner->name)) {
                                $ownerName = $owner->name;
                            }
                        } else {
                            $ownerType = ucfirst($ot !== '' ? $ot : 'National');
                        }
                    } catch (\Throwable $e) {
                        // owner row missing — keep the slot, label it honestly
                    }

                    $isVideo = $a->kind === 'video';

                    return [
                        'id' => $a->id,
                        'kind' => $isVideo ? 'Film' : 'Still',
                        'slot' => $a->slot ?: 'media',
                        'owner' => $ownerName,
                        'owner_type' => $ownerType,
                        'video' => $isVideo ? ($a->mp4Url() ?: $a->url()) : null,
                        'image' => $isVideo ? $a->posterUrl() : $a->url(),
                        'mime' => $a->mime,
                    ];
                })
                ->filter(fn ($row) => $row['video'] || $row['image'])
                ->values();
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('home archive strip: ' . $e->getMessage());
        }

        // Counters for the spec's chapter figures, read from the store.
        $hallsCount = $venues->count();
        $makersCount = $products->count();
        $exhibitionCount = Exhibition::where('status', 'published')->count();
        $archiveCount = $archive->count();
        $institutionCount = 0;
        try {
            $institutionCount = \Illuminate\Support\Facades\DB::table('county_institutions')->count();
        } catch (\Throwable $e) {
        }

        // "Step Inside" targets: /room3d and /exhibition-3d/* are the working
        // viewers on production (the /3d/* Inertia routes need a Vite build).
        $rooms3d = collect();
        try {
            $rooms3d = \App\Models\Room3d::whereIn('status', ['ready', 'processed'])
                ->orderByDesc('created_at')->take(3)->get();
        } catch (\Throwable $e) {
        }

        return view('home', compact(
            'featuredExhibitions', 'counties', 'products', 'venues',
            'tradeAgreementsHome', 'heroVideo', 'heroWebm', 'heroPoster', 'heroStill',
            'countyHeroVideos', 'countyHeroStates', 'liveStreams',
            'archive', 'hallsCount', 'makersCount', 'exhibitionCount',
            'archiveCount', 'institutionCount', 'rooms3d',
        ));
    }
}