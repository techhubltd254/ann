<?php

namespace App\Console\Commands;

use App\Models\County;
use App\Models\Exhibition;
use App\Models\Marketplace\Product;
use App\Services\ElasticsearchService;
use Illuminate\Console\Command;

/**
 * Index all searchable content into Elasticsearch.
 * Runs silently when ES is unavailable (MySQL fallback mode).
 */
class IndexToElasticsearch extends Command
{
    protected $signature = 'search:index-es';
    protected $description = 'Index counties, exhibitions, products into Elasticsearch';

    public function handle(ElasticsearchService $es): int
    {
        if (! $es->available()) {
            $this->warn('Elasticsearch not available — skipping index (MySQL fallback is active)');
            return self::SUCCESS;
        }

        $es->ensureIndices();
        $count = 0;

        // Index counties
        foreach (County::cursor() as $county) {
            $es->index('kicc_counties', 'county_' . $county->id, [
                'name' => $county->name,
                'description' => $county->description ?? '',
                'region' => $county->former_province ?? '',
                'slug' => $county->slug,
                'type' => 'county',
            ]);
            $count++;
        }

        // Index exhibitions
        foreach (Exhibition::cursor() as $exhibition) {
            $es->index('kicc_exhibitions', 'exhibition_' . $exhibition->id, [
                'name' => $exhibition->name,
                'description' => $exhibition->description ?? '',
                'venue_name' => $exhibition->venue?->name ?? '',
                'status' => $exhibition->status ?? '',
                'start_date' => $exhibition->start_date?->toIso8601String() ?? '',
                'type' => 'exhibition',
            ]);
            $count++;
        }

        // Index products
        foreach (Product::cursor() as $product) {
            $es->index('kicc_products', 'product_' . $product->id, [
                'name' => $product->name,
                'description' => $product->description ?? '',
                'county_name' => $product->county?->name ?? '',
                'category' => $product->category?->name ?? '',
                'price' => (float) ($product->price ?? 0),
                'type' => 'product',
            ]);
            $count++;
        }

        $this->info("Indexed {$count} documents into Elasticsearch");

        return self::SUCCESS;
    }
}