<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Agency;
use App\Models\County;
use App\Models\CountyInstitution;
use App\Models\EscrowTransaction;
use App\Models\ExperienceBooking;
use App\Models\Marketplace\Order;
use App\Models\Marketplace\Product;
use App\Models\MediaAsset;
use App\Models\Ministry;
use App\Models\Payment\PaymentIntent;
use App\Models\User;
use App\Services\MediaLibraryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;

/**
 * KICC Overall Admin Portal — the platform owner's god-mode.
 *
 * Controls all four exhibitor tiers: national government, counties,
 * private exhibitors, and the marketplace itself. Every entity links
 * out to its independent public website from here.
 */
class KiccAdminController extends Controller
{
    protected function authorizeKicc(): void
    {
        if (!Auth::user()?->hasRole('kicc_admin')) {
            abort(403, 'KICC admin access required.');
        }
    }

    public function index(Request $request)
    {
        $this->authorizeKicc();
        $tab = $request->get('tab', 'overview');

        // Cached 60s (admin TTL); busted by CacheSyncService::kicc() on write.
        $page = (int) $request->get('page', 1);
        $dash = \Illuminate\Support\Facades\Cache::remember(
            "kicc_admin_dash_{$tab}_{$page}",
            config('kicc.cache_ttl.admin', 60),
            function () {
                $stats = [
            'counties' => County::count(),
            'ministries' => Ministry::count(),
            'agencies' => Agency::count(),
            'exhibitors' => User::where('account_type', 'exhibitor')->count(),
            'tradeBoards' => User::where('email', 'like', 'trade@%.kicc.go.ke')->count(),
            'products' => Product::active()->count(),
            'orders' => Order::count(),
            'users' => User::count(),
            'payments' => PaymentIntent::where('status', 'confirmed')->sum('amount'),
            'escrowHeld' => EscrowTransaction::where('status', 'held')->sum('amount'),
            'escrowTotal' => EscrowTransaction::sum('amount'),
        ];

        // County rollup: trade stats per county — 2 aggregate queries, no N+1
        $productCounts = Product::selectRaw('county_id, COUNT(*) as c')->groupBy('county_id')->pluck('c', 'county_id');
        $tradeVolumes = EscrowTransaction::join('users', 'escrow_transactions.seller_id', '=', 'users.id')
            ->whereNotNull('users.county_id')
            ->selectRaw('users.county_id, SUM(escrow_transactions.amount) as v')
            ->groupBy('users.county_id')->pluck('v', 'county_id');
        $institutionCounts = \App\Models\CountyInstitution::selectRaw('county_id, COUNT(*) as c')
            ->where('is_published', true)->groupBy('county_id')->pluck('c', 'county_id');

        // Load hero video assets for all counties in one batch
        $heroAssets = MediaAsset::where('owner_type', \App\Models\County::class)
            ->whereIn('owner_id', County::pluck('id'))
            ->where('slot', 'hero_video')
            ->ready()
            ->with('derivatives')
            ->get()
            ->keyBy('owner_id');

        $counties = County::orderBy('name')->paginate(50)->map(function ($c) use ($productCounts, $tradeVolumes, $institutionCounts, $heroAssets) {
            $c->product_count = $productCounts[$c->id] ?? 0;
            $c->trade_volume = $tradeVolumes[$c->id] ?? 0;
            $c->institution_count = $institutionCounts[$c->id] ?? 0;
            $asset = $heroAssets->get($c->id);
            $c->hero_video_url = $asset?->mp4Url() ?? $asset?->url();
            $c->hero_thumbnail = $asset?->posterUrl() ?? $asset?->thumbnailUrl();
            $c->hero_asset_id = $asset?->id;
            return $c;
        });

        $exhibitors = User::where('account_type', 'exhibitor')->with('county')->paginate(50);
        $exhCounts = Product::selectRaw('user_id, COUNT(*) as c')->whereIn('user_id', $exhibitors->pluck('id'))
            ->groupBy('user_id')->pluck('c', 'user_id');
        $exhibitors->each(fn ($u) => $u->product_count = $exhCounts[$u->id] ?? 0);

        $ministries = Ministry::with('agencies')->orderBy('name')->paginate(50);
        $orders = Order::with('items')->latest()->paginate(50);
        $escrows = EscrowTransaction::with('buyer', 'seller')->latest()->paginate(50);
        $users = User::with('roles')->latest()->paginate(50);
        $institutions = \App\Models\CountyInstitution::where('is_published', true)->with('county')->orderBy('name')->paginate(50);
        $experienceBookings = ExperienceBooking::with('user', 'destination', 'originCounty')
            ->latest()
            ->take(50)
            ->get();
        $experienceStats = [
            'total' => ExperienceBooking::count(),
            'pending' => ExperienceBooking::where('status', 'pending')->count(),
            'confirmed' => ExperienceBooking::where('status', 'confirmed')->count(),
            'cancelled' => ExperienceBooking::where('status', 'cancelled')->count(),
            'revenue' => ExperienceBooking::where('status', 'confirmed')->sum('grand_total'),
        ];

        // Live stream management for the admin
        $streams = \App\Models\LiveStream::with('exhibition', 'county', 'user')
            ->latest()
            ->take(50)
            ->get();
        $streamStats = [
            'total' => \App\Models\LiveStream::count(),
            'live' => \App\Models\LiveStream::where('status', 'live')->count(),
            'idle' => \App\Models\LiveStream::where('status', 'idle')->count(),
            'ended' => \App\Models\LiveStream::where('status', 'ended')->count(),
            'viewers' => \App\Models\LiveStream::sum('viewer_count'),
        ];
        $adminExhibitions = \App\Models\Exhibition::where('status', 'published')->orderBy('start_date', 'desc')->get();
        $adminCounties = \App\Models\County::orderBy('name')->get(['id', 'name', 'slug']);

        $heroAsset = MediaAsset::resolveSlot('landing_page', 1, 'hero_video');

        // Subscription plans (Exhibitor Packages)
        $plans = \App\Models\SubscriptionPlan::where('is_active', true)->orderBy('sort_order')->get();
        $allPlans = \App\Models\SubscriptionPlan::orderBy('sort_order')->get();

        // Provider certification queue (pending services across travel providers).
        // Each source is guarded — a missing table must never 500 the admin.
        $pendingServices = collect();
        foreach ([
            ['flight_inventory', 'is_active', 0, fn ($s) => 'Flight seat inventory', 'price'],
            ['hotel_rooms', 'is_active', 0, fn ($s) => "Room: {$s->name}", 'price_per_night'],
            ['airport_transfers', 'is_active', 0, fn ($s) => "Transfer: {$s->provider_name} ({$s->vehicle_type})", 'price'],
            ['flights', 'status', 'pending', fn ($s) => "Flight: {$s->flight_number}", 'base_price'],
        ] as [$table, $whereCol, $whereVal, $labelFn, $priceCol]) {
            try {
                $rows = \Illuminate\Support\Facades\DB::table($table)->where($whereCol, $whereVal)->limit(20)->get();
            } catch (\Throwable $e) {
                $rows = collect();
            }
            $pendingServices = $pendingServices->merge($rows->map(fn ($s) => [
                'table' => $table, 'id' => $s->id,
                'label' => $labelFn($s), 'price' => $s->{$priceCol} ?? null,
            ]));
        }

                return compact(
                    'stats', 'counties', 'exhibitors', 'ministries', 'orders', 'escrows', 'users',
                    'providers', 'institutions', 'pendingServices', 'plans', 'allPlans',
                    'experienceBookings', 'experienceStats', 'streams', 'streamStats',
                    'adminExhibitions', 'adminCounties', 'heroAsset',
                );
            }
        );

        extract($dash);

        $navItems = [
            ['label' => 'Overview', 'tab' => 'overview', 'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6'],
            ['label' => 'Sub-Portals', 'tab' => 'portals', 'icon' => 'M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z'],
            ['label' => 'Counties (47)', 'tab' => 'counties', 'icon' => 'M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7'],
            ['label' => 'Institutions', 'tab' => 'institutions', 'icon' => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4'],
            ['label' => 'National Govt', 'tab' => 'national', 'icon' => 'M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M3 3v18h18V3z'],
            ['label' => 'Exhibitors', 'tab' => 'exhibitors', 'icon' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z'],
            ['label' => 'Orders', 'tab' => 'orders', 'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2'],
            ['label' => 'Providers', 'tab' => 'providers', 'icon' => 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z'],
            ['label' => 'Escrow', 'tab' => 'escrow', 'icon' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1'],
            ['label' => 'Experiences', 'tab' => 'experiences', 'icon' => 'M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7'],
            ['label' => 'Live Events', 'tab' => 'live_events', 'icon' => 'M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z'],
            ['label' => 'Users', 'tab' => 'users', 'icon' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197'],
            ['label' => 'Hero Media', 'tab' => 'hero_media', 'icon' => 'M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z'],
            ['label' => 'Packages', 'tab' => 'packages', 'icon' => 'M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z'],
            ['label' => 'Analytics', 'tab' => 'analytics', 'icon' => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z'],
        ];

        $analytics = app(\App\Services\AnalyticsService::class)->forKicc($stats);

        return view('kicc-mother-admin', compact(
            'stats', 'counties', 'exhibitors', 'ministries',
            'orders', 'escrows', 'users', 'providers', 'institutions',
            'pendingServices', 'navItems', 'tab', 'heroAsset', 'analytics',
            'plans', 'allPlans', 'experienceBookings', 'experienceStats',
            'streams', 'streamStats', 'adminExhibitions', 'adminCounties',
        ));
    }

    /** KICC releases escrow funds to a seller after delivery confirmation. */
    public function releaseEscrow(int $id)
    {
        $this->authorizeKicc();
        $escrow = EscrowTransaction::findOrFail($id);
        $steps = collect($escrow->steps ?? [])->map(fn ($s) => array_merge($s, ['done' => true]))->values()->all();
        $escrow->update(['status' => 'released', 'steps' => $steps, 'current_step' => 4, 'released_at' => now()]);
        app(\App\Services\CacheSyncService::class)->kicc();
        return redirect()->route('kicc.admin', ['tab' => 'escrow'])->with('success', "Escrow {$escrow->escrow_id} released to {$escrow->seller?->name}.");
    }

    /** KICC certifies a provider's service/price change (govt certification). */
    public function approveService(string $table, int $id)
    {
        $this->authorizeKicc();
        $allowed = ['flight_inventory', 'hotel_rooms', 'airport_transfers', 'flights'];
        abort_unless(in_array($table, $allowed), 404);

        $update = ['is_active' => 1, 'updated_at' => now()];
        if ($table === 'flights') $update = ['status' => 'active', 'updated_at' => now()];
        \Illuminate\Support\Facades\DB::table($table)->where('id', $id)->update($update);

        \App\Services\N8nService::fire('provider_service_approved', ['table' => $table, 'id' => $id]);
        app(\App\Services\CacheSyncService::class)->kicc();
        return redirect()->route('kicc.admin', ['tab' => 'providers'])->with('success', 'Service certified and now live.');
    }

    /** Run an Artisan command from the admin panel (superadmin only). */
    public function runCommand(Request $request)
    {
        $this->authorizeKicc();

        $validated = $request->validate([
            'command' => 'required|string|max:500',
        ]);

        $allowed = [
            'cache:clear', 'config:clear', 'route:clear', 'view:clear',
            'optimize:clear', 'optimize', 'migrate', 'migrate:fresh',
            'queue:restart', 'schedule:run', 'horizon:snapshot',
            'dba:index-audit', 'search:index-es',
            'analytics:trends', 'recommendations:build',
            'embeddings:build', 'vendors:score',
        ];

        $cmd = $validated['command'];
        // Only allow safe commands
        $baseCmd = explode(' ', $cmd)[0];
        if (! in_array($baseCmd, $allowed)) {
            return back()->withErrors(['command' => "Command '$baseCmd' is not in the allowed list."]);
        }

        $exitCode = Artisan::call($cmd);
        $output = Artisan::output();

        return back()->with('artisan_result', [
            'command' => $cmd,
            'exit_code' => $exitCode,
            'output' => $output,
        ]);
    }

    public function uploadCountyHero(Request $request, string $slug, MediaLibraryService $library)
    {
        $this->authorizeKicc();

        $county = County::where('slug', $slug)->firstOrFail();

        $request->validate([
            'video' => ['required', 'file', 'mimes:mp4,webm,mov,avi', 'max:512000'],
        ]);

        // Delete old hero asset for this county
        MediaAsset::forSlot(County::class, $county->id, 'hero_video')->delete();

        $asset = $library->store($request->file('video'), [
            'owner_type' => County::class,
            'owner_id' => $county->id,
            'slot' => 'hero_video',
            'disk' => 'public',
            'alt_text' => $county->name . ' County Hero Video',
        ]);

        $asset->forceFill(['status' => 'ready'])->save();
        $asset->derivatives()->create([
            'kind' => 'video_mp4',
            'path' => $asset->path,
            'mime' => $asset->mime,
            'size_bytes' => $asset->size_bytes,
            'width' => $asset->width,
            'height' => $asset->height,
            'variant' => '1080p',
        ]);

        app(\App\Services\CacheSyncService::class)->county(\App\Models\County::where('slug', $slug)->value('id'));
        return redirect()->route('kicc.admin', ['tab' => 'counties'])->with('success', $county->name . ' hero video uploaded.');
    }

    /** Update an exhibitor subscription plan from the mother admin. */
    public function updatePlan(Request $request, int $id)
    {
        $this->authorizeKicc();
        $plan = \App\Models\SubscriptionPlan::findOrFail($id);
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'max_booths' => 'required|integer|min:1',
            'is_active' => 'nullable|boolean',
            'description' => 'nullable|string|max:2000',
        ]);
        $plan->update(array_merge($data, [
            'is_active' => $request->boolean('is_active'),
        ]));
        \App\Services\N8nService::fire('package_updated', ['plan_id' => $plan->id, 'name' => $plan->name, 'price' => $plan->price]);
        app(\App\Services\CacheSyncService::class)->kicc();
        return redirect()->route('kicc.admin', ['tab' => 'packages'])->with('success', "Package '{$plan->name}' updated.");
    }

    public function uploadHeroVideo(Request $request, MediaLibraryService $library)
    {
        $this->authorizeKicc();

        $request->validate([
            'video' => ['required', 'file', 'mimes:mp4,webm,mov,avi', 'max:1024000'],
        ]);

        MediaAsset::forSlot('landing_page', 1, 'hero_video')->delete();

        $asset = $library->store($request->file('video'), [
            'owner_type' => 'landing_page',
            'owner_id' => 1,
            'slot' => 'hero_video',
            'disk' => 'public',
            'alt_text' => 'KICC Landing Page Hero Video',
        ]);

        $asset->forceFill(['status' => 'ready'])->save();
        $asset->derivatives()->create([
            'kind' => 'video_mp4',
            'path' => $asset->path,
            'mime' => $asset->mime,
            'size_bytes' => $asset->size_bytes,
            'width' => $asset->width,
            'height' => $asset->height,
            'variant' => '1080p',
        ]);

        app(\App\Services\CacheSyncService::class)->kicc();
        return redirect()->route('kicc.admin', ['tab' => 'hero_media'])->with('success', 'Hero video uploaded and set as active.');
    }

    public function deleteHeroVideo()
    {
        $this->authorizeKicc();

        $asset = MediaAsset::forSlot('landing_page', 1, 'hero_video')->first();
        if ($asset) {
            app(MediaLibraryService::class)->delete($asset);
        }

        app(\App\Services\CacheSyncService::class)->kicc();
        return redirect()->route('kicc.admin', ['tab' => 'hero_media'])->with('success', 'Hero video removed. Homepage will use fallback video.');
    }
}
