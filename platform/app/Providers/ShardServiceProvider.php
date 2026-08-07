<?php

namespace App\Providers;

use App\Services\ShardManager;
use Illuminate\Support\ServiceProvider;

class ShardServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ShardManager::class, function () {
            return new ShardManager;
        });
    }

    public function boot(): void
    {
        // Register shard connections from env
        $this->configureShardConnections();
    }

    private function configureShardConnections(): void
    {
        $shardCount = 4;

        for ($i = 0; $i < $shardCount; $i++) {
            $prefix = 'SHARD_' . $i;
            $connection = 'shard_' . $i;

            $dbName = env($prefix . '_DATABASE', "kicc_$connection");

            config([
                "database.connections.$connection.database" => $dbName,
                "database.connections.$connection.host" => env($prefix . '_HOST', env('DB_HOST')),
                "database.connections.$connection.port" => env($prefix . '_PORT', env('DB_PORT')),
                "database.connections.$connection.username" => env($prefix . '_USERNAME', env('DB_USERNAME')),
                "database.connections.$connection.password" => env($prefix . '_PASSWORD', env('DB_PASSWORD')),
            ]);
        }
    }
}
