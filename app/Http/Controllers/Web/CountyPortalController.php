<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\County;
use App\Models\EscrowTransaction;
use App\Models\Marketplace\Order;
use App\Models\Marketplace\Product;
use App\Models\SectorEntity;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * County Exhibitor Portal — a county is a website by itself.
 *
 * The county trade board manages the county's entire exhibition presence:
 * its products (trade board + private exhibitors in the county), orders,
 * escrow revenue, and its public county website.
 */
class CountyPortalController extends Controller
{
    protected function authorizeCounty(): void
    {
        if (!Auth::user()?->hasAnyRole(['county_admin', 'kicc_admin'])) {
            abort(403, 'County admin access required.');
        }
    }

    public function index(Request $request)
    {
        $this->authorizeCounty();
        $user = Auth::user();
        // KICC admins see all counties; county admins go to their own
        if ($user->hasRole('kicc_admin')) {
            return redirect()->route('kicc.admin', ['tab' => 'counties']);
        }
        $county = County::findOrFail($user->county_id);
        return redirect()->route('county.admin.pro', $county->slug);
    }

    public function exhibitor(Request $request)
    {
        $this->authorizeCounty();
        $user = Auth::user();
        $county = County::withCount('sectors')->findOrFail($user->county_id);
        $tab = $request->get('tab', 'overview');

        $products = Product::where('county_id', $county->id)->with('category', 'variants', 'seller')->paginate(50);
        $exhibitors = User::where('account_type', 'exhibitor')->where('county_id', $county->id)
            ->withCount(['products' => fn($q) => $q->where('county_id', $county->id)])
            ->paginate(50);
        $orders = Order::whereHas('items', fn($q) => $q->where('county_id', $county->id))
            ->with('items')->latest()->take(50)->get();
        $escrows = EscrowTransaction::where('county_id', $county->id)
            ->with('seller', 'buyer')->latest()->take(50)->get();

        $stats = [
            'products' => $products->count(),
            'exhibitors' => $exhibitors->count(),
            'orders' => $orders->count(),
            'revenue' => $orders->sum(fn($o) => $o->items->where('county_id', $county->id)->sum('total')),
            'escrowHeld' => $escrows->where('status', 'held')->sum('amount'),
            'sectorEntities' => SectorEntity::where('county_id', $county->id)->count(),
        ];

        $navItems = [
            ['label' => 'Overview', 'tab' => 'overview', 'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6'],
            ['label' => 'Products', 'tab' => 'products', 'icon' => 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4'],
            ['label' => 'Exhibitors', 'tab' => 'exhibitors', 'icon' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857'],
            ['label' => 'Orders', 'tab' => 'orders', 'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2'],
            ['label' => 'Escrow', 'tab' => 'escrow', 'icon' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1'],
            ['label' => 'Website', 'tab' => 'website', 'icon' => 'M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14'],
        ];

        $countySiteUrl = route('counties.show', $county->slug);

        return view('dashboards.county-exhibitor', compact(
            'county', 'tab', 'navItems', 'stats', 'products', 'exhibitors', 'orders', 'escrows', 'countySiteUrl'
        ));
    }
}
