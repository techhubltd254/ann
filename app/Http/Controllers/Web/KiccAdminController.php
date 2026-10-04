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
use App\Models\Pipeline\DynamicPipeline;
use App\Models\User;
use App\Models\Venue;
use App\Services\AuditLogger;
use App\Events\EscrowReleased;
use App\Events\GenericDomainEvent;
use App\Events\ProviderServiceChanged;
use App\Services\IntegrationClient;
use App\Services\JournalService;
use App\Services\MediaLibraryService;
use App\Services\PoolEngine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * KICC Overall Admin Portal — the platform owner's god-mode.
 *
 * Controls all four exhibitor tiers: national government, counties,
 * private exhibitors, and the marketplace itself. Every entity links
 * out to its independent public website from here.
 */
class KiccAdminController extends Controller
{
    /**
     * Provider service tables — each entry maps a table name to its
     * activation column and its display column. This is the SINGLE
     * source of truth for approveService/denyService. No raw table
     * names accepted from request context.
     */
    private const PROVIDER_SERVICES = [
        'flight_inventory'  => ['active_col' => 'is_active', 'label_col' => 'price'],
        'hotel_rooms'       => ['active_col' => 'is_active', 'label_col' => 'price_per_night'],
        'airport_transfers' => ['active_col' => 'is_active', 'label_col' => 'price'],
        'flights'           => ['active_col' => 'status',     'label_col' => 'base_price'],
    ];

    protected function authorizedTable(string $table): string
    {
        abort_unless(isset(self::PROVIDER_SERVICES[$table]), 404, 'Unknown provider table.');
        return $table;
    }
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

        // 3D Assets tab redirects to its dedicated admin page
        if ($tab === '3d_assets') {
            return redirect()->route('admin.3d.assets');
        }

        // Cached 60s (admin TTL); busted by CacheSyncService::kicc() on write.
        $page = (int) $request->get('page', 1);
        $buildDash = function () {
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
        $providers = collect();
        // Each source is guarded — a missing table must never 500 the admin.
        $pendingServices = collect();
        foreach (self::PROVIDER_SERVICES as $table => $spec) {
            $col = $spec['active_col'];
            $inactiveVal = $col === 'status' ? 'pending' : 0;
            try {
                $rows = \Illuminate\Support\Facades\DB::table($table)->where($col, $inactiveVal)->limit(20)->get();
            } catch (\Throwable $e) {
                $rows = collect();
            }
            $labelFn = fn ($s) => "{$table}: {$s->id}";
            $priceCol = $spec['label_col'];
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
        };

        $dash = $buildDash();

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
            ['label' => '3D Assets', 'tab' => '3d_assets', 'icon' => 'M17 8l4-4m0-4-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1'],
            ['label' => 'Selling Pool', 'tab' => 'pool', 'icon' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1'],
            ['label' => 'Pipeline Management', 'tab' => 'pipelines', 'icon' => 'M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z'],
            ['label' => 'Experiences', 'tab' => 'experiences', 'icon' => 'M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7'],
            ['label' => 'Live Events', 'tab' => 'live_events', 'icon' => 'M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z'],
            ['label' => 'Users', 'tab' => 'users', 'icon' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197'],
            ['label' => 'Venues', 'tab' => 'venues', 'icon' => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4'],
            ['label' => 'Exhibitor Requests', 'tab' => 'exh_requests', 'icon' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197'],
            ['label' => 'Hero Media', 'tab' => 'hero_media', 'icon' => 'M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z'],
            ['label' => 'Packages', 'tab' => 'packages', 'icon' => 'M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z'],
            ['label' => 'Analytics', 'tab' => 'analytics', 'icon' => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z'],
            ['label' => 'Integration', 'tab' => 'integration', 'icon' => 'M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z'],
            ['label' => 'Pipeline Creator', 'tab' => 'pipeline-creator', 'icon' => 'M12 6v6m0 0v6m0-6h6m-6 0H6'],
            ['label' => 'Earnings', 'tab' => 'earnings', 'icon' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1'],
            ['label' => 'Search Analytics', 'tab' => 'search-analytics', 'icon' => 'M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z'],
            ['label' => 'Cache', 'tab' => 'cache', 'icon' => 'M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15'],
            ['label' => 'Licence Queue', 'tab' => 'licence-queue', 'icon' => 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z'],
            ['label' => 'Pipeline Settings', 'tab' => 'pipeline-settings', 'icon' => 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z'],
        ];

        $analytics = app(\App\Services\AnalyticsService::class)->forKicc($stats);

        // ── Pool data (outside cache — needs real-time accuracy) ──
        $pool = \App\Models\Pool\Pool::where('scope', 'global')->where('is_active', true)->first();
        $poolBalance = $pool?->balance ?? 0;
        $poolContributionsCountyPointer = null; // placeholder
        $poolPendingDistributions = \App\Models\Pool\PoolDistribution::where('status', 'pending')->latest()->take(20)->get();
        $poolPeriodContributions = \App\Models\Pool\PoolContribution::where('period_id', now()->format('Y-m'))
            ->selectRaw('county_id, SUM(pool_share) as total')
            ->groupBy('county_id')->orderByDesc('total')->take(10)->get();

        // ── Pipeline Management (all 202 registered pipelines) ──
        $q_pipelines = request()->get('pipeline_q');
        $plugin_filter = request()->get('pipeline_sector', '');
        $pipelinesQuery = \Illuminate\Support\Facades\DB::table('pipeline_registrations');
        if ($plugin_filter) $pipelinesQuery->where('sector', $plugin_filter);
        if ($q_pipelines) $pipelinesQuery->where(fn($qq) => $qq->where('code', 'like', "%$q_pipelines%")->orWhere('slug', 'like', "%$q_pipelines%"));
        $pipelines = $pipelinesQuery->orderBy('sector')->orderBy('code')->paginate(100)->withQueryString();
        $pipelineSectors = \Illuminate\Support\Facades\DB::table('pipeline_registrations')
            ->selectRaw('sector, COUNT(*) as c')->groupBy('sector')->orderByDesc('c')->get();
        $pipelineStatusBreakdown = \Illuminate\Support\Facades\DB::table('pipeline_registrations')
            ->selectRaw('status, COUNT(*) as c')->groupBy('status')->orderByDesc('c')->get();
        $pipelineTotal = \Illuminate\Support\Facades\DB::table('pipeline_registrations')->count();

        // ── Integration tab (real-time data, no cache) ──
        $integrationHealth = [];
        $algorithmsHealth = [];
        $integrationProviders = [];
        $integrationCredentials = [];
        $integrationLiveCount = 0;
        $integrationWebhookRoutes = [];
        try {
            $intClient = app(IntegrationClient::class);
            $integrationHealth = $intClient->health();
            $integrationProviders = $intClient->providers()['providers'] ?? [];
            $integrationWebhookRoutes = $integrationHealth['routes'] ?? [];
            $integrationLiveCount = count($integrationHealth['live_configured'] ?? []);
            $integrationCredentials = collect($integrationProviders)->filter(fn($p) => count($p['missing']) === 0)->values()->toArray();
        } catch (\Throwable $e) {
            $integrationHealth = ['ok' => false];
        }
        try {
            $resp = Http::timeout(3)->get('http://127.0.0.1:8400/health');
            if ($resp->successful()) $algorithmsHealth = $resp->json();
        } catch (\Throwable $e) {
            $algorithmsHealth = ['status' => 'offline'];
        }

        // ── Pipeline Creator tab ──
        $dynamicPipelines = DynamicPipeline::orderBy('sector')->orderBy('code')->paginate(50);
        $dynamicPipelineTotal = DynamicPipeline::count();
        $dynamicSectors = DynamicPipeline::selectRaw('DISTINCT sector')->pluck('sector')->sort()->values()->toArray();
        $suggestedPipelines = [
            ['code' => 'XR1', 'name' => 'Cashew Nuts Collection', 'sector' => 'crops', 'fee' => '3%', 'description' => 'Collection & bulking of raw cashew from smallholders for processing & export'],
            ['code' => 'XR2', 'name' => 'Macadamia Processing', 'sector' => 'crops', 'fee' => '4%', 'description' => 'Macadamia kernel processing, grading, roasting for local & export market'],
            ['code' => 'XR3', 'name' => 'Avocado Export (Hass)', 'sector' => 'crops', 'fee' => '4%', 'description' => 'Hass avocado export — ripening, packing, phytosanitary compliance'],
            ['code' => 'XR4', 'name' => 'Shea Butter Processing', 'sector' => 'crops', 'fee' => '3%', 'description' => 'Shea nut collection, butter extraction, cosmetic-grade processing'],
            ['code' => 'XR5', 'name' => 'Fish Farming (Aquaculture)', 'sector' => 'fisheries', 'fee' => '3%', 'description' => 'Tilapia and catfish farming, pond management, feed supply, harvest logistics'],
            ['code' => 'XR6', 'name' => 'Bee Keeping & Honey', 'sector' => 'livestock', 'fee' => '3%', 'description' => 'Modern beekeeping, honey extraction, beeswax processing, propolis collection'],
        ];

        // ── Earnings dashboard (per-pipeline revenue) ──
        $earningsQuery = \Illuminate\Support\Facades\DB::table('escrow_transactions')
            ->where('status', 'released');
        $earningsTotal = (float) $earningsQuery->clone()->sum('amount');
        $earningsCount = $earningsQuery->clone()->count();
        $earningsByPipeline = $earningsQuery->clone()
            ->selectRaw('reference_type as code, COUNT(*) as trades, SUM(amount) as gmv')
            ->groupBy('reference_type')
            ->orderByDesc('gmv')
            ->limit(30)
            ->get();
        $earningsByDay = $earningsQuery->clone()
            ->selectRaw('DATE(released_at) as day, SUM(amount) as gmv, COUNT(*) as trades')
            ->groupBy('day')
            ->orderByDesc('day')
            ->limit(14)
            ->get();
        $poolEarnings = (float) (\App\Models\Pool\Pool::where('scope', 'global')->where('is_active', true)->value('balance') ?? 0);
        $poolContribTotal = (float) \App\Models\Pool\PoolContribution::sum('pool_share');

        // Pipeline earn status (which pipelines have earned)
        $earnedCodes = \Illuminate\Support\Facades\DB::table('escrow_transactions')
            ->where('status', 'released')
            ->distinct()->pluck('reference_type')->toArray();
        $allRegisteredCodes = \Illuminate\Support\Facades\DB::table('pipeline_registrations')->pluck('code')->toArray();
        $earnCoverage = [
            'total' => count($allRegisteredCodes),
            'earned' => count(array_intersect($allRegisteredCodes, $earnedCodes)),
        ];

        // ── Licence Queue data ──
        $licences = \App\Models\Pipeline\PipelineLicence::orderByDesc('created_at')->limit(50)->get();
        $regulatorMap = config('kicc.licence-regulators', []);
        $gatedPipelineCodes = collect($regulatorMap)->flatMap(fn ($r) => $r['pipelines'] ?? [])->unique()->values()->toArray();

        // ── Pipeline Settings data ──
        $pipelineSettings = DB::table('pipeline_registrations')
            ->orderBy('sector')->orderBy('code')
            ->get(['code', 'sector', 'status', 'earning_locked', 'regulators', 'economics', 'slug', 'phase']);

        // ── Search Analytics data ──
        $searchQuery = DB::table('search_analytics')
            ->selectRaw('query, engine, COUNT(*) as searches, MAX(created_at) as last_seen')
            ->groupBy('query', 'engine')
            ->orderByDesc('searches')
            ->limit(30)
            ->get();
        $searchSources = DB::table('search_analytics')
            ->selectRaw('source, COUNT(*) as visits')
            ->groupBy('source')
            ->orderByDesc('visits')
            ->get();
        $searchTotal = DB::table('search_analytics')->count();
        $searchEngines = DB::table('search_analytics')
            ->selectRaw('engine, COUNT(*) as c')
            ->groupBy('engine')
            ->orderByDesc('c')
            ->get();

        // ── User & Order Analytics ──
        $analyticsGrowth = \App\Models\User::selectRaw("DATE(created_at) as day, COUNT(*) as registrations")
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('day')->orderBy('day')->pluck('registrations', 'day');
        $analyticsUsers = [
            'total' => \App\Models\User::count(),
            'active' => \App\Models\User::where('status', 'active')->orWhereNull('status')->count(),
            'today' => \App\Models\User::whereDate('created_at', today())->count(),
            'this_week' => \App\Models\User::where('created_at', '>=', now()->startOfWeek())->count(),
            'by_type' => \App\Models\User::selectRaw('account_type, COUNT(*) as c')->groupBy('account_type')->pluck('c', 'account_type'),
        ];
        $analyticsOrders = [
            'total' => \App\Models\Marketplace\Order::count(),
            'today' => \App\Models\Marketplace\Order::whereDate('created_at', today())->count(),
            'total_revenue' => \App\Models\Marketplace\Order::sum('grand_total'),
            'avg_order' => \App\Models\Marketplace\Order::avg('grand_total'),
        ];
        $analyticsEscrows = [
            'total' => \App\Models\EscrowTransaction::count(),
            'released' => \App\Models\EscrowTransaction::where('status', 'released')->count(),
            'total_value' => \App\Models\EscrowTransaction::where('status', 'released')->sum('amount'),
        ];
        $analyticsSearch = [
            'total' => DB::table('search_analytics')->count(),
            'unique_queries' => DB::table('search_analytics')->distinct('query')->count('query'),
            'top_source' => DB::table('search_analytics')->selectRaw('source, COUNT(*) as c')->groupBy('source')->orderByDesc('c')->first(),
        ];

        // ── Cache Stats ──
        $cacheStats = app(\App\Services\CacheService::class)->stats();

        // ── Venues (manageable via admin) ──
        $adminVenues = Venue::orderBy('name')->paginate(50);

        // ── Exhibitor Requests (pending custom/premium setups) ──
        $exhRequests = User::with('county')
            ->where('account_type', 'exhibitor')
            ->where('metadata', 'like', '%"complexity"%')
            ->latest()
            ->take(50)
            ->get();

        return view('kicc-mother-admin', compact(
            'stats', 'counties', 'exhibitors', 'ministries',
            'orders', 'escrows', 'users', 'providers', 'institutions',
            'pendingServices', 'navItems', 'tab', 'heroAsset', 'analytics',
            'plans', 'allPlans', 'experienceBookings', 'experienceStats',
            'streams', 'streamStats', 'adminExhibitions', 'adminCounties',
            'adminVenues', 'exhRequests',
            'pool', 'poolBalance', 'poolPendingDistributions', 'poolPeriodContributions',
            'pipelines', 'pipelineSectors', 'pipelineStatusBreakdown', 'pipelineTotal',
            // Integration tab data
            'integrationHealth', 'algorithmsHealth', 'integrationProviders',
            'integrationCredentials', 'integrationLiveCount',
            'integrationWebhookRoutes',
            // Pipeline Creator tab data
            'dynamicPipelines', 'dynamicPipelineTotal', 'dynamicSectors',
            'suggestedPipelines',
            // Earnings dashboard data
            'earningsTotal', 'earningsCount', 'earningsByPipeline',
            'earningsByDay', 'poolEarnings', 'poolContribTotal',
            'earnedCodes', 'earnCoverage',
            // Licence Queue + Pipeline Settings tab data
            'licences', 'gatedPipelineCodes', 'regulatorMap',
            'pipelineSettings',
            // Search Analytics data
            'searchQuery', 'searchSources', 'searchTotal', 'searchEngines',
            // User & Order Analytics
            'analyticsGrowth', 'analyticsUsers', 'analyticsOrders',
            'analyticsEscrows', 'analyticsSearch',
            // Cache stats
            'cacheStats',
        ));
    }

    /** KICC releases escrow funds — ledger entry + audit trail + pool recalc. */
    public function releaseEscrow(int $id)
    {
        $this->authorizeKicc();
        return DB::transaction(function () use ($id) {
            $escrow = EscrowTransaction::findOrFail($id);
            abort_if($escrow->status === 'released', 422, 'Escrow already released.');

            $steps = collect($escrow->steps ?? [])->map(fn ($s) => array_merge($s, ['done' => true]))->values()->all();
            $escrow->update([
                'status' => 'released',
                'steps' => $steps,
                'current_step' => 4,
                'released_at' => now(),
                'released_by' => Auth::id(),
            ]);

            // Double-entry ledger: debit escrow_liability, credit seller_payable
            JournalService::post([
                'journal_ref' => 'escrow-release-' . $escrow->escrow_id,
                'memo'        => 'Escrow released to seller',
                'entries'     => [
                    ['account' => 'escrow_liability', 'debit' => (float) $escrow->amount, 'credit' => 0.0],
                    ['account' => 'seller_payable',   'debit' => 0.0, 'credit' => (float) $escrow->amount],
                ],
            ]);

            AuditLogger::log(Auth::id(), 'escrow.released', EscrowTransaction::class, $escrow->id, [
                'amount' => (float) $escrow->amount,
                'seller_id' => $escrow->seller_id,
                'buyer_id'  => $escrow->buyer_id,
            ]);

            PoolEngine::recalcFor(period: now()->format('Y-m'), scope: 'global', reason: 'escrow.release');

            event(new EscrowReleased($escrow->escrow_id, (float) $escrow->amount, $escrow->seller_id, $escrow->buyer_id));

            return redirect()->route('kicc.admin', ['tab' => 'escrow'])
                ->with('success', "Escrow {$escrow->escrow_id} released + ledger posted.");
        });
    }

    /** KICC certifies a provider's service/price change (govt certification) — transactional + audited. */
    public function approveService(string $table, int $id)
    {
        $this->authorizeKicc();
        $table = $this->authorizedTable($table);
        $spec = self::PROVIDER_SERVICES[$table];
        $activeCol = $spec['active_col'];

        return DB::transaction(function () use ($table, $id, $activeCol) {
            $update = [$activeCol => $activeCol === 'status' ? 'active' : 1, 'updated_at' => now()];
            $rows = DB::table($table)->where('id', $id)->update($update);
            abort_if($rows === 0, 422, 'Service not found or already approved.');

            AuditLogger::log(Auth::id(), 'provider.approved', $table, $id, [
                'approver_ip' => request()->ip(),
            ]);

            event(new ProviderServiceChanged($table, $id, 'approved'));
            return redirect()->route('kicc.admin', ['tab' => 'providers'])->with('success', 'Service certified and now live.');
        });
    }

    public function denyService(string $table, int $id, Request $request)
    {
        $this->authorizeKicc();
        $table = $this->authorizedTable($table);
        $spec = self::PROVIDER_SERVICES[$table];
        $activeCol = $spec['active_col'];

        $data = $request->validate(['reason' => 'required|string|max:500']);

        return DB::transaction(function () use ($table, $id, $data, $activeCol) {
            $update = [$activeCol => $activeCol === 'status' ? 'denied' : 0, 'denial_reason' => $data['reason'], 'updated_at' => now()];
            DB::table($table)->where('id', $id)->update($update);

            AuditLogger::log(Auth::id(), 'service.denied', null, $id, [
                'table' => $table,
                'reason' => $data['reason'],
            ]);
            return redirect()->route('kicc.admin', ['tab' => 'providers'])
                ->with('success', 'Service denied with reason recorded.');
        });
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
            'db:seed --class=MurangaLiveInstitutionsSeeder',
            'db:seed --class=InstitutionSeeder',
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
        event(new GenericDomainEvent('package_updated', ['plan_id' => $plan->id, 'name' => $plan->name, 'price' => $plan->price], n8nEventName: 'package_updated'));;
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

    /** Store a new dynamic pipeline. */
    public function storePipeline(Request $request)
    {
        $this->authorizeKicc();

        $data = $request->validate([
            'code' => 'required|string|max:20|unique:dynamic_pipelines,code',
            'name' => 'required|string|max:200',
            'sector' => 'required|string|max:100',
            'description' => 'nullable|string|max:2000',
            'mechanism' => 'nullable|string|max:50',
            'fee_rate' => 'nullable|numeric|min:0|max:100',
        ]);

        $data['slug'] = Str::slug($data['name']);
        $data['is_active'] = true;
        if (empty($data['mechanism'])) $data['mechanism'] = 'commission';
        if (empty($data['fee_rate'])) $data['fee_rate'] = 4.00;

        $pipeline = DynamicPipeline::create($data);

        // Also register in pipeline_registrations for unified tracking
        \Illuminate\Support\Facades\DB::table('pipeline_registrations')->insert([
            'code' => $pipeline->code,
            'sector' => $pipeline->sector,
            'slug' => $pipeline->slug,
            'parent' => null,
            'phase' => 3,
            'status' => 'built',
            'economics' => json_encode([
                'model' => $pipeline->mechanism,
                'take_rate_pct' => $pipeline->fee_rate,
                'description' => $pipeline->description,
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        app(\App\Services\CacheSyncService::class)->kicc();
        return redirect()->route('kicc.admin', ['tab' => 'pipeline-creator'])
            ->with('success', "Pipeline {$pipeline->code}: {$pipeline->name} created.");
    }

    /** Store a new venue (created from Mother Admin → Venues tab). */
    public function storeVenue(Request $request)
    {
        $this->authorizeKicc();
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255',
            'venue_type' => 'nullable|string|max:100',
            'county_id' => 'nullable|integer|exists:counties,id',
            'institution_id' => 'nullable|integer|exists:county_institutions,id',
            'capacity' => 'nullable|integer|min:0',
            'description' => 'nullable|string|max:5000',
            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:200',
            'cover_image' => 'nullable|string|max:500',
            'amenities' => 'nullable|string',
        ]);

        $venue = Venue::create([
            'name' => $data['name'],
            'slug' => $data['slug'],
            'venue_type' => $data['venue_type'] ?? 'General',
            'county_id' => (int) ($data['county_id'] ?? 0) > 0 ? (int) $data['county_id'] : null,
            'institution_id' => (int) ($data['institution_id'] ?? 0) > 0 ? (int) $data['institution_id'] : null,
            'capacity' => (int) ($data['capacity'] ?? 0) > 0 ? (int) $data['capacity'] : null,
            'description' => $data['description'] ?? '',
            'address' => $data['address'] ?? '',
            'city' => $data['city'] ?? '',
            'cover_image' => $data['cover_image'] ?? '',
            'amenities' => $data['amenities'] ?? '[]',
            'is_active' => true,
        ]);

        app(\App\Services\CacheSyncService::class)->kicc();
        return redirect()->route('kicc.admin', ['tab' => 'venues'])
            ->with('success', "Venue \"{$venue->name}\" created.");
    }

    /** Update a venue from Mother Admin. */
    public function updateVenue(Request $request, int $id)
    {
        $this->authorizeKicc();
        $venue = Venue::findOrFail($id);

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255',
            'venue_type' => 'nullable|string|max:100',
            'county_id' => 'nullable|integer|exists:counties,id',
            'institution_id' => 'nullable|integer|exists:county_institutions,id',
            'capacity' => 'nullable|integer|min:0',
            'description' => 'nullable|string|max:5000',
            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:200',
            'cover_image' => 'nullable|string|max:500',
            'amenities' => 'nullable|string',
        ]);

        $venue->update([
            'name' => $data['name'],
            'slug' => $data['slug'],
            'venue_type' => $data['venue_type'] ?? 'General',
            'county_id' => (int) ($data['county_id'] ?? 0) > 0 ? (int) $data['county_id'] : null,
            'institution_id' => (int) ($data['institution_id'] ?? 0) > 0 ? (int) $data['institution_id'] : null,
            'capacity' => (int) ($data['capacity'] ?? 0) > 0 ? (int) $data['capacity'] : null,
            'description' => $data['description'] ?? '',
            'address' => $data['address'] ?? '',
            'city' => $data['city'] ?? '',
            'cover_image' => $data['cover_image'] ?? '',
            'amenities' => $data['amenities'] ?? '[]',
        ]);

        app(\App\Services\CacheSyncService::class)->kicc();
        return redirect()->route('kicc.admin', ['tab' => 'venues'])
            ->with('success', "Venue \"{$venue->name}\" updated.");
    }
}
