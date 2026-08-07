<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class AdminPortalController extends Controller
{
    public function selector()
    {
        $user = Auth::user();
        if (!$user) abort(403);

        if ($user->hasRole('kicc_admin')) {
            return redirect()->route('kicc.admin');
        }
        if ($user->hasRole('national_admin')) {
            return redirect()->route('national.admin');
        }
        if ($user->hasRole('county_admin')) {
            if ($user->county_id) {
                $county = \App\Models\County::find($user->county_id);
                if ($county) {
                    return redirect()->route('county.admin.pro', $county->slug);
                }
            }
            return redirect()->route('dashboard.county');
        }

        abort(403, 'You do not have admin access.');
    }

    public function national()
    {
        $user = Auth::user();
        if (!$user?->hasAnyRole(['national_admin', 'kicc_admin'])) {
            abort(403, 'National Government admin access required.');
        }
        return view('admin.national');
    }

    public function county()
    {
        $user = Auth::user();
        if (!$user?->hasAnyRole(['county_admin', 'kicc_admin'])) {
            abort(403, 'County admin access required.');
        }
        return redirect()->route('dashboard.county');
    }
}