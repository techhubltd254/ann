<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\County;
use App\Models\MediaAsset;
use App\Models\SectorEntity;
use Illuminate\Support\Facades\DB;

class CountyController extends Controller
{
    public function index()
    {
        $counties = County::withCount('sectors')->orderBy('name')->get();
        return view('counties.index', compact('counties'));
    }

    public function show(County $county)
    {
        $county->load('sectors');
        $sectors = $county->sectors;

        // Build sector data from the county_sector pivot where display_on_tile = 'yes'
        // This replaces the old hardcoded 8-sector array — now driven by the admin.
        $sectorData = [];
        $tileSectors = DB::table('county_sector')
            ->where('county_id', $county->id)
            ->where('display_on_tile', 'yes')
            ->get();

        $countMap = [
            'tourism' => $county->tourismAttractions()->count(),
            'hotels' => $county->hotels()->count(),
            'farms' => $county->farms()->count(),
            'products' => $county->products()->count(),
            'institutions' => $county->institutions()->count(),
            'transport' => $county->transport()->count(),
            'health' => $county->healthFacilities()->count(),
            'culture' => $county->cultureSites()->count(),
        ];

        foreach ($tileSectors as $pivot) {
            $sectorModel = $sectors->firstWhere('id', $pivot->sector_id);
            if (!$sectorModel) continue;
            $slug = $sectorModel->slug;
            $count = $countMap[$slug] ?? \App\Models\SectorEntity::where('county_id', $county->id)
                ->where('sector_id', $sectorModel->id)->count();
            $sectorData[$sectorModel->name] = [
                'count' => $count,
                'icon' => $sectorModel->emoji ?: '📋',
                'route' => $slug,
            ];
        }

        $featuredAttractions = $county->tourismAttractions()->where('is_published', true)->orderBy('name')->take(12)->get();
        $featuredHotels = $county->hotels()->where('is_published', true)->orderByDesc('star_rating')->take(8)->get();
        $countyProducts = $county->products()->where('is_published', true)->whereNotNull('price')->orderByDesc('price')->take(8)->get();

        // Attach uploaded 4D immersive videos (slot '4d_video') to each entity
        foreach ($featuredAttractions as $a) {
            $a->media4d = MediaAsset::resolveSlot(\App\Models\CountyTourismAttraction::class, $a->id, '4d_video');
        }
        foreach ($featuredHotels as $h) {
            $h->media4d = MediaAsset::resolveSlot(\App\Models\CountyHotel::class, $h->id, '4d_video');
        }
        foreach ($countyProducts as $p) {
            $p->media4d = MediaAsset::resolveSlot(\App\Models\CountyProduct::class, $p->id, '4d_video');
        }
        $exhibitions = $county->exhibitions()->where('status', 'published')->orderBy('start_date', 'desc')->take(3)->get();
        $linkedSectors = $county->sectors()->orderBy('name')->get()->unique('name')->values()->reject(function($s) {
            return in_array($s->slug, [
                'lands-planning-and-urban-development',
                'quality-healthcare-for-all',
                'health-and-sanitation',
            ]);
        })->values();

        $countyMedia = MediaAsset::resolveSlot(County::class, $county->id, 'hero_video');

        // Trade agreements + blocs for export opportunities section
        $countyTradeAgreements = \App\Models\TradeAgreement::with('bloc')->featured()->active()->latest()->take(3)->get();
        $countyTradeBlocs = \App\Models\TradingBloc::where('is_active', true)->orderBy('name')->take(4)->get();

        return view('counties.show', compact(
            'county', 'sectors', 'sectorData',
            'featuredAttractions', 'featuredHotels', 'countyProducts',
            'exhibitions', 'linkedSectors', 'countyMedia',
            'countyTradeAgreements', 'countyTradeBlocs'
        ));
    }

    public function sector(County $county, string $sector)
    {
        $sectorModel = $county->sectors()->where('slug', $sector)->first();
        if (!$sectorModel) abort(404, "Sector not found for {$county->name}");

        $items = SectorEntity::where('county_id', $county->id)
            ->where('sector_id', $sectorModel->id)
            ->where('is_published', true)
            ->orderBy('name')
            ->paginate(12);

        // Load bookable services (marketplace products) for this county
        $services = \App\Models\Marketplace\Product::where('county_id', $county->id)
            ->where('status', 'active')
            ->with('variants')
            ->get();

        $info = [
            'tourism' => ['title' => 'Tourism & Attractions', 'icon' => '🏖️', 'desc' => 'Discover attractions, tours, and cultural sites.'],
            'hotels' => ['title' => 'Hospitality & Hotels', 'icon' => '🏨', 'desc' => 'Hotels and accommodation.'],
            'products' => ['title' => 'Commerce & End Products', 'icon' => '🛒', 'desc' => 'Bookable county end products and marketplace goods.'],
            'institutions' => ['title' => 'Education & Institutions', 'icon' => '🎓', 'desc' => 'Schools and training centers.'],
            'farms' => ['title' => 'Agriculture & Farms', 'icon' => '🌾', 'desc' => 'Farms and agribusiness.'],
            'transport' => ['title' => 'Transport & Logistics', 'icon' => '🚢', 'desc' => 'Transport hubs and logistics.'],
            'health' => ['title' => 'Healthcare', 'icon' => '🏥', 'desc' => 'Hospitals and clinics.'],
            'culture' => ['title' => 'Culture & Heritage', 'icon' => '🎭', 'desc' => 'Cultural sites and traditions.'],
            'agriculture' => ['title' => 'Agriculture', 'icon' => '🌱', 'desc' => 'Agriculture and farming.'],
        ];

        $sectorInfo = $info[$sector] ?? ['title' => $sectorModel->name, 'icon' => '📋', 'desc' => "{$sectorModel->name} in {$county->name} County."];

        // 4D video mapping for Muranga sectors
        $fourDVideo = null;
        if ($county->slug === 'muranga') {
            $fourDMap = [
                'tourism' => 'clips/drone_aerial.mp4',
                'hotels' => null,
                'products' => null,
                'institutions' => 'clips/drone_wide.mp4',
                'farms' => null,
                'transport' => null,
                'health' => null,
                'culture' => 'clips/mukurwe_culture.mp4',
                'agriculture' => null,
                'education' => 'clips/drone_wide.mp4',
                'education-4' => 'clips/drone_wide.mp4',
            ];
            $videoFile = $fourDMap[$sector] ?? null;
            if ($videoFile && \Illuminate\Support\Facades\Storage::disk('public')->exists('kicc/4d/' . $videoFile)) {
                $fourDVideo = \Illuminate\Support\Facades\Storage::disk('public')->url('kicc/4d/' . $videoFile);
            }
        }

        return view('counties.sector', compact('county', 'items', 'services', 'sector', 'sectorInfo', 'sectorModel', 'fourDVideo'));
    }
}
