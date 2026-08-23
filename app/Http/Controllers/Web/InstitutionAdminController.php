<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\County;
use App\Models\CountyInstitution;
use App\Models\EscrowTransaction;
use App\Models\Marketplace\Order;
use App\Models\Marketplace\Product;
use App\Models\MediaAsset;
use App\Services\InstitutionSyncService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * InstitutionAdminController — the per-institution admin panel.
 * Strictly scoped: institution admins can ONLY manage their own institution.
 */
class InstitutionAdminController extends Controller
{
    protected function authorizeInstitution(string $slug): CountyInstitution
    {
        $user = Auth::user();
        $institution = CountyInstitution::where('slug', $slug)->firstOrFail();

        $allowed = $user->hasRole('kicc_admin')
            || ($user->institution_id === $institution->id)
            || ($user->hasRole('county_admin') && $user->county_id === $institution->county_id);

        abort_unless($allowed, 403, 'You do not have access to this institution.');

        return $institution;
    }

    public function dashboard(string $slug, Request $request)
    {
        $institution = $this->authorizeInstitution($slug);
        $tab = $request->get('tab', 'overview');

        // ── Analytics (real data) ──
        $ownerId = $institution->user_id;

        // Revenue: released escrow transactions to this institution's seller
        $revenue = EscrowTransaction::where('seller_id', $ownerId ?? -1)
            ->where('status', 'released')
            ->sum('amount');

        $revenuePrevMonth = EscrowTransaction::where('seller_id', $ownerId ?? -1)
            ->where('status', 'released')
            ->where('created_at', '<', now()->startOfMonth())
            ->sum('amount');

        // Orders: order_items joined products where products.user_id = owner
        $orderStats = DB::table('order_items')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->where('products.user_id', $ownerId ?? -1)
            ->select(
                DB::raw('COUNT(DISTINCT order_items.order_id) as total_orders'),
                DB::raw('COALESCE(SUM(order_items.quantity),0) as total_qty'),
                DB::raw('COALESCE(SUM(order_items.total),0) as total_amount'),
                DB::raw('SUM(CASE WHEN order_items.created_at >= NOW() - INTERVAL 30 DAY THEN 1 ELSE 0 END) as orders_30d')
            )
            ->first();

        $totalOrders = (int) ($orderStats->total_orders ?? 0);
        $orders30d = (int) ($orderStats->orders_30d ?? 0);
        $orderGrowth = $totalOrders > 0 ? round(($orders30d / max($totalOrders, 1)) * 100, 2) : 0;

        // Products (global marketplace)
        $marketplaceProducts = Product::with(['variants', 'images'])
            ->where('user_id', $ownerId ?? -1)
            ->latest()
            ->get();

        // County products (synced)
        $countyProducts = \App\Models\CountyProduct::where('county_id', $institution->county_id)
            ->where('name', 'like', $institution->name . '%')
            ->get();

        // Videos
        $videos = MediaAsset::where('owner_type', CountyInstitution::class)
            ->where('owner_id', $institution->id)
            ->get()
            ->merge(
                MediaAsset::where('owner_type', \App\Models\SectorEntity::class)
                    ->whereIn('owner_id', $institution->sectorEntities()->pluck('id'))
                    ->get()
            );

        // Transactions (recent order_items)
        $transactions = DB::table('order_items')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->leftJoin('users', 'orders.user_id', '=', 'users.id')
            ->where('products.user_id', $ownerId ?? -1)
            ->select(
                'orders.order_number',
                'order_items.product_name',
                'order_items.quantity',
                'order_items.total',
                'orders.payment_status',
                'orders.placed_at',
                'users.name as customer_name'
            )
            ->orderByDesc('order_items.created_at')
            ->limit(10)
            ->get();

        // Monthly bar data (last 12 months)
        $monthly = collect(range(11, 0))->map(function ($i) use ($ownerId) {
            $month = now()->startOfMonth()->subMonths($i);
            $next = $month->copy()->addMonth();
            $newUser = DB::table('order_items')
                ->join('products', 'order_items.product_id', '=', 'products.id')
                ->where('products.user_id', $ownerId ?? -1)
                ->whereBetween('order_items.created_at', [$month, $next])
                ->sum('order_items.total');
            $existing = DB::table('order_items')
                ->join('products', 'order_items.product_id', '=', 'products.id')
                ->where('products.user_id', '!=', $ownerId ?? -1)
                ->whereBetween('order_items.created_at', [$month, $next])
                ->sum('order_items.total');
            return [
                'month' => $month->format('M'),
                'new' => (float) $newUser,
                'existing' => (float) $existing,
            ];
        });

        $customers = DB::table('orders')
            ->join('order_items', 'orders.id', '=', 'order_items.order_id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->join('users', 'orders.user_id', '=', 'users.id')
            ->where('products.user_id', $ownerId ?? -1)
            ->select('users.name', 'users.email', 'users.phone', DB::raw('COUNT(DISTINCT orders.id) as orders_count'), DB::raw('SUM(order_items.total) as total_spent'))
            ->groupBy('users.id', 'users.name', 'users.email', 'users.phone')
            ->orderByDesc('total_spent')
            ->limit(20)
            ->get();

        $sectorEntities = $institution->sectorEntities()->with('sector')->get();

        // Nav
        $navItems = [
            ['label' => 'Dashboard', 'tab' => 'overview', 'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6'],
            ['label' => 'Products', 'tab' => 'products', 'icon' => 'M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z'],
            ['label' => 'Videos', 'tab' => 'videos', 'icon' => 'M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z'],
            ['label' => 'Transactions', 'tab' => 'transactions', 'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2'],
            ['label' => 'Analytics', 'tab' => 'analytics', 'icon' => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z'],
            ['label' => 'Customers', 'tab' => 'customers', 'icon' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z'],
            ['label' => 'Production Chain', 'tab' => 'production', 'icon' => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4'],
            ['label' => 'Sector Mapping', 'tab' => 'sectors', 'icon' => 'M4 6h16M4 10h16M4 14h16M4 18h16'],
            ['label' => 'Profile', 'tab' => 'profile', 'icon' => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z'],
            ['label' => 'Team', 'tab' => 'team', 'icon' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197'],
        ];

        $analytics = app(\App\Services\AnalyticsService::class)->forInstitution($institution);

        return view('dashboards.institution-admin', compact(
            'institution', 'tab', 'navItems',
            'revenue', 'revenuePrevMonth', 'totalOrders', 'orderGrowth', 'orders30d',
            'marketplaceProducts', 'countyProducts', 'videos', 'transactions',
            'monthly', 'customers', 'sectorEntities', 'analytics',
        ));
    }

    /* ─── PROFILE ─── */
    public function updateProfile(Request $request, string $slug)
    {
        $institution = $this->authorizeInstitution($slug);
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'nullable|string|max:100',
            'description' => 'nullable|string|max:5000',
            'story' => 'nullable|string|max:50000',
            'location' => 'nullable|string|max:255',
            'headquarters' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'website' => 'nullable|url|max:255',
            'founded_year' => 'nullable|integer|min:1800|max:2100',
            'lat' => 'nullable|numeric',
            'lng' => 'nullable|numeric',
        ]);

        $institution->update($data);

        return back()->with('success', 'Profile updated. Click "Sync Now" to push changes to the county portal & marketplace.');
    }

    public function uploadLogo(Request $request, string $slug)
    {
        $institution = $this->authorizeInstitution($slug);
        $data = $request->validate([
            'logo' => 'required|image|mimes:jpeg,png,jpg,webp|max:5120',
            'cover' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:10240',
        ]);

        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store("institutions/{$institution->slug}/logo", 'r2');
            $institution->update(['logo_url' => media_url() . '/' . $path]);
        }
        if ($request->hasFile('cover')) {
            $path = $request->file('cover')->store("institutions/{$institution->slug}/cover", 'r2');
            $institution->update(['cover_image_url' => media_url() . '/' . $path]);
        }

        return back()->with('success', 'Images uploaded.');
    }

    /* ─── PRODUCTION CHAIN ─── */
    public function updateProduction(Request $request, string $slug)
    {
        $institution = $this->authorizeInstitution($slug);
        $data = $request->validate([
            'production_chain' => 'nullable|array',
            'production_chain.*.step' => 'nullable|string|max:255',
            'production_chain.*.description' => 'nullable|string|max:5000',
        ]);

        $chain = collect($data['production_chain'] ?? [])
            ->filter(fn ($r) => !empty($r['step']) || !empty($r['description']))
            ->values()
            ->map(fn ($r, $i) => ['step' => $r['step'] ?? "Step " . ($i + 1), 'description' => $r['description'] ?? ''])
            ->all();

        $institution->update(['production_chain' => $chain]);

        return back()->with('success', 'Production chain updated.');
    }

    /* ─── SECTOR MAPPING ─── */
    public function updateSectors(Request $request, string $slug)
    {
        $institution = $this->authorizeInstitution($slug);
        $data = $request->validate([
            'sector_mappings' => 'nullable|array',
            'sector_mappings.*.sector_slug' => 'required|string',
            'sector_mappings.*.entry_name' => 'nullable|string|max:255',
            'sector_mappings.*.entry_type' => 'nullable|string|max:100',
            'sector_mappings.*.entry_fee' => 'nullable|numeric|min:0',
            'sector_mappings.*.description' => 'nullable|string|max:5000',
            'sector_mappings.*.location' => 'nullable|string|max:255',
        ]);

        $mappings = collect($data['sector_mappings'] ?? [])
            ->filter(fn ($r) => !empty($r['sector_slug']))
            ->values()
            ->all();

        $institution->update(['sector_mappings' => $mappings]);

        return back()->with('success', 'Sector mappings saved. Run "Sync Now" to publish across the county.');
    }

    /* ─── PRODUCTS ─── */
    public function storeProduct(Request $request, string $slug)
    {
        $institution = $this->authorizeInstitution($slug);
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'unit' => 'nullable|string|max:50',
            'category' => 'nullable|string|max:100',
            'description' => 'nullable|string|max:5000',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:10240',
            'stock' => 'nullable|integer|min:0',
        ]);

        $imageUrl = null;
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store("institutions/{$institution->slug}/products", 'r2');
            $imageUrl = media_url() . '/' . $path;
        }

        $products = $institution->products ?? [];
        $products[] = [
            'name' => $data['name'],
            'price' => $data['price'],
            'unit' => $data['unit'] ?? 'unit',
            'category' => $data['category'] ?? 'Food',
            'description' => $data['description'] ?? '',
            'image_url' => $imageUrl,
            'stock' => $data['stock'] ?? 100,
        ];
        $institution->update(['products' => $products]);

        // Immediate sync for this product
        app(InstitutionSyncService::class)->sync($institution);

        return back()->with('success', "Product \"{$data['name']}\" added & synced to county + marketplace.");
    }

    public function deleteProduct(Request $request, string $slug, int $index)
    {
        $institution = $this->authorizeInstitution($slug);
        $products = $institution->products ?? [];
        if (isset($products[$index])) {
            unset($products[$index]);
            $institution->update(['products' => array_values($products)]);
        }

        app(InstitutionSyncService::class)->sync($institution);

        return back()->with('success', 'Product removed & synced.');
    }

    /* ─── VIDEOS (unlimited) ─── */
    public function uploadVideo(Request $request, string $slug)
    {
        $institution = $this->authorizeInstitution($slug);
        $data = $request->validate([
            'video' => 'required|file|mimes:mp4,webm,mov|max:512000',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:5000',
            'entity_key' => 'nullable|string|max:100',
        ]);

        $file = $request->file('video');
        $path = $file->storeAs(
            "institutions/{$institution->slug}/videos",
            Str::slug($data['title']) . '-' . Str::lower(Str::random(5)) . '.' . $file->getClientOriginalExtension(),
            'r2'
        );

        $videos = $institution->videos ?? [];
        $videos[] = [
            'title' => $data['title'],
            'description' => $data['description'] ?? '',
            'path' => $path,
            'mime' => $file->getMimeType(),
            'size_bytes' => $file->getSize(),
            'entity_key' => $data['entity_key'] ?? null,
        ];
        $institution->update(['videos' => $videos]);

        app(InstitutionSyncService::class)->sync($institution);

        return back()->with('success', "Video \"{$data['title']}\" uploaded & synced.");
    }

    public function deleteVideo(Request $request, string $slug, int $index)
    {
        $institution = $this->authorizeInstitution($slug);
        $videos = $institution->videos ?? [];
        if (isset($videos[$index])) {
            try {
                Storage::disk('r2')->delete($videos[$index]['path'] ?? '');
            } catch (\Throwable $e) {
            }
            unset($videos[$index]);
            $institution->update(['videos' => array_values($videos)]);
        }

        app(InstitutionSyncService::class)->sync($institution);

        return back()->with('success', 'Video removed & synced.');
    }

    /* ─── SYNC ─── */
    public function sync(string $slug)
    {
        $institution = $this->authorizeInstitution($slug);
        $summary = app(InstitutionSyncService::class)->sync($institution);

        return back()->with('success',
            "Sync complete — {$summary['entities']} entities, {$summary['county_products']} county products, "
            . "{$summary['marketplace_products']} marketplace products, {$summary['attractions']} attractions, "
            . "{$summary['videos']} videos, {$summary['sectors']} sectors."
        );
    }

    /* ─── TEAM ─── */
    public function addTeamMember(Request $request, string $slug)
    {
        $institution = $this->authorizeInstitution($slug);
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
        ]);

        $user = \App\Models\User::where('email', $data['email'])->first();
        if (!$user) {
            $user = \App\Models\User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => bcrypt(Str::random(16)),
                'account_type' => 'institution',
                'institution_id' => $institution->id,
                'status' => 'active',
            ]);
        } else {
            $user->update(['institution_id' => $institution->id, 'account_type' => 'institution']);
        }
        $user->assignRole('institution_admin');

        return back()->with('success', "{$data['email']} added to {$institution->name} team.");
    }

    public function removeTeamMember(Request $request, string $slug, int $userId)
    {
        $institution = $this->authorizeInstitution($slug);
        $user = \App\Models\User::findOrFail($userId);
        if ($user->id === Auth::id()) {
            return back()->withErrors(['team' => 'You cannot remove yourself.']);
        }
        $user->update(['institution_id' => null, 'account_type' => 'individual']);
        $user->removeRole('institution_admin');

        return back()->with('success', 'Team member removed.');
    }
}
