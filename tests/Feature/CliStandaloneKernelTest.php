<?php

namespace Scry\Tests\Feature;

use Illuminate\Http\Request;
use PHPUnit\Framework\TestCase;
use Scry\Cli\StandaloneKernel;

class CliStandaloneKernelTest extends TestCase
{
    protected StandaloneKernel $kernel;

    protected function setUp(): void
    {
        parent::setUp();

        $connections = [
            'default' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
            ],
        ];

        $this->kernel = new StandaloneKernel($connections);
    }

    public function test_it_serves_spa_html(): void
    {
        $request = Request::create('/', 'GET');
        $response = $this->kernel->handle($request);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString('Scry Database Manager', $response->getContent());
        $this->assertStringContainsString('window.ScryConfig', $response->getContent());
    }

    public function test_it_handles_api_databases_endpoint(): void
    {
        $request = Request::create('/api/databases', 'GET', ['connection' => 'default']);
        $response = $this->kernel->handle($request);

        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('databases', $data);
    }

    public function test_it_handles_table_lifecycle_and_sql_execution(): void
    {
        // 1. Create a table via SQL
        $sqlReq = Request::create('/api/sql/execute', 'POST', [
            'query' => 'CREATE TABLE products (id INTEGER PRIMARY KEY AUTOINCREMENT, title TEXT, price REAL)',
            'connection' => 'default',
        ]);
        $sqlRes = $this->kernel->handle($sqlReq);
        $this->assertEquals(200, $sqlRes->getStatusCode(), $sqlRes->getContent());

        // 2. Fetch tables list
        $tablesReq = Request::create('/api/tables', 'GET', ['connection' => 'default']);
        $tablesRes = $this->kernel->handle($tablesReq);
        $this->assertEquals(200, $tablesRes->getStatusCode());
        $tablesData = json_decode($tablesRes->getContent(), true);
        $this->assertCount(1, $tablesData['tables']);
        $this->assertEquals('products', $tablesData['tables'][0]['name']);

        // 3. Insert a row
        $insertReq = Request::create('/api/tables/products/rows', 'POST', [
            'data' => ['title' => 'Mechanical Keyboard', 'price' => 129.99],
            'connection' => 'default',
        ]);
        $insertRes = $this->kernel->handle($insertReq);
        $this->assertEquals(201, $insertRes->getStatusCode());

        // 4. Fetch rows
        $rowsReq = Request::create('/api/tables/products/rows', 'GET', ['connection' => 'default']);
        $rowsRes = $this->kernel->handle($rowsReq);
        $this->assertEquals(200, $rowsRes->getStatusCode());
        $rowsData = json_decode($rowsRes->getContent(), true);
        $this->assertEquals(1, $rowsData['total']);
        $this->assertEquals('Mechanical Keyboard', $rowsData['data'][0]['title']);

        // 5. Query via QBE / query runner
        $queryReq = Request::create('/api/query', 'POST', [
            'query' => 'SELECT * FROM products WHERE price > 100',
            'connection' => 'default',
        ]);
        $queryRes = $this->kernel->handle($queryReq);
        $this->assertEquals(200, $queryRes->getStatusCode(), $queryRes->getContent());
        $queryData = json_decode($queryRes->getContent(), true);
        $this->assertCount(1, $queryData['data']);
    }

    public function test_it_handles_dynamic_connections_management(): void
    {
        // 1. Get connections
        $req = Request::create('/api/connections', 'GET');
        $res = $this->kernel->handle($req);
        $this->assertEquals(200, $res->getStatusCode());
        $data = json_decode($res->getContent(), true);
        $this->assertContains('default', $data['connections']);

        // 2. Add dynamic connection
        $addReq = Request::create('/api/connections', 'POST', [
            'name' => 'secondary_sqlite',
            'dsn' => 'sqlite://:memory:',
        ]);
        $addRes = $this->kernel->handle($addReq);
        $this->assertEquals(200, $addRes->getStatusCode());
        $addData = json_decode($addRes->getContent(), true);
        $this->assertTrue($addData['success']);
    }

    public function test_it_serves_static_assets(): void
    {
        $req = Request::create('/app.js', 'GET');
        $res = $this->kernel->handle($req);
        $this->assertEquals(200, $res->getStatusCode());
        $this->assertEquals('application/javascript', $res->headers->get('Content-Type'));

        $cssReq = Request::create('/app.css', 'GET');
        $cssRes = $this->kernel->handle($cssReq);
        $this->assertEquals(200, $cssRes->getStatusCode());
        $this->assertEquals('text/css', $cssRes->headers->get('Content-Type'));
    }

    public function test_it_handles_unknown_api_routes(): void
    {
        $req = Request::create('/api/non-existent-route', 'GET');
        $res = $this->kernel->handle($req);
        $this->assertEquals(404, $res->getStatusCode());
        $data = json_decode($res->getContent(), true);
        $this->assertArrayHasKey('error', $data);
    }
}
