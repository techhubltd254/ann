<?php

namespace App\Services;

use App\Models\County;
use App\Models\Marketplace\Product;
use App\Models\ReviewSeed;
use Illuminate\Support\Facades\DB;

/**
 * Display prioritization — a genuine review-driven recommendation algorithm.
 *
 * Scoring (per product):
 *   1. reviewScore = avgRating * log(1 + reviewCount)   — DOMINANT signal.
 *      Blends seeded online reviews (Google/Tripadvisor) with real user reviews,
 *      so a 4.8×200 genuinely beats a 5.0×1, and no county gets artificial help.
 *   2. sectorBoost — small, soft diversity weight so grids aren't monotone:
 *      the top-ranked product of each sector gets +0.08. Never lets a weak
 *      product displace a strong review score.
 *   3. completeness + freshness — tiny tiebreakers when scores are near-equal.
 *
 * Only counties with REAL synced data are eligible (auto-detected: >10 products).
 * Seed data (3-7 products per county) is excluded. No hardcoded county list.
 */
class DisplayPriorityService
{
    /** Minimum active products for a county to qualify as having real synced data. */
    public const SYNCED_THRESHOLD = 10;

    /**
     * Counties with real synced data — auto-detected by product count.
     * Seed data never exceeds 7 products per county; real data has 100+.
     */
    public function displayCountyIds(): array
    {
        return County::whereHas('products', function ($q) {
            $q->active();
        }, '>=', self::SYNCED_THRESHOLD)->pluck('id')->all();
    }

    /**
     * Recommended product IDs for the marketplace grid / home section.
     */
    public function marketplaceProductIds(?string $categorySlug = null): array
    {
        $countyIds = $this->displayCountyIds();
        if (empty($countyIds)) {
            return [];
        }

        $bindings = [...$countyIds];
        $categoryJoin = '';
        $categoryFilter = '';
        if ($categorySlug) {
            $categoryJoin = 'JOIN product_categories pc ON pc.id = p.category_id';
            $categoryFilter = ' AND pc.slug = ?';
            $bindings[] = $categorySlug;
        }
        $bindings[] = 'active';

        $rows = DB::select("
            SELECT p.id, p.county_id, p.category_id, p.created_at,
                   (p.video_url IS NOT NULL OR p.videos IS NOT NULL) AS has_media
            FROM products p
            $categoryJoin
            WHERE p.county_id IN (" . implode(',', array_fill(0, count($countyIds), '?')) . ")
              $categoryFilter
              AND p.status = ? AND p.deleted_at IS NULL
        ", $bindings);

        $ids = array_map(fn ($r) => (int) $r->id, $rows);
        if (empty($ids)) {
            return [];
        }

        return $this->recommend($ids, $rows);
    }

    /**
     * Core recommendation: review-score ranking with soft sector diversity
     * and tiny completeness/freshness tiebreakers.
     */
    protected function recommend(array $ids, array $rows): array
    {
        $scores = $this->reviewScores($ids);

        $meta = [];
        foreach ($rows as $r) {
            $meta[(int) $r->id] = $r;
        }

        // Sector leaders (top review score per category) get a soft boost
        $sectorBest = [];
        foreach ($ids as $id) {
            $cat = $meta[$id]->category_id ?? null;
            if ($cat === null) continue;
            $cur = $sectorBest[$cat] ?? null;
            if ($cur === null || ($scores[$id] ?? 0) > ($scores[$cur] ?? 0)) {
                $sectorBest[$cat] = $id;
            }
        }

        $now = now()->timestamp;
        $scored = [];
        foreach ($ids as $id) {
            $review = $scores[$id] ?? 0.0;
            $boost = isset($sectorBest[$meta[$id]->category_id ?? -1]) && $sectorBest[$meta[$id]->category_id ?? -1] === $id ? 0.08 : 0.0;

            // Completeness: products with media get a tiny edge over bare listings
            $completeness = !empty($meta[$id]->has_media) ? 0.02 : 0.0;

            // Freshness: near-zero recency weight (newer breaks ties only)
            $ageDays = max(0, ($now - strtotime($meta[$id]->created_at)) / 86400);
            $freshness = $ageDays < 14 ? 0.01 : 0.0;

            $scored[$id] = $review + $boost + $completeness + $freshness;
        }

        arsort($scored);
        return array_keys($scored);
    }

    /**
     * Blended review score per product: avgRating * log(1 + count).
     * Real user reviews + seeded online reviews.
     */
    public function reviewScores(array $ids): array
    {
        if (empty($ids)) {
            return [];
        }

        $reviewRows = DB::table('reviews')
            ->where('reviewable_type', Product::class)
            ->whereIn('reviewable_id', $ids)
            ->where('status', 'approved')
            ->selectRaw('reviewable_id, AVG(rating) as avg_r, COUNT(*) as cnt')
            ->groupBy('reviewable_id')
            ->get();
        $prRows = DB::table('product_reviews')
            ->whereIn('product_id', $ids)
            ->where('is_approved', true)
            ->selectRaw('product_id, AVG(rating) as avg_r, COUNT(*) as cnt')
            ->groupBy('product_id')
            ->get();
        $seedRows = ReviewSeed::whereIn('owner_type', [Product::class, 'product'])
            ->whereIn('owner_id', $ids)
            ->get()
            ->groupBy('owner_id');

        $scores = [];
        foreach ($ids as $id) {
            $realAvg = 0.0;
            $realCount = 0;
            foreach ($reviewRows as $r) {
                if ((int) $r->reviewable_id === $id) { $realAvg = (float) $r->avg_r; $realCount = (int) $r->cnt; break; }
            }
            if ($realCount === 0) {
                foreach ($prRows as $r) {
                    if ((int) $r->product_id === $id) { $realAvg = (float) $r->avg_r; $realCount = (int) $r->cnt; break; }
                }
            }

            $seedAvg = 0.0;
            $seedCount = 0;
            foreach (($seedRows->get($id) ?? collect()) as $s) {
                $seedAvg += (float) $s->rating * (int) $s->review_count;
                $seedCount += (int) $s->review_count;
            }

            $totalCount = $realCount + $seedCount;
            $avg = $totalCount > 0
                ? (($realAvg * $realCount) + $seedAvg) / $totalCount
                : 0.0;

            $scores[$id] = $totalCount > 0 ? $avg * log(1 + $totalCount) : 0.0;
        }

        return $scores;
    }

    /** Review score summary for a single product (badges/widgets). */
    public function scoreFor(int $productId): array
    {
        $real = DB::table('product_reviews')
            ->where('product_id', $productId)->where('is_approved', true)
            ->selectRaw('AVG(rating) as avg_r, COUNT(*) as cnt')->first();
        $real2 = DB::table('reviews')
            ->where('reviewable_type', Product::class)->where('reviewable_id', $productId)->where('status', 'approved')
            ->selectRaw('AVG(rating) as avg_r, COUNT(*) as cnt')->first();

        $realAvg = (float) ($real->avg_r ?? $real2->avg_r ?? 0);
        $realCount = (int) ($real->cnt ?? 0) + (int) ($real2->cnt ?? 0);

        $seeds = ReviewSeed::where('owner_type', Product::class)->where('owner_id', $productId)->get();
        $seedAvgSum = 0.0;
        $seedCount = 0;
        $seedSource = null;
        foreach ($seeds as $s) {
            $seedAvgSum += (float) $s->rating * (int) $s->review_count;
            $seedCount += (int) $s->review_count;
            $seedSource = $s->sourceLabel();
        }

        $totalCount = $realCount + $seedCount;
        $avg = $totalCount > 0 ? (($realAvg * $realCount) + $seedAvgSum) / $totalCount : 0;

        return [
            'average' => round($avg, 1),
            'count' => $totalCount,
            'real_count' => $realCount,
            'seed_source' => $seedSource,
        ];
    }
}