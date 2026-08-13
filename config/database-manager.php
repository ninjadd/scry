<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Scry Path
    |--------------------------------------------------------------------------
    |
    | The URI path where Scry Database Manager will be accessible from.
    |
    */

    'path' => env('SCRY_PATH', env('DB_MANAGER_PATH', 'scry')),

    /*
    |--------------------------------------------------------------------------
    | Route Middleware
    |--------------------------------------------------------------------------
    |
    | Middleware assigned to Scry routes.
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
    | Restricted to local environment by default.
    |
    */

    'allowed_environments' => [
        'local',
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Connection
    |--------------------------------------------------------------------------
    |
    | Connection to inspect. If null, uses application default DB connection.
    |
    */

    'connection' => env('SCRY_CONNECTION', env('DB_MANAGER_CONNECTION', null)),
];
