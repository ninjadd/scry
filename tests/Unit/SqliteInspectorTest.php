<?php

namespace Scry\Tests\Unit;

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Scry\DatabaseExplorerManager;
use Scry\Inspectors\SqliteInspector;
use Scry\Tests\TestCase;

class SqliteInspectorTest extends TestCase
{
    protected SqliteInspector $inspector;

    protected function setUp(): void
    {
        parent::setUp();
        $manager = $this->app->make(DatabaseExplorerManager::class);
        $this->inspector = $manager->forConnection('sqlite');

        // Create sample tables for testing
        Schema::create('test_users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamps();
        });

        Schema::create('test_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('test_users')->cascadeOnDelete();
            $table->string('title');
            $table->text('content');
            $table->timestamps();
        });
    }

    public function test_get_tables_returns_created_tables(): void
    {
        $tables = $this->inspector->getTables();
        $tableNames = array_column($tables, 'name');

        $this->assertContains('test_users', $tableNames);
        $this->assertContains('test_posts', $tableNames);
    }

    public function test_get_table_schema_returns_columns(): void
    {
        $schema = $this->inspector->getTableSchema('test_users');

        $this->assertEquals('test_users', $schema['table']);
        $colNames = array_column($schema['columns'], 'name');
        $this->assertContains('id', $colNames);
        $this->assertContains('name', $colNames);
        $this->assertContains('email', $colNames);
    }

    public function test_get_paginated_rows_and_insert(): void
    {
        \DB::table('test_users')->insert([
            ['name' => 'Alice', 'email' => 'alice@example.com'],
            ['name' => 'Bob', 'email' => 'bob@example.com'],
        ]);

        $paginated = $this->inspector->getPaginatedRows('test_users', 1, 10);

        $this->assertEquals(2, $paginated['total']);
        $this->assertCount(2, $paginated['data']);
        $this->assertEquals('Alice', $paginated['data'][0]->name);
    }

    public function test_get_server_stats_returns_driver_metadata(): void
    {
        $stats = $this->inspector->getServerStats();

        $this->assertEquals('sqlite', $stats['driver']);
        $this->assertArrayHasKey('version', $stats);
        $this->assertArrayHasKey('database_name', $stats);
    }

    public function test_copy_rename_and_drop_table(): void
    {
        // Copy table structure & data
        $copied = $this->inspector->copyTable('test_users', 'test_users_copy');
        $this->assertTrue($copied);
        $this->assertTrue(Schema::hasTable('test_users_copy'));

        // Rename table
        $renamed = $this->inspector->renameTable('test_users_copy', 'test_users_renamed');
        $this->assertTrue($renamed);
        $this->assertTrue(Schema::hasTable('test_users_renamed'));
        $this->assertFalse(Schema::hasTable('test_users_copy'));

        // Drop table
        $dropped = $this->inspector->dropTable('test_users_renamed');
        $this->assertTrue($dropped);
        $this->assertFalse(Schema::hasTable('test_users_renamed'));
    }

    public function test_get_schema_relationships_returns_tables_and_relationships(): void
    {
        $relData = $this->inspector->getSchemaRelationships();

        $this->assertArrayHasKey('tables', $relData);
        $this->assertArrayHasKey('relationships', $relData);
        $this->assertArrayHasKey('total_tables', $relData);
        $this->assertArrayHasKey('total_relationships', $relData);

        $tableNames = array_column($relData['tables'], 'name');
        $this->assertContains('test_users', $tableNames);
        $this->assertContains('test_posts', $tableNames);

        $this->assertGreaterThanOrEqual(1, count($relData['relationships']));
        $this->assertEquals('test_posts', $relData['relationships'][0]['from_table']);
        $this->assertEquals('test_users', $relData['relationships'][0]['to_table']);
    }
}
