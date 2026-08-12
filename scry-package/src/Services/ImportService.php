<?php

namespace Scry\Services;

use Illuminate\Database\DatabaseManager as LaravelDatabaseManager;
use Throwable;

class ImportService
{
    public function __construct(
        protected LaravelDatabaseManager $dbManager
    ) {}

    /**
     * Import a raw SQL dump file into the connection.
     */
    public function importSql(string $sqlContent, ?string $connectionName = null): array
    {
        $connection = $this->dbManager->connection($connectionName ?? config('database.default'));
        
        $statements = array_filter(
            array_map('trim', explode(';', $sqlContent)),
            fn($s) => !empty($s) && !str_starts_with($s, '--') && !str_starts_with($s, '/*')
        );

        $executed = 0;
        $errors = [];

        foreach ($statements as $stmt) {
            try {
                $connection->statement($stmt);
                $executed++;
            } catch (Throwable $e) {
                $errors[] = $e->getMessage();
            }
        }

        return [
            'success' => empty($errors),
            'executed_statements' => $executed,
            'errors' => $errors,
        ];
    }

    /**
     * Import CSV content into a target table.
     */
    public function importCsv(string $table, string $csvContent, ?string $connectionName = null): array
    {
        $connection = $this->dbManager->connection($connectionName ?? config('database.default'));

        $stream = fopen('php://temp', 'r+');
        fwrite($stream, $csvContent);
        rewind($stream);

        $headers = fgetcsv($stream);
        if (!$headers) {
            return ['success' => false, 'inserted_rows' => 0, 'error' => 'CSV file is empty or missing headers.'];
        }

        $inserted = 0;
        $batch = [];

        while (($row = fgetcsv($stream)) !== false) {
            if (count($row) === count($headers)) {
                $batch[] = array_combine($headers, $row);
                $inserted++;
            }

            if (count($batch) >= 100) {
                $connection->table($table)->insert($batch);
                $batch = [];
            }
        }

        if (!empty($batch)) {
            $connection->table($table)->insert($batch);
        }

        fclose($stream);

        return [
            'success' => true,
            'inserted_rows' => $inserted,
        ];
    }
}
