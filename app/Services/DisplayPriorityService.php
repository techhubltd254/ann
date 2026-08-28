<?php

namespace App\Services;

use App\Models\County;
use App\Models\Marketplace\Product;
use App\Models\Review;
use App\Models\ReviewSeed;
use Illuminate\Support\Facades\DB;

/**
 * Display prioritization — decides what appears on the marketplace & home.
 *
 * Rules:
 *  1. Only counties with real synced data are visible (Mombasa + Muranga for now).
 *  2. Sector representation: every sector gets a slot (one top product per sector per page).
 *  3. Within a sector, rank by review score = avg(rating) * log(1 + count)
 *     (blends seeded online reviews with real user reviews so a 4.8×200
 *      beats a 5.0×1).
 */
class DisplayPriorityService
{
    /** Counties currently eligible for display (real synced data only). */
    public const DISPLAY_COUNTY_SLUGS = ['mombasa', 'muranga'];

    public function displayCountyIds(): array
    {
        return County::whereIn('slug', self::DISPLAY_COUNTY_SLUGS)->pluck('id')->all();
    }

    /**
     * Ordered product IDs for the marketplace grid:
     * one top product per sector first, then the rest, all review-ranked.
     */
    public function marketplaceProductIds(?string $categorySlug = null): array
    {
        $countyIds = $this->displayCountyIds();
        if (empty($countyIds)) {
            return [];
        }

        $categoryJoin = '';
        $categoryFilter = '';
        $bindings = [...$countyIds];
        if ($categorySlug) {
            $categoryJoin = 'JOIN product_categories pc ON pc.id = p.category_id';
            $categoryFilter = ' AND pc.slug = ?';
            $bindings[] = $categorySlug;
        }

        $bindings[] = 'active';

        $rows = DB::select("
            SELECT p.id, p.category_id
            FROM products p
            $categoryJoin
            WHERE p.county_id IN (" . implode(',', array_fill(0, count($countyIds), '?')) . ")
              $categoryFilter
              AND p.status = ? AND p.deleted_at IS NULL
            ORDER BY p.created_at DESC
        ", $bindings);

        $ids = array_map(fn ($r) => (int) $r->id, $rows);
        if (empty($ids)) {
            return [];
        }

        // Sector-first ordering: one per category, then the rest
        $seen = [];
        $ordered = [];
        foreach ($ids as $id) {
            $cat = null;
            foreach ($rows as $r) {
                if ((int) $r->id === $id) { $cat = (int) $r->category_id; break; }
            }
            if ($cat !== null && !isset($seen[$cat])) {
                $seen[$cat] = true;
                array_unshift($ordered, $id);
            } else {
                $ordered[] = $id;
            }
        }

        // Review-score ranking within the sector-first block
        return $this->rankByReview($ordered);
    }

    /**
     * Rank IDs by blended review score: avg(rating) * log(1 + count).
     * Combines real user reviews (reviews/product_reviews) + seeded online reviews.
     */
    public function rankByReview(array $ids): array
    {
        if (empty($ids)) {
            return [];
        }

        $scores = [];

        // Real user reviews (polymorphic reviews + product_reviews)
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

        // Seeded online reviews
        $seedRows = ReviewSeed::whereIn('owner_type', [Product::class, 'product'])
            ->whereIn('owner_id', $ids)
            ->get();

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
            foreach ($seedRows as $s) {
                if ((int) $s->owner_id === $id) { $seedAvg = (float) $s->rating; $seedCount = (int) $s->review_count; break; }
            }

            $avg = $realCount > 0 ? (($realAvg * $realCount) + ($seedAvg * $seedCount)) / max(1, $realCount + $seedCount) : $seedAvg;
            $count = $realCount + $seedCount;

            $scores[$id] = $count > 0 ? $avg * log(1 + $count) : 0.0;
        }

        uasort($scores, fn ($a, $b) => $b <=> $a);
        return array_keys($scores);
    }

    /** Review score summary for a single product (for badges/widgets). */
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

        $seed = ReviewSeed::where('owner_type', Product::class)->where('owner_id', $productId)->first();

        $avg = $realCount > 0 && $seed
            ? (($realAvg * $realCount) + ((float) $seed->rating * (int) $seed->review_count)) / max(1, $realCount + (int) $seed->review_count)
            : ($realCount > 0 ? $realAvg : ($seed ? (float) $seed->rating : 0));

        return [
            'average' => round($avg, 1),
            'count' => $realCount + ($seed ? (int) $seed->review_count : 0),
            'real_count' => $realCount,
            'seed_source' => $seed?->source,
        ];
    }
}