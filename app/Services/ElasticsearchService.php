<?php

namespace App\Services;

use Elastic\Elasticsearch\ClientBuilder;
use Illuminate\Support\Facades\Log;

/**
 * Elasticsearch wrapper — keyword search fallback + log analytics.
 *
 * Gracefully degrades to MySQL-based search when ES is unavailable
 * (no server running, no connection configured). This lets the code
 * stay in production without requiring ES infrastructure.
 */
class ElasticsearchService
{
    protected ?\Elastic\Elasticsearch\Client $client = null;
    protected bool $available = false;

    public function __construct()
    {
        $host = config('services.elasticsearch.host', '');
        if (! $host) {
            return;
        }

        try {
            $this->client = ClientBuilder::create()
                ->setHosts([$host])
                ->setSSLVerification(config('services.elasticsearch.ssl_verify', false))
                ->setBasicAuthentication(
                    config('services.elasticsearch.username', ''),
                    config('services.elasticsearch.password', ''),
                )
                ->build();
            $this->available = $this->client->ping();
        } catch (\Throwable $e) {
            Log::warning('Elasticsearch unavailable, using MySQL fallback', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function available(): bool
    {
        return $this->available;
    }

    /**
     * Index a document. Fields are auto-detected from the body.
     */
    public function index(string $index, string $id, array $body): bool
    {
        if (! $this->available) {
            return false;
        }

        try {
            $this->client->index([
                'index' => $index,
                'id' => $id,
                'body' => $body,
            ]);
            return true;
        } catch (\Throwable $e) {
            Log::warning('ES index failed', ['index' => $index, 'id' => $id, 'error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Bulk index multiple documents.
     */
    public function bulkIndex(string $index, array $documents): bool
    {
        if (! $this->available || empty($documents)) {
            return false;
        }

        $params = ['body' => []];
        foreach ($documents as $id => $body) {
            $params['body'][] = ['index' => ['_index' => $index, '_id' => (string) $id]];
            $params['body'][] = $body;
        }

        try {
            $this->client->bulk($params);
            return true;
        } catch (\Throwable $e) {
            Log::warning('ES bulk index failed', ['index' => $index, 'count' => count($documents), 'error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Search across multiple indices. Returns results compatible with
     * the semantic search response format.
     */
    public function search(string $query, array $indices = ['*'], int $limit = 10): array
    {
        if (! $this->available || mb_strlen($query) < 2) {
            return [];
        }

        try {
            $response = $this->client->search([
                'index' => implode(',', $indices),
                'body' => [
                    'size' => $limit,
                    'query' => [
                        'multi_match' => [
                            'query' => $query,
                            'fields' => ['name^3', 'description^2', 'content', 'tags', 'county_name'],
                            'type' => 'best_fields',
                            'fuzziness' => 'AUTO',
                        ],
                    ],
                ],
            ]);

            return $this->formatResults($response->asArray());
        } catch (\Throwable $e) {
            Log::warning('ES search failed', ['query' => $query, 'error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Delete a document from the index.
     */
    public function delete(string $index, string $id): bool
    {
        if (! $this->available) {
            return false;
        }

        try {
            $this->client->delete(['index' => $index, 'id' => $id]);
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Create an index with the given mapping (if it doesn't exist).
     */
    public function createIndex(string $name, array $mappings): bool
    {
        if (! $this->available) {
            return false;
        }

        try {
            $exists = $this->client->indices()->exists(['index' => $name]);
            if (! $exists) {
                $this->client->indices()->create([
                    'index' => $name,
                    'body' => [
                        'settings' => [
                            'number_of_shards' => 1,
                            'number_of_replicas' => 0,
                        ],
                        'mappings' => $mappings,
                    ],
                ]);
            }
            return true;
        } catch (\Throwable $e) {
            Log::warning('ES create index failed', ['index' => $name, 'error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Ensure search indices exist with proper mappings.
     */
    public function ensureIndices(): void
    {
        $indices = [
            'kicc_counties' => [
                'properties' => [
                    'name' => ['type' => 'text', 'analyzer' => 'standard'],
                    'description' => ['type' => 'text', 'analyzer' => 'standard'],
                    'region' => ['type' => 'keyword'],
                    'slug' => ['type' => 'keyword'],
                    'type' => ['type' => 'keyword'],
                ],
            ],
            'kicc_exhibitions' => [
                'properties' => [
                    'name' => ['type' => 'text', 'analyzer' => 'standard'],
                    'description' => ['type' => 'text', 'analyzer' => 'standard'],
                    'venue_name' => ['type' => 'text'],
                    'status' => ['type' => 'keyword'],
                    'start_date' => ['type' => 'date'],
                    'type' => ['type' => 'keyword'],
                ],
            ],
            'kicc_products' => [
                'properties' => [
                    'name' => ['type' => 'text', 'analyzer' => 'standard'],
                    'description' => ['type' => 'text', 'analyzer' => 'standard'],
                    'county_name' => ['type' => 'text'],
                    'category' => ['type' => 'keyword'],
                    'price' => ['type' => 'float'],
                    'type' => ['type' => 'keyword'],
                ],
            ],
        ];

        foreach ($indices as $name => $mappings) {
            $this->createIndex($name, $mappings);
        }
    }

    protected function formatResults(array $response): array
    {
        $results = [];
        $hits = $response['hits']['hits'] ?? [];

        foreach ($hits as $hit) {
            $source = $hit['_source'];
            $results[] = [
                'id' => $hit['_id'],
                'type' => $source['type'] ?? 'unknown',
                'name' => $source['name'] ?? '',
                'description' => $source['description'] ?? '',
                'score' => $hit['_score'] ?? 0,
                'index' => $hit['_index'] ?? '',
            ];
        }

        return $results;
    }
}