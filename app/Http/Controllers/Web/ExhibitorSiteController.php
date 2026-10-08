<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Marketplace\Product;
use App\Models\User;
use Illuminate\Support\Str;

/**
 * Public exhibitor storefront — every private exhibitor gets a website.
 * Also matches KICC admin / superadmin accounts so kicc-administrator works.
 */
class ExhibitorSiteController extends Controller
{
    public function show(string $slug)
    {
        $exhibitor = User::with('county')
            ->whereIn('account_type', ['exhibitor', 'superadmin', 'admin', 'kicc_admin'])
            ->get()
            ->first(fn ($u) => Str::slug($u->name) === $slug);
        abort_unless($exhibitor, 404);

        $products = Product::with(['category', 'variants', 'images'])
            ->where('user_id', $exhibitor->id)->active()->latest()->get();

        $totalStock = $products->sum(fn ($p) => $p->variants->sum('stock'));

        return view('experience.pages.exhibitor.site', compact('exhibitor', 'products', 'totalStock'));
    }
}
