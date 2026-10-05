<?php

namespace App\Services;

use App\Models\Marketplace\ProductCategory;

/**
 * PipelineResolver — resolves the ledger sector (and ideal pipeline) for a
 * product category from its name/slug. Kept as a thin, single-method helper
 * so category backfill (migrations) and runtime routing share one source.
 */
class PipelineResolver
{
    /**
     * Map category keywords to ledger sectors. Broadly mirrors
     * PipelineRouter::keywordToSector so category.sector stays consistent
     * with how products are routed to pipelines.
     */
    private const SECTOR_KEYWORDS = [
        'agriculture' => ['agriculture', 'farm', 'crop', 'agri', 'food', 'grain', 'coffee', 'tea', 'sugar', 'cereal', 'vegetable', 'fruit', 'nuts', 'spice', 'macadamia', 'avocado'],
        'fisheries'   => ['fish', 'fishery', 'aquacul', 'seafood', 'prawn', 'shrimp', 'tilapia', 'marine', 'ocean', 'seaweed'],
        'livestock'   => ['livestock', 'animal', 'cattle', 'goat', 'sheep', 'pig', 'poultry', 'chicken', 'egg', 'meat', 'beef', 'mutton', 'leather', 'hide', 'wool', 'dairy', 'milk'],
        'tourism'     => ['tourism', 'travel', 'hotel', 'lodge', 'safari', 'tour', 'attraction', 'hospitality', 'resort', 'camp', 'excursion'],
        'trade'       => ['trade', 'market', 'commerce', 'retail', 'wholesale', 'shop', 'store', 'general', 'merchandise', 'consumer', 'beverage', 'pack'],
        'energy'      => ['energy', 'power', 'fuel', 'petroleum', 'gas', 'electric', 'solar', 'wind', 'geothermal', 'biomass'],
        'mining'      => ['mining', 'mineral', 'ore', 'gold', 'gem', 'titanium', 'rare earth', 'quarry', 'stone', 'cement'],
        'health'      => ['health', 'medical', 'pharma', 'clinic', 'hospital', 'wellness', 'pharmaceutical', 'medicine', 'device'],
        'education'   => ['education', 'school', 'training', 'college', 'university', 'learning', 'course', 'academy'],
        'creative'    => ['creative', 'media', 'film', 'video', 'music', 'art', 'design', 'studio', 'photography', 'animation', 'textile', 'handwoven', 'basket', 'craft'],
        'mobility'    => ['mobility', 'transport', 'logistics', 'vehicle', 'shipping', 'delivery', 'courier', 'freight', 'aviation'],
        'investment'  => ['investment', 'sez', 'special economic', 'diaspora', 'real estate', 'property', 'estate', 'housing', 'construction'],
        'financing'   => ['financing', 'finance', 'loan', 'credit', 'bank', 'sacco', 'fund', 'capital', 'insurance'],
        'government'  => ['government', 'public', 'procurement', 'county', 'national', 'ministry', 'state', 'agency'],
        'identity'    => ['identity', 'kyc', 'kyb', 'verification', 'biometric', 'document', 'credential'],
        'real-estate' => ['real estate', 'property', 'land', 'housing', 'apartment', 'commercial', 'lease', 'rent', 'estate', 'construction'],
        'milk-dairy'  => ['milk', 'dairy', 'cheese', 'butter', 'yoghurt', 'cream', 'ghee', 'whey', 'ice cream'],
    ];

    public function forCategory(ProductCategory $category): ?string
    {
        $text = strtolower(($category->name ?? '') . ' ' . ($category->slug ?? ''));

        foreach (self::SECTOR_KEYWORDS as $sector => $keywords) {
            foreach ($keywords as $kw) {
                if (str_contains($text, $kw)) {
                    return $sector;
                }
            }
        }

        return null;
    }
}