<?php

namespace App\Console\Commands;

use App\Models\County;
use App\Models\Marketplace\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class WarmupCache extends Command
{
    protected $signature = 'kicc:warmup-cache';
    protected $description = 'Pre-warm homepage stats, county rollups, and marketplace counts.';

    public function handle(): int
    {
        Cache::put('homepage.stats', [
            'counties'    => County::count(),
            'products'    => Product::active()->count(),
            'updated_at'  => now()->toIso8601String(),
        ], 900);

        foreach (County::orderBy('name')->get() as $c) {
            Cache::put("county:{$c->slug}:products", Product::active()->where('county_id', $c->id)->count(), 900);
        }
        $this->info('Cache warmed.');
        return self::SUCCESS;
    }
}
