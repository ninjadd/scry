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
     * Search for a keyword across all tables and text columns in a database using dynamic SQL queries.
     */
    public function search(string $term, ?string $connectionName = null): array
    {
        $connectionName = $connectionName ?? config('database.default');
        $inspector = $this->explorerManager->forConnection($connectionName);
        $tables = $inspector->getTables();
        $db = $this->dbManager->connection($connectionName);

        $results = [];

        foreach ($tables as $tableMeta) {
            $tableName = $tableMeta['name'];
            $schema = $inspector->getTableSchema($tableName);

            $textCols = array_column(
                array_filter($schema['columns'], function ($col) {
                    $t = strtolower($col['data_type']);
                    return str_contains($t, 'char') || str_contains($t, 'text') || str_contains($t, 'string') || str_contains($t, 'varchar') || $t === 'json' || $t === 'jsonb';
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
                    $sample = $query->limit(5)->get()->toArray();
                    $results[] = [
                        'table' => $tableName,
                        'match_count' => $totalMatches,
                        'sample_matches' => array_map(fn($row) => (array) $row, $sample),
                    ];
                }
            } catch (Throwable $e) {
                // Ignore search exceptions for inaccessible tables
            }
        }

        return [
            'term' => $term,
            'total_tables_searched' => count($tables),
            'matching_tables_count' => count($results),
            'results' => $results,
        ];
    }
}
