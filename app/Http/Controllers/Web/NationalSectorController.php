<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\County;
use App\Models\Sector;
use App\Models\SectorEntity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class NationalSectorController extends Controller
{
    public function index()
    {
        $sectorIds = Sector::where('is_active', true)->pluck('id');
        $aggregated = Cache::remember('kicc_national_sector_counts', 3600, function () use ($sectorIds) {
            return SectorEntity::whereIn('sector_id', $sectorIds)
                ->where(function ($q) { $q->where('is_published', true)->orWhere('isPublished', true); })
                ->selectRaw('sector_id, count(*) as total')
                ->groupBy('sector_id')
                ->pluck('total', 'sector_id')
                ->toArray();
        });

        $sectors = Sector::whereIn('id', array_keys($aggregated))
            ->orderBy('name')
            ->get()
            ->map(fn (Sector $s) => [
                'id'          => $s->id,
                'name'        => $s->name,
                'slug'        => $s->slug,
                'emoji'       => $s->emoji ?? null,
                'description' => $s->description,
                'count'       => $aggregated[$s->id] ?? 0,
            ]);

        return view('experience.sectors', compact('sectors'));
    }

    public function show(string $slug)
    {
        $sector = Sector::where('slug', $slug)->where('is_active', true)->firstOrFail();

        $page = max(1, (int) request()->get('page', 1));
        $cacheVersion = Cache::get("kicc_nat_sector_version_{$sector->id}", 1);
        $perPage = 24;

        $entityIds = Cache::remember(
            "kicc_nat_sector_items_{$sector->id}_{$cacheVersion}_{$page}",
            21600,
            function () use ($sector, $perPage, $page) {
                return SectorEntity::where('sector_id', $sector->id)
                    ->where(function ($q) { $q->where('is_published', true)->orWhere('isPublished', true); })
                    ->orderBy('name')
                    ->skip(($page - 1) * $perPage)
                    ->take($perPage)
                    ->pluck('id')
                    ->toArray();
            }
        );

        $entities = !empty($entityIds)
            ? SectorEntity::whereIn('id', $entityIds)->with('county')->orderBy('name')->get()
            : collect();

        $total = Cache::remember("kicc_nat_sector_total_{$sector->id}", 21600, function () use ($sector) {
            return SectorEntity::where('sector_id', $sector->id)
                ->where(function ($q) { $q->where('is_published', true)->orWhere('isPublished', true); })
                ->count();
        });

        $hasMore = ($page * $perPage) < $total;

        $allSectors = Sector::where('is_active', true)->orderBy('name')->get();

        return view('experience.pages.national.sector-show', compact(
            'sector', 'entities', 'total', 'page', 'hasMore', 'allSectors'
        ));
    }
}