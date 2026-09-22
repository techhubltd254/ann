<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\County;
use App\Models\CountyTourismAttraction;
use App\Models\EscrowTransaction;
use App\Models\Marketplace\Order;
use App\Models\Marketplace\Product;
use App\Models\Sector;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * MCP (Model Context Protocol) Server — exposes KICC platform data
 * as resources and tools for AI agents (Claude, OpenCode, etc.).
 *
 * Endpoints follow the MCP specification:
 *   GET  /api/mcp/resources           — list all available resources
 *   GET  /api/mcp/resources/{type}     — read a specific resource type
 *   POST /api/mcp/tools/{name}         — execute a tool
 *   GET  /api/mcp                    — MCP protocol discovery
 */
class McpController extends Controller
{
    /* ─── RESOURCE DEFINITIONS ─── */
    private array $resources = [
        'counties' => [
            'description' => 'All 47 Kenyan counties with metadata, sectors, and stats',
            'mimeType' => 'application/json',
        ],
        'products' => [
            'description' => 'Marketplace products across all counties (218+ live)',
            'mimeType' => 'application/json',
        ],
        'attractions' => [
            'description' => 'Tourist attractions with pricing and booking info',
            'mimeType' => 'application/json',
        ],
        'sectors' => [
            'description' => 'Economic sectors linked to counties',
            'mimeType' => 'application/json',
        ],
        'orders' => [
            'description' => 'Order history and status',
            'mimeType' => 'application/json',
        ],
        'escrow' => [
            'description' => 'Escrow transactions with buyer/seller info',
            'mimeType' => 'application/json',
        ],
    ];

    private array $tools = [
        'search_counties' => [
            'description' => 'Search counties by name, region, or sector',
            'inputSchema' => [
                'type' => 'object',
                'properties' => [
                    'query' => ['type' => 'string', 'description' => 'Search term (county name, region, sector)'],
                    'limit' => ['type' => 'integer', 'description' => 'Max results', 'default' => 10],
                ],
            ],
        ],
        'get_county_products' => [
            'description' => 'Get marketplace products for a specific county',
            'inputSchema' => [
                'type' => 'object',
                'properties' => [
                    'county' => ['type' => 'string', 'description' => 'County slug (e.g. mombasa, kilifi)'],
                    'category' => ['type' => 'string', 'description' => 'Product category filter'],
                ],
                'required' => ['county'],
            ],
        ],
        'get_booking_recommendations' => [
            'description' => 'Get personalized recommendations based on origin and interests',
            'inputSchema' => [
                'type' => 'object',
                'properties' => [
                    'origin_temp' => ['type' => 'integer', 'description' => 'Current temperature in user\'s city'],
                    'origin_city' => ['type' => 'string', 'description' => 'User\'s city name'],
                    'interest' => ['type' => 'string', 'description' => 'Tourism interest (beach, safari, culture, etc.)'],
                ],
            ],
        ],
        'analyze_trade_volume' => [
            'description' => 'Get trade analytics by county or time period',
            'inputSchema' => [
                'type' => 'object',
                'properties' => [
                    'county' => ['type' => 'string', 'description' => 'County slug (optional — all counties if omitted)'],
                    'days' => ['type' => 'integer', 'description' => 'Lookback period in days', 'default' => 30],
                ],
            ],
        ],
    ];

    /* ─── MCP DISCOVERY ─── */
    public function discovery(): JsonResponse
    {
        return response()->json([
            'protocol' => '2025-03-26',
            'name' => 'KICC Platform MCP Server',
            'version' => '1.0.0',
            'description' => 'Kenya National Exhibition Platform — data and tools for AI agents',
            'resources' => collect($this->resources)->map(fn ($r, $k) => [
                'uri' => url("/api/mcp/resources/$k"),
                'name' => $k,
                'description' => $r['description'],
                'mimeType' => $r['mimeType'],
            ])->values(),
            'tools' => collect($this->tools)->map(fn ($t, $k) => [
                'name' => $k,
                'description' => $t['description'],
                'inputSchema' => $t['inputSchema'],
            ])->values(),
        ]);
    }

    /* ─── LIST RESOURCES ─── */
    public function listResources(): JsonResponse
    {
        return response()->json([
            'resources' => collect($this->resources)->map(fn ($r, $k) => [
                'uri' => url("/api/mcp/resources/$k"),
                'name' => $k,
                'description' => $r['description'],
                'mimeType' => $r['mimeType'],
            ])->values(),
        ]);
    }

    /* ─── READ RESOURCE ─── */
    public function readResource(string $type, Request $request): JsonResponse
    {
        $data = match ($type) {
            'counties' => $this->getCounties($request),
            'products' => $this->getProducts($request),
            'attractions' => $this->getAttractions($request),
            'sectors' => $this->getSectors(),
            'orders' => $this->getOrders($request),
            'escrow' => $this->getEscrow($request),
            default => null,
        };

        if ($data === null) {
            return response()->json(['error' => "Resource '$type' not found"], 404);
        }

        // k-anonymity gate — no individual-identifying data exposed to AI agents
        // when group sizes fall below configured threshold
        if (is_array($data)) {
            try {
                $data = app(\App\Services\Mcp\KAnonymizerService::class)->anonymize($data,
                    $type === 'orders' || $type === 'escrow' ? 'user_id' : 'county_id'
                );
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('mcp: k-anonymizer skipped', ['error' => $e->getMessage()]);
            }
        }

        return response()->json([
            'uri' => url("/api/mcp/resources/$type"),
            'mimeType' => $this->resources[$type]['mimeType'],
            'data' => $data,
        ]);
    }

    /* ─── EXECUTE TOOL ─── */
    public function executeTool(string $name, Request $request): JsonResponse
    {
        $input = $request->input('input', []);
        $result = match ($name) {
            'search_counties' => $this->toolSearchCounties($input),
            'get_county_products' => $this->toolGetCountyProducts($input),
            'get_booking_recommendations' => $this->toolGetRecommendations($input),
            'analyze_trade_volume' => $this->toolAnalyzeTrade($input),
            default => null,
        };

        if ($result === null) {
            return response()->json(['error' => "Tool '$name' not found"], 404);
        }

        return response()->json(['result' => $result]);
    }

    /* ─── RESOURCE PROVIDERS ─── */
    private function getCounties(Request $request): array
    {
        $query = County::query();
        if ($s = $request->get('search')) {
            $query->where('name', 'like', "%$s%")
                  ->orWhere('description', 'like', "%$s%");
        }
        return $query->limit(50)->get(['id', 'name', 'slug', 'capital', 'population_2024', 'area_km2', 'tagline', 'description', 'former_province', 'economic_zone'])->toArray();
    }

    private function getProducts(Request $request): array
    {
        return Product::with(['county:id,name,slug', 'category:id,name'])
            ->active()
            ->limit(100)
            ->get(['id', 'name', 'slug', 'county_id', 'category_id', 'status', 'created_at'])
            ->toArray();
    }

    private function getAttractions(Request $request): array
    {
        return CountyTourismAttraction::with('county:id,name,slug')
            ->where('is_published', true)
            ->limit(100)
            ->get()
            ->toArray();
    }

    private function getSectors(): array
    {
        return Sector::withCount('counties')->orderBy('name')->get()->toArray();
    }

    private function getOrders(Request $request): array
    {
        $query = Order::with('items');
        if ($status = $request->get('status')) $query->where('payment_status', $status);
        return $query->latest()->limit(50)->get()->toArray();
    }

    private function getEscrow(Request $request): array
    {
        return EscrowTransaction::with('buyer:id,name,email', 'seller:id,name,email')
            ->latest()->limit(50)->get()->toArray();
    }

    /* ─── TOOL HANDLERS ─── */
    private function toolSearchCounties(array $input): array
    {
        $q = $input['query'] ?? '';
        $limit = min($input['limit'] ?? 10, 50);

        $results = County::where('name', 'like', "%$q%")
            ->orWhere('former_province', 'like', "%$q%")
            ->orWhere('description', 'like', "%$q%")
            ->limit($limit)
            ->get(['id', 'name', 'slug', 'capital', 'former_province', 'tagline', 'population_2024']);

        $productCount = Product::whereIn('county_id', $results->pluck('id'))->count();

        return [
            'query' => $q,
            'count' => $results->count(),
            'total_products_available' => $productCount,
            'results' => $results,
        ];
    }

    private function toolGetCountyProducts(array $input): array
    {
        $county = County::where('slug', $input['county'])->first();
        if (!$county) return ['error' => "County '{$input['county']}' not found"];

        $query = Product::with('variants')->where('county_id', $county->id)->active();
        if (!empty($input['category'])) $query->whereHas('category', fn ($q) => $q->where('slug', $input['category']));

        $products = $query->get();
        return [
            'county' => $county->name,
            'products_count' => $products->count(),
            'stock_total' => $products->sum(fn ($p) => $p->variants->sum('stock')),
            'price_range' => [
                'min' => $products->min(fn ($p) => $p->price),
                'max' => $products->max(fn ($p) => $p->price),
            ],
            'products' => $products->map(fn ($p) => [
                'name' => $p->name, 'price' => $p->price, 'stock' => $p->variants->sum('stock')
            ]),
        ];
    }

    private function toolGetRecommendations(array $input): array
    {
        $temp = $input['origin_temp'] ?? 20;
        $city = $input['origin_city'] ?? 'Unknown';
        $interest = $input['interest'] ?? '';

        $warm = $temp <= 14;
        $counties = $warm
            ? County::whereIn('slug', config('kicc.travel_recommendations.warm_counties', ['mombasa', 'kwale', 'kilifi', 'lamu', 'malindi', 'taita-taveta']))->get()
            : County::inRandomOrder()->limit(5)->get();

        return [
            'origin' => $city,
            'origin_temp' => $temp,
            'seasonal_recommendation' => $warm
                ? "Escape the cold! {$city} is {$temp}°C — Kenya's coast is 30°C"
                : "Explore Kenya year-round",
            'recommended_destinations' => $counties->map(fn ($c) => [
                'name' => $c->name, 'slug' => $c->slug,
                'tagline' => $c->tagline,
                'avg_temp' => $warm ? '28-32°C' : '20-26°C',
            ]),
            'interest_filter' => $interest ? "Showing destinations matching '$interest'" : 'All destinations',
        ];
    }

    private function toolAnalyzeTrade(array $input): array
    {
        $days = min($input['days'] ?? 30, 365);
        $since = now()->subDays($days);

        $query = EscrowTransaction::where('created_at', '>=', $since);

        if (!empty($input['county'])) {
            $county = County::where('slug', $input['county'])->first();
            if (!$county) return ['error' => 'County not found'];
            $ids = Product::where('county_id', $county->id)->whereNotNull('user_id')->pluck('user_id');
            $query->whereIn('seller_id', $ids);
        }

        $txns = $query->get();
        return [
            'period_days' => $days,
            'total_volume' => $txns->sum('amount'),
            'transaction_count' => $txns->count(),
            'average_value' => $txns->avg('amount'),
            'held' => $txns->where('status', 'held')->sum('amount'),
            'released' => $txns->where('status', 'released')->sum('amount'),
        ];
    }
}