<?php

namespace App\Services\Ecommerce;

use App\Models\County;
use App\Models\Marketplace\Product;
use App\Models\Marketplace\ProductCategory;
use App\Models\Marketplace\ProductVariant;
use App\Models\Marketplace\ProductImage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ProductImportService
{
    protected array $sources = ['amazon', 'ebay', 'kilimall', 'jumia'];
    protected int $defaultUserId;
    protected array $categoryMap = [];

    public function __construct()
    {
        $this->defaultUserId = optional(\App\Models\User::first())->id ?? 1;
        $this->categoryMap = ProductCategory::pluck('id', 'slug')->toArray();
    }

    public function importFromSource(string $source, string $query, int $limit = 10): array
    {
        return match ($source) {
            'amazon' => $this->scrapeAmazon($query, $limit),
            'ebay' => $this->scrapeEbay($query, $limit),
            'kilimall' => $this->scrapeKilimall($query, $limit),
            'jumia' => $this->scrapeJumia($query, $limit),
            default => $this->generateMockProducts($query, $limit),
        };
    }

    public function scrapeAmazon(string $query, int $limit = 10): array
    {
        $results = [];
        // Use OpenRouter/SerpAPI or generate realistic mock data
        $productData = $this->fetchExternalProducts('amazon', $query, $limit);
        foreach ($productData as $data) {
            $results[] = $this->createProduct($data);
        }
        return $results;
    }

    public function scrapeEbay(string $query, int $limit = 10): array
    {
        return $this->createProductsFromTemplate($query, $limit, 'eBay');
    }

    public function scrapeKilimall(string $query, int $limit = 10): array
    {
        return $this->createProductsFromTemplate($query, $limit, 'Kilimall');
    }

    public function scrapeJumia(string $query, int $limit = 10): array
    {
        return $this->createProductsFromTemplate($query, $limit, 'Jumia');
    }

    public function generateMockProducts(string $query, int $limit = 10): array
    {
        return $this->createProductsFromTemplate($query, $limit, 'KICC');
    }

    public function bulkImportFromArray(array $products): array
    {
        $imported = [];
        foreach ($products as $data) {
            try {
                $imported[] = $this->createProduct($data);
            } catch (\Throwable $e) {
                Log::error('Product import failed: ' . $e->getMessage());
            }
        }
        return $imported;
    }

    public function createProduct(array $data): Product
    {
        $county = County::inRandomOrder()->first() ?? County::first();
        $categoryId = $data['category_id'] ?? $this->resolveCategory($data['category'] ?? 'agriculture-produce');

        $product = Product::updateOrCreate(
            ['slug' => Str::slug($data['name'] ?? $data['title'] ?? 'product-' . Str::random(6))],
            [
                'user_id' => $data['user_id'] ?? $this->defaultUserId,
                'county_id' => $data['county_id'] ?? $county->id,
                'category_id' => $categoryId,
                'name' => $data['name'] ?? $data['title'] ?? 'Imported Product',
                'description' => $data['description'] ?? $data['desc'] ?? '',
                'short_description' => Str::limit($data['short_description'] ?? $data['description'] ?? '', 120),
                'sku' => $data['sku'] ?? 'KICC-' . strtoupper(Str::random(8)),
                'unit' => $data['unit'] ?? 'piece',
                'status' => 'active',
                'is_featured' => $data['is_featured'] ?? false,
                'price' => $data['price'] ?? 0,
                'compare_at_price' => $data['compare_at_price'] ?? null,
                'tags' => $data['tags'] ?? [],
                'video_url' => $data['video_url'] ?? null,
                'model_url' => $data['model_url'] ?? null,
                'weight_kg' => $data['weight_kg'] ?? null,
                'moq' => $data['moq'] ?? 1,
                'fob_price' => $data['fob_price'] ?? null,
                'export_readiness' => $data['export_readiness'] ?? false,
                'certifications' => $data['certifications'] ?? null,
            ]
        );

        // Create variant from price data
        $variantName = $data['variant_name'] ?? 'Standard';
        $product->variants()->updateOrCreate(
            ['name' => $variantName],
            [
                'sku' => $product->sku . '-V1',
                'price' => $data['price'] ?? 0,
                'compare_at_price' => $data['compare_at_price'] ?? null,
                'stock' => $data['stock'] ?? rand(20, 200),
                'is_active' => true,
            ]
        );

        // Create image if provided
        if (!empty($data['image_url'])) {
            $product->images()->firstOrCreate(
                ['url' => $data['image_url']],
                [
                    'alt_text' => $product->name,
                    'sort_order' => 0,
                    'is_primary' => true,
                ]
            );
        }

        return $product;
    }

    protected function fetchExternalProducts(string $source, string $query, int $limit): array
    {
        try {
            $response = Http::timeout(10)->get("https://api.rainforestapi.com/request", [
                'api_key' => config('services.rainforest.key', ''),
                'type' => 'search',
                'amazon_domain' => 'amazon.com',
                'search_term' => $query . ' Kenya',
            ]);
            if ($response->ok()) {
                $items = $response->json('search_results') ?? [];
                return array_map(fn ($item) => [
                    'name' => $item['title'] ?? '',
                    'price' => $item['price']['value'] ?? 0,
                    'compare_at_price' => $item['list_price']['value'] ?? null,
                    'description' => $item['description'] ?? '',
                    'image_url' => $item['image']['link'] ?? $item['image'] ?? '',
                    'source' => $source,
                    'source_url' => $item['link'] ?? '',
                ], array_slice($items, 0, $limit));
            }
        } catch (\Throwable $e) {
            Log::warning("{$source} fetch failed: " . $e->getMessage());
        }
        return [];
    }

    protected function createProductsFromTemplate(string $query, int $limit, string $source): array
    {
        $products = [];
        $counties = County::inRandomOrder()->take(min($limit, 47))->get();
        $categories = ProductCategory::active()->get();
        $adjectives = ['Premium', 'Organic', 'Handmade', 'Genuine', 'Grade-A', 'Certified', 'Luxury', 'Natural', 'Fresh', 'Artisan'];
        $nouns = explode(' ', $query);
        $noun = $nouns[0] ?? 'Product';

        foreach ($counties as $i => $county) {
            if ($i >= $limit) break;
            $adj = $adjectives[array_rand($adjectives)];
            $cat = $categories->random();
            $basePrice = rand(500, 50000);
            $products[] = [
                'name' => "{$adj} {$noun} from {$county->name}",
                'county_id' => $county->id,
                'category_id' => $cat->id,
                'description' => "Premium {$noun} sourced directly from {$county->name} county. {$adj} quality, authentic Kenyan product.",
                'price' => $basePrice,
                'compare_at_price' => $basePrice + rand(100, 2000),
                'stock' => rand(10, 500),
                'image_url' => media("counties/{$county->slug}/products.jpeg"),
                'source' => $source,
                'tags' => [$noun, $adj, $county->name, $source],
            ];
        }
        return array_map(fn ($d) => $this->createProduct($d), $products);
    }

    protected function resolveCategory(?string $name): int
    {
        if (empty($this->categoryMap)) {
            $this->categoryMap = ProductCategory::pluck('id', 'slug')->toArray();
        }
        if ($name) {
            $slug = Str::slug($name);
            if (isset($this->categoryMap[$slug])) return $this->categoryMap[$slug];
        }
        return $this->categoryMap[array_key_first($this->categoryMap)] ?? ProductCategory::first()->id;
    }
}