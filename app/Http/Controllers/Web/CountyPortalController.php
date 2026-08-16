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
        $county = County::findOrFail($user->county_id);
        // Redirect to the professional admin page
        return redirect()->route('county.admin.pro', $county->slug);
    }
}
