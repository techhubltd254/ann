<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\County;
use App\Models\Marketplace\Product;
use App\Models\Marketplace\ProductCategory;
use App\Models\Ecommerce\RecentlyViewed;
use App\Models\Ecommerce\ProductQuestion;
use App\Models\Ecommerce\FlashSale;
use App\Models\TradeAgreement;
use App\Services\CorrelationService;
use App\Services\PipelineRouter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MarketplaceController extends Controller
{
    public function index(Request $request)
    {
        $cat = $request->get('category');
        $countySlug = $request->get('county');
        $search = $request->get('q');
        $page = (int) $request->get('page', 1);

        $priority = app(\App\Services\DisplayPriorityService::class);
        $router = app(PipelineRouter::class);

        // ── Google SEO: capture search intent from session ──
        $searchIntent = session('search_intent', []);
        $googleQuery = $searchIntent['query'] ?? '';
        $intentPipeline = $googleQuery ? $router->fromSearchQuery($googleQuery) : null;

        // Use Google search query as the marketplace search
        if ($googleQuery && empty($search)) {
            $search = $googleQuery;
        }

        // Cache the query's product IDs + sidebars; hydrate models fresh (avoids Redis serialization issues)
        $cacheKey = "marketplace_data_{$cat}_{$countySlug}_{$search}_{$page}_" . cache_buster();

        $data = \Illuminate\Support\Facades\Cache::remember($cacheKey, config('kicc.cache_ttl.public', 21600), function () use ($cat, $countySlug, $search, $priority, $googleQuery, $googleEngine) {
            // Display only real-data counties (auto-detected by product count >10, not hardcoded)
            $countyIds = $priority->displayCountyIds();

            // Filtered query (county/category/search still respected)
            $query = Product::active()->whereIn('county_id', $countyIds)->latest();

            if ($cat) {
                $query->whereHas('category', fn ($q) => $q->where('slug', $cat));
            }
            if ($countySlug) {
                $query->whereHas('county', fn ($q) => $q->where('slug', $countySlug));
            }

            // Google-personalized search: boost products matching the search query
            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('short_description', 'like', "%{$search}%")
                      ->orWhere('tags', 'like', "%{$search}%");
                });
                // Order by relevance: title match > description match > tag match
                $query->orderByRaw(
                    "CASE WHEN name LIKE ? THEN 0 WHEN short_description LIKE ? THEN 1 WHEN tags LIKE ? THEN 2 ELSE 3 END",
                    ["%{$search}%", "%{$search}%", "%{$search}%"]
                );
            } else {
                // When no search query, use priority-based display
                $query->orderBy('is_featured', 'desc')->latest();
            }

            $ids = $query->pluck('id')->all();

            // Sector-first + review-score ordering when no filters (priority display)
            if (!$cat && !$countySlug && !$search) {
                $ids = $priority->marketplaceProductIds();
            }

            // Cache only primitives — Eloquent models are hydrated after cache read
            return [
                'ids' => $ids,
                'categories' => ProductCategory::active()->withCount(['products' => fn ($q) => $q->active()->whereIn('county_id', $countyIds)])
                    ->get(['id', 'name', 'slug', 'products_count'])->toArray(),
                // Only real-data counties appear in the filter (auto-detected, not hardcoded)
                'counties' => County::whereIn('id', $countyIds)->orderBy('name')->get(['id', 'name', 'slug'])->toArray(),
            ];
        });

        $ids = $data['ids'] ?? [];
        $total = count($ids);
        $perPage = 24;
        $pageIds = array_slice($ids, ($page - 1) * $perPage, $perPage);
        $products = $pageIds
            ? Product::with(['county', 'category', 'variants', 'images'])
                ->whereIn('id', $pageIds)
                ->orderByRaw('FIELD(id, ' . implode(',', array_map('intval', $pageIds)) . ')')
                ->get()
            : collect();

        $categories = collect($data['categories'] ?? [])->map(fn ($c) => (object) $c);
        $countiesList = collect($data['counties'] ?? [])->map(fn ($c) => (object) $c);

        $tradeAgreements = TradeAgreement::with('bloc')->featured()->active()->latest()->take(3)->get();

        $activeFlashSale = FlashSale::where('is_active', true)
            ->where('starts_at', '<=', now())->where('ends_at', '>=', now())->first();

        return view('marketplace.index', [
            'products' => new \Illuminate\Pagination\LengthAwarePaginator(
                $products, $total, $perPage, $page, ['path' => \Illuminate\Support\Facades\Request::url(), 'query' => $request->query()]
            ),
            'categories' => $categories,
            'counties' => $countiesList,
            'activeCategory' => $cat ?? null,
            'activeCounty' => $countySlug ?? null,
            'q' => $search ?? '',
            'tradeAgreements' => $tradeAgreements,
            'activeFlashSale' => $activeFlashSale,
        ]);
    }

    public function show(string $slug)
    {
        $product = Product::with(['county', 'category', 'variants' => fn ($q) => $q->where('is_active', true)->orderBy('price'), 'images'])
            ->active()
            ->where('slug', $slug)
            ->firstOrFail();

        // Track recently viewed
        try {
            RecentlyViewed::create([
                'user_id' => auth()->id(),
                'session_id' => session()->getId(),
                'viewable_type' => Product::class,
                'viewable_id' => $product->id,
                'viewed_at' => now(),
            ]);
        } catch (\Throwable $e) {}

        // ── Pipeline attribution: link search intent to pipeline ──
        try {
            $searchIntent = session('search_intent', []);
            if (!empty($searchIntent['query'])) {
                $router = app(PipelineRouter::class);
                $pipeline = $router->forProduct($product);
                $router->attributeSearchToPipeline($searchIntent['query'], $pipeline);
            }
        } catch (\Throwable $e) {}

        // Trip correlation: related places to visit, places to stay, transport
        $tripRecommendations = [];
        try {
            $tripRecommendations = app(CorrelationService::class)->forProduct($product);
        } catch (\Throwable $e) {
            Log::warning('trip recommendation: ' . $e->getMessage());
        }
        $related = Product::with(['variants', 'county'])
            ->active()
            ->where('id', '!=', $product->id)
            ->where(fn ($q) => $q->where('category_id', $product->category_id)->orWhere('county_id', $product->county_id))
            ->inRandomOrder()
            ->limit(4)
            ->get();

        $tradeAgreements = TradeAgreement::with('bloc')->active()
            ->whereHas('categories', fn ($q) => $q->where('product_categories.id', $product->category_id))
            ->orWhere(fn ($q) => $q->whereNull('trading_bloc_id')->where('agreement_type', 'bilateral'))
            ->latest()->take(3)->get();

        $questions = [];
        try {
            $questions = ProductQuestion::where('product_id', $product->id)
                ->whereNotNull('answer')->with('user')->latest()->get();
        } catch (\Throwable $e) {
            Log::warning('product questions unavailable: ' . $e->getMessage());
        }

        // Reviews + blended review score
        $productReviews = \App\Models\ProductReview::where('product_id', $product->id)
            ->where('is_approved', true)->with('user')->latest()->get();
        $reviewScore = app(\App\Services\DisplayPriorityService::class)->scoreFor($product->id);
        $reviewSeed = \App\Models\ReviewSeed::where('owner_type', Product::class)->where('owner_id', $product->id)->first();

        // Check if product is in an active flash sale
        $flashSaleProduct = null;
        $activeSale = FlashSale::where('is_active', true)
            ->where('starts_at', '<=', now())->where('ends_at', '>=', now())
            ->whereHas('products', fn($q) => $q->where('product_id', $product->id))
            ->first();
        if ($activeSale) {
            $flashSaleProduct = $activeSale->products()->where('product_id', $product->id)->first();
        }

        return view('marketplace.show', compact('product', 'related', 'tripRecommendations', 'tradeAgreements', 'questions', 'flashSaleProduct', 'productReviews', 'reviewScore', 'reviewSeed'));
    }

    public function compare(Request $request)
    {
        $ids = $request->get('ids', []);
        if (!is_array($ids)) $ids = explode(',', $ids);
        $products = Product::with(['variants', 'images', 'county', 'category'])
            ->whereIn('id', array_slice($ids, 0, 4))->active()->get();
        return view('marketplace.compare', compact('products'));
    }
}
