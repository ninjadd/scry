<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Database Manager Path
    |--------------------------------------------------------------------------
    |
    | This is the URI path where Database Manager will be accessible from.
    |
    */

    'path' => env('DB_MANAGER_PATH', 'db-manager'),

    /*
    |--------------------------------------------------------------------------
    | Database Manager Route Middleware
    |--------------------------------------------------------------------------
    |
    | These middleware will be assigned to every Database Manager route.
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
    | By default, Database Manager is only accessible in local environment.
    |
    */

    'allowed_environments' => [
        'local',
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Database Connection
    |--------------------------------------------------------------------------
    |
    | The connection to inspect. If null, uses default DB connection.
    |
    */

    'connection' => env('DB_MANAGER_CONNECTION', null),
];
