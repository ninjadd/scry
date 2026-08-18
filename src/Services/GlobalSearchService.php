<?php

namespace Scry\Services;

use Illuminate\Database\DatabaseManager as LaravelDatabaseManager;
use Scry\DatabaseExplorerManager;
use Throwable;

class GlobalSearchService
{
    public function __construct(
        protected DatabaseExplorerManager $explorerManager,
        protected LaravelDatabaseManager $dbManager
    ) {}

    /**
     * Search for a keyword across all tables and text columns in a database using memory-safe batch queries.
     *
     * @param string $term
     * @param string|null $connectionName
     * @param array $options ['per_table_limit' => 10, 'tables' => []]
     * @return array
     */
    public function search(string $term, ?string $connectionName = null, array $options = []): array
    {
        $connectionName = $this->explorerManager->resolveConnectionName($connectionName);
        $inspector = $this->explorerManager->forConnection($connectionName);
        $allTables = $inspector->getTables();
        $db = $this->dbManager->connection($connectionName);

        $perTableLimit = max(1, min((int) ($options['per_table_limit'] ?? 10), 100));
        $targetTableFilter = $options['tables'] ?? [];

        $tablesToSearch = empty($targetTableFilter)
            ? $allTables
            : array_filter($allTables, fn($t) => in_array($t['name'], $targetTableFilter));

        $results = [];
        $startTime = microtime(true);
        $totalMatchesAcrossDatabase = 0;

        foreach ($tablesToSearch as $tableMeta) {
            $tableName = $tableMeta['name'];
            
            try {
                $schema = $inspector->getTableSchema($tableName);
            } catch (Throwable) {
                continue;
            }

            $textCols = array_column(
                array_filter($schema['columns'] ?? [], function ($col) {
                    $t = strtolower($col['data_type'] ?? '');
                    $fullType = strtolower($col['full_type'] ?? '');
                    
                    return str_contains($t, 'char')
                        || str_contains($t, 'text')
                        || str_contains($t, 'string')
                        || str_contains($t, 'varchar')
                        || str_contains($t, 'nvarchar')
                        || str_contains($t, 'citext')
                        || str_contains($t, 'json')
                        || str_contains($t, 'uuid')
                        || str_contains($t, 'clob')
                        || str_contains($t, 'enum')
                        || str_contains($fullType, 'text')
                        || str_contains($fullType, 'char');
                }),
                'name'
            );

            if (empty($textCols)) {
                continue;
            }

            try {
                $query = $db->table($tableName)->where(function ($q) use ($textCols, $term) {
                    foreach ($textCols as $i => $col) {
                        if ($i === 0) {
                            $q->where($col, 'LIKE', "%{$term}%");
                        } else {
                            $q->orWhere($col, 'LIKE', "%{$term}%");
                        }
                    }
                });

                $totalMatches = $query->count();

                if ($totalMatches > 0) {
                    $totalMatchesAcrossDatabase += $totalMatches;
                    $sample = $query->limit($perTableLimit)->get()->toArray();

                    $results[] = [
                        'table' => $tableName,
                        'total_rows_in_table' => $tableMeta['rows'] ?? 0,
                        'matched_columns' => $textCols,
                        'match_count' => $totalMatches,
                        'sample_matches' => array_map(fn($row) => (array) $row, $sample),
                    ];
                }
            } catch (Throwable) {
                // Safely skip inaccessible tables or specialized views without failing the overall search
                continue;
            }
        }

        $executionTimeMs = round((microtime(true) - $startTime) * 1000, 2);

        return [
            'term' => $term,
            'connection' => $connectionName,
            'execution_time_ms' => $executionTimeMs,
            'total_tables_searched' => count($tablesToSearch),
            'matching_tables_count' => count($results),
            'total_matches' => $totalMatchesAcrossDatabase,
            'per_table_limit' => $perTableLimit,
            'results' => $results,
        ];
    }
}
