<?php

// config/automation.php — inter-pipeline automation: the bus, its consumers, and
// the two services that used to be HTTP-only silos (kicc_algorithms, ml-engine).
// Registered as a normal Laravel config so the Mother Admin panel and any artisan
// command can read the live consumer topology without hard-coding paths.

return [
    'bus' => [
        'url'        => env('PIPELINE_BUS_URL', 'http://127.0.0.1:8790'),
        'journal'    => env('PIPELINE_BUS_JOURNAL', base_path('integrations-service/run/artifacts/bus.jsonl')),
        'timeout'    => (int) env('PIPELINE_BUS_TIMEOUT', 5),
        'retry'      => [
            'max_attempts' => (int) env('CONSUMER_MAX_ATTEMPTS', 3),
            'backoff_ms'   => (int) env('CONSUMER_BACKOFF_MS', 20),
        ],
    ],

    'consumers' => [
        'kicc-algorithms' => [
            'service'      => 'kicc_algorithms',
            'group'        => 'kicc-algorithms',
            'endpoint'     => env('KICC_ALGORITHMS_URL', 'http://127.0.0.1:8801/algorithms/score'),
            'consumes'     => ['pipeline.settled', 'pipeline.trigger'],
            'emits'        => ['ml.inference.requested', 'algorithms.result'],
            'state_dir'    => base_path('integrations-service/run/offsets/kicc-algorithms'),
            'max_attempts' => (int) env('CONSUMER_MAX_ATTEMPTS', 3),
            'backoff_ms'   => (int) env('CONSUMER_BACKOFF_MS', 20),
            'max_in_flight' => (int) env('CONSUMER_MAX_IN_FLIGHT', 8),
            'command'      => 'node integrations-service/consumers/run_consumers.mjs --only=kicc-algorithms',
        ],
        'ml-engine' => [
            'service'      => 'ml-engine',
            'group'        => 'ml-engine',
            'endpoint'     => env('ML_ENGINE_URL', 'http://127.0.0.1:8802/predict'),
            'consumes'     => ['ml.inference.requested', 'pipeline.trigger'],
            'emits'        => ['ml.prediction'],
            'state_dir'    => base_path('integrations-service/run/offsets/ml-engine'),
            'max_attempts' => (int) env('CONSUMER_MAX_ATTEMPTS', 3),
            'backoff_ms'   => (int) env('CONSUMER_BACKOFF_MS', 20),
            'max_in_flight' => (int) env('CONSUMER_MAX_IN_FLIGHT', 8),
            'command'      => 'node integrations-service/consumers/run_consumers.mjs --only=ml-engine',
        ],
    ],

    'status' => [
        'algorithms' => env('KICC_ALGORITHMS_STATUS_URL', 'http://127.0.0.1:8791/api/consumers/status'),
        'ml_engine'  => env('ML_ENGINE_STATUS_URL', 'http://127.0.0.1:8792/api/consumers/status'),
        'reaper'     => env('PIPELINE_BUS_STATUS_URL', 'http://127.0.0.1:8790/health'),
    ],
];
