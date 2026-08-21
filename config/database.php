<?php

use Illuminate\Support\Str;
use Pdo\Mysql;

return [

    /*
    | Default: TiDB (production app data). The 'mysql' connection is reserved
    | for the host platform (Laravel Cloud) internal use. All app queries
    | (counties, products, users, etc.) go through the 'tidb' connection.
    */
    'default' => env('DB_CONNECTION', 'tidb'),

    'connections' => [

        'sqlite' => [
            'driver' => 'sqlite',
            'url' => env('DB_URL'),
            'database' => env('DB_DATABASE', database_path('database.sqlite')),
            'prefix' => '',
            'foreign_key_constraints' => env('DB_FOREIGN_KEYS', true),
            'busy_timeout' => null,
            'journal_mode' => null,
            'synchronous' => null,
            'transaction_mode' => 'DEFERRED',
        ],

        'mysql' => [
            'driver' => 'mysql',
            'url' => env('DB_URL'),
            // Laravel Cloud injects DB_HOST for its own internal MySQL.
            // We NEVER use the injected DB_HOST — always use TIDB_* vars.
            'host' => env('TIDB_HOST', 'gateway01.eu-central-1.prod.aws.tidbcloud.com'),
            'port' => env('TIDB_PORT', '4000'),
            'database' => env('TIDB_DATABASE', 'kicc'),
            'username' => env('TIDB_USERNAME', '28dbcDfwh5hEbSc.root'),
            'password' => env('TIDB_PASSWORD', 'D8trCZaYhqZWo5Vq'),
            'unix_socket' => env('DB_SOCKET', ''),
            'charset' => env('DB_CHARSET', 'utf8mb4'),
            'collation' => env('DB_COLLATION', 'utf8mb4_unicode_ci'),
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => null,
            'options' => extension_loaded('pdo_mysql') ? array_filter([
                Mysql::ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA', '/etc/ssl/certs/ca-certificates.crt'),
                Mysql::ATTR_SSL_VERIFY_SERVER_CERT => env('DB_SSL_VERIFY', false),
            ], fn($v) => $v !== null && $v !== '') : [],
        ],

        /*
        | TiDB Cloud — production app data (counties, products, users, etc.).
        | This connection is separate from the host platform's MySQL so that
        | Laravel Cloud's injected DB_* vars don't conflict with our TiDB.
        */
        'tidb' => [
            'driver' => 'mysql',
            'host' => env('TIDB_HOST', 'gateway01.eu-central-1.prod.aws.tidbcloud.com'),
            'port' => env('TIDB_PORT', '4000'),
            'database' => env('TIDB_DATABASE', 'kicc'),
            'username' => env('TIDB_USERNAME', '28dbcDfwh5hEbSc.root'),
            'password' => env('TIDB_PASSWORD', 'D8trCZaYhqZWo5Vq'),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => null,
            'options' => extension_loaded('pdo_mysql') ? array_filter([
                Mysql::ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA', '/etc/ssl/certs/ca-certificates.crt'),
                Mysql::ATTR_SSL_VERIFY_SERVER_CERT => env('DB_SSL_VERIFY', false),
            ], fn($v) => $v !== null && $v !== '') : [],
        ],

        'mariadb' => [
            'driver' => 'mariadb',
            'url' => env('DB_URL'),
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '3306'),
            'database' => env('DB_DATABASE', 'laravel'),
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', ''),
            'unix_socket' => env('DB_SOCKET', ''),
            'charset' => env('DB_CHARSET', 'utf8mb4'),
            'collation' => env('DB_COLLATION', 'utf8mb4_unicode_ci'),
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => null,
            'options' => extension_loaded('pdo_mysql') ? array_filter([
                Mysql::ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
                Mysql::ATTR_SSL_VERIFY_SERVER_CERT => env('DB_SSL_VERIFY', true),
            ], fn($v) => $v !== null && $v !== '') : [],
        ],

        'pgsql' => [
            'driver' => 'pgsql',
            'url' => env('DB_URL'),
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '5432'),
            'database' => env('DB_DATABASE', 'laravel'),
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => env('DB_CHARSET', 'utf8'),
            'prefix' => '',
            'prefix_indexes' => true,
            'search_path' => 'public',
            'sslmode' => env('DB_SSLMODE', 'prefer'),
        ],

        'sqlsrv' => [
            'driver' => 'sqlsrv',
            'url' => env('DB_URL'),
            'host' => env('DB_HOST', 'localhost'),
            'port' => env('DB_PORT', '1433'),
            'database' => env('DB_DATABASE', 'laravel'),
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => env('DB_CHARSET', 'utf8'),
            'prefix' => '',
            'prefix_indexes' => true,
        ],

        // ── Read replica / reporting sink (see docs/ops/replication-plan.md) ──
        // Populated by a TiDB Cloud Changefeed. Used only for read-only,
        // non-critical reporting queries via DB::connection('reporting').
        // Inactive until REPORTING_DB_HOST is set.
        'reporting' => [
            'driver' => 'mysql',
            'host' => env('REPORTING_DB_HOST'),
            'port' => env('REPORTING_DB_PORT', '4000'),
            'database' => env('REPORTING_DB_DATABASE', 'kicc_reporting'),
            'username' => env('REPORTING_DB_USERNAME'),
            'password' => env('REPORTING_DB_PASSWORD'),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => null,
            'options' => extension_loaded('pdo_mysql') ? array_filter([
                Mysql::ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA', '/etc/ssl/certs/ca-certificates.crt'),
            ], fn($v) => $v !== null && $v !== '') : [],
        ],

        // ── Database Shards (horizontal scaling for 100K+ concurrent) ──
        'shard_0' => [
            'driver' => 'mysql',
            'host' => env('SHARD_0_HOST', env('TIDB_HOST', 'gateway01.eu-central-1.prod.aws.tidbcloud.com')),
            'port' => env('SHARD_0_PORT', env('TIDB_PORT', '4000')),
            'database' => env('SHARD_0_DATABASE', 'kicc_shard_0'),
            'username' => env('SHARD_0_USERNAME', env('TIDB_USERNAME', '28dbcDfwh5hEbSc.root')),
            'password' => env('SHARD_0_PASSWORD', env('TIDB_PASSWORD', 'D8trCZaYhqZWo5Vq')),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => null,
            'options' => extension_loaded('pdo_mysql') ? array_filter([
                Mysql::ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA', '/etc/ssl/certs/ca-certificates.crt'),
            ], fn($v) => $v !== null && $v !== '') : [],
        ],
        'shard_1' => [
            'driver' => 'mysql',
            'host' => env('SHARD_1_HOST', env('TIDB_HOST', 'gateway01.eu-central-1.prod.aws.tidbcloud.com')),
            'port' => env('SHARD_1_PORT', env('TIDB_PORT', '4000')),
            'database' => env('SHARD_1_DATABASE', 'kicc_shard_1'),
            'username' => env('SHARD_1_USERNAME', env('TIDB_USERNAME', '28dbcDfwh5hEbSc.root')),
            'password' => env('SHARD_1_PASSWORD', env('TIDB_PASSWORD', 'D8trCZaYhqZWo5Vq')),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => null,
            'options' => extension_loaded('pdo_mysql') ? array_filter([
                Mysql::ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA', '/etc/ssl/certs/ca-certificates.crt'),
            ], fn($v) => $v !== null && $v !== '') : [],
        ],
        'shard_2' => [
            'driver' => 'mysql',
            'host' => env('SHARD_2_HOST', env('TIDB_HOST', 'gateway01.eu-central-1.prod.aws.tidbcloud.com')),
            'port' => env('SHARD_2_PORT', env('TIDB_PORT', '4000')),
            'database' => env('SHARD_2_DATABASE', 'kicc_shard_2'),
            'username' => env('SHARD_2_USERNAME', env('TIDB_USERNAME', '28dbcDfwh5hEbSc.root')),
            'password' => env('SHARD_2_PASSWORD', env('TIDB_PASSWORD', 'D8trCZaYhqZWo5Vq')),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => null,
            'options' => extension_loaded('pdo_mysql') ? array_filter([
                Mysql::ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA', '/etc/ssl/certs/ca-certificates.crt'),
            ], fn($v) => $v !== null && $v !== '') : [],
        ],
        'shard_3' => [
            'driver' => 'mysql',
            'host' => env('SHARD_3_HOST', env('TIDB_HOST', 'gateway01.eu-central-1.prod.aws.tidbcloud.com')),
            'port' => env('SHARD_3_PORT', env('TIDB_PORT', '4000')),
            'database' => env('SHARD_3_DATABASE', 'kicc_shard_3'),
            'username' => env('SHARD_3_USERNAME', env('TIDB_USERNAME', '28dbcDfwh5hEbSc.root')),
            'password' => env('SHARD_3_PASSWORD', env('TIDB_PASSWORD', 'D8trCZaYhqZWo5Vq')),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => null,
            'options' => extension_loaded('pdo_mysql') ? array_filter([
                Mysql::ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA', '/etc/ssl/certs/ca-certificates.crt'),
            ], fn($v) => $v !== null && $v !== '') : [],
        ],

    ],

    'migrations' => [
        'table' => 'migrations',
        'update_date_on_publish' => true,
    ],

    'redis' => [

        'client' => env('REDIS_CLIENT', 'phpredis'),

        'options' => [
            'cluster' => env('REDIS_CLUSTER', 'redis'),
            'prefix' => env('REDIS_PREFIX', Str::slug((string) env('APP_NAME', 'laravel')).'-database-'),
            'persistent' => env('REDIS_PERSISTENT', false),
        ],

        'default' => [
            'url' => env('REDIS_URL'),
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'username' => env('REDIS_USERNAME'),
            'password' => env('REDIS_PASSWORD'),
            'port' => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_DB', '0'),
            'max_retries' => env('REDIS_MAX_RETRIES', 3),
            'backoff_algorithm' => env('REDIS_BACKOFF_ALGORITHM', 'decorrelated_jitter'),
            'backoff_base' => env('REDIS_BACKOFF_BASE', 100),
            'backoff_cap' => env('REDIS_BACKOFF_CAP', 1000),
        ],

        'cache' => [
            'url' => env('REDIS_URL'),
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'username' => env('REDIS_USERNAME'),
            'password' => env('REDIS_PASSWORD'),
            'port' => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_CACHE_DB', '1'),
            'max_retries' => env('REDIS_MAX_RETRIES', 3),
            'backoff_algorithm' => env('REDIS_BACKOFF_ALGORITHM', 'decorrelated_jitter'),
            'backoff_base' => env('REDIS_BACKOFF_BASE', 100),
            'backoff_cap' => env('REDIS_BACKOFF_CAP', 1000),
        ],

    ],

];
