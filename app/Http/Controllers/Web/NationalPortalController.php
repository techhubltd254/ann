<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Agency;
use App\Models\County;
use App\Models\EscrowTransaction;
use App\Models\Marketplace\Order;
use App\Models\Marketplace\Product;
use App\Models\Ministry;
use App\Models\Sector;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * National Government Exhibitor Portal.
 *
 * The national government exhibits through its ministries & agencies.
 * This portal manages them and shows the aggregate national trade picture.
 * Each ministry gets a public website at /national/{slug}.
 */
class NationalPortalController extends Controller
{
    protected function authorizeNational(): void
    {
        if (!Auth::user()?->hasAnyRole(['national_admin', 'kicc_admin'])) {
            abort(403, 'National Government admin access required.');
        }
    }

    public function index(Request $request)
    {
        $this->authorizeNational();
        $tab = $request->get('tab', 'overview');

        $ministries = Ministry::with('agencies')->orderBy('name')->get();
        $agencies = Agency::with('ministry')->orderBy('name')->get();

        $stats = [
            'ministries' => $ministries->count(),
            'agencies' => $agencies->count(),
            'sectors' => Sector::count(),
            'counties' => County::count(),
            'exhibitors' => User::where('account_type', 'exhibitor')->count(),
            'products' => Product::active()->count(),
            'orders' => Order::count(),
            'tradeVolume' => EscrowTransaction::sum('amount'),
        ];

        $navItems = [
            ['label' => 'Overview', 'tab' => 'overview', 'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6'],
            ['label' => 'Ministries', 'tab' => 'ministries', 'icon' => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4'],
            ['label' => 'Agencies', 'tab' => 'agencies', 'icon' => 'M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z'],
            ['label' => 'National Trade', 'tab' => 'trade', 'icon' => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z'],
        ];

        return view('dashboards.national-exhibitor', [
            'tab' => $tab, 'navItems' => $navItems, 'stats' => $stats,
            'ministries' => $ministries, 'agencies' => $agencies,
            'user' => Auth::user(),
        ]);
    }
}
