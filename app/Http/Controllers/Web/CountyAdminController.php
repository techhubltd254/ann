<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Advertisement;
use App\Models\County;
use App\Models\CountyProduct;
use App\Models\CountyTourismAttraction;
use App\Models\CountyHotel;
use App\Models\CountyInstitution;
use App\Models\CountyFarm;
use App\Models\CountyTransport;
use App\Models\CountyHealthFacility;
use App\Models\CountyCultureSite;
use App\Models\Marketplace\Product;
use App\Models\Marketplace\ProductImage;
use App\Models\Marketplace\ProductVariant;
use App\Models\Sector;
use App\Models\SectorEntity;
use App\Models\SubscriptionPlan;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Professional county admin — content control, image management,
 * pricing, advertising, packages, analytics and reports.
 * Built for Mombasa, replicable for all 47 counties.
 */
class CountyAdminController extends Controller
{
    protected function authorizeCounty(string $slug): County
    {
        $user = Auth::user();
        $county = County::where('slug', $slug)->firstOrFail();
        $allowed = $user->hasRole('kicc_admin')
            || ($user->county_id == $county->id)
            || ($user->hasRole('county_admin') && $user->county_id == $county->id);
        abort_unless($allowed, 403, 'You do not have access to this county.');
        return $county;
    }

    public function dashboard(string $slug, Request $request)
    {
        $user = Auth::user();
        $county = County::where('slug', $slug)->firstOrFail();
        abort_if(!$user->isAdmin() && $user->county_id !== $county->id, 403);
        $county = $this->authorizeCounty($slug);
        $tab = $request->get('tab', 'overview');

        // Analytics
        $products = CountyProduct::where('county_id', $county->id)->get();
        $attractions = CountyTourismAttraction::where('county_id', $county->id)->get();
        $hotels = CountyHotel::where('county_id', $county->id)->get();
        $sectorImages = $this->sectorImages($county->slug);
        $plans = SubscriptionPlan::where('is_active', true)->orderBy('sort_order')->get();
        $marketplaceProducts = Product::with(['variants', 'images'])->where('county_id', $county->id)->latest()->get();
        $ads = Advertisement::where('placement', 'like', "%{$county->slug}%")->latest()->get();
        $sectors = Sector::where('is_active', true)->orderBy('name')->get();
        $linkedSectors = DB::table('county_sector')->where('county_id', $county->id)->pluck('sector_id');
        $tileSectors = DB::table('county_sector')->where('county_id', $county->id)->where('display_on_tile', 'yes')->pluck('sector_id')->toArray();
        $allSectors = Sector::orderBy('name')->get();
        $sectorEntities = SectorEntity::where('county_id', $county->id)->limit(100)->get();
        $institutions = \App\Models\CountyInstitution::with('owner', 'sectorEntities')
            ->where('county_id', $county->id)
            ->latest()
            ->get();

        // Stats
        $totalOrders = DB::table('order_items')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->where('products.county_id', $county->id)->count();
        $totalRevenue = DB::table('escrow_transactions')
            ->join('users', 'escrow_transactions.seller_id', '=', 'users.id')
            ->where('users.county_id', $county->id)->where('escrow_transactions.status', 'released')
            ->sum('escrow_transactions.amount');

        $stats = [
            'products' => $products->count(),
            'attractions' => $attractions->count(),
            'hotels' => $hotels->count(),
            'marketplaceProducts' => $marketplaceProducts->count(),
            'orders' => $totalOrders,
            'revenue' => $totalRevenue,
            'sector_images' => count($sectorImages ?? []),
            'packages' => $plans->count(),
            // Dynamic KPI descriptors
            'top_attractions' => $attractions->take(2)->pluck('name')->join(', '),
            'hotel_rating' => $hotels->count() > 0 ? round($hotels->avg('star_rating') ?: 0, 1) : null,
            'products_new' => $products->where('created_at', '>=', now()->subMonth())->count(),
            'marketplace_new' => $marketplaceProducts->where('created_at', '>=', now()->subMonth())->count(),
            'institutions' => $institutions->count(),
            'sector_entities' => $sectorEntities->count(),
        ];

        $navItems = [
            ['label' => 'Overview', 'tab' => 'overview', 'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6'],
            ['label' => 'Details', 'tab' => 'details', 'icon' => 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
            ['label' => 'Content', 'tab' => 'content', 'icon' => 'M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z'],
            ['label' => 'Hero Video', 'tab' => 'hero', 'icon' => 'M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z'],
            ['label' => 'Institutions', 'tab' => 'institutions', 'icon' => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4'],
            ['label' => 'Images', 'tab' => 'images', 'icon' => 'M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z'],
            ['label' => '4D Videos', 'tab' => 'videos4d', 'icon' => 'M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z'],
            ['label' => 'Sectors', 'tab' => 'sectors', 'icon' => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4'],
            ['label' => 'Prices', 'tab' => 'prices', 'icon' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1'],
            ['label' => 'Marketplace', 'tab' => 'marketplace', 'icon' => 'M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z'],
            ['label' => 'Advertising', 'tab' => 'ads', 'icon' => 'M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z'],
            ['label' => 'Packages', 'tab' => 'packages', 'icon' => 'M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z'],
            ['label' => 'Reports', 'tab' => 'reports', 'icon' => 'M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
            ['label' => 'Analytics', 'tab' => 'analytics', 'icon' => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z'],
        ];

        $analytics = app(\App\Services\AnalyticsService::class)->forCounty($county);

        return view('dashboards.county-admin', compact(
            'county', 'tab', 'navItems', 'stats', 'products', 'attractions',
            'hotels', 'sectorImages', 'plans', 'marketplaceProducts', 'ads',
            'sectors', 'linkedSectors', 'tileSectors', 'allSectors', 'sectorEntities',
            'institutions',
        ) + ['video4dMap' => $this->video4dMap($county), 'analytics' => $analytics]);
    }

    /* ─── CONTENT ─── */
    public function updateContent(Request $request, string $slug)
    {
        $user = Auth::user();
        $county = County::where('slug', $slug)->firstOrFail();
        abort_if(!$user->isAdmin() && $user->county_id !== $county->id, 403);
        $county = $this->authorizeCounty($slug);
        $data = $request->validate([
            'tagline' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:5000',
            'tourism_highlights' => 'nullable|string|max:500',
        ]);
        $county->update($data);
        return back()->with('success', 'County content updated. Changes are live immediately.');
    }

    /* ─── FULL COUNTY DETAILS ─── */
    public function updateDetails(Request $request, string $slug)
    {
        $user = Auth::user();
        $county = County::where('slug', $slug)->firstOrFail();
        abort_if(!$user->isAdmin() && $user->county_id !== $county->id, 403);
        $county = $this->authorizeCounty($slug);
        $data = $request->validate([
            'tagline' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:5000',
            'capital' => 'nullable|string|max:255',
            'population_2024' => 'nullable|integer|min:0',
            'area_km2' => 'nullable|numeric|min:0',
            'economic_zone' => 'nullable|string|max:255',
            'warmest_month' => 'nullable|string|max:100',
            'coolest_month' => 'nullable|string|max:100',
            'rainy_season' => 'nullable|string|max:200',
            'dry_season' => 'nullable|string|max:200',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'icon_emoji' => 'nullable|string|max:10',
        ]);
        $county->update($data);
        return back()->with('success', 'All county details updated.');
    }

    /* ─── SECTOR MANAGEMENT ─── */
    public function toggleSector(Request $request, string $slug)
    {
        $user = Auth::user();
        $county = County::where('slug', $slug)->firstOrFail();
        abort_if(!$user->isAdmin() && $user->county_id !== $county->id, 403);
        $county = $this->authorizeCounty($slug);
        $data = $request->validate([
            'sector_id' => 'required|exists:sectors,id',
            'action' => 'required|in:attach,detach',
        ]);
        if ($data['action'] === 'attach') {
            $county->sectors()->syncWithoutDetaching([$data['sector_id']]);
        } else {
            $county->sectors()->detach($data['sector_id']);
        }
        return back()->with('success', 'Sector ' . ($data['action'] === 'attach' ? 'added' : 'removed') . '.');
    }

    /** Toggle a sector's display_on_tile flag for the Economic Sectors grid. */
    public function toggleTileSector(Request $request, string $slug)
    {
        $user = Auth::user();
        $county = County::where('slug', $slug)->firstOrFail();
        abort_if(!$user->isAdmin() && $user->county_id !== $county->id, 403);
        $county = $this->authorizeCounty($slug);
        $data = $request->validate([
            'sector_id' => 'required|exists:sectors,id',
            'display_on_tile' => 'required|in:yes,no',
        ]);
        DB::table('county_sector')
            ->where('county_id', $county->id)
            ->where('sector_id', $data['sector_id'])
            ->update([
                'display_on_tile' => $data['display_on_tile'],
                'displayOnTile' => $data['display_on_tile'],
            ]);
        return back()->with('success', 'Tile display ' . ($data['display_on_tile'] === 'yes' ? 'enabled' : 'disabled') . '.');
    }

    /* ─── SECTOR ENTITY CRUD ─── */
    public function addEntity(Request $request, string $slug)
    {
        $user = Auth::user();
        $county = County::where('slug', $slug)->firstOrFail();
        abort_if(!$user->isAdmin() && $user->county_id !== $county->id, 403);
        $county = $this->authorizeCounty($slug);
        $data = $request->validate([
            'sector_id' => 'required|exists:sectors,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'entity_type' => 'required|string|max:100',
        ]);

        $entity = SectorEntity::create([
            'county_id' => $county->id,
            'sector_id' => $data['sector_id'],
            'name' => $data['name'],
            'description' => $data['description'],
            'entity_type' => $data['entity_type'],
            'is_published' => true,
        ]);

        return back()->with('success', "Entity '{$entity->name}' added to sector.");
    }

    public function deleteEntity(string $slug, int $entityId)
    {
        $user = Auth::user();
        $county = County::where('slug', $slug)->firstOrFail();
        abort_if(!$user->isAdmin() && $user->county_id !== $county->id, 403);
        $county = $this->authorizeCounty($slug);
        $entity = SectorEntity::where('county_id', $county->id)->findOrFail($entityId);
        $entity->delete();
        return back()->with('success', 'Entity removed.');
    }

    /* ─── IMAGES ─── */
    protected function sectorImages(string $slug): array
    {
        $sectors = ['hero', 'tourism', 'products', 'education', 'culture', 'hotels', 'farms', 'transport', 'health'];
        $images = [];
        $county = \App\Models\County::where('slug', $slug)->first();
        foreach ($sectors as $s) {
            $path = "counties/{$slug}/{$s}.jpeg";
            $fullPath = storage_path("app/public/{$path}");
            $images[$s] = [
                'path' => $path,
                'exists' => file_exists($fullPath),
                'url' => $path,
                'video' => null,
                'video_name' => null,
            ];
            // Resolve sector video for non-hero sectors
            if ($county && $s !== 'hero') {
                $asset = \App\Models\MediaAsset::resolveSlot(\App\Models\County::class, $county->id, 'sector_video_' . $s);
                $images[$s]['video'] = $asset?->mp4Url();
                $images[$s]['video_name'] = $asset?->original_name;
            }
        }
        // Hero video
        if ($county) {
            $heroAsset = \App\Models\MediaAsset::resolveSlot(\App\Models\County::class, $county->id, 'hero_video');
            $images['hero']['video'] = $heroAsset?->mp4Url();
            $images['hero']['video_name'] = $heroAsset?->original_name;
        }
        return $images;
    }

    public function uploadImage(Request $request, string $slug)
    {
        $user = Auth::user();
        $county = County::where('slug', $slug)->firstOrFail();
        abort_if(!$user->isAdmin() && $user->county_id !== $county->id, 403);
        $county = $this->authorizeCounty($slug);
        $data = $request->validate([
            'sector' => 'required|in:hero,tourism,products,education,culture,hotels,farms,transport,health',
            'image' => 'required|image|mimes:jpeg,png,jpg,webp|max:10240',
        ]);
        $file = $request->file('image');
        $filename = "{$data['sector']}.{$file->extension()}";
        $path = $file->storeAs("counties/{$slug}", $filename, 'public');
        if ($file->extension() !== 'jpeg') {
            $jpegPath = storage_path("app/public/counties/{$slug}/{$data['sector']}.jpeg");
            @copy(storage_path("app/public/{$path}"), $jpegPath);
        }
        \App\Services\N8nService::fire('county_image_updated', [
            'county' => $slug, 'sector' => $data['sector'],
        ]);
        return back()->with('success', "{$data['sector']} image updated. Changes reflect everywhere immediately.");
    }

    public function uploadSectorVideo(Request $request, string $slug)
    {
        $user = Auth::user();
        $county = County::where('slug', $slug)->firstOrFail();
        abort_if(!$user->isAdmin() && $user->county_id !== $county->id, 403);
        $county = $this->authorizeCounty($slug);
        $data = $request->validate([
            'sector' => 'required|in:tourism,products,education,culture,hotels,farms,transport,health',
            'video' => 'required|file|mimes:mp4,webm,mov|max:512000',
        ]);

        $file = $request->file('video');
        $slot = 'sector_video_' . $data['sector'];
        $filename = "{$data['sector']}." . $file->getClientOriginalExtension();

        // Upload to R2
        $disk = Storage::disk('r2');
        $r2Path = "counties/{$slug}/sector-videos/{$filename}";
        $disk->writeStream($r2Path, fopen($file->getRealPath(), 'r'), ['visibility' => 'public']);

        // Delete old asset for this slot
        \App\Models\MediaAsset::forSlot(County::class, $county->id, $slot)->delete();

        $asset = \App\Models\MediaAsset::create([
            'uuid' => (string) Str::uuid(),
            'owner_id' => $county->id,
            'owner_type' => County::class,
            'slot' => $slot,
            'disk' => 'r2',
            'path' => $r2Path,
            'original_name' => $file->getClientOriginalName(),
            'mime' => $file->getMimeType(),
            'kind' => 'video',
            'size_bytes' => $file->getSize(),
            'status' => 'ready',
        ]);

        $asset->derivatives()->create([
            'kind' => 'video_mp4',
            'path' => $r2Path,
            'mime' => 'video/mp4',
            'size_bytes' => $file->getSize(),
            'variant' => 'source',
        ]);

        return back()->with('success', "Sector video for {$data['sector']} uploaded. It plays on the county page tile background.");
    }

    public function deleteSectorVideo(string $slug, string $sector)
    {
        $user = Auth::user();
        $county = County::where('slug', $slug)->firstOrFail();
        abort_if(!$user->isAdmin() && $user->county_id !== $county->id, 403);
        $this->authorizeCounty($slug);

        $slot = 'sector_video_' . $sector;
        $assets = \App\Models\MediaAsset::forSlot(County::class, $county->id, $slot)->get();
        foreach ($assets as $a) {
            if ($a->disk === 'r2') {
                Storage::disk('r2')->delete($a->path);
            }
            $a->derivatives()->delete();
            $a->delete();
        }
        return back()->with('success', "Sector video for {$sector} removed.");
    }

    public function deleteImage(string $slug, string $sector)
    {
        $user = Auth::user();
        $county = County::where('slug', $slug)->firstOrFail();
        abort_if(!$user->isAdmin() && $user->county_id !== $county->id, 403);
        $county = $this->authorizeCounty($slug);
        $path = storage_path("app/public/counties/{$slug}/{$sector}.jpeg");
        if (file_exists($path)) @unlink($path);
        $altPath = storage_path("app/public/counties/{$slug}/{$sector}.jpg");
        if (file_exists($altPath)) @unlink($altPath);
        return back()->with('success', "{$sector} image removed. Fallback will show.");
    }

    /* ─── HERO VIDEO ─── */

    public function storeInstitution(Request $request, string $slug)
    {
        $user = Auth::user();
        $county = County::where('slug', $slug)->firstOrFail();
        abort_if(!$user->isAdmin() && $user->county_id !== $county->id, 403);
        $county = $this->authorizeCounty($slug);

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'nullable|string|max:100',
            'description' => 'nullable|string|max:5000',
            'admin_email' => 'nullable|email|max:255',
            'admin_name' => 'nullable|string|max:255',
        ]);

        $institution = \App\Models\CountyInstitution::create([
            'county_id' => $county->id,
            'name' => $data['name'],
            'type' => $data['type'] ?? 'Company',
            'description' => $data['description'] ?? '',
            'is_published' => true,
        ]);

        // Auto-create the institution admin user
        if (!empty($data['admin_email'])) {
            $admin = \App\Models\User::where('email', $data['admin_email'])->first();
            if (!$admin) {
                $admin = \App\Models\User::create([
                    'name' => $data['admin_name'] ?? $data['name'],
                    'email' => $data['admin_email'],
                    'password' => bcrypt(\Illuminate\Support\Str::random(16)),
                    'account_type' => 'institution',
                    'institution_id' => $institution->id,
                    'status' => 'active',
                ]);
            } else {
                $admin->update(['institution_id' => $institution->id, 'account_type' => 'institution']);
            }
            $admin->assignRole('institution_admin');
            $institution->update(['user_id' => $admin->id]);
        }

        return back()->with('success', "Institution \"{$data['name']}\" created. Open it to start building the profile.");
    }

    public function deleteInstitution(string $slug, int $institutionId)
    {
        $user = Auth::user();
        $county = County::where('slug', $slug)->firstOrFail();
        abort_if(!$user->isAdmin() && $user->county_id !== $county->id, 403);
        $this->authorizeCounty($slug);

        $institution = \App\Models\CountyInstitution::where('county_id', $county->id)->findOrFail($institutionId);
        \App\Services\InstitutionSyncService::deleteDerived($institution);
        $institution->delete();

        return back()->with('success', "Institution \"{$institution->name}\" deleted. All derived data removed.");
    }

    public function uploadHeroVideo(Request $request, string $slug)
    {
        $user = Auth::user();
        $county = County::where('slug', $slug)->firstOrFail();
        abort_if(!$user->isAdmin() && $user->county_id !== $county->id, 403);
        $county = $this->authorizeCounty($slug);
        $data = $request->validate([
            'video' => 'required|file|mimes:mp4,webm,mov|max:512000',
        ]);

        $file = $request->file('video');
        $filename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME) . '.' . $file->getClientOriginalExtension();

        // Upload to R2
        $disk = Storage::disk('r2');
        $r2Path = $county->slug . "/video/hero/hero.mp4";
        $disk->writeStream($r2Path, fopen($file->getRealPath(), 'r'), ['visibility' => 'public']);

        // Delete old hero asset
        \App\Models\MediaAsset::forSlot(County::class, $county->id, 'hero_video')->delete();

        // Create new asset
        $asset = \App\Models\MediaAsset::create([
            'uuid' => (string) Str::uuid(),
            'owner_id' => $county->id,
            'owner_type' => County::class,
            'slot' => 'hero_video',
            'disk' => 'r2',
            'path' => $r2Path,
            'original_name' => $file->getClientOriginalName(),
            'mime' => $file->getMimeType(),
            'kind' => 'video',
            'size_bytes' => $file->getSize(),
            'status' => 'ready',
        ]);

        // Create derivatives
        $asset->derivatives()->create([
            'kind' => 'video_mp4',
            'path' => $r2Path,
            'mime' => 'video/mp4',
            'size_bytes' => $file->getSize(),
            'variant' => '1080p',
        ]);

        return back()->with('success', 'Hero video uploaded. Processing derivatives...');
    }

    public function deleteHeroVideo(string $slug)
    {
        $user = Auth::user();
        $county = County::where('slug', $slug)->firstOrFail();
        abort_if(!$user->isAdmin() && $user->county_id !== $county->id, 403);
        $this->authorizeCounty($slug);
        
        $assets = \App\Models\MediaAsset::forSlot(County::class, $county->id, 'hero_video')->get();
        foreach ($assets as $a) {
            // Delete from R2
            if ($a->disk === 'r2') {
                Storage::disk('r2')->delete($a->path);
                foreach ($a->derivatives as $d) {
                    Storage::disk('r2')->delete($d->path);
                }
            }
            $a->derivatives()->delete();
            $a->delete();
        }
        return back()->with('success', 'Hero video removed.');
    }

    /* ─── 4D VIDEOS ─── */

    /**
     * Upload a finished 4D immersive video for one entity (attraction, hotel, product).
     * The 4D render is produced elsewhere (Gaussian Splat session); this attaches it
     * to the entity's '4d_video' slot so the county page plays it instead of a still.
     */
    public function upload4dVideo(Request $request, string $slug)
    {
        $user = Auth::user();
        $county = County::where('slug', $slug)->firstOrFail();
        abort_if(!$user->isAdmin() && $user->county_id !== $county->id, 403);
        $county = $this->authorizeCounty($slug);
        $data = $request->validate([
            'entity_type' => 'required|in:attraction,hotel,product,sector_entity',
            'entity_id' => 'required|integer',
            'video' => 'required|file|mimes:mp4,webm,mov|max:512000', // 500 MB
        ]);

        $model = match ($data['entity_type']) {
            'attraction' => CountyTourismAttraction::class,
            'hotel' => CountyHotel::class,
            'product' => CountyProduct::class,
            'sector_entity' => SectorEntity::class,
        };
        $entity = $model::where('county_id', $county->id)->findOrFail($data['entity_id']);

        $file = $request->file('video');
        $filename = "{$data['entity_type']}-{$entity->id}." . $file->getClientOriginalExtension();

        // Upload to R2 instead of local public disk
        $disk = Storage::disk('r2');
        $r2Path = "counties/{$slug}/4d/{$filename}";
        $disk->writeStream($r2Path, fopen($file->getRealPath(), 'r'), ['visibility' => 'public']);

        // Replace any existing 4d_video asset for this entity
        \App\Models\MediaAsset::forSlot($model, $entity->id, '4d_video')->delete();
        $asset = \App\Models\MediaAsset::create([
            'uuid' => (string) Str::uuid(),
            'owner_id' => $entity->id,
            'owner_type' => $model,
            'slot' => '4d_video',
            'disk' => 'r2',
            'path' => $r2Path,
            'original_name' => $file->getClientOriginalName(),
            'mime' => $file->getMimeType(),
            'kind' => 'video',
            'size_bytes' => $file->getSize(),
            'status' => 'ready',
        ]);

        // Create MP4 derivative so mp4Url() works
        $asset->derivatives()->create([
            'kind' => 'video_mp4',
            'path' => $r2Path,
            'mime' => 'video/mp4',
            'size_bytes' => $file->getSize(),
            'variant' => 'source',
        ]);

        try {
            \App\Services\N8nService::fire('county_4d_uploaded', [
                'county' => $slug, 'entity_type' => $data['entity_type'], 'entity_id' => $entity->id,
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('N8n fire failed for 4D upload: ' . $e->getMessage());
        }

        return back()->with('success', "4D video attached to {$entity->name}. It now plays on the county page.");
    }

    public function delete4dVideo(string $slug, string $entityType, int $entityId)
    {
        $user = Auth::user();
        $county = County::where('slug', $slug)->firstOrFail();
        abort_if(!$user->isAdmin() && $user->county_id !== $county->id, 403);
        $county = $this->authorizeCounty($slug);
        $model = match ($entityType) {
            'attraction' => CountyTourismAttraction::class,
            'hotel' => CountyHotel::class,
            'product' => CountyProduct::class,
            default => abort(422, 'Unknown entity type'),
        };
        \App\Models\MediaAsset::forSlot($model, $entityId, '4d_video')->delete();
        return back()->with('success', '4D video removed. The still image shows again.');
    }

    /** Existing 4D video asset per entity, keyed "type-id" for the admin view. */
    protected function video4dMap(County $county): array
    {
        $map = [];
        $icons = [
            'CountyTourismAttraction' => '🏖️',
            'CountyHotel' => '🏨',
            'CountyProduct' => '🛍️',
            'SectorEntity' => '📋',
        ];

        // Pre-fetch all entity IDs ONCE (no N+1)
        $attractions = $county->tourismAttractions()->get(['id', 'name']);
        $hotels = $county->hotels()->get(['id', 'name']);
        $products = $county->products()->get(['id', 'name']);
        $sectorEntities = \App\Models\SectorEntity::where('county_id', $county->id)->get(['id', 'name']);

        $attractionIds = $attractions->pluck('id');
        $hotelIds = $hotels->pluck('id');
        $productIds = $products->pluck('id');
        $sectorEntityIds = $sectorEntities->pluck('id');

        // Single query for all 4D videos across all entity types
        $loadedVideos = \App\Models\MediaAsset::where('slot', '4d_video')
            ->where(function ($q) use ($attractionIds, $hotelIds, $productIds, $sectorEntityIds) {
                if ($attractionIds->isNotEmpty()) {
                    $q->orWhere(fn ($q2) => $q2->where('owner_type', \App\Models\CountyTourismAttraction::class)->whereIn('owner_id', $attractionIds));
                }
                if ($hotelIds->isNotEmpty()) {
                    $q->orWhere(fn ($q2) => $q2->where('owner_type', \App\Models\CountyHotel::class)->whereIn('owner_id', $hotelIds));
                }
                if ($productIds->isNotEmpty()) {
                    $q->orWhere(fn ($q2) => $q2->where('owner_type', \App\Models\CountyProduct::class)->whereIn('owner_id', $productIds));
                }
                if ($sectorEntityIds->isNotEmpty()) {
                    $q->orWhere(fn ($q2) => $q2->where('owner_type', \App\Models\SectorEntity::class)->whereIn('owner_id', $sectorEntityIds));
                }
            })
            ->get()
            ->keyBy(fn ($a) => $a->owner_type . '-' . $a->owner_id);

        // Attractions
        foreach ($attractions as $e) {
            $key = \App\Models\CountyTourismAttraction::class . '-' . $e->id;
            $asset = $loadedVideos->get($key);
            $map["attraction-{$e->id}"] = [
                'name' => $e->name,
                'icon' => '🏖️',
                'entityType' => 'attraction',
                'entityId' => $e->id,
                'video' => $asset?->path,
            ];
        }
        // Hotels
        foreach ($hotels as $e) {
            $key = \App\Models\CountyHotel::class . '-' . $e->id;
            $asset = $loadedVideos->get($key);
            $map["hotel-{$e->id}"] = [
                'name' => $e->name,
                'icon' => '🏨',
                'entityType' => 'hotel',
                'entityId' => $e->id,
                'video' => $asset?->path,
            ];
        }
        // Products
        foreach ($products as $e) {
            $key = \App\Models\CountyProduct::class . '-' . $e->id;
            $asset = $loadedVideos->get($key);
            $map["product-{$e->id}"] = [
                'name' => $e->name,
                'icon' => '🛍️',
                'entityType' => 'product',
                'entityId' => $e->id,
                'video' => $asset?->path,
            ];
        }
        // Sector entities (from sectors tab)
        foreach ($sectorEntities as $e) {
            $key = \App\Models\SectorEntity::class . '-' . $e->id;
            if (!isset($map["attraction-{$e->id}"]) && !isset($map["hotel-{$e->id}"]) && !isset($map["product-{$e->id}"])) {
                $asset = $loadedVideos->get($key);
                $map["sector-{$e->id}"] = [
                    'name' => $e->name,
                    'icon' => '📋',
                    'entityType' => 'sector_entity',
                    'entityId' => $e->id,
                    'video' => $asset?->path,
                ];
            }
        }
        return $map;
    }

    /* ─── PRICES ─── */
    public function updatePrice(Request $request, string $slug)
    {
        $user = Auth::user();
        $county = County::where('slug', $slug)->firstOrFail();
        abort_if(!$user->isAdmin() && $user->county_id !== $county->id, 403);
        $county = $this->authorizeCounty($slug);
        $data = $request->validate([
            'table' => 'required|in:county_products,county_tourism_attractions',
            'id' => 'required|integer',
            'price' => 'required|numeric|min:0',
        ]);
        DB::table($data['table'])->where('id', $data['id'])->update([
            'price' => $data['price'], 'updated_at' => now(),
        ]);
        return back()->with('success', 'Price updated and live on the county page.');
    }

    /* ─── ADVERTISING ─── */
    public function createAd(Request $request, string $slug)
    {
        $user = Auth::user();
        $county = County::where('slug', $slug)->firstOrFail();
        abort_if(!$user->isAdmin() && $user->county_id !== $county->id, 403);
        $county = $this->authorizeCounty($slug);
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'price' => 'required|numeric|min:0',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:10240',
        ]);
        $imageUrl = null;
        if ($request->hasFile('image')) {
            $imageUrl = $request->file('image')->store("counties/{$slug}/ads", 'public');
        }
        // Store as a county_product (appears on the county page immediately)
        $cp = CountyProduct::create([
            'county_id' => $county->id,
            'name' => $data['name'],
            'description' => $data['description'] ?? $data['name'],
            'category' => 'advertised',
            'price' => $data['price'],
            'status' => 'available',
            'is_published' => true,
        ]);
        // Also create an Advertisement record
        Advertisement::create([
            'name' => $data['name'] . " — {$county->name} County",
            'type' => 'county_goods',
            'placement' => $county->slug,
            'user_id' => Auth::id(),
            'image_url' => $imageUrl ? asset("storage/{$imageUrl}") : null,
            'budget' => $data['price'],
            'is_active' => true,
            'starts_at' => now(),
        ]);

        \App\Services\N8nService::fire('county_ad_created', [
            'county' => $slug, 'product' => $data['name'], 'price' => $data['price'],
        ]);
        return back()->with('success', "Ad for {$data['name']} is now live on {$county->name}'s page.");
    }

    /* ─── PACKAGES ─── */
    public function purchasePackage(Request $request, string $slug, PaymentService $payments)
    {
        $user = Auth::user();
        $county = County::where('slug', $slug)->firstOrFail();
        abort_if(!$user->isAdmin() && $user->county_id !== $county->id, 403);
        $county = $this->authorizeCounty($slug);
        $data = $request->validate([
            'plan_slug' => 'required|exists:subscription_plans,slug',
        ]);
        $plan = SubscriptionPlan::where('slug', $data['plan_slug'])->first();
        $planPrice = $plan->price;
        $user = Auth::user();
        $intent = $payments->charge($county, $planPrice, [
            'description' => "{$county->name} County — {$plan->name} plan",
            'phone' => $user->phone ?? '',
        ]);

        \App\Models\Subscription\CountySubscriber::create([
            'county_id' => $county->id,
            'plan_name' => $data['plan_name'] ?? 'Basic',
            'amount_paid' => $planPrice,
            'status' => 'active',
            'started_at' => now(),
            'expires_at' => now()->addMonth(),
        ]);

        return back()->with('success', "{$plan->name} plan purchased (KES {$planPrice}). Payment confirmed.");
    }

    /* ─── REPORTS ─── */
    public function downloadReport(string $slug, string $type)
    {
        $user = Auth::user();
        $county = County::where('slug', $slug)->firstOrFail();
        abort_if(!$user->isAdmin() && $user->county_id !== $county->id, 403);
        $county = $this->authorizeCounty($slug);
        $rows = match ($type) {
            'products' => CountyProduct::where('county_id', $county->id)->get()->toArray(),
            'attractions' => CountyTourismAttraction::where('county_id', $county->id)->get()->toArray(),
            'hotels' => CountyHotel::where('county_id', $county->id)->get()->toArray(),
            default => null,
        };
        abort_unless($rows, 404);

        $csv = implode(',', array_keys($rows[0] ?? [])) . "\n";
        foreach ($rows as $r) {
            $csv .= '"' . implode('","', array_map(fn ($v) => str_replace('"', '""', (string) $v), array_values($r))) . "\"\n";
        }
        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$county->slug}_{$type}_report.csv\"",
        ]);
    }
}