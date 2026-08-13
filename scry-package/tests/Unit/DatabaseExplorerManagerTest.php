<?php

namespace Scry\Tests\Unit;

use Scry\DatabaseExplorerManager;
use Scry\Exceptions\UnsupportedDriverException;
use Scry\Inspectors\MariadbInspector;
use Scry\Inspectors\MysqlInspector;
use Scry\Inspectors\PostgresInspector;
use Scry\Inspectors\SqliteInspector;
use Scry\Inspectors\SqlsrvInspector;
use Scry\Tests\TestCase;

class DatabaseExplorerManagerTest extends TestCase
{
    protected DatabaseExplorerManager $manager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->manager = $this->app->make(DatabaseExplorerManager::class);
    }

    public function test_it_resolves_sqlite_inspector(): void
    {
        $inspector = $this->manager->forConnection('sqlite');
        $this->assertInstanceOf(SqliteInspector::class, $inspector);
        $this->assertEquals('sqlite', $this->manager->getDriverForConnection('sqlite'));
    }

    public function test_it_resolves_mysql_inspector(): void
    {
        $inspector = $this->manager->forConnection('mysql');
        $this->assertInstanceOf(MysqlInspector::class, $inspector);
        $this->assertEquals('mysql', $this->manager->getDriverForConnection('mysql'));
    }

    public function test_it_resolves_mariadb_inspector(): void
    {
        $inspector = $this->manager->forConnection('mariadb');
        $this->assertInstanceOf(MariadbInspector::class, $inspector);
        $this->assertEquals('mariadb', $this->manager->getDriverForConnection('mariadb'));
    }

    public function test_it_resolves_postgres_inspector(): void
    {
        $inspector = $this->manager->forConnection('pgsql');
        $this->assertInstanceOf(PostgresInspector::class, $inspector);
        $this->assertEquals('pgsql', $this->manager->getDriverForConnection('pgsql'));
    }

    public function test_it_resolves_sqlsrv_inspector(): void
    {
        $inspector = $this->manager->forConnection('sqlsrv');
        $this->assertInstanceOf(SqlsrvInspector::class, $inspector);
        $this->assertEquals('sqlsrv', $this->manager->getDriverForConnection('sqlsrv'));
    }

    public function test_it_throws_unsupported_driver_exception_for_invalid_driver(): void
    {
        $this->app['config']->set('database.connections.oracle', [
            'driver' => 'oracle',
        ]);

        $this->expectException(UnsupportedDriverException::class);
        $this->manager->forConnection('oracle');
    }
}
