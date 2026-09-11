<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Agency;
use App\Models\Ministry;
use App\Models\MediaAsset;
use Illuminate\Support\Facades\Cache;

class NationalGovernmentController extends Controller
{
    const CACHE_TTL = 3600; // 1 hour

    public function index()
    {
        $stats = Cache::remember('ng_stats', self::CACHE_TTL, fn() => [
            'ministries' => Ministry::where('is_active', true)->count(),
            'agencies' => Agency::where('is_active', true)->count(),
        ]);

        $ministries = Cache::remember('ng_ministries', self::CACHE_TTL, fn() =>
            Ministry::with('agencies')->where('is_active', true)->orderBy('name')->get()
        );

        $agencies = Cache::remember('ng_agencies', self::CACHE_TTL, fn() =>
            Agency::with('ministry')->where('is_active', true)->orderBy('name')->get()
        );

        $heroVid = Cache::remember('ng_hero', 900, function () {
            try {
                $asset = MediaAsset::resolveSlot(\App\Models\County::class, 0, 'national_hero_video');
                return $asset?->mp4Url() ?? null;
            } catch (\Throwable) {
                return null;
            }
        });

        $heroPoster = '';
        try { $heroPoster = media('kicc/national-hero.jpeg'); } catch (\Throwable) {}

        return view('national-government.index', compact('ministries', 'agencies', 'stats', 'heroVid', 'heroPoster'));
    }
}