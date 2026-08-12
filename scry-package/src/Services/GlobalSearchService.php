<?php

namespace Scry\Services;

use Scry\DatabaseExplorerManager;
use Throwable;

class GlobalSearchService
{
    public function __construct(
        protected DatabaseExplorerManager $explorerManager
    ) {}

    /**
     * Search for a keyword across all tables and text columns in a database.
     */
    public function search(string $term, ?string $connectionName = null): array
    {
        $inspector = $this->explorerManager->forConnection($connectionName);
        $tables = $inspector->getTables();

        $results = [];

        foreach ($tables as $tableMeta) {
            $tableName = $tableMeta['name'];
            $schema = $inspector->getTableSchema($tableName);

            $textCols = array_column(
                array_filter($schema['columns'], function ($col) {
                    $t = strtolower($col['data_type']);
                    return str_contains($t, 'char') || str_contains($t, 'text') || str_contains($t, 'string') || $t === 'json' || $t === 'jsonb';
                }),
                'name'
            );

            if (empty($textCols)) {
                continue;
            }

            try {
                $query = $inspector->getPaginatedRows($tableName, 1, 10);

                $matchingRows = [];
                foreach ($query['data'] as $row) {
                    $rowArray = (array)$row;
                    foreach ($textCols as $col) {
                        $val = (string)($rowArray[$col] ?? '');
                        if (stripos($val, $term) !== false) {
                            $matchingRows[] = $rowArray;
                            break;
                        }
                    }
                }

                if (!empty($matchingRows)) {
                    $results[] = [
                        'table' => $tableName,
                        'match_count' => count($matchingRows),
                        'sample_matches' => array_slice($matchingRows, 0, 5),
                    ];
                }
            } catch (Throwable $e) {
                // Ignore search exceptions
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
