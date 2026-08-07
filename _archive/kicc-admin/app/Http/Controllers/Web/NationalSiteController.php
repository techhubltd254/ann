<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Ministry;

/**
 * Public national exhibitor websites — one per ministry: /national/{slug}
 */
class NationalSiteController extends Controller
{
    public function show(string $slug)
    {
        $ministry = Ministry::with('agencies')->where('slug', $slug)->where('is_active', true)->firstOrFail();
        return view('national.site', compact('ministry'));
    }
}
