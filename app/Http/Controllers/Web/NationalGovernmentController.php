<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Agency;
use App\Models\MediaAsset;
use App\Models\Ministry;
use App\Services\TileMediaResolver;
use Illuminate\Support\Facades\Cache;

class NationalGovernmentController extends Controller
{
    protected function cacheKey(string $key): string
    {
        return "ng_{$key}_" . cache_buster();
    }

    public function index()
    {
        $stats = [];
        $ministries = [];
        $agencies = [];
        try {
            $stats = Cache::remember($this->cacheKey('stats'), 3600, fn() => [
                'ministries' => Ministry::where('is_active', true)->count(),
                'agencies' => Agency::where('is_active', true)->count(),
            ]);
        } catch (\Throwable $e) { $stats = ['ministries' => 0, 'agencies' => 0]; }

        try {
            $ministries = Cache::remember($this->cacheKey('ministries'), 3600, function () {
                return Ministry::with('agencies')->where('is_active', true)->orderBy('name')->get()
                    ->map(fn($m) => [
                        'id' => $m->id, 'slug' => $m->slug, 'name' => $m->name,
                        'code' => $m->code, 'color' => $m->color, 'description' => $m->description,
                        'agencies' => $m->agencies->map(fn($a) => ['id' => $a->id, 'name' => $a->name])->toArray(),
                    ])->toArray();
            });
        } catch (\Throwable $e) { $ministries = []; }

        try {
            $agencies = Cache::remember($this->cacheKey('agencies'), 3600, function () {
                return Agency::with('ministry')->where('is_active', true)->orderBy('name')->get()
                    ->map(fn($a) => ['id' => $a->id, 'name' => $a->name, 'ministry_name' => $a->ministry?->name ?? ''])
                    ->toArray();
            });
        } catch (\Throwable $e) { $agencies = []; }

        // Hero video
        $heroVid = Cache::remember($this->cacheKey('hero'), 900, function () {
            try {
                $asset = MediaAsset::resolveSlot(\App\Models\County::class, 0, 'national_hero_video');
                return $asset?->mp4Url();
            } catch (\Throwable) { return null; }
        });
        $heroPoster = MediaAsset::resolveSlot(\App\Models\County::class, 0, 'national_hero_video')?->posterUrl() ?? '';

        // Per-ministry tile media via TileMediaResolver (5-level fallback)
        $resolver = app(TileMediaResolver::class);
        $tileMedia = [];
        foreach ($ministries as $m) {
            $ministry = Ministry::find($m['id']);
            if ($ministry) {
                $tileMedia[$m['slug']] = $resolver->forMinistry($ministry);
            }
        }

        return view('national-government.index', compact('ministries', 'agencies', 'stats', 'heroVid', 'heroPoster', 'tileMedia'));
    }
}