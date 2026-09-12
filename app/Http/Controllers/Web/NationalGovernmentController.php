<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Agency;
use App\Models\Ministry;
use App\Models\MediaAsset;
use Illuminate\Support\Facades\Cache;

class NationalGovernmentController extends Controller
{
    public function index()
    {
        $stats = Cache::remember('ng_stats', 3600, fn() => [
            'ministries' => Ministry::where('is_active', true)->count(),
            'agencies' => Agency::where('is_active', true)->count(),
        ]);

        $ministries = Cache::remember('ng_ministries', 3600, function () {
            return Ministry::with('agencies')->where('is_active', true)->orderBy('name')->get()
                ->map(fn($m) => [
                    'id' => $m->id, 'slug' => $m->slug, 'name' => $m->name,
                    'code' => $m->code, 'color' => $m->color, 'description' => $m->description,
                    'agencies' => $m->agencies->map(fn($a) => ['id' => $a->id, 'name' => $a->name])->toArray(),
                ])->toArray();
        });

        $agencies = Cache::remember('ng_agencies', 3600, function () {
            return Agency::with('ministry')->where('is_active', true)->orderBy('name')->get()
                ->map(fn($a) => ['id' => $a->id, 'name' => $a->name, 'ministry_name' => $a->ministry?->name ?? ''])
                ->toArray();
        });

        // Hero video
        $heroVid = Cache::remember('ng_hero', 900, function () {
            try {
                $asset = MediaAsset::resolveSlot(\App\Models\County::class, 0, 'national_hero_video');
                return $asset?->mp4Url();
            } catch (\Throwable) { return null; }
        });
        $heroPoster = '';
        try { $heroPoster = media('kicc/national-hero.jpeg'); } catch (\Throwable) {}

        // Tile hover loops + posters: fetch from existing county sector videos
        $tileHoverLoops = Cache::remember('ng_tile_hovers', 3600, function () {
            $assets = MediaAsset::where('kind', 'video')
                ->where('slot', 'like', 'sector_video_%')
                ->ready()
                ->with(['derivatives' => fn($q) => $q->whereIn('kind', ['hover_loop', 'poster'])])
                ->get();
            $hovers = $assets->map(fn($a) => $a->hoverLoopUrl())->filter()->values()->toArray();
            while (count($hovers) < 6 && count($hovers) > 0) $hovers = array_merge($hovers, $hovers);
            return $hovers;
        });

        $tilePosters = Cache::remember('ng_tile_posters', 3600, function () {
            $assets = MediaAsset::where('kind', 'video')
                ->where('slot', 'like', 'sector_video_%')
                ->ready()
                ->with(['derivatives' => fn($q) => $q->where('kind', 'poster')])
                ->get();
            $posters = $assets->map(fn($a) => $a->posterUrl())->filter()->values()->toArray();
            while (count($posters) < 6 && count($posters) > 0) $posters = array_merge($posters, $posters);
            return $posters;
        });

        return view('national-government.index', compact('ministries', 'agencies', 'stats', 'heroVid', 'heroPoster', 'tileHoverLoops', 'tilePosters'));
    }
}