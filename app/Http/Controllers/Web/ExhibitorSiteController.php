<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Marketplace\Product;
use App\Models\User;
use Illuminate\Support\Str;

/**
 * Public exhibitor storefront — every private exhibitor gets a website.
 */
class ExhibitorSiteController extends Controller
{
    public function show(string $slug)
    {
        $exhibitor = User::with('county')->where('account_type', 'exhibitor')->get()
            ->first(fn ($u) => Str::slug($u->name) === $slug);
        abort_unless($exhibitor, 404);

        $products = Product::with(['category', 'variants', 'images'])
            ->where('user_id', $exhibitor->id)->active()->latest()->get();

        $totalStock = $products->sum(fn ($p) => $p->variants->sum('stock'));

        return view('exhibitor.site', compact('exhibitor', 'products', 'totalStock'));
    }
}
