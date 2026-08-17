<?php

namespace Scry\Tests\Unit;

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Scry\Services\GlobalSearchService;
use Scry\Tests\TestCase;

class GlobalSearchServiceTest extends TestCase
{
    protected GlobalSearchService $searchService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->searchService = $this->app->make(GlobalSearchService::class);

        Schema::create('search_users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('city');
        });

        Schema::create('search_notes', function (Blueprint $table) {
            $table->id();
            $table->text('body');
        });

        \DB::table('search_users')->insert([
            ['name' => 'John Antigravity', 'city' => 'Portland'],
            ['name' => 'Jane Smith', 'city' => 'Seattle'],
        ]);

        \DB::table('search_notes')->insert([
            ['body' => 'Contains Antigravity project keyword.'],
            ['body' => 'Unrelated note body.'],
        ]);
    }

    public function test_global_search_finds_term_across_multiple_tables(): void
    {
        $res = $this->searchService->search('Antigravity', 'sqlite');

        $this->assertEquals('Antigravity', $res['term']);
        $this->assertEquals(2, $res['matching_tables_count']);
        $this->assertCount(2, $res['results']);

        $tableNames = array_column($res['results'], 'table');
        $this->assertContains('search_users', $tableNames);
        $this->assertContains('search_notes', $tableNames);
    }

    public function test_global_search_respects_table_filter_and_limits(): void
    {
        $res = $this->searchService->search('Antigravity', 'sqlite', [
            'tables' => ['search_users'],
            'per_table_limit' => 1,
        ]);

        $this->assertEquals(1, $res['matching_tables_count']);
        $this->assertEquals('search_users', $res['results'][0]['table']);
        $this->assertCount(1, $res['results'][0]['sample_matches']);
    }
}
