<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Ministry;

/**
 * Public national exhibitor websites — one per ministry: /national/{slug}
 */
class NationalSiteController extends Controller
{
    public function index()
    {
        $ministries = Ministry::withCount('agencies')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        // Active sectors organized into canonical major groups (cleaned 2026-08).
        $grouped = \App\Models\Sector::where('is_active', true)
            ->whereNotNull('sector_group')
            ->orderBy('name')
            ->get()
            ->groupBy('sector_group');
        $sectorGroups = collect(\App\Models\Sector::GROUP_META)
            ->filter(fn ($meta, $key) => ($grouped[$key] ?? collect())->isNotEmpty())
            ->map(fn ($meta, $key) => [
                'key' => $key,
                'name' => $meta[0],
                'icon' => $meta[1],
                'sectors' => $grouped[$key],
            ])
            ->values();

        return view('national.index', compact('ministries', 'sectorGroups'));
    }

    public function show(string $slug)
    {
        $ministry = Ministry::with('agencies')->where('slug', $slug)->where('is_active', true)->firstOrFail();
        return view('national.site', compact('ministry'));
    }
}
