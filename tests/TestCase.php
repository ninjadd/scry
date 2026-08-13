<?php

namespace Scry\Tests;

use Orchestra\Testbench\TestCase as BaseTestCase;
use Scry\ScryServiceProvider;

abstract class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            ScryServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:' . base64_encode(random_bytes(32)));
        $app['config']->set('app.cipher', 'AES-256-CBC');

        // Set up in-memory sqlite connection for testing
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        // Register dummy test connections for driver testing
        $app['config']->set('database.connections.pgsql', [
            'driver' => 'pgsql',
            'host' => '127.0.0.1',
            'port' => '5432',
            'database' => 'scry_pg_db',
            'username' => 'postgres',
            'password' => 'postgres',
        ]);

        $app['config']->set('database.connections.mysql', [
            'driver' => 'mysql',
            'host' => '127.0.0.1',
            'port' => '3306',
            'database' => 'scry_my_db',
            'username' => 'root',
            'password' => 'root',
        ]);

        $app['config']->set('database.connections.mariadb', [
            'driver' => 'mariadb',
            'host' => '127.0.0.1',
            'port' => '3307',
            'database' => 'scry_maria_db',
            'username' => 'root',
            'password' => 'root',
        ]);

        $app['config']->set('database.connections.sqlsrv', [
            'driver' => 'sqlsrv',
            'host' => '127.0.0.1',
            'port' => '1433',
            'database' => 'scry_sql_db',
            'username' => 'sa',
            'password' => 'Secret_Password123!',
        ]);

        $app['config']->set('scry.path', 'scry');
        $app['config']->set('scry.middleware', ['web']);
        $app['config']->set('scry.allowed_environments', ['local', 'testing']);
        $app['config']->set('database-manager.allowed_environments', ['local', 'testing']);
    }
}
