<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\County;
use App\Models\Marketplace\Product;
use App\Models\Marketplace\ProductCategory;
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

        // Trade agreements relevant to this filter (by category or all featured)
        $tradeAgreements = \App\Models\TradeAgreement::with('bloc')->featured()->active()->latest()->take(3)->get();

        return view('marketplace.index', [
            'products' => $query->paginate(24)->withQueryString(),
            'categories' => ProductCategory::active()->withCount(['products' => fn ($q) => $q->active()])->get(),
            'counties' => County::orderBy('name')->get(['id', 'name', 'slug']),
            'activeCategory' => $cat ?? null,
            'activeCounty' => $county ?? null,
            'q' => $search ?? '',
            'tradeAgreements' => $tradeAgreements,
        ]);
    }

    public function show(string $slug)
    {
        $product = Product::with(['county', 'category', 'variants' => fn ($q) => $q->where('is_active', true)->orderBy('price'), 'images'])
            ->active()
            ->where('slug', $slug)
            ->firstOrFail();

        $related = Product::with(['variants', 'county'])
            ->active()
            ->where('id', '!=', $product->id)
            ->where(fn ($q) => $q->where('category_id', $product->category_id)->orWhere('county_id', $product->county_id))
            ->inRandomOrder()
            ->limit(4)
            ->get();

        // Trade agreements covering this product's category
        $tradeAgreements = \App\Models\TradeAgreement::with('bloc')->active()
            ->whereHas('categories', fn ($q) => $q->where('categories.id', $product->category_id))
            ->orWhere(fn ($q) => $q->whereNull('trading_bloc_id')->where('agreement_type', 'bilateral'))
            ->latest()
            ->take(3)
            ->get();

        return view('marketplace.show', compact('product', 'related', 'tradeAgreements'));
    }
}
