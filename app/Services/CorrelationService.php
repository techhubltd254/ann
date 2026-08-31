<?php

namespace App\Services;

use App\Models\CountyInstitution;
use App\Models\Marketplace\Product;
use App\Models\County;
use App\Models\ReviewSeed;
use Illuminate\Support\Facades\DB;

class CorrelationService
{
    const PLACES_TO_VISIT = 'places_to_visit';
    const PLACES_TO_STAY = 'places_to_stay';
    const TRANSPORT = 'transport';

    const EXPERIENCE_TYPES = [
        'culture' => ['label' => 'Culture & Heritage', 'emoji' => '🏛'],
        'food' => ['label' => 'Food & Dining', 'emoji' => '🍽'],
        'nature' => ['label' => 'Nature & Outdoors', 'emoji' => '🌿'],
        'hotel' => ['label' => 'Places to Stay', 'emoji' => '🏨'],
        'shopping' => ['label' => 'Shopping & Markets', 'emoji' => '🛍'],
        'entertainment' => ['label' => 'Entertainment', 'emoji' => '🎬'],
        'transport' => ['label' => 'Transport', 'emoji' => '🚗'],
        'education' => ['label' => 'Education', 'emoji' => '📚'],
        'industry' => ['label' => 'Industry & Trade', 'emoji' => '🏭'],
        'wellness' => ['label' => 'Wellness & Spa', 'emoji' => '💆'],
    ];

    const TYPE_AFFINITY = [
        'culture' => ['food', 'nature', 'shopping', 'entertainment', 'hotel', 'transport'],
        'food' => ['culture', 'nature', 'entertainment', 'hotel', 'transport'],
        'nature' => ['food', 'culture', 'entertainment', 'hotel', 'transport'],
        'hotel' => ['food', 'entertainment', 'nature', 'culture', 'transport', 'shopping'],
        'shopping' => ['food', 'culture', 'entertainment', 'hotel', 'transport'],
        'entertainment' => ['food', 'nature', 'culture', 'hotel', 'transport'],
        'transport' => ['hotel', 'food', 'culture', 'nature', 'entertainment'],
        'education' => ['nature', 'culture', 'food', 'hotel', 'transport'],
        'industry' => ['food', 'hotel', 'transport', 'culture'],
        'wellness' => ['nature', 'hotel', 'food', 'culture', 'transport'],
    ];

    const INSTITUTION_TYPE_MAP = [
        'National Monument' => 'culture',
        'Heritage Site' => 'culture',
        'Museum' => 'culture',
        'Historic Site' => 'culture',
        'Marina' => 'entertainment',
        'Beach Resort' => 'hotel',
        'Hotel' => 'hotel',
        'Restaurant' => 'food',
        'Nature Sanctuary' => 'nature',
        'Nature Trail' => 'nature',
        'Park' => 'nature',
        'Workshop' => 'shopping',
        'Cooperative' => 'shopping',
        'Market' => 'shopping',
        'Airport' => 'transport',
        'Terminal' => 'transport',
        'Port' => 'transport',
        'Bypass' => 'transport',
        'University' => 'education',
        'Institute' => 'education',
        'School' => 'education',
        'College' => 'education',
        'Hospital' => 'wellness',
        'Golf Club' => 'entertainment',
        'Convention Centre' => 'entertainment',
        'Water Park' => 'entertainment',
        'Chamber of Commerce' => 'industry',
        'Manufacturers Association' => 'industry',
        'Manufacturing' => 'industry',
        'Cement' => 'industry',
        'Oil' => 'industry',
        'Special Economic Zone' => 'industry',
        'Port Authority' => 'transport',
        'Maritime' => 'transport',
        'Shipyard' => 'industry',
        'SGR Terminus' => 'transport',
        'Inland Container Depot' => 'transport',
        'Showground' => 'entertainment',
    ];

    public function forInstitution(CountyInstitution $institution, int $limit = 6): array
    {
        $county = $institution->county;
        $anchorType = $this->classifyInstitution($institution);
        $countyIds = [$county->id];
        $nearbyIds = $this->nearbyCountyIds($institution, $countyIds, 20);

        $candidates = CountyInstitution::whereIn('county_id', $nearbyIds)
            ->where('is_published', true)
            ->where('id', '!=', $institution->id)
            ->whereNotNull('lat')
            ->whereNotNull('lng')
            ->with('county')
            ->get();

        $productCandidates = $this->fetchProductCandidates($nearbyIds, $institution);

        $scored = [];
        foreach ($candidates as $cand) {
            $candType = $this->classifyInstitution($cand);
            $scored[] = $this->score($institution, $cand, null, $anchorType, $candType);
        }

        if (!empty($scored)) {
            usort($scored, fn ($a, $b) => $b['score'] <=> $a['score']);
        }

        $placesToVisit = [];
        $placesToStay = [];
        $usedTypes = [];

        foreach ($scored as $item) {
            $cat = $item['type'];
            $typeGroup = in_array($cat, ['hotel']) ? self::PLACES_TO_STAY : self::PLACES_TO_VISIT;

            if ($typeGroup === self::PLACES_TO_STAY) {
                if (count($placesToStay) < 3) {
                    $placesToStay[] = $item;
                }
            } else {
                $lastType = count($placesToVisit) > 0 ? $placesToVisit[array_key_last($placesToVisit)]['type'] : null;
                $affinityTypes = self::TYPE_AFFINITY[$anchorType] ?? array_keys(self::EXPERIENCE_TYPES);
                if ($cat === $lastType) {
                    $item['score'] *= 0.85;
                }
                $preferred = in_array($cat, $affinityTypes);
                if ($preferred) {
                    $item['score'] *= 1.1;
                }
                $placesToVisit[] = $item;
                if (count($placesToVisit) >= 4) break;
            }
        }

        usort($placesToVisit, fn ($a, $b) => $b['score'] <=> $a['score']);

        $transport = $this->getTransportOptions($institution);

        return [
            'anchor' => ['id' => $institution->id, 'name' => $institution->name, 'type' => $anchorType, 'lat' => $institution->lat, 'lng' => $institution->lng],
            'places_to_visit' => $placesToVisit,
            'places_to_stay' => $placesToStay,
            'transport' => $transport,
        ];
    }

    public function forProduct(Product $product): array
    {
        $institution = null;
        if ($product->user_id) {
            $institution = CountyInstitution::where('user_id', $product->user_id)->first();
        }
        if (!$institution) {
            $county = $product->county;
            $institution = CountyInstitution::where('county_id', $county->id)->first();
        }
        if ($institution) {
            return $this->forInstitution($institution);
        }
        return ['anchor' => null, 'places_to_visit' => [], 'places_to_stay' => [], 'transport' => []];
    }

    public function forAttraction($attraction): array
    {
        $countyId = $attraction->county_id;
        $institution = CountyInstitution::where('county_id', $countyId)
            ->whereNotNull('lat')->whereNotNull('lng')
            ->first();
        if ($institution) {
            return $this->forInstitution($institution);
        }
        return ['anchor' => null, 'places_to_visit' => [], 'places_to_stay' => [], 'transport' => []];
    }

    public function classifyInstitution(?CountyInstitution $inst): string
    {
        if (!$inst) return 'culture';
        $type = $inst->type ?? '';
        foreach (self::INSTITUTION_TYPE_MAP as $pattern => $cat) {
            if (stripos($type, $pattern) !== false) {
                return $cat;
            }
        }
        return 'culture';
    }

    protected function score(CountyInstitution $anchor, CountyInstitution $cand, ?Product $product, string $anchorType, string $candType): array
    {
        $geoScore = $anchor->lat && $anchor->lng && $cand->lat && $cand->lng
            ? $this->geoProximityScore((float) $anchor->lat, (float) $anchor->lng, (float) $cand->lat, (float) $cand->lng)
            : 0.5;

        $diversityScore = $this->diversityScore($anchorType, $candType);

        $reviewData = $this->institutionReviewScore($cand);
        $reviewScore = $reviewData['score'];

        $completeness = $this->completenessScore($cand);

        $sectorScore = $this->sectorOverlapScore($anchor, $cand);

        $totalScore = ($geoScore * 0.25) + ($diversityScore * 0.35) + ($reviewScore * 0.20) + ($completeness * 0.10) + ($sectorScore * 0.10);

        return [
            'type' => $candType,
            'type_label' => self::EXPERIENCE_TYPES[$candType]['label'] ?? $candType,
            'type_emoji' => self::EXPERIENCE_TYPES[$candType]['emoji'] ?? '📍',
            'id' => $cand->id,
            'name' => $cand->name,
            'slug' => $cand->slug,
            'description' => $cand->description ?? '',
            'location' => $cand->location ?? '',
            'lat' => (float) ($cand->lat ?? 0),
            'lng' => (float) ($cand->lng ?? 0),
            'website' => $cand->website ?? '',
            'image_url' => $cand->cover_image_url ?? $cand->logo_url ?? null,
            'distance_km' => $anchor->lat && $anchor->lng && $cand->lat && $cand->lng
                ? round($this->haversine((float) $anchor->lat, (float) $anchor->lng, (float) $cand->lat, (float) $cand->lng), 1)
                : null,
            'geo_score' => round($geoScore, 4),
            'diversity_score' => round($diversityScore, 4),
            'review_score' => round($reviewScore, 4),
            'completeness' => round($completeness, 4),
            'sector_score' => round($sectorScore, 4),
            'score' => round($totalScore, 4),
        ];
    }

    protected function geoProximityScore(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $km = $this->haversine($lat1, $lng1, $lat2, $lng2);
        if ($km < 0.3) return 1.0;
        if ($km < 1) return 0.9;
        if ($km < 3) return 0.8;
        if ($km < 5) return 0.7;
        if ($km < 10) return 0.5;
        if ($km < 20) return 0.3;
        return max(0.1, 1.0 - ($km / 50));
    }

    protected function diversityScore(string $anchorType, string $candType): float
    {
        if ($anchorType === $candType) return 0.1;
        $affinity = self::TYPE_AFFINITY[$anchorType] ?? [];
        if (in_array($candType, $affinity, true)) {
            $pos = array_search($candType, $affinity, true);
            return 0.9 - ($pos * 0.06);
        }
        return 0.5;
    }

    protected function institutionReviewScore(CountyInstitution $inst): array
    {
        $reviewRows = DB::table('reviews')
            ->where('reviewable_type', CountyInstitution::class)
            ->where('reviewable_id', $inst->id)
            ->where('status', 'approved')
            ->selectRaw('AVG(rating) as avg_r, COUNT(*) as cnt')
            ->first();

        $realAvg = (float) ($reviewRows->avg_r ?? 0);
        $realCount = (int) ($reviewRows->cnt ?? 0);

        $seeds = ReviewSeed::where('owner_type', CountyInstitution::class)
            ->where('owner_id', $inst->id)->get();
        $seedAvgSum = 0.0;
        $seedCount = 0;
        foreach ($seeds as $s) {
            $seedAvgSum += (float) $s->rating * (int) $s->review_count;
            $seedCount += (int) $s->review_count;
        }

        $totalCount = $realCount + $seedCount;
        $avg = $totalCount > 0
            ? (($realAvg * $realCount) + $seedAvgSum) / $totalCount
            : 0.0;

        return [
            'count' => $totalCount,
            'average' => round($avg, 1),
            'score' => $totalCount > 0 ? min(1.0, ($avg * log(1 + $totalCount)) / 10) : 0.1,
        ];
    }

    protected function completenessScore(CountyInstitution $inst): float
    {
        $score = 0.0;
        if ($inst->description) $score += 0.35;
        if ($inst->story) $score += 0.20;
        if ($inst->cover_image_url || $inst->logo_url) $score += 0.20;
        if ($inst->lat && $inst->lng) $score += 0.15;
        if ($inst->website) $score += 0.10;
        return $score;
    }

    protected function sectorOverlapScore(CountyInstitution $anchor, CountyInstitution $cand): float
    {
        $anchorMappings = $anchor->sector_mappings ?? [];
        $candMappings = $cand->sector_mappings ?? [];
        if (empty($anchorMappings) || empty($candMappings)) return 0.3;

        $anchorSlugs = array_map(fn ($m) => $m['sector_slug'] ?? '', $anchorMappings);
        $candSlugs = array_map(fn ($m) => $m['sector_slug'] ?? '', $candMappings);

        $intersection = array_intersect($anchorSlugs, $candSlugs);
        $union = array_unique(array_merge($anchorSlugs, $candSlugs));

        if (empty($union)) return 0.3;
        $jaccard = count($intersection) / count($union);
        return 0.3 + ($jaccard * 0.7);
    }

    protected function haversine(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;
        return $earthRadius * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    protected function nearbyCountyIds(CountyInstitution $inst, array $primaryIds, float $radiusKm = 20): array
    {
        return $primaryIds;
    }

    protected function fetchProductCandidates(array $countyIds, CountyInstitution $anchor): array
    {
        if (!$anchor->user_id) return [];
        return Product::active()
            ->whereIn('county_id', $countyIds)
            ->where('user_id', '!=', $anchor->user_id)
            ->with(['county', 'variants', 'images'])
            ->take(4)
            ->get()
            ->toArray();
    }

    protected function getTransportOptions(CountyInstitution $institution): array
    {
        return app(TransportIntegrationService::class)->getOptions(
            (float) ($institution->lat ?? 0),
            (float) ($institution->lng ?? 0),
            $institution->county_id
        );
    }
}