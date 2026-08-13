<?php

namespace Scry\Tests\Feature;

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Scry\Tests\TestCase;

class ApiControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('api_products', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->decimal('price', 8, 2);
            $table->timestamps();
        });

        \DB::table('api_products')->insert([
            ['title' => 'Laptop', 'price' => 999.99],
            ['title' => 'Phone', 'price' => 499.99],
        ]);
    }

    public function test_get_databases_endpoint(): void
    {
        $response = $this->getJson('/scry/api/databases?connection=sqlite');

        $response->assertStatus(200)
            ->assertJsonStructure(['current_database', 'databases']);
    }

    public function test_get_server_stats_endpoint(): void
    {
        $response = $this->getJson('/scry/api/server/stats?connection=sqlite');

        $response->assertStatus(200)
            ->assertJsonStructure(['driver', 'version', 'available_connections']);
    }

    public function test_get_tables_endpoint(): void
    {
        $response = $this->getJson('/scry/api/tables?connection=sqlite');

        $response->assertStatus(200)
            ->assertJsonStructure(['connection', 'driver', 'tables', 'available_connections']);
    }

    public function test_get_table_schema_endpoint(): void
    {
        $response = $this->getJson('/scry/api/tables/api_products/schema?connection=sqlite');

        $response->assertStatus(200)
            ->assertJsonStructure(['table', 'columns', 'indexes', 'foreign_keys']);
    }

    public function test_get_table_data_endpoint(): void
    {
        $response = $this->getJson('/scry/api/tables/api_products/data?connection=sqlite');

        $response->assertStatus(200)
            ->assertJsonStructure(['current_page', 'data', 'total', 'per_page']);
    }

    public function test_execute_sql_endpoint(): void
    {
        $response = $this->postJson('/scry/api/sql/execute?connection=sqlite', [
            'query' => 'SELECT * FROM api_products',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['query_type', 'is_read', 'row_count', 'data']);
    }

    public function test_global_search_endpoint(): void
    {
        $response = $this->getJson('/scry/api/search?connection=sqlite&q=Laptop');

        $response->assertStatus(200)
            ->assertJsonStructure(['term', 'results']);
    }
}
