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
    public function selector(\Illuminate\Http\Request $request)
    {
        return app(UnifiedAdminController::class)->index($request);
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
