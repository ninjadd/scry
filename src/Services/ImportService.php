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
     * Import a raw SQL dump file into the connection within an isolated database transaction.
     * Automatically rolls back if any statement fails.
     */
    public function importSql(string $sqlContent, ?string $connectionName = null): array
    {
        $connectionName = $connectionName ?? config('database.default');
        $connection = $this->dbManager->connection($connectionName);
        
        $statements = $this->parseSqlStatements($sqlContent);

        if (empty($statements)) {
            return [
                'success' => false,
                'executed_statements' => 0,
                'total_statements' => 0,
                'error' => 'No valid SQL statements found in the uploaded content.',
            ];
        }

        $executed = 0;
        $connection->beginTransaction();

        try {
            foreach ($statements as $index => $stmt) {
                $connection->statement($stmt);
                $executed++;
            }
            $connection->commit();

            return [
                'success' => true,
                'executed_statements' => $executed,
                'total_statements' => count($statements),
                'transaction_committed' => true,
            ];
        } catch (Throwable $e) {
            $connection->rollBack();

            return [
                'success' => false,
                'executed_statements' => $executed,
                'total_statements' => count($statements),
                'failed_statement_index' => $executed + 1,
                'failed_statement' => $statements[$executed] ?? '',
                'transaction_committed' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Parse raw SQL string into distinct executable statements, respecting quotes and comments.
     */
    public function parseSqlStatements(string $sql): array
    {
        $statements = [];
        $length = strlen($sql);
        $current = '';
        $inSingleQuote = false;
        $inDoubleQuote = false;
        $inBacktick = false;
        $inLineComment = false;
        $inBlockComment = false;

        for ($i = 0; $i < $length; $i++) {
            $char = $sql[$i];
            $next = $i + 1 < $length ? $sql[$i + 1] : '';

            // Handle line comments (-- or #)
            if ($inLineComment) {
                if ($char === "\n" || $char === "\r") {
                    $inLineComment = false;
                }
                continue;
            }

            // Handle block comments (/* ... */)
            if ($inBlockComment) {
                if ($char === '*' && $next === '/') {
                    $inBlockComment = false;
                    $i++;
                }
                continue;
            }

            // Check comment starts if not inside quote
            if (!$inSingleQuote && !$inDoubleQuote && !$inBacktick) {
                if ($char === '-' && $next === '-') {
                    $inLineComment = true;
                    $i++;
                    continue;
                }
                if ($char === '#') {
                    $inLineComment = true;
                    continue;
                }
                if ($char === '/' && $next === '*') {
                    $inBlockComment = true;
                    $i++;
                    continue;
                }
            }

            // Handle quote toggling
            if ($char === "'" && !$inDoubleQuote && !$inBacktick) {
                $escaped = ($i > 0 && $sql[$i - 1] === '\\');
                if (!$escaped) {
                    $inSingleQuote = !$inSingleQuote;
                }
            } elseif ($char === '"' && !$inSingleQuote && !$inBacktick) {
                $escaped = ($i > 0 && $sql[$i - 1] === '\\');
                if (!$escaped) {
                    $inDoubleQuote = !$inDoubleQuote;
                }
            } elseif ($char === '`' && !$inSingleQuote && !$inDoubleQuote) {
                $escaped = ($i > 0 && $sql[$i - 1] === '\\');
                if (!$escaped) {
                    $inBacktick = !$inBacktick;
                }
            }

            // Statement delimiter
            if ($char === ';' && !$inSingleQuote && !$inDoubleQuote && !$inBacktick) {
                $trimmed = trim($current);
                if (!empty($trimmed)) {
                    $statements[] = $trimmed;
                }
                $current = '';
                continue;
            }

            $current .= $char;
        }

        $trimmed = trim($current);
        if (!empty($trimmed)) {
            $statements[] = $trimmed;
        }

        return $statements;
    }

    /**
     * Import CSV content into a target table within a database transaction.
     */
    public function importCsv(string $table, string $csvContent, ?string $connectionName = null): array
    {
        $connectionName = $connectionName ?? config('database.default');
        $connection = $this->dbManager->connection($connectionName);

        $stream = fopen('php://temp', 'r+');
        fwrite($stream, $csvContent);
        rewind($stream);

        $headers = fgetcsv($stream);
        if (!$headers) {
            fclose($stream);
            return ['success' => false, 'inserted_rows' => 0, 'error' => 'CSV file is empty or missing headers.'];
        }

        $inserted = 0;
        $batch = [];
        $connection->beginTransaction();

        try {
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

            $connection->commit();
            fclose($stream);

            return [
                'success' => true,
                'inserted_rows' => $inserted,
                'transaction_committed' => true,
            ];
        } catch (Throwable $e) {
            $connection->rollBack();
            fclose($stream);

            return [
                'success' => false,
                'inserted_rows' => 0,
                'transaction_committed' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}
