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
}
