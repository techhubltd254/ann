<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * ScatterGather — fan-out queries to all physical shards and merge results.
 *
 * Used for county-wide list queries where the shard key is unknown
 * (e.g. "all products in Mombasa" — data may be on any of the 1024 partitions).
 *
 * With the 1024-virtual-partition scheme, every shard potentially holds data
 * for every county, so scatter-gather is required for unkeyed list queries.
 *
 * OPTIMIZATION
 * ────────────
 * Add a search index (Elasticsearch/Meilisearch) for production at scale.
 * Until then, responses are cached with the county_id as part of the key.
 * Parallel execution via eachShardParallel keeps latency manageable.
 */
class ScatterGather
{
    private ShardManager $shardManager;

    public function __construct(ShardManager $shardManager)
    {
        $this->shardManager = $shardManager;
    }

    /**
     * Run a query on every shard and merge results into a single collection.
     *
     * @param  callable  $shardQuery  fn(string $connectionName) => Collection|array
     * @param  int       $cacheTTL    Cache results for this many seconds (0 = no cache)
     * @param  string    $cacheKey    Custom cache key
     * @return Collection
     */
    public function gather(callable $shardQuery, int $cacheTTL = 0, string $cacheKey = ''): Collection
    {
        if ($cacheTTL > 0 && $cacheKey) {
            return Cache::remember($cacheKey, $cacheTTL, function () use ($shardQuery) {
                return $this->execute($shardQuery);
            });
        }

        return $this->execute($shardQuery);
    }

    /**
     * Paginate scatter-gather results across all shards.
     *
     * @param  callable  $shardQuery  fn(string $conn, int $offset, int $limit) => Collection
     * @param  int       $page
     * @param  int       $perPage
     * @return array{data: Collection, total: int, page: int, perPage: int}
     */
    public function paginate(callable $shardQuery, int $page = 1, int $perPage = 20): array
    {
        $offset = ($page - 1) * $perPage;
        $perShard = (int) ceil($perPage / $this->shardManager->physicalShardCount());

        $allResults = $this->shardManager->eachShardParallel(
            fn ($conn) => $shardQuery($conn, $offset, $perShard)
        );

        $merged = new Collection;
        foreach ($allResults as $result) {
            if ($result instanceof Collection) {
                $merged = $merged->merge($result);
            } elseif (is_array($result)) {
                $merged = $merged->merge($result);
            }
        }

        return [
            'data' => $merged->forPage($page, $perPage)->values(),
            'total' => $merged->count(),
            'page' => $page,
            'perPage' => $perPage,
        ];
    }

    private function execute(callable $shardQuery): Collection
    {
        $results = $this->shardManager->eachShardParallel($shardQuery);

        $merged = new Collection;
        foreach ($results as $result) {
            if ($result instanceof Collection) {
                $merged = $merged->merge($result);
            } elseif (is_array($result)) {
                $merged = $merged->merge($result);
            }
        }

        return $merged;
    }
}
