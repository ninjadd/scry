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

    public function test_get_schema_relationships_endpoint(): void
    {
        $response = $this->getJson('/scry/api/schema/relationships?connection=sqlite');

        $response->assertStatus(200)
            ->assertJsonStructure(['tables', 'relationships', 'total_tables', 'total_relationships', 'connection']);
    }

    public function test_post_global_search_endpoint(): void
    {
        $response = $this->postJson('/scry/api/search/global?connection=sqlite', [
            'term' => 'Laptop',
            'limit' => 5,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['term', 'results', 'total_matches', 'matching_tables_count']);
    }

    public function test_get_server_processes_endpoint(): void
    {
        $response = $this->getJson('/scry/api/server/processes?connection=sqlite');

        $response->assertStatus(200)
            ->assertJsonStructure(['driver', 'processes']);
    }

    public function test_get_server_health_endpoint(): void
    {
        $response = $this->getJson('/scry/api/server/health?connection=sqlite');

        $response->assertStatus(200)
            ->assertJsonStructure(['status', 'connection', 'driver', 'latency_ms']);
    }

    public function test_create_and_alter_table_endpoints(): void
    {
        // 1. Create table via /scry/api/schema/tables
        $createRes = $this->postJson('/scry/api/schema/tables?connection=sqlite', [
            'table_name' => 'api_orders',
            'columns' => [
                ['name' => 'id', 'type' => 'INTEGER', 'nullable' => false, 'is_primary' => true, 'auto_increment' => true],
                ['name' => 'order_number', 'type' => 'VARCHAR(100)', 'nullable' => false],
            ],
        ]);

        $createRes->assertStatus(201)
            ->assertJsonStructure(['success', 'message']);

        // 2. Alter table by adding a column
        $alterRes = $this->putJson('/scry/api/schema/tables/api_orders?connection=sqlite', [
            'add_columns' => [
                ['name' => 'total_amount', 'type' => 'DECIMAL(10,2)', 'nullable' => true],
            ],
        ]);

        $alterRes->assertStatus(200)
            ->assertJsonStructure(['success', 'message', 'statements_executed']);

        // 3. Create Index on table
        $indexRes = $this->postJson('/scry/api/tables/api_orders/indexes?connection=sqlite', [
            'name' => 'idx_order_num',
            'columns' => ['order_number'],
            'type' => 'unique',
        ]);

        $indexRes->assertStatus(201)
            ->assertJsonStructure(['success', 'message']);

        // 4. Drop Index
        $dropIndexRes = $this->deleteJson('/scry/api/tables/api_orders/indexes/idx_order_num?connection=sqlite');
        $dropIndexRes->assertStatus(200);

        // 5. Drop Table
        $dropRes = $this->deleteJson('/scry/api/tables/api_orders?connection=sqlite');
        $dropRes->assertStatus(200);
    }
}
