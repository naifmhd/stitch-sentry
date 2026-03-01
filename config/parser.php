<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Parser Service URL
    |--------------------------------------------------------------------------
    |
    | The base URL for the external embroidery parser/renderer service.
    |
    */

    'base_url' => env('PARSER_SERVICE_URL', ''),

    /*
    |--------------------------------------------------------------------------
    | Signing Secret
    |--------------------------------------------------------------------------
    |
    | HMAC-SHA256 secret used to sign outgoing requests.
    |
    */

    'secret' => env('PARSER_SERVICE_SECRET', ''),

    /*
    |--------------------------------------------------------------------------
    | Timeouts
    |--------------------------------------------------------------------------
    */

    'timeout_seconds' => (int) env('PARSER_TIMEOUT_SECONDS', 20),

    'connect_timeout_seconds' => (int) env('PARSER_CONNECT_TIMEOUT_SECONDS', 5),

    /*
    |--------------------------------------------------------------------------
    | Retries
    |--------------------------------------------------------------------------
    */

    'retry_times' => (int) env('PARSER_RETRY_TIMES', 2),

    'retry_sleep_ms' => (int) env('PARSER_RETRY_SLEEP_MS', 200),

    /*
    |--------------------------------------------------------------------------
    | Mock Mode
    |--------------------------------------------------------------------------
    |
    | When enabled, ParserClient returns deterministic fake data instead of
    | calling the real service. Useful for local development and testing.
    |
    */

    'mock_enabled' => (bool) env('PARSER_MOCK', false),

];
