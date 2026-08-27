<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Scry Domain & Path
    |--------------------------------------------------------------------------
    |
    | The URI path or domain where Scry Database Manager will be accessible.
    |
    */

    'path' => env('SCRY_PATH', 'scry'),

    'domain' => env('SCRY_DOMAIN', null),

    /*
    |--------------------------------------------------------------------------
    | Route Middleware
    |--------------------------------------------------------------------------
    |
    | Middleware assigned to Scry web and API routes.
    |
    */

    'middleware' => [
        'web',
    ],

    /*
    |--------------------------------------------------------------------------
    | Allowed Environments
    |--------------------------------------------------------------------------
    |
    | Environments where Scry is accessible by default.
    |
    */

    'allowed_environments' => [
        'local',
        'testing',
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Connection
    |--------------------------------------------------------------------------
    |
    | Connection to inspect initially. If null, uses default DB connection.
    |
    */

    'connection' => env('SCRY_CONNECTION', null),

    /*
    |--------------------------------------------------------------------------
    | Max Query Rows
    |--------------------------------------------------------------------------
    |
    | Upper bound on rows materialized in memory and returned for a single
    | read query, to guard against unbounded result sets exhausting memory.
    |
    */

    'max_query_rows' => (int) env('SCRY_MAX_QUERY_ROWS', 10000),
];
