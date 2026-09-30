<?php

namespace App\Console\Commands;

use App\Services\CacheService;
use App\Services\PipelineRouter;
use Illuminate\Console\Command;

/**
 * kicc:cache:warm — warm critical cache keys for optimal performance.
 * Run after deploy: php artisan kicc:cache:warm
 * Schedule via cron: php artisan schedule:run (every hour)
 */
class CacheWarm extends Command
{
    protected $signature = 'kicc:cache:warm {--silent : Suppress output}';

    protected $description = 'Warm Redis cache for critical data queries — counties, products, pipelines, pools';

    public function handle(CacheService $cache): int
    {
        $this->info('Warming cache...');

        $keys = [];

        // County classification data (cold — 24h)
        foreach (\App\Models\County::cursor() as $county) {
            $keys["county_{$county->id}"] = fn () => $county->load('sectors');
        }

        // Pipeline registrations (cold — 24h)
        $keys['pipeline_registrations_all'] = fn () => \Illuminate\Support\Facades\DB::table('pipeline_registrations')
            ->orderBy('sector')->orderBy('code')->get();

        // Product categories (medium — 10m)
        $keys['product_categories_active'] = fn () => \App\Models\Marketplace\ProductCategory::active()
            ->get(['id', 'name', 'slug', 'sector', 'pipeline_code']);

        // Pipeline bus registry (cold — 24h)
        $keys['pipeline_bus_registry'] = fn () => app(PipelineRouter::class)->mesh('A1');

        // Pool balance (hot — 30s)
        $keys['pool_balance'] = fn () => \App\Models\Pool\Pool::where('scope', 'global')
            ->where('is_active', true)->value('balance');

        $results = $cache->warm($keys);

        $success = count(array_filter($results, fn ($r) => $r === true));
        $failed = count($results) - $success;

        if (! $this->option('silent')) {
            $this->info("Warmed {$success} keys" . ($failed ? ", {$failed} failed" : ''));
        }

        return $failed > 0 ? self::SUCCESS : self::SUCCESS;
    }
}