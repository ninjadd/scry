<?php

namespace Scry\Tests\Unit;

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Scry\Services\ImportService;
use Scry\Tests\TestCase;

class ImportServiceTest extends TestCase
{
    protected ImportService $importService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->importService = $this->app->make(ImportService::class);
    }

    public function test_parse_sql_statements_handles_quotes_and_comments(): void
    {
        $sql = "
            -- First comment
            INSERT INTO users (name) VALUES ('John; Doe');
            # Second comment
            /* Block comment with ; */
            INSERT INTO users (name) VALUES ('Jane; Smith');
        ";

        $statements = $this->importService->parseSqlStatements($sql);

        $this->assertCount(2, $statements);
        $this->assertEquals("INSERT INTO users (name) VALUES ('John; Doe')", $statements[0]);
        $this->assertEquals("INSERT INTO users (name) VALUES ('Jane; Smith')", $statements[1]);
    }

    public function test_import_csv_inserts_rows_into_table(): void
    {
        Schema::create('csv_test', function (Blueprint $table) {
            $table->id();
            $table->string('product');
            $table->integer('stock');
        });

        $csv = "product,stock\nKeyboard,45\nMouse,120";

        $res = $this->importService->importCsv('csv_test', $csv);

        $this->assertTrue($res['success']);
        $this->assertEquals(2, $res['inserted_rows']);
        $this->assertEquals(2, \DB::table('csv_test')->count());
    }
}
