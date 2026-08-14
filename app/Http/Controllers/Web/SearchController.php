<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\County;
use App\Models\Marketplace\ProductCategory;
use App\Models\Marketplace\Product;
use App\Models\CountyTourismAttraction;
use App\Models\CountyHotel;
use App\Models\TradeAgreement;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function index(Request $request)
    {
        $q = $request->get('q');
        $type = $request->get('type', 'all'); // all, products, attractions, hotels, accommodations, guides, events
        $county = $request->get('county');
        $category = $request->get('category');
        $minPrice = $request->get('min_price');
        $maxPrice = $request->get('max_price');
        $rating = $request->get('rating');
        $sort = $request->get('sort', 'relevance');

        $results = collect();
        $counties = County::orderBy('name')->get();
        $categories = ProductCategory::active()->get();
        $total = 0;

        $searchTerm = $q ?: '';

        if ($type === 'all' || $type === 'products') {
            $query = Product::with('county', 'category')->active();
            if ($searchTerm) $query->where(function ($w) use ($searchTerm) {
                $w->where('name', 'like', "%{$searchTerm}%")
                  ->orWhere('short_description', 'like', "%{$searchTerm}%")
                  ->orWhere('description', 'like', "%{$searchTerm}%");
            });
            if ($county) $query->whereHas('county', fn ($q) => $q->where('slug', $county));
            if ($category) $query->whereHas('category', fn ($q) => $q->where('slug', $category));
            if ($minPrice) $query->whereRelation('variants', 'price', '>=', $minPrice);
            if ($maxPrice) $query->whereRelation('variants', 'price', '<=', $maxPrice);
            if ($sort === 'price_asc') $query->orderBy('id', 'asc'); // simplified
            if ($sort === 'price_desc') $query->orderBy('id', 'desc');
            $products = $query->take(20)->get()->map(fn ($p) => [
                'type' => 'product', 'id' => $p->id, 'name' => $p->name,
                'description' => $p->short_description ?? $p->description,
                'image' => $p->image_url, 'price' => $p->price,
                'url' => route('marketplace.show', $p->slug),
                'county' => $p->county?->name, 'rating' => null,
            ]);
            $results = $results->concat($products);
            $total += $products->count();
        }

        if ($type === 'all' || $type === 'attractions') {
            $query = CountyTourismAttraction::with('county')->where('is_published', true);
            if ($searchTerm) $query->where(function ($w) use ($searchTerm) {
                $w->where('name', 'like', "%{$searchTerm}%")
                  ->orWhere('description', 'like', "%{$searchTerm}%")
                  ->orWhere('category', 'like', "%{$searchTerm}%");
            });
            if ($county) $query->whereHas('county', fn ($q) => $q->where('slug', $county));
            if ($minPrice) $query->where('entry_fee', '>=', $minPrice);
            $attrs = $query->take(20)->get()->map(fn ($a) => [
                'type' => 'attraction', 'id' => $a->id, 'name' => $a->name,
                'description' => $a->description, 'image' => $a->image_url,
                'price' => $a->entry_fee, 'url' => route('attractions.show', $a->id),
                'county' => $a->county?->name, 'rating' => null,
            ]);
            $results = $results->concat($attrs);
            $total += $attrs->count();
        }

        if ($type === 'all' || $type === 'accommodations') {
            $query = CountyHotel::with('county')->where('is_published', true);
            if ($searchTerm) $query->where('name', 'like', "%{$searchTerm}%");
            if ($county) $query->whereHas('county', fn ($q) => $q->where('slug', $county));
            $hotels = $query->take(20)->get()->map(fn ($h) => [
                'type' => 'accommodation', 'id' => $h->id, 'name' => $h->name,
                'description' => $h->description, 'image' => $h->image_url,
                'price' => $h->price_range_min, 'url' => '#',
                'county' => $h->county?->name, 'rating' => $h->star_rating,
            ]);
            $results = $results->concat($hotels);
            $total += $hotels->count();
        }

        if ($type === 'all' || $type === 'agreements') {
            $agreements = TradeAgreement::with('bloc')->active();
            if ($searchTerm) $agreements->where(function ($w) use ($searchTerm) {
                $w->where('title', 'like', "%{$searchTerm}%")
                  ->orWhere('summary', 'like', "%{$searchTerm}%");
            });
            $agr = $agreements->take(10)->get()->map(fn ($a) => [
                'type' => 'agreement', 'id' => $a->id, 'name' => $a->title,
                'description' => $a->summary, 'image' => null,
                'price' => null, 'url' => route('trade.agreements.show', $a->slug),
                'county' => $a->bloc?->name,
                'rating' => null,
            ]);
            $results = $results->concat($agr);
            $total += $agr->count();
        }

        $results = $results->sortByDesc('price');

        return view('search.index', compact(
            'results', 'q', 'type', 'county', 'category',
            'minPrice', 'maxPrice', 'rating', 'sort',
            'counties', 'categories', 'total', 'searchTerm'
        ));
    }
}