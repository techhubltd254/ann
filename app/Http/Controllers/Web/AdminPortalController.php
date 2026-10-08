<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Ministry;
use App\Models\Agency;
use App\Models\Sector;
use App\Models\County;
use Illuminate\Support\Facades\Auth;

class AdminPortalController extends Controller
{
    public function selector()
    {
        $user = Auth::user();
        // Any of the four admin tiers may see the portal selector
        if (!$user?->hasAnyRole(['kicc_admin', 'national_admin', 'county_admin', 'exhibitor'])) {
            abort(403, 'You do not have admin access.');
        }

        // KICC admins go to the KICC admin dashboard with the portals tab open
        if ($user->hasRole('kicc_admin')) {
            return redirect()->route('kicc.admin', ['tab' => 'portals']);
        }

        // County admins go directly to their county's professional admin page
        if ($user->hasRole('county_admin') && $user->county_id) {
            $county = \App\Models\County::find($user->county_id);
            if ($county) {
                return redirect()->route('county.admin.pro', $county->slug);
            }
        }

        // Exhibitors go to their portal
        if ($user->hasRole('exhibitor')) {
            return redirect()->route('exhibitor.admin');
        }

        // National admins go to their portal
        if ($user->hasRole('national_admin')) {
            return redirect()->route('national.admin');
        }

        return redirect('/');
    }

    public function national()
    {
        // National Government Admin or KICC Admin only
        if (!Auth::user()?->hasAnyRole(['national_admin', 'kicc_admin'])) {
            abort(403, 'National Government admin access required.');
        }
        $ministries = Ministry::with('agencies')->get();
        $agencies = Agency::with('ministry')->paginate(50);
        $sectors = Sector::withCount('counties')->orderBy('name')->get();
        $counties = County::all();
        return view('experience.pages.admin.national', compact('ministries', 'agencies', 'sectors', 'counties'));
    }

    public function county()
    {
        // County Admin or KICC Admin only — send directly to the pro admin dashboard
        if (!Auth::user()?->hasAnyRole(['county_admin', 'kicc_admin'])) {
            abort(403, 'County admin access required.');
        }
        $user = Auth::user();
        if ($user->county_id) {
            $county = County::find($user->county_id);
            if ($county) {
                return redirect()->route('county.admin.pro', $county->slug);
            }
        }
        return redirect()->route('dashboard.county');
    }
}
