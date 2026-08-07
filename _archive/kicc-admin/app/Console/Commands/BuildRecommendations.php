<?php

namespace App\Console\Commands;

use App\Models\County;
use App\Models\Marketplace\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Nightly recommendation engine — no external APIs needed.
 *
 * Reads from TiDB (orders, searches, bookings) and writes per-user
 * recommendations to the `recommendations` table. Served from
 * Redis/TiDB on every page. Scales to millions of users by running
 * as a batch job (not per-request).
 */
class BuildRecommendations extends Command
{
    protected $signature = 'recommendations:build';
    protected $description = 'Build personalized recommendations from user behavior data';

    public function handle(): int
    {
        $this->info('Building recommendations...');
        $now = now();

        // 1. Trending products (everyone sees these)
        $this->buildTrending($now);

        // 2. Seasonal weather-based picks
        $this->buildSeasonal($now);

        // 3. Per-user collaborative recommendations
        $this->buildPerUser($now);

        // 4. Similar products in same county/category
        $this->buildSimilar($now);

        $this->info('Done.');
        return self::SUCCESS;
    }

    protected function buildTrending($now): void
    {
        $products = DB::select("
            SELECT oi.product_id, COUNT(*) as c
            FROM order_items oi
            WHERE oi.created_at >= NOW() - INTERVAL 7 DAY
            GROUP BY oi.product_id
            ORDER BY c DESC
            LIMIT 12
        ");
        if (empty($products)) return;

        $ids = collect($products)->pluck('product_id');
        $pids = Product::with('variants', 'images')->whereIn('id', $ids)->where('status', 'active')->get();

        DB::table('recommendations')->updateOrInsert(
            ['user_id' => 0, 'type' => 'global_trending'],
            [
                'title' => '🔥 Trending Now in Kenya',
                'items' => json_encode($pids->map(fn ($p) => [
                    'id' => $p->id, 'name' => $p->name,
                    'slug' => $p->slug, 'price' => $p->price,
                    'image' => $p->image_url,
                ])->values()),
                'context' => 'trending',
                'expires_at' => $now->copy()->addDays(1),
                'created_at' => $now, 
            ]
        );
        $this->info('  Trending: ' . $pids->count() . ' products');
    }

    protected function buildSeasonal($now): void
    {
        $warm = County::whereIn('slug', [
            'mombasa', 'kwale', 'kilifi', 'lamu', 'taita-taveta',
        ])->pluck('id');

        $products = Product::with('variants', 'images')->whereIn('county_id', $warm)
            ->where('status', 'active')
            ->inRandomOrder()->limit(8)->get();

        DB::table('recommendations')->updateOrInsert(
            ['user_id' => 0, 'type' => 'seasonal_escape'],
            [
                'title' => '☀️ Escape the Cold — Warm Coast Getaways',
                'items' => json_encode($products->map(fn ($p) => [
                    'id' => $p->id, 'name' => $p->name,
                    'slug' => $p->slug, 'price' => $p->price,
                    'image' => $p->image_url,
                ])->values()),
                'context' => 'seasonal',
                'expires_at' => $now->copy()->addDays(7),
                'created_at' => $now, 
            ]
        );
        $this->info('  Seasonal: ' . $products->count() . ' products');
    }

    protected function buildPerUser($now): void
    {
        $userOrders = DB::table('orders')
            ->select('user_id')
            ->distinct()
            ->whereNotNull('user_id')
            ->get();

        $count = 0;
        foreach ($userOrders as $u) {
            $countyIds = DB::table('order_items')
                ->join('products', 'order_items.product_id', '=', 'products.id')
                ->where('orders.user_id', $u->user_id)
                ->join('orders', 'order_items.order_id', '=', 'orders.id')
                ->distinct()
                ->pluck('products.county_id');

            if ($countyIds->isEmpty()) continue;

            $bought = DB::table('order_items')
                ->join('orders', 'order_items.order_id', '=', 'orders.id')
                ->where('orders.user_id', $u->user_id)
                ->pluck('order_items.product_id');

            $similar = Product::with('variants', 'images')
                ->whereIn('county_id', $countyIds)
                ->whereNotIn('id', $bought)
                ->where('status', 'active')
                ->inRandomOrder()
                ->limit(8)
                ->get();

            if ($similar->isEmpty()) continue;

            DB::table('recommendations')->updateOrInsert(
                ['user_id' => $u->user_id, 'type' => 'personalized'],
                [
                    'title' => '📦 Based on your purchases',
                    'items' => json_encode($similar->map(fn ($p) => [
                        'id' => $p->id, 'name' => $p->name,
                        'slug' => $p->slug, 'price' => $p->price,
                        'image' => $p->image_url,
                    ])->values()),
                    'context' => 'collaborative',
                    'expires_at' => $now->copy()->addDays(3),
                    'created_at' => $now,
                ]
            );
            $count++;
        }
        $this->info('  Per-user: ' . $count . ' users with recommendations');
    }

    protected function buildSimilar($now): void
    {
        $products = Product::with('variants', 'images')
            ->where('status', 'active')
            ->whereNotNull('county_id')
            ->whereNotNull('category_id')
            ->get()
            ->groupBy(fn ($p) => $p->county_id . '_' . $p->category_id);

        $count = 0;
        foreach ($products as $group) {
            $ids = $group->pluck('id');
            foreach ($group as $p) {
                $others = $ids->filter(fn ($id) => $id !== $p->id)->take(4);
                if ($others->isEmpty()) continue;
                $similar = Product::with('variants', 'images')->whereIn('id', $others)->get();
                DB::table('recommendations')->updateOrInsert(
                    ['user_id' => 0, 'type' => 'similar_' . $p->id],
                    [
                        'title' => 'You might also like',
                        'items' => json_encode($similar->map(fn ($s) => [
                            'id' => $s->id, 'name' => $s->name,
                            'slug' => $s->slug, 'price' => $s->price,
                            'image' => $s->image_url,
                        ])->values()),
                        'context' => 'similar',
                        'expires_at' => $now->copy()->addDays(7),
                        'created_at' => $now,
                    ]
                );
                $count++;
            }
        }
        $this->info('  Similar: ' . $count . ' product groups');
    }
}
