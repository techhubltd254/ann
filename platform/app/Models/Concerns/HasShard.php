<?php

namespace App\Models\Concerns;

use App\Services\ShardManager;
use Illuminate\Database\Eloquent\Model;

/**
 * HasShard — routes Eloquent models to the correct physical shard via
 * the 1024-virtual-partition system.
 *
 * Usage:
 *   class Product extends Model {
 *       use HasShard;
 *       protected string $entityType = 'product';
 *   }
 *
 * The model will automatically read/write from the correct shard connection
 * by hashing entity_type + primary_key into a virtual partition, then
 * looking up which physical shard hosts that partition.
 *
 * For listing queries that span shards (e.g. "all products in county X"),
 * use ShardManager::eachShard() and merge results.
 */
trait HasShard
{
    /**
     * Boot the trait — hook into Eloquent's lifecycle.
     */
    protected static function bootHasShard(): void
    {
        static::addGlobalScope('shard', function ($builder) {
            $instance = $builder->getModel();
            $connection = $instance->resolveShardConnection();

            if ($connection && $connection !== $instance->getConnectionName()) {
                $builder->getConnection()->disconnect();
                $instance->setConnection($connection);
                $builder->from($instance->getTable());
            }
        });

        static::creating(function (Model $model) {
            $model->setShardConnection();
        });

        static::retrieved(function (Model $model) {
            $model->setShardConnection();
        });
    }

    /**
     * Get the configured entity type for shard routing.
     * Override in your model: protected string $entityType = 'product';
     */
    public function getEntityType(): string
    {
        return $this->entityType ?? $this->getTable();
    }

    /**
     * Resolve the shard connection name for this model instance.
     */
    public function resolveShardConnection(): ?string
    {
        $pk = $this->getKey();
        if (!$pk) {
            return null;
        }

        return app(ShardManager::class)->connectionFor(
            $this->getEntityType(),
            (int) $pk
        );
    }

    /**
     * Assign the correct shard connection to this model instance.
     */
    public function setShardConnection(): void
    {
        $connection = $this->resolveShardConnection();
        if ($connection && $connection !== $this->getConnectionName()) {
            $this->setConnection($connection);
        }
    }

    /**
     * Override newQuery to ensure query builder routes to the correct shard.
     */
    public function newQuery()
    {
        $query = parent::newQuery();
        $connection = $this->resolveShardConnection();
        if ($connection) {
            $query->setConnection($connection);
        }
        return $query;
    }

    /**
     * Get the shard connection name for a specific entity ID of this type.
     */
    public static function shardConnectionForId(int $id): string
    {
        $instance = new static;
        return app(ShardManager::class)->connectionFor($instance->getEntityType(), $id);
    }

    /**
     * Execute a query on every shard that MAY contain this model's data.
     * Since entities are distributed via hash, ALL shards may contain data.
     * Results are keyed by connection name.
     */
    public static function onAllShards(callable $query): array
    {
        return app(ShardManager::class)->eachShard($query);
    }

    /**
     * Parallel scatter-gather: run a query on ALL shards simultaneously
     * and merge the results into a single collection.
     *
     * @param  callable  $shardQuery  fn($connectionName) => Collection
     * @return \Illuminate\Support\Collection
     */
    public static function scatterGather(callable $shardQuery): \Illuminate\Support\Collection
    {
        $results = app(ShardManager::class)->eachShardParallel($shardQuery);
        $merged = new \Illuminate\Support\Collection;

        foreach ($results as $collection) {
            if ($collection instanceof \Illuminate\Support\Collection) {
                $merged = $merged->merge($collection);
            }
        }

        return $merged;
    }
}
