<?php

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],
    'allowed_methods' => ['*'],
    'allowed_origins' => [env('APP_URL', 'https://kicctest.org')],
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],
    'exposed_headers' => ['Content-Type', 'Content-Length', 'Content-Range', 'Accept-Ranges'],
    'max_age' => 86400,
    'supports_credentials' => true,
];