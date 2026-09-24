<?php

namespace App\Services;

use App\Models\Marketplace\Product;
use App\Models\Marketplace\ProductCategory;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * PipelineResolver — maps product categories, sectors, and counties
 * to the correct pipeline codes. This is the bridge between the
 * marketplace frontend and the pipeline revenue engine.
 *
 * Every product belongs to a category → sector → pipeline.
 * Every county activates specific pipelines via pipeline_activations.
 */
class PipelineResolver
{
    private const CACHE_TTL = 3600; // 1 hour

    /** Sector → default pipeline code mapping */
    private const SECTOR_PIPELINES = [
        'trade'       => 'A1',
        'agriculture' => 'B1',
        'tourism'     => 'C1',
        'investment'  => 'D1',
        'financing'   => 'F1',
        'government'  => 'G1',
        'creative'    => 'H1',
        'health'      => 'K1',
        'education'   => 'L1',
        'energy'      => 'M1',
        'mobility'    => 'N1',
        'identity'    => 'P1',
        'milk-dairy'  => 'DA1',
    ];

    /** Resolve the pipeline code for a product. */
    public function forProduct(Product $product): string
    {
        $category = $product->category;
        return $this->forCategory($category);
    }

    /** Resolve the pipeline code for a product category. */
    public function forCategory(?ProductCategory $category): string
    {
        if (! $category) return 'A1';
        $sector = $category->sector ?? $this->inferSector($category);
        return self::SECTOR_PIPELINES[$sector] ?? 'A1';
    }

    /** Resolve the pipeline code for a sector string. */
    public function forSector(string $sector): string
    {
        return self::SECTOR_PIPELINES[$sector] ?? 'A1';
    }

    /** Get all pipelines active for a county. */
    public function forCounty(int $countyId): array
    {
        return Cache::remember("pipeline_resolver_county_{$countyId}", self::CACHE_TTL, function () use ($countyId) {
            return DB::table('pipeline_activations')
                ->where('county_id', $countyId)
                ->where('is_active', true)
                ->pluck('pipeline_code')
                ->toArray();
        });
    }

    /** Get the fee rate for a pipeline code. */
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

    /** Infer sector from category name/slug. */
    private function inferSector(ProductCategory $category): string
    {
        $name = strtolower($category->name);
        $slug = strtolower($category->slug ?? $name);

        $map = [
            'agriculture' => ['agriculture', 'farm', 'crop', 'livestock', 'agri', 'food', 'grain', 'coffee', 'tea'],
            'tourism'     => ['tourism', 'travel', 'hotel', 'lodge', 'safari', 'tour', 'attraction', 'hospitality'],
            'trade'       => ['trade', 'market', 'commerce', 'retail', 'wholesale', 'shop', 'store', 'general'],
            'energy'      => ['energy', 'power', 'fuel', 'petroleum', 'gas', 'electric', 'solar', 'wind'],
            'health'      => ['health', 'medical', 'pharma', 'clinic', 'hospital', 'wellness'],
            'education'   => ['education', 'school', 'training', 'college', 'university', 'learning'],
            'creative'    => ['creative', 'media', 'film', 'video', 'music', 'art', 'design', 'studio'],
            'mobility'    => ['mobility', 'transport', 'logistics', 'vehicle', 'shipping', 'delivery'],
            'investment'  => ['investment', 'sez', 'special economic', 'diaspora', 'mining'],
            'financing'   => ['financing', 'finance', 'loan', 'credit', 'bank', 'insurance'],
            'government'  => ['government', 'public', 'procurement', 'county', 'national', 'ministry'],
            'identity'    => ['identity', 'kyc', 'kyb', 'verification', 'biometric'],
            'milk-dairy'  => ['milk', 'dairy', 'cheese', 'butter', 'yoghurt', 'cream', 'ghee'],
        ];

        foreach ($map as $sector => $keywords) {
            foreach ($keywords as $kw) {
                if (str_contains($name, $kw) || str_contains($slug, $kw)) {
                    return $sector;
                }
            }
        }

        return 'trade'; // fallback
    }

    /** All sector → pipeline mapping for frontend display. */
    public function allMappings(): array
    {
        return self::SECTOR_PIPELINES;
    }
}
