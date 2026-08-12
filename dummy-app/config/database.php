<?php

return [

    'default' => env('DB_CONNECTION', 'pgsql'),

    'connections' => [

        'pgsql' => [
            'driver' => 'pgsql',
            'host' => env('DB_HOST', 'postgres'),
            'port' => env('DB_PORT', '5432'),
            'database' => env('DB_DATABASE', 'scry_test'),
            'username' => env('DB_USERNAME', 'scry'),
            'password' => env('DB_PASSWORD', 'secret'),
            'charset' => 'utf8',
            'prefix' => '',
            'prefix_indexes' => true,
            'search_path' => 'public',
            'sslmode' => 'prefer',
        ],

        'mysql' => [
            'driver' => 'mysql',
            'host' => env('MYSQL_HOST', 'mysql'),
            'port' => env('MYSQL_PORT', '3306'),
            'database' => env('MYSQL_DATABASE', 'scry_test'),
            'username' => env('MYSQL_USERNAME', 'scry'),
            'password' => env('MYSQL_PASSWORD', 'secret'),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'strict' => true,
            'engine' => null,
        ],

    ],

    'migrations' => [
        'table' => 'migrations',
        'update_date_on_publish' => true,
    ],

];
