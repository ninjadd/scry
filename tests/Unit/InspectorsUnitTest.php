<?php

namespace Scry\Tests\Unit;

use Scry\DatabaseExplorerManager;
use Scry\Inspectors\MariadbInspector;
use Scry\Inspectors\MysqlInspector;
use Scry\Inspectors\PostgresInspector;
use Scry\Inspectors\SqlsrvInspector;
use Scry\Tests\TestCase;

class InspectorsUnitTest extends TestCase
{
    protected DatabaseExplorerManager $manager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->manager = $this->app->make(DatabaseExplorerManager::class);
    }

    public function test_postgres_inspector_instantiation(): void
    {
        $inspector = $this->manager->forConnection('pgsql');
        $this->assertInstanceOf(PostgresInspector::class, $inspector);
    }

    public function test_mysql_inspector_instantiation(): void
    {
        $inspector = $this->manager->forConnection('mysql');
        $this->assertInstanceOf(MysqlInspector::class, $inspector);
    }

    public function test_mariadb_inspector_instantiation(): void
    {
        $inspector = $this->manager->forConnection('mariadb');
        $this->assertInstanceOf(MariadbInspector::class, $inspector);
    }

    public function test_sqlsrv_inspector_instantiation(): void
    {
        $inspector = $this->manager->forConnection('sqlsrv');
        $this->assertInstanceOf(SqlsrvInspector::class, $inspector);
    }
}
