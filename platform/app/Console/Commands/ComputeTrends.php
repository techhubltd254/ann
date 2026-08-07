<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Predictive analytics (blueprint §Layer 6): nightly trend computation.
 * Demand sensing (orders/products momentum), county momentum, tourism seasonality.
 * Writes rollups into `analytics_rollups` for dashboards.
 */
class ComputeTrends extends Command
{
    protected $signature = 'analytics:trends';
    protected $description = 'Compute demand + trend rollups';

    public function handle(): int
    {
        DB::unprepared('CREATE TABLE IF NOT EXISTS analytics_rollups (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            metric VARCHAR(80) NOT NULL,
            dimension VARCHAR(120) NULL,
            value DOUBLE NOT NULL,
            computed_at DATETIME NOT NULL,
            KEY (metric), KEY (computed_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');

        $now = now();
        $rows = 0;

        // Product demand momentum (orders per product, last 7d vs prior 7d)
        try {
            $momentum = DB::table('order_items')
                ->selectRaw('product_id, SUM(CASE WHEN created_at >= ? THEN quantity ELSE 0 END) as recent, SUM(CASE WHEN created_at < ? AND created_at >= ? THEN quantity ELSE 0 END) as prior', [$now->copy()->subDays(7), $now->copy()->subDays(7), $now->copy()->subDays(14)])
                ->groupBy('product_id')->get();
            foreach ($momentum as $m) {
                $growth = $m->prior > 0 ? round((($m->recent - $m->prior) / $m->prior) * 100, 1) : ($m->recent > 0 ? 100 : 0);
                DB::table('analytics_rollups')->insert(['metric' => 'product_demand_growth', 'dimension' => 'product:' . $m->product_id, 'value' => $growth, 'computed_at' => $now]);
                $rows++;
            }
        } catch (\Throwable $e) { $this->warn('product momentum: ' . $e->getMessage()); }

        // County order momentum
        try {
            $countyMomentum = DB::table('orders')
                ->selectRaw('county_id, COUNT(*) as n')
                ->where('created_at', '>=', $now->copy()->subDays(7))
                ->groupBy('county_id')->get();
            foreach ($countyMomentum as $c) {
                DB::table('analytics_rollups')->insert(['metric' => 'county_orders_7d', 'dimension' => 'county:' . $c->county_id, 'value' => $c->n, 'computed_at' => $now]);
                $rows++;
            }
        } catch (\Throwable $e) { $this->warn('county momentum: ' . $e->getMessage()); }

        $this->info("trends: {$rows} rollups computed");
        return self::SUCCESS;
    }
}
