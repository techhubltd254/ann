<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Response cache TTL (seconds)
    |--------------------------------------------------------------------------
    |
    | How long public GET responses are cached in Redis. 60s is a good
    | balance: it absorbs most of the repeated home/county/sector page hits
    | and read-only API traffic while staying fresh enough for demos.
    |
    */

    'ttl' => (int) env('RESPONSE_CACHE_TTL', 60),

];