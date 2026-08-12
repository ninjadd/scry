<?php

namespace Scry\Services;

use Illuminate\Database\DatabaseManager as LaravelDatabaseManager;
use Scry\DatabaseExplorerManager;
use PDOException;
use Throwable;

class SqlRunner
{
    public function __construct(
        protected DatabaseExplorerManager $explorerManager,
        protected LaravelDatabaseManager $dbManager
    ) {}

    /**
     * Execute a raw SQL query safely against a named connection.
     *
     * @param string $query
     * @param string|null $connectionName
     * @return array
     */
    public function execute(string $query, ?string $connectionName = null): array
    {
        $connectionName = $connectionName ?? config('database.default');
        $connection = $this->dbManager->connection($connectionName);

        $trimmedQuery = ltrim($query);
        $queryType = strtoupper(strtok($trimmedQuery, " \n\r\t"));

        $startTime = microtime(true);

        try {
            $isReadQuery = in_array($queryType, ['SELECT', 'EXPLAIN', 'SHOW', 'DESCRIBE', 'WITH', 'PRAGMA']);

            if ($isReadQuery) {
                $results = $connection->select($query);
                $executionTime = round((microtime(true) - $startTime) * 1000, 2);

                $columns = [];
                if (!empty($results)) {
                    $firstRow = (array) $results[0];
                    $columns = array_keys($firstRow);
                }

                return [
                    'query_type' => $queryType,
                    'is_read' => true,
                    'execution_time_ms' => $executionTime,
                    'row_count' => count($results),
                    'columns' => $columns,
                    'data' => $results,
                ];
            } else {
                $affectedRows = $connection->affectingStatement($query);
                $executionTime = round((microtime(true) - $startTime) * 1000, 2);

                return [
                    'query_type' => $queryType,
                    'is_read' => false,
                    'execution_time_ms' => $executionTime,
                    'affected_rows' => $affectedRows,
                    'message' => "Query executed successfully. {$affectedRows} row(s) affected.",
                ];
            }
        } catch (PDOException $e) {
            $executionTime = round((microtime(true) - $startTime) * 1000, 2);

            return [
                'error' => 'Database SQL Error: ' . $e->getMessage(),
                'code' => $e->getCode(),
                'execution_time_ms' => $executionTime,
            ];
        } catch (Throwable $e) {
            $executionTime = round((microtime(true) - $startTime) * 1000, 2);

            return [
                'error' => 'SQL Execution Error: ' . $e->getMessage(),
                'execution_time_ms' => $executionTime,
            ];
        }
    }
}
