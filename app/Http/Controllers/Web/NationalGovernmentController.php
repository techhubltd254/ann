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
        // Use simple cache keys — store JSON arrays, not Eloquent objects
        $stats = Cache::remember('ng_st', 3600, fn() => [
            'ministries' => Ministry::where('is_active', true)->count(),
            'agencies' => Agency::where('is_active', true)->count(),
        ]);

        $ministries = Cache::remember('ng_mn', 3600, function () {
            $data = Ministry::with('agencies')->where('is_active', true)->orderBy('name')->get();
            // Serialize IDs + names only to avoid Eloquent serialization issues
            return $data->map(fn($m) => [
                'id' => $m->id, 'slug' => $m->slug, 'name' => $m->name,
                'code' => $m->code, 'color' => $m->color, 'description' => $m->description,
                'agencies' => $m->agencies->map(fn($a) => ['id' => $a->id, 'name' => $a->name])->toArray(),
            ])->toArray();
        });

        $agencies = Cache::remember('ng_ag', 3600, function () {
            return Agency::with('ministry')->where('is_active', true)->orderBy('name')->get()
                ->map(fn($a) => ['id' => $a->id, 'name' => $a->name, 'ministry_name' => $a->ministry?->name ?? ''])
                ->toArray();
        });

        $heroVid = Cache::remember('ng_hr', 900, function () {
            try {
                $asset = MediaAsset::resolveSlot(\App\Models\County::class, 0, 'national_hero_video');
                return $asset?->mp4Url();
            } catch (\Throwable) { return null; }
        });

        $heroPoster = '';
        try { $heroPoster = media('kicc/national-hero.jpeg'); } catch (\Throwable) {}

        return view('national-government.index', compact('ministries', 'agencies', 'stats', 'heroVid', 'heroPoster'));
    }
}