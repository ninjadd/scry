<?php

namespace Scry\Tests\Unit;

use Scry\Services\ServerTuningAdvisor;
use Scry\Tests\TestCase;

class ServerTuningAdvisorTest extends TestCase
{
    protected ServerTuningAdvisor $advisor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->advisor = $this->app->make(ServerTuningAdvisor::class);
    }

    public function test_analyze_returns_recommendations(): void
    {
        $res = $this->advisor->analyze('sqlite');

        $this->assertEquals('sqlite', $res['driver']);
        $this->assertArrayHasKey('suggestions', $res);
        $this->assertIsArray($res['suggestions']);
    }

    public function test_get_slow_queries_returns_array(): void
    {
        $res = $this->advisor->getSlowQueries('sqlite');

        $this->assertEquals('sqlite', $res['driver']);
        $this->assertArrayHasKey('processes', $res);
        $this->assertIsArray($res['processes']);
    }

    public function test_check_health_returns_status(): void
    {
        $res = $this->advisor->checkHealth('sqlite');

        $this->assertEquals('healthy', $res['status']);
        $this->assertEquals('sqlite', $res['driver']);
        $this->assertArrayHasKey('latency_ms', $res);
    }

    public function test_check_health_honors_scry_connection_over_database_default(): void
    {
        config(['database.connections.secondary' => [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]]);
        config(['scry.connection' => 'secondary']);

        // No explicit connection passed — should resolve via scry.connection, not database.default.
        $res = $this->advisor->checkHealth();

        $this->assertEquals('secondary', $res['connection']);
        $this->assertEquals('healthy', $res['status']);
    }

    public function test_get_slow_queries_honors_scry_connection_over_database_default(): void
    {
        config(['database.connections.secondary' => [
            'driver' => 'pgsql',
            'host' => '127.0.0.1',
            'port' => '5432',
            'database' => 'scry_pg_db',
            'username' => 'postgres',
            'password' => 'postgres',
        ]]);
        config(['scry.connection' => 'secondary']);

        // database.default is sqlite; scry.connection is pgsql — the reported driver
        // must reflect the resolved scry.connection, not database.default.
        $res = $this->advisor->getSlowQueries();

        $this->assertEquals('pgsql', $res['driver']);
    }
}
