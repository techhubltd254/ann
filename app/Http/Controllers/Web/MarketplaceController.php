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
use Illuminate\Http\Request;

class MarketplaceController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::with(['county', 'category', 'variants', 'images'])
            ->active()
            ->latest();

        if ($cat = $request->get('category')) {
            $query->whereHas('category', fn ($q) => $q->where('slug', $cat));
        }
        if ($county = $request->get('county')) {
            $query->whereHas('county', fn ($q) => $q->where('slug', $county));
        }
        if ($search = $request->get('q')) {
            $query->where(fn ($q) => $q->where('name', 'like', "%{$search}%")
                ->orWhere('short_description', 'like', "%{$search}%"));
        }

        $tradeAgreements = TradeAgreement::with('bloc')->featured()->active()->latest()->take(3)->get();

        // Active flash sale for badge display
        $activeFlashSale = FlashSale::where('is_active', true)
            ->where('starts_at', '<=', now())->where('ends_at', '>=', now())->first();

        return view('marketplace.index', [
            'products' => $query->paginate(24)->withQueryString(),
            'categories' => ProductCategory::active()->withCount(['products' => fn ($q) => $q->active()])->get(),
            'counties' => County::orderBy('name')->get(['id', 'name', 'slug']),
            'activeCategory' => $cat ?? null,
            'activeCounty' => $county ?? null,
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

        $questions = ProductQuestion::where('product_id', $product->id)
            ->whereNotNull('answer')->with('user')->latest()->get();

        // Check if product is in an active flash sale
        $flashSaleProduct = null;
        $activeSale = FlashSale::where('is_active', true)
            ->where('starts_at', '<=', now())->where('ends_at', '>=', now())
            ->whereHas('products', fn($q) => $q->where('product_id', $product->id))
            ->first();
        if ($activeSale) {
            $flashSaleProduct = $activeSale->products()->where('product_id', $product->id)->first();
        }

        return view('marketplace.show', compact('product', 'related', 'tradeAgreements', 'questions', 'flashSaleProduct'));
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
