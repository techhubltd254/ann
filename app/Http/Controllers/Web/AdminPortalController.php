<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class AdminPortalController extends Controller
{
    public function selector()
    {
        // Only allow users with admin roles to see the portal
        if (!Auth::user()?->hasAnyRole(['kicc_admin', 'national_admin', 'county_admin'])) {
            abort(403, 'You do not have admin access.');
        }
        return view('admin.portal');
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
