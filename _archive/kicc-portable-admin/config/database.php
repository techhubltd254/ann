<?php

return [
    'default' => env('DB_CONNECTION', 'sqlite'),
    'connections' => [
        'sqlite' => [
            'driver' => 'sqlite',
            'url' => env('DB_URL'),
            'database' => env('DB_DATABASE', database_path('database.sqlite')),
            'prefix' => '',
            'foreign_key_constraints' => env('DB_FOREIGN_KEYS', true),
        ],
        'mysql' => [
            'driver' => 'mysql',
            'url' => env('DB_URL'),
            'host' => env('TIDB_HOST', 'gateway01.eu-central-1.prod.aws.tidbcloud.com'),
            'port' => env('TIDB_PORT', '4000'),
            'database' => env('TIDB_DATABASE', 'kicc'),
            'username' => env('TIDB_USERNAME', '28dbcDfwh5hEbSc.root'),
            'password' => env('TIDB_PASSWORD', ''),
            'unix_socket' => env('DB_SOCKET', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'strict' => true,
            'engine' => null,
            'options' => extension_loaded('pdo_mysql') ? array_filter([
                \PDO::MYSQL_ATTR_SSL_CA => env('TIDB_SSL_CA', '/etc/ssl/certs/ca-certificates.crt'),
                \PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => env('DB_SSL_VERIFY', true),
            ], fn($v) => $v !== null && $v !== '') : [],
        ],
    ],
];