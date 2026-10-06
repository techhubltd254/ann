<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Agency;
use App\Models\County;
use App\Models\CountyBulkSlotAllocation;
use App\Models\MediaAsset;
use App\Models\Ministry;
use App\Models\Page;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class NationalAdminController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        return redirect()->route('national.admin.v2');
    }

    public function dashboard(Request $request)
    {
        $tab = $request->get('tab', 'ministries');

        // Cached 60s (admin TTL); busted by CacheSyncService::national() on write.
        $buildDash = function () {
                $ministries = Ministry::with('agencies')->orderBy('name')->get();
                $agencies = Agency::with('ministry')->orderBy('name')->get();
                $nationalPages = Page::whereIn('slug', ['about','mission','vision','history','org-structure','pricing'])->orderBy('sort_order')->get();

                $stats = [
                    'ministries' => $ministries->count(),
                    'agencies' => $agencies->count(),
                ];

                // Media data
                $nationalHero = MediaAsset::resolveSlot(County::class, 0, 'national_hero_video');
                $nationalFlag = MediaAsset::resolveSlot(County::class, 0, 'national_flag_video');

                $ministryMedia = [];
                foreach ($ministries as $m) {
                    $ministryMedia[$m->id] = [
                        'video' => MediaAsset::resolveSlot(Ministry::class, $m->id, 'ministry_video_' . $m->slug),
                        'flag' => MediaAsset::resolveSlot(Ministry::class, $m->id, 'ministry_flag_video'),
                    ];
                }

                return compact('ministries', 'agencies', 'nationalPages', 'stats', 'nationalHero', 'nationalFlag', 'ministryMedia');
        };

        try {
            $data = $buildDash();
        } catch (\Throwable $e) {
            $data = $buildDash();
        }

        extract($data);

        $navItems = [
            ['label' => 'Ministries', 'tab' => 'ministries', 'icon' => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4'],
            ['label' => 'Agencies', 'tab' => 'agencies', 'icon' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z'],
            ['label' => 'Hero Video', 'tab' => 'hero', 'icon' => 'M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z'],
            ['label' => 'Ministry Media', 'tab' => 'media', 'icon' => 'M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z'],
            ['label' => 'National Flag', 'tab' => 'flag', 'icon' => 'M3 3v18h18M7 16l4-8 4 4 4-6'],
            ['label' => 'Pages', 'tab' => 'pages', 'icon' => 'M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z'],
            ['label' => 'County Classification', 'tab' => 'counties', 'icon' => 'M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7'],
            ['label' => 'Exhibitor Req.', 'tab' => 'exh_requests', 'icon' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197'],
        ];

        // ── County classification data + pipeline activations (outside cache) ──
        $counties = \App\Models\County::orderBy('name')->get(['id', 'name', 'slug', 'classification_rps', 'classification_fns', 'classification_quadrant']);
        $quadrantCounts = $counties->groupBy('classification_quadrant')->map->count();
        $unclassified = $counties->whereNull('classification_quadrant');

        // Pipeline activation counts per county
        $activationCounts = \Illuminate\Support\Facades\DB::table('pipeline_activations')
            ->selectRaw('county_id, COUNT(*) as c')
            ->groupBy('county_id')->pluck('c', 'county_id');
        $activationTotal = $activationCounts->sum();

        // Pipeline codes grouped by county (for drill-down)
        $activeByCounty = \Illuminate\Support\Facades\DB::table('pipeline_activations')
            ->join('pipeline_registrations', 'pipeline_activations.pipeline_code', '=', 'pipeline_registrations.code')
            ->selectRaw('pipeline_activations.county_id, pipeline_registrations.code, pipeline_registrations.slug')
            ->get()->groupBy('county_id');

        // Exhibitor requests (custom/premium setups)
        $exhRequests = User::with('county')
            ->where('account_type', 'exhibitor')
            ->where('metadata', 'like', '%"complexity"%')
            ->where('metadata', 'like', '%"onboarding_complete":true%')
            ->latest()->take(50)->get();

        return view('national.admin', compact(
            'tab', 'navItems', 'ministries', 'agencies', 'nationalPages',
            'stats', 'nationalHero', 'nationalFlag', 'ministryMedia',
            'counties', 'quadrantCounts', 'unclassified',
            'activationCounts', 'activationTotal', 'activeByCounty',
            'exhRequests',
        ));
    }

    /* ─── NATIONAL HERO VIDEO ─── */

    public function uploadNationalHero(Request $request)
    {
        abort_if(!Auth::user()?->isAdmin(), 403);
        $data = $request->validate(['video' => 'required|file|mimes:mp4,webm,mov|max:2048000']);
        $file = $request->file('video');
        $disk = Storage::disk('r2');
        $r2Path = 'national/hero/hero.mp4';
        $disk->writeStream($r2Path, fopen($file->getRealPath(), 'r'), ['visibility' => 'public']);

        MediaAsset::forSlot(County::class, 0, 'national_hero_video')->delete();
        $asset = MediaAsset::create([
            'uuid' => (string) Str::uuid(),
            'owner_id' => 0,
            'owner_type' => County::class,
            'slot' => 'national_hero_video',
            'disk' => 'r2',
            'path' => $r2Path,
            'original_name' => $file->getClientOriginalName(),
            'mime' => $file->getMimeType(),
            'kind' => 'video',
            'size_bytes' => $file->getSize(),
            'status' => 'ready',
        ]);
        $asset->derivatives()->create([
            'kind' => 'video_mp4', 'path' => $r2Path, 'mime' => 'video/mp4',
            'size_bytes' => $file->getSize(), 'variant' => 'source',
        ]);
        app(\App\Services\CacheSyncService::class)->national();
        return redirect()->route('national.admin.v2', ['tab' => 'hero'])->with('success', 'National hero video uploaded.');
    }

    public function deleteNationalHero()
    {
        abort_if(!Auth::user()?->isAdmin(), 403);
        $assets = MediaAsset::forSlot(County::class, 0, 'national_hero_video')->get();
        foreach ($assets as $a) {
            if ($a->disk === 'r2') Storage::disk('r2')->delete($a->path);
            $a->derivatives()->delete();
            $a->delete();
        }
        app(\App\Services\CacheSyncService::class)->national();
        return redirect()->route('national.admin.v2', ['tab' => 'hero'])->with('success', 'National hero video removed.');
    }

    /* ─── MINISTRY MEDIA ─── */

    public function uploadMinistryVideo(Request $request, Ministry $ministry)
    {
        abort_if(!Auth::user()?->isAdmin(), 403);
        $data = $request->validate(['video' => 'required|file|mimes:mp4,webm,mov|max:2048000']);
        $file = $request->file('video');
        $disk = Storage::disk('r2');
        $filename = $ministry->slug . '.' . $file->getClientOriginalExtension();
        $r2Path = "national/ministries/{$ministry->slug}/video/{$filename}";
        $disk->writeStream($r2Path, fopen($file->getRealPath(), 'r'), ['visibility' => 'public']);

        MediaAsset::forSlot(Ministry::class, $ministry->id, 'ministry_video_' . $ministry->slug)->delete();
        $asset = MediaAsset::create([
            'uuid' => (string) Str::uuid(),
            'owner_id' => $ministry->id,
            'owner_type' => Ministry::class,
            'slot' => 'ministry_video_' . $ministry->slug,
            'disk' => 'r2',
            'path' => $r2Path,
            'original_name' => $file->getClientOriginalName(),
            'mime' => $file->getMimeType(),
            'kind' => 'video',
            'size_bytes' => $file->getSize(),
            'status' => 'ready',
        ]);
        $asset->derivatives()->create([
            'kind' => 'video_mp4', 'path' => $r2Path, 'mime' => 'video/mp4',
            'size_bytes' => $file->getSize(), 'variant' => 'source',
        ]);
        app(\App\Services\CacheSyncService::class)->national();
        return redirect()->route('national.admin.v2', ['tab' => 'media'])->with('success', "Video for {$ministry->name} uploaded.");
    }

    public function deleteMinistryVideo(Ministry $ministry)
    {
        abort_if(!Auth::user()?->isAdmin(), 403);
        $assets = MediaAsset::forSlot(Ministry::class, $ministry->id, 'ministry_video_' . $ministry->slug)->get();
        foreach ($assets as $a) {
            if ($a->disk === 'r2') Storage::disk('r2')->delete($a->path);
            $a->derivatives()->delete();
            $a->delete();
        }
        app(\App\Services\CacheSyncService::class)->national();
        return redirect()->route('national.admin.v2', ['tab' => 'media'])->with('success', "Video for {$ministry->name} removed.");
    }

    public function uploadMinistryFlag(Request $request, Ministry $ministry)
    {
        abort_if(!Auth::user()?->isAdmin(), 403);
        $data = $request->validate(['video' => 'required|file|mimes:mp4,webm,mov|max:2048000']);
        $file = $request->file('video');
        $disk = Storage::disk('r2');
        $filename = 'flag.' . $file->getClientOriginalExtension();
        $r2Path = "national/ministries/{$ministry->slug}/flag/{$filename}";
        $disk->writeStream($r2Path, fopen($file->getRealPath(), 'r'), ['visibility' => 'public']);

        MediaAsset::forSlot(Ministry::class, $ministry->id, 'ministry_flag_video')->delete();
        $asset = MediaAsset::create([
            'uuid' => (string) Str::uuid(),
            'owner_id' => $ministry->id,
            'owner_type' => Ministry::class,
            'slot' => 'ministry_flag_video',
            'disk' => 'r2',
            'path' => $r2Path,
            'original_name' => $file->getClientOriginalName(),
            'mime' => $file->getMimeType(),
            'kind' => 'video',
            'size_bytes' => $file->getSize(),
            'status' => 'ready',
        ]);
        $asset->derivatives()->create([
            'kind' => 'video_mp4', 'path' => $r2Path, 'mime' => 'video/mp4',
            'size_bytes' => $file->getSize(), 'variant' => 'source',
        ]);
        app(\App\Services\CacheSyncService::class)->national();
        return redirect()->route('national.admin.v2', ['tab' => 'media'])->with('success', "Flag for {$ministry->name} uploaded.");
    }

    public function deleteMinistryFlag(Ministry $ministry)
    {
        abort_if(!Auth::user()?->isAdmin(), 403);
        $assets = MediaAsset::forSlot(Ministry::class, $ministry->id, 'ministry_flag_video')->get();
        foreach ($assets as $a) {
            if ($a->disk === 'r2') Storage::disk('r2')->delete($a->path);
            $a->derivatives()->delete();
            $a->delete();
        }
        app(\App\Services\CacheSyncService::class)->national();
        return redirect()->route('national.admin.v2', ['tab' => 'media'])->with('success', "Flag for {$ministry->name} removed.");
    }

    /* ─── NATIONAL FLAG ─── */

    public function uploadNationalFlag(Request $request)
    {
        abort_if(!Auth::user()?->isAdmin(), 403);
        $data = $request->validate(['video' => 'required|file|mimes:mp4,webm,mov|max:2048000']);
        $file = $request->file('video');
        $disk = Storage::disk('r2');
        $filename = 'national-flag.' . $file->getClientOriginalExtension();
        $r2Path = "national/flag/{$filename}";
        $disk->writeStream($r2Path, fopen($file->getRealPath(), 'r'), ['visibility' => 'public']);

        MediaAsset::forSlot(County::class, 0, 'national_flag_video')->delete();
        $asset = MediaAsset::create([
            'uuid' => (string) Str::uuid(),
            'owner_id' => 0,
            'owner_type' => County::class,
            'slot' => 'national_flag_video',
            'disk' => 'r2',
            'path' => $r2Path,
            'original_name' => $file->getClientOriginalName(),
            'mime' => $file->getMimeType(),
            'kind' => 'video',
            'size_bytes' => $file->getSize(),
            'status' => 'ready',
        ]);
        $asset->derivatives()->create([
            'kind' => 'video_mp4', 'path' => $r2Path, 'mime' => 'video/mp4',
            'size_bytes' => $file->getSize(), 'variant' => 'source',
        ]);
        app(\App\Services\CacheSyncService::class)->national();
        return redirect()->route('national.admin.v2', ['tab' => 'flag'])->with('success', 'National animated flag uploaded.');
    }

    public function deleteNationalFlag()
    {
        abort_if(!Auth::user()?->isAdmin(), 403);
        $assets = MediaAsset::forSlot(County::class, 0, 'national_flag_video')->get();
        foreach ($assets as $a) {
            if ($a->disk === 'r2') Storage::disk('r2')->delete($a->path);
            $a->derivatives()->delete();
            $a->delete();
        }
        app(\App\Services\CacheSyncService::class)->national();
        return redirect()->route('national.admin.v2', ['tab' => 'flag'])->with('success', 'National animated flag removed.');
    }

    /* ─── CRUD: kept from original ─── */

    public function storeMinistry(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:20',
            'color' => 'nullable|string|max:20',
            'description' => 'nullable|string|max:2000',
            'website' => 'nullable|url|max:500',
            'contact_email' => 'nullable|email|max:255',
        ]);
        $data['slug'] = Str::slug($data['name']);
        Ministry::create($data);
        app(\App\Services\CacheSyncService::class)->national();
        return redirect()->route('national.admin.v2', ['tab' => 'ministries'])->with('success', "Ministry created.");
    }

    public function updateMinistry(Request $request, Ministry $ministry)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:20',
            'description' => 'nullable|string|max:2000',
            'is_active' => 'nullable|boolean',
        ]);
        $ministry->update($data);
        app(\App\Services\CacheSyncService::class)->national();
        return redirect()->route('national.admin.v2', ['tab' => 'ministries'])->with('success', "Ministry updated.");
    }

    public function deleteMinistry(Ministry $ministry)
    {
        $ministry->delete();
        app(\App\Services\CacheSyncService::class)->national();
        return redirect()->route('national.admin.v2', ['tab' => 'ministries'])->with('success', "Ministry removed.");
    }

    public function storeAgency(Request $request)
    {
        $data = $request->validate([
            'ministry_id' => 'required|exists:ministries,id',
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:20',
            'description' => 'nullable|string|max:2000',
        ]);
        $data['slug'] = Str::slug($data['name']);
        Agency::create($data);
        app(\App\Services\CacheSyncService::class)->national();
        return redirect()->route('national.admin.v2', ['tab' => 'agencies'])->with('success', "Agency created.");
    }

    public function deleteAgency(Agency $agency)
    {
        $agency->delete();
        app(\App\Services\CacheSyncService::class)->national();
        return redirect()->route('national.admin.v2', ['tab' => 'agencies'])->with('success', "Agency removed.");
    }
}