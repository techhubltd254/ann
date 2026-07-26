<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class AdminPortalController extends Controller
{
    public function selector()
    {
        return view('admin.portal');
    }

    public function index()
    {
        // KICC Admin — redirect to Filament
        return redirect('/admin');
    }

    public function national()
    {
        // National Government Admin — custom dashboard with ministries/agencies
        return view('admin.national');
    }

    public function county()
    {
        // County Admin — scoped to own county
        return redirect()->route('dashboard.county');
    }
}
