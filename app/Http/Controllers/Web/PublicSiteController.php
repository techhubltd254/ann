<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\County;
use App\Models\Exhibition;
use App\Models\Marketplace\Product;
use App\Models\Venue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * Public site controllers — previously declared in the private 'ann' repo
 * under web.php but never reachable from `kicc-admin/routes/web.php`,
 * which is why every public page returned 500. Wired here so the admin
 * app can serve (or proxy to) the same public surface.
 */
class PublicSiteController extends Controller
{
    public function home()
    {
        $stats = Cache::remember('homepage.stats', 900, function () {
            return [
                'counties'    => County::count(),
                'products'    => Product::active()->count(),
                'exhibitions' => Exhibition::where('status', 'published')->count(),
            ];
        });
        $featuredExhibitions = Exhibition::where('status', 'published')->latest()->take(6)->get();
        $featuredCounties     = County::orderBy('name')->take(8)->get();
        return view('public.home', compact('stats', 'featuredExhibitions', 'featuredCounties'));
    }

    public function counties()
    {
        $counties = County::orderBy('name')->paginate(24);
        return view('public.counties', compact('counties'));
    }

    public function county($slug)
    {
        $county = County::where('slug', $slug)->firstOrFail();
        $products = Product::active()->where('county_id', $county->id)->take(8)->get();
        return view('public.county', compact('county', 'products'));
    }

    public function marketplace(Request $request)
    {
        $q        = Product::active()->with('county', 'category')->latest();
        if ($request->filled('county')) {
            $county = County::where('slug', $request->county)->first();
            if ($county) $q->where('county_id', $county->id);
        }
        if ($request->filled('q')) {
            $term = '%' . $request->q . '%';
            $q->where(function ($x) use ($term) {
                $x->where('name', 'like', $term)->orWhere('description', 'like', $term);
            });
        }
        $products = $q->paginate(18);
        return view('public.marketplace', compact('products'));
    }

    public function exhibitions()
    {
        $exhibitions = Exhibition::whereIn('status', ['published', 'upcoming'])->latest()->paginate(18);
        return view('public.exhibitions', compact('exhibitions'));
    }

    public function venues()
    {
        $venues = Venue::where('is_active', true)->orderBy('name')->paginate(18);
        return view('public.venues', compact('venues'));
    }

    public function tradeAgreements()
    {
        return view('public.trade-agreements');
    }

    public function siteMap()
    {
        $urls = [
            route('public.home'),
            route('public.counties'),
            route('public.marketplace'),
            route('public.exhibitions'),
            route('public.venues'),
            route('public.trade-agreements'),
        ];
        $xml = '<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
        foreach ($urls as $u) {
            $xml .= '<url><loc>' . htmlspecialchars($u) . '</loc><changefreq>daily</changefreq></url>';
        }
        foreach (County::orderBy('name')->get() as $c) {
            $xml .= '<url><loc>' . htmlspecialchars(route('public.county', ['slug' => $c->slug])) . '</loc></url>';
        }
        $xml .= '</urlset>';
        return response($xml, 200, ['Content-Type' => 'application/xml']);
    }
}
