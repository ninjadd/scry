<?php

namespace Scry\Tests\Unit;

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Scry\Services\ExportService;
use Scry\Tests\TestCase;

class ExportServiceTest extends TestCase
{
    protected ExportService $exportService;
    protected array $rows;

    protected function setUp(): void
    {
        parent::setUp();
        $this->exportService = $this->app->make(ExportService::class);

        Schema::create('export_items', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->decimal('price', 8, 2);
        });

        \DB::table('export_items')->insert([
            ['name' => 'Book', 'price' => 12.99],
            ['name' => 'Pen', 'price' => 1.50],
        ]);

        $this->rows = \DB::table('export_items')->get()->toArray();
    }

    public function test_export_csv_format(): void
    {
        $csv = $this->exportService->exportCsv($this->rows);

        $this->assertStringContainsString('id,name,price', $csv);
        $this->assertStringContainsString('Book', $csv);
        $this->assertStringContainsString('12.99', $csv);
    }

    public function test_export_json_format(): void
    {
        $json = json_encode($this->rows);
        $decoded = json_decode($json, true);

        $this->assertIsArray($decoded);
        $this->assertCount(2, $decoded);
        $this->assertEquals('Book', $decoded[0]['name']);
    }

    public function test_export_xml_format(): void
    {
        $xml = $this->exportService->exportXml('export_items', $this->rows);

        $this->assertStringContainsString('<?xml version="1.0" encoding="UTF-8"?>', $xml);
        $this->assertStringContainsString('<table name="export_items">', $xml);
        $this->assertStringContainsString('<name>Book</name>', $xml);
    }

    public function test_export_sql_format(): void
    {
        $sql = $this->exportService->exportSql('export_items', $this->rows, 'sqlite');

        $this->assertStringContainsString('INSERT INTO "export_items"', $sql);
        $this->assertStringContainsString("'Book'", $sql);
    }
}
