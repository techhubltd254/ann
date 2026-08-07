<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
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

        // KICC admins go to the KICC admin dashboard with the portals tab open
        if ($user->hasRole('kicc_admin')) {
            return redirect()->route('kicc.admin', ['tab' => 'portals']);
        }

        return redirect('/');
    }

    public function national()
    {
        // National Government Admin or KICC Admin only
        if (!Auth::user()?->hasAnyRole(['national_admin', 'kicc_admin'])) {
            abort(403, 'National Government admin access required.');
        }
        return view('admin.national');
    }

    public function county()
    {
        // County Admin or KICC Admin only
        if (!Auth::user()?->hasAnyRole(['county_admin', 'kicc_admin'])) {
            abort(403, 'County admin access required.');
        }
        return redirect()->route('dashboard.county');
    }
}
