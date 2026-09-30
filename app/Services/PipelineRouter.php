<?php

namespace App\Services;

use App\Models\Marketplace\Product;
use App\Models\Marketplace\ProductCategory;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * PipelineRouter — product→pipeline assignment using real product signals.
 *
 * Uses every available product field to determine the correct pipeline:
 *   hs_code → trade classification (HS chapter = sector)
 *   moq / incoterm → B2B vs retail pipeline
 *   export_readiness → cross-border logistics
 *   certifications → regulated/licenced pipeline
 *   category.sector → sector→pipeline mapping
 *   county → county-specific pipeline activation
 */
class PipelineRouter
{
    /** Intent modes that override default category→pipeline routing */
    public const INTENT_LOCAL    = 'local';
    public const INTENT_EXPORT   = 'export';
    public const INTENT_B2B      = 'b2b';
    public const INTENT_LICENCED = 'licenced';
    public const INTENT_TOURISM  = 'tourism';

    /** Search query → pipeline mapping (Google SEO intent routing) */
    private const SEARCH_PIPELINES = [
        'hotel' => 'C1', 'lodge' => 'C1', 'resort' => 'C1', 'accommodation' => 'C1',
        'safari' => 'C1', 'tour' => 'C1', 'tourism' => 'C1', 'travel' => 'C1', 'vacation' => 'C1',
        'coffee' => 'B1', 'tea' => 'B1', 'crop' => 'B1', 'agriculture' => 'B1',
        'fish' => 'B2', 'seafood' => 'B2', 'tilapia' => 'B2',
        'export' => 'B3', 'logistics' => 'B3', 'freight' => 'B3', 'shipping' => 'B3',
        'wholesale' => 'A3', 'bulk' => 'A3', 'group' => 'A3',
        'equipment' => 'M1', 'energy' => 'M1', 'power' => 'M1', 'solar' => 'M1',
        'vehicle' => 'N1', 'car' => 'N1', 'transport' => 'N1',
        'real estate' => 'P2', 'property' => 'P2', 'land' => 'P2', 'rent' => 'P2',
        'conference' => 'P2', 'event' => 'P2', 'venue' => 'P2',
        'dairy' => 'DA1', 'milk' => 'DA1', 'cheese' => 'DA1', 'yoghurt' => 'DA1',
        'health' => 'K1', 'medical' => 'K1', 'clinic' => 'K1', 'pharmacy' => 'K1',
        'education' => 'L1', 'school' => 'L1', 'training' => 'L1', 'course' => 'L1',
        'government' => 'G1', 'tender' => 'G2', 'procurement' => 'G2',
        'finance' => 'F1', 'loan' => 'F1', 'credit' => 'F1', 'insurance' => 'F1',
        'creative' => 'H1', 'media' => 'H1', 'film' => 'H1', 'video' => 'H1',
        'market' => 'A1', 'shop' => 'A1', 'store' => 'A1', 'product' => 'A1',
    ];

    /** Sector → default pipeline code mapping (expanded) */
    private const SECTOR_PIPELINES = [
        'trade'       => 'A1',
        'agriculture' => 'B1',
        'crops'       => 'B1',
        'fisheries'   => 'B2',
        'livestock'   => 'B2',
        'tourism'     => 'C1',
        'investment'  => 'D1',
        'financing'   => 'F1',
        'government'  => 'G1',
        'creative'    => 'H1',
        'health'      => 'K1',
        'education'   => 'L1',
        'logistics'   => 'L1',
        'energy'      => 'M1',
        'mining'      => 'M2',
        'mobility'    => 'N1',
        'identity'    => 'P1',
        'real-estate' => 'P2',
        'milk-dairy'  => 'DA1',
    ];

    /** HS chapter → sector mapping (first 2 digits of HS code) */
    private const HS_SECTORS = [
        '01' => 'agriculture', '02' => 'agriculture', '03' => 'fisheries', '04' => 'livestock', '05' => 'agriculture',
        '06' => 'agriculture', '07' => 'agriculture', '08' => 'agriculture', '09' => 'agriculture', '10' => 'agriculture',
        '11' => 'agriculture', '12' => 'agriculture', '13' => 'agriculture', '14' => 'agriculture',
        '15' => 'agriculture', '16' => 'fisheries', '17' => 'agriculture', '18' => 'agriculture',
        '19' => 'agriculture', '20' => 'agriculture', '21' => 'agriculture', '22' => 'agriculture',
        '23' => 'agriculture', '24' => 'agriculture',
        '25' => 'mining', '26' => 'mining', '27' => 'energy',
        '28' => 'trade', '29' => 'trade', '30' => 'health', '31' => 'agriculture',
        '32' => 'trade', '33' => 'creative',
        '38' => 'trade', '39' => 'trade',
        '40' => 'trade', '41' => 'livestock',
        '42' => 'trade', '43' => 'trade',
        '44' => 'trade', '45' => 'trade',
        '46' => 'trade', '47' => 'trade', '48' => 'trade', '49' => 'trade',
        '50' => 'trade', '51' => 'livestock', '52' => 'agriculture', '53' => 'agriculture',
        '54' => 'trade', '55' => 'trade', '56' => 'trade', '57' => 'trade', '58' => 'trade', '59' => 'trade',
        '60' => 'trade', '61' => 'trade', '62' => 'trade', '63' => 'trade',
        '64' => 'trade', '65' => 'trade', '66' => 'trade', '67' => 'trade',
        '68' => 'mining', '69' => 'trade', '70' => 'trade',
        '71' => 'investment',   // precious stones/metals
        '72' => 'mining', '73' => 'mining', '74' => 'mining', '75' => 'mining', '76' => 'mining',
        '77' => 'mining', '78' => 'mining', '79' => 'mining', '80' => 'mining', '81' => 'mining', '82' => 'mining', '83' => 'mining',
        '84' => 'trade', '85' => 'trade', '86' => 'trade', '87' => 'mobility',  // vehicles
        '88' => 'mobility', '89' => 'mobility',
        '90' => 'health',   // medical instruments
        '91' => 'investment',
        '92' => 'creative',  // musical instruments
        '93' => 'trade',
        '94' => 'trade', '95' => 'trade', '96' => 'trade',
        '97' => 'trade',
        '98' => 'trade', '99' => 'trade',
    ];

    /** Resolve the pipeline for a product using ALL available signals. */
    public function forProduct(Product $product, string $intent = self::INTENT_LOCAL): string
    {
        // 1. Check if the product has an explicit pipeline_code override
        if ($product->pipeline_code) return $product->pipeline_code;

        // 2. Check if its category has an explicit pipeline_code override
        $category = $product->category;
        if ($category && $category->pipeline_code) return $category->pipeline_code;

        // 3. Use intent-based routing
        $intentPipeline = $this->routeByIntent($product, $intent);
        if ($intentPipeline) return $intentPipeline;

        // 4. Use HS code for accurate trade classification
        if ($product->hs_code) {
            $hsPrefix = substr(str_pad($product->hs_code, 2, '0', STR_PAD_LEFT), 0, 2);
            $sector = self::HS_SECTORS[$hsPrefix] ?? null;
            if ($sector && isset(self::SECTOR_PIPELINES[$sector])) {
                return self::SECTOR_PIPELINES[$sector];
            }
        }

        // 5. Use category sector
        if ($category) {
            $sector = $category->sector ?? $this->inferSector($category);
            if ($sector && isset(self::SECTOR_PIPELINES[$sector])) {
                return self::SECTOR_PIPELINES[$sector];
            }
        }

        // 6. Use tags for keyword matching
        if ($product->tags) {
            $tags = is_array($product->tags) ? $product->tags : [$product->tags];
            foreach ($tags as $tag) {
                $sector = $this->keywordToSector($tag);
                if ($sector && isset(self::SECTOR_PIPELINES[$sector])) {
                    return self::SECTOR_PIPELINES[$sector];
                }
            }
        }

        // 7. Fallback to name-based keyword matching
        $sector = $this->keywordToSector($product->name);
        if ($sector && isset(self::SECTOR_PIPELINES[$sector])) return self::SECTOR_PIPELINES[$sector];

        return 'A1';
    }

    /** Route by user intent — overrides category defaults. */
    public function routeByIntent(Product $product, string $intent): ?string
    {
        return match ($intent) {
            self::INTENT_EXPORT => $product->hs_code ? 'B3' : ($product->export_readiness ? 'B3' : null),
            self::INTENT_B2B    => ($product->moq ?? 0) > 50 ? 'A3' : null,
            self::INTENT_LICENCED => $product->certifications ? 'F1' : null,
            self::INTENT_TOURISM  => str_contains(get_class($product), 'Tourism') || str_contains(get_class($product), 'Hotel') ? 'C1' : null,
            default => null,
        };
    }

    /** Get upstream and downstream pipelines for a pipeline code from the graph. */
    public function mesh(string $pipelineCode): array
    {
        $graph = $this->loadGraph();
        $edges = $graph['edges'] ?? [];

        $codeToId = $graph['codeToId'] ?? [];
        $idToCode = $graph['idToCode'] ?? [];
        $pipelineId = $codeToId[$pipelineCode] ?? null;

        if (! $pipelineId) return ['upstream' => [], 'downstream' => [], 'pipeline_code' => $pipelineCode];

        $upstream = [];
        $downstream = [];

        foreach ($edges as $e) {
            if ($e['to'] === $pipelineId && isset($idToCode[$e['from']])) {
                $upstream[] = $idToCode[$e['from']];
            }
            if ($e['from'] === $pipelineId && isset($idToCode[$e['to']])) {
                $downstream[] = $idToCode[$e['to']];
            }
        }

        return [
            'upstream' => array_unique($upstream),
            'downstream' => array_unique($downstream),
            'pipeline_code' => $pipelineCode,
        ];
    }

    /** Fee rate for a pipeline code. */
    public function feeRate(string $pipelineCode): float
    {
        static $configs;
        if (! $configs) $configs = collect(config('kicc-pipelines'))->keyBy('code');

        $config = $configs->get($pipelineCode);
        if (! $config) return 4.0;

        $rate = $config['take_rate'] ?? '4%';
        if (is_string($rate)) {
            if (preg_match('/([\d.]+)\s*-\s*([\d.]+)/', $rate, $m)) {
                return ((float) $m[1] + (float) $m[2]) / 2;
            }
            preg_match('/([\d.]+)/', $rate, $m);
            return (float) ($m[1] ?? 4);
        }
        return (float) $rate;
    }

    /** All sector→pipeline mappings for frontend display. */
    public function allMappings(): array
    {
        return self::SECTOR_PIPELINES;
    }

    /** Resolve pipeline for a category (compatibility adapter for PipelineResolver). */
    public function forCategory(?ProductCategory $category): string
    {
        if (! $category) return 'A1';
        $sector = $category->sector ?? $this->inferSector($category);
        return self::SECTOR_PIPELINES[$sector] ?? 'A1';
    }

    /** Resolve pipeline for a sector string (compatibility adapter). */
    public function forSector(string $sector): string
    {
        return self::SECTOR_PIPELINES[$sector] ?? 'A1';
    }

    /** Get active pipeline activations for a county. */
    public function forCounty(int $countyId): array
    {
        return Cache::remember("pipeline_router_county_{$countyId}", 3600, fn () =>
            DB::table('pipeline_activations')
                ->where('county_id', $countyId)
                ->where('is_active', true)
                ->pluck('pipeline_code')
                ->toArray()
        );
    }

    /** Resolve the pipeline a search query targets (Google SEO intent routing). */
    public function fromSearchQuery(string $query): ?string
    {
        if (empty($query)) return null;

        $query = strtolower($query);
        foreach (self::SEARCH_PIPELINES as $keyword => $pipeline) {
            if (str_contains($query, $keyword)) {
                return $pipeline;
            }
        }
        return null;
    }

    /** Update search_analytics with the pipeline a search intent maps to. */
    public function attributeSearchToPipeline(string $query, string $pipelineCode): void
    {
        try {
            $count = DB::table('search_analytics')
                ->where('query', 'like', "%{$query}%")
                ->whereNull('pipeline')
                ->count();
            if ($count > 0) {
                DB::table('search_analytics')
                    ->where('query', 'like', "%{$query}%")
                    ->whereNull('pipeline')
                    ->update(['pipeline' => $pipelineCode]);
            }
        } catch (\Throwable) {}
    }

    /** Load the pipeline dependency graph from integration-map.json. */
    private function loadGraph(): array
    {
        $path = base_path('pipelines.json');
        $mapPath = base_path('integration-map.json');

        if (! file_exists($path) || ! file_exists($mapPath)) return [];

        $data = json_decode(file_get_contents($path), true);
        $edges = json_decode(file_get_contents($mapPath), true);

        $codeToId = [];
        $idToCode = [];
        foreach ($data['pipelines'] ?? [] as $p) {
            $id = (int) $p['id'];
            $codeToId[$p['code']] = $id;
            $idToCode[$id] = $p['code'];
        }

        return [
            'codeToId' => $codeToId,
            'idToCode' => $idToCode,
            'edges' => $edges['edges'] ?? [],
            'pipelines' => $data['pipelines'] ?? [],
        ];
    }

    /** Infer sector from category name/slug. */
    private function inferSector(ProductCategory $category): ?string
    {
        return $this->keywordToSector($category->name . ' ' . ($category->slug ?? ''));
    }

    /** Match a string to a sector via comprehensive keyword map. */
    private function keywordToSector(string $text): ?string
    {
        $text = strtolower($text);

        $map = [
            'agriculture' => ['agriculture', 'farm', 'crop', 'agri', 'food', 'grain', 'coffee', 'tea', 'sugar', 'cereal', 'vegetable', 'fruit', 'nuts', 'spice'],
            'fisheries'   => ['fish', 'fishery', 'aquacul', 'seafood', 'prawn', 'shrimp', 'tilapia', 'marine', 'ocean', 'seaweed'],
            'livestock'   => ['livestock', 'animal', 'cattle', 'goat', 'sheep', 'pig', 'poultry', 'chicken', 'egg', 'meat', 'beef', 'mutton', 'leather', 'hide', 'wool'],
            'tourism'     => ['tourism', 'travel', 'hotel', 'lodge', 'safari', 'tour', 'attraction', 'hospitality', 'resort', 'camp', 'excursion'],
            'trade'       => ['trade', 'market', 'commerce', 'retail', 'wholesale', 'shop', 'store', 'general', 'merchandise', 'consumer'],
            'energy'      => ['energy', 'power', 'fuel', 'petroleum', 'gas', 'electric', 'solar', 'wind', 'geothermal', 'biomass'],
            'mining'      => ['mining', 'mineral', 'ore', 'gold', 'gem', 'titanium', 'rare earth', 'quarry', 'stone', 'cement'],
            'health'      => ['health', 'medical', 'pharma', 'clinic', 'hospital', 'wellness', 'pharmaceutical', 'medicine', 'device'],
            'education'   => ['education', 'school', 'training', 'college', 'university', 'learning', 'course', 'academy'],
            'creative'    => ['creative', 'media', 'film', 'video', 'music', 'art', 'design', 'studio', 'photography', 'animation'],
            'mobility'    => ['mobility', 'transport', 'logistics', 'vehicle', 'shipping', 'delivery', 'courier', 'freight', 'aviation'],
            'investment'  => ['investment', 'sez', 'special economic', 'diaspora', 'mining', 'real estate', 'property'],
            'financing'   => ['financing', 'finance', 'loan', 'credit', 'bank', 'insurance', 'fund', 'capital'],
            'government'  => ['government', 'public', 'procurement', 'county', 'national', 'ministry', 'state', 'agency'],
            'identity'    => ['identity', 'kyc', 'kyb', 'verification', 'biometric', 'document', 'credential'],
            'milk-dairy'  => ['milk', 'dairy', 'cheese', 'butter', 'yoghurt', 'cream', 'ghee', 'whey', 'ice cream'],
            'real-estate' => ['real estate', 'property', 'land', 'housing', 'apartment', 'commercial', 'lease', 'rent'],
            'logistics'   => ['logistics', 'warehouse', 'storage', 'cold chain', 'supply chain', 'distribution'],
        ];

        foreach ($map as $sector => $keywords) {
            foreach ($keywords as $kw) {
                if (str_contains($text, $kw)) return $sector;
            }
        }

        return null;
    }
}