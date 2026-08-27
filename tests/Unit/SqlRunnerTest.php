<?php

namespace Scry\Tests\Unit;

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Scry\Services\SqlRunner;
use Scry\Tests\TestCase;

class SqlRunnerTest extends TestCase
{
    protected SqlRunner $sqlRunner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sqlRunner = $this->app->make(SqlRunner::class);

        Schema::create('query_test', function (Blueprint $table) {
            $table->id();
            $table->string('item');
            $table->integer('quantity');
        });
    }

    public function test_execute_select_query_returns_data_and_execution_time(): void
    {
        \DB::table('query_test')->insert(['item' => 'Widget', 'quantity' => 10]);

        $res = $this->sqlRunner->execute('SELECT * FROM query_test');

        $this->assertTrue($res['is_read']);
        $this->assertEquals('SELECT', $res['query_type']);
        $this->assertEquals(1, $res['row_count']);
        $this->assertArrayHasKey('execution_time_ms', $res);
        $this->assertEquals(['id', 'item', 'quantity'], $res['columns']);
    }

    public function test_execute_insert_query_returns_affected_rows(): void
    {
        $res = $this->sqlRunner->execute("INSERT INTO query_test (item, quantity) VALUES ('Gadget', 20)");

        $this->assertFalse($res['is_read']);
        $this->assertEquals('INSERT', $res['query_type']);
        $this->assertEquals(1, $res['affected_rows']);
        $this->assertStringContainsString('successfully', $res['message']);
    }

    public function test_execute_invalid_sql_returns_error(): void
    {
        $res = $this->sqlRunner->execute("SELECT * FROM non_existent_table");

        $this->assertArrayHasKey('error', $res);
        $this->assertArrayHasKey('execution_time_ms', $res);
    }

    public function test_execute_accepts_bound_parameters_for_inserts(): void
    {
        $res = $this->sqlRunner->execute(
            'INSERT INTO query_test (item, quantity) VALUES (?, ?)',
            null,
            ["O'Brien's Gadget", 3]
        );

        $this->assertFalse($res['is_read']);
        $this->assertEquals(1, $res['affected_rows']);
        $this->assertEquals("O'Brien's Gadget", \DB::table('query_test')->value('item'));
    }

    public function test_execute_accepts_bound_parameters_for_selects(): void
    {
        \DB::table('query_test')->insert(['item' => 'Widget', 'quantity' => 10]);
        \DB::table('query_test')->insert(['item' => 'Gizmo', 'quantity' => 5]);

        $res = $this->sqlRunner->execute('SELECT * FROM query_test WHERE item = ?', null, ['Widget']);

        $this->assertEquals(1, $res['row_count']);
        $this->assertEquals('Widget', $res['data'][0]->item);
    }

    public function test_execute_caps_read_query_rows_and_flags_truncation(): void
    {
        config(['scry.max_query_rows' => 3]);

        for ($i = 0; $i < 10; $i++) {
            \DB::table('query_test')->insert(['item' => "item-{$i}", 'quantity' => $i]);
        }

        $res = $this->sqlRunner->execute('SELECT * FROM query_test');

        $this->assertEquals(3, $res['row_count']);
        $this->assertTrue($res['truncated']);
    }

    public function test_execute_does_not_flag_truncation_when_under_the_cap(): void
    {
        config(['scry.max_query_rows' => 100]);

        \DB::table('query_test')->insert(['item' => 'Widget', 'quantity' => 10]);

        $res = $this->sqlRunner->execute('SELECT * FROM query_test');

        $this->assertEquals(1, $res['row_count']);
        $this->assertFalse($res['truncated']);
    }
}
