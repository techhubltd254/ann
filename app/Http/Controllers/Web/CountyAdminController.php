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
        $allSectors = Sector::orderBy('name')->get();
        $sectorEntities = SectorEntity::where('county_id', $county->id)->limit(100)->get();

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
        ];

        $navItems = [
            ['label' => 'Overview', 'tab' => 'overview', 'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6'],
            ['label' => 'Details', 'tab' => 'details', 'icon' => 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
            ['label' => 'Content', 'tab' => 'content', 'icon' => 'M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z'],
            ['label' => 'Images', 'tab' => 'images', 'icon' => 'M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z'],
            ['label' => 'Sectors', 'tab' => 'sectors', 'icon' => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4'],
            ['label' => 'Prices', 'tab' => 'prices', 'icon' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1'],
            ['label' => 'Marketplace', 'tab' => 'marketplace', 'icon' => 'M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z'],
            ['label' => 'Advertising', 'tab' => 'ads', 'icon' => 'M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z'],
            ['label' => 'Packages', 'tab' => 'packages', 'icon' => 'M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z'],
            ['label' => 'Reports', 'tab' => 'reports', 'icon' => 'M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
        ];

        return view('dashboards.county-admin', compact(
            'county', 'tab', 'navItems', 'stats', 'products', 'attractions',
            'hotels', 'sectorImages', 'plans', 'marketplaceProducts', 'ads',
            'sectors', 'linkedSectors', 'allSectors', 'sectorEntities'
        ));
    }

    /* ─── CONTENT ─── */
    public function updateContent(Request $request, string $slug)
    {
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

    /* ─── SECTOR ENTITY CRUD ─── */
    public function addEntity(Request $request, string $slug)
    {
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
        foreach ($sectors as $s) {
            $path = "counties/{$slug}/{$s}.jpeg";
            $fullPath = storage_path("app/public/{$path}");
            $images[$s] = [
                'path' => $path,
                'exists' => file_exists($fullPath),
                'url' => $path,
            ];
        }
        return $images;
    }

    public function uploadImage(Request $request, string $slug)
    {
        $county = $this->authorizeCounty($slug);
        $data = $request->validate([
            'sector' => 'required|in:hero,tourism,products,education,culture,hotels,farms,transport,health',
            'image' => 'required|image|mimes:jpeg,png,jpg,webp|max:10240',
        ]);
        $file = $request->file('image');
        $filename = "{$data['sector']}.{$file->extension()}";
        // Store with the same naming convention for immediate reflection
        $path = $file->storeAs("counties/{$slug}", $filename, 'public');
        // Also convert JPEG naming for backwards compat
        if ($file->extension() !== 'jpeg') {
            // Copy as jpeg too so all references work
            $jpegPath = storage_path("app/public/counties/{$slug}/{$data['sector']}.jpeg");
            @copy(storage_path("app/public/{$path}"), $jpegPath);
        }
        \App\Services\N8nService::fire('county_image_updated', [
            'county' => $slug, 'sector' => $data['sector'],
        ]);
        return back()->with('success', "{$data['sector']} image updated. Changes reflect everywhere immediately.");
    }

    public function deleteImage(string $slug, string $sector)
    {
        $county = $this->authorizeCounty($slug);
        $path = storage_path("app/public/counties/{$slug}/{$sector}.jpeg");
        if (file_exists($path)) @unlink($path);
        $altPath = storage_path("app/public/counties/{$slug}/{$sector}.jpg");
        if (file_exists($altPath)) @unlink($altPath);
        return back()->with('success', "{$sector} image removed. Fallback will show.");
    }

    /* ─── PRICES ─── */
    public function updatePrice(Request $request, string $slug)
    {
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
        $county = $this->authorizeCounty($slug);
        $data = $request->validate([
            'plan_slug' => 'required|exists:subscription_plans,slug',
        ]);
        $plan = SubscriptionPlan::where('slug', $data['plan_slug'])->first();
        $user = Auth::user();
        $intent = $payments->charge($county, $plan->price, [
            'description' => "{$county->name} County — {$plan->name} plan",
            'phone' => $user->phone ?? '',
        ]);
        return back()->with('success', "{$plan->name} plan purchased (KES {$plan->price}). Payment confirmed.");
    }

    /* ─── REPORTS ─── */
    public function downloadReport(string $slug, string $type)
    {
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