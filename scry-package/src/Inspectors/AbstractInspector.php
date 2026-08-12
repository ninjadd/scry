<?php

namespace Scry\Inspectors;

use Illuminate\Database\Connection;
use Scry\Contracts\DatabaseInspector;

abstract class AbstractInspector implements DatabaseInspector
{
    public function __construct(protected Connection $connection)
    {
    }

    public function getConnection(): Connection
    {
        return $this->connection;
    }

    public function getPaginatedRows(
        string $table,
        int $page = 1,
        int $perPage = 25,
        ?string $sortBy = null,
        string $sortDir = 'asc'
    ): array {
        $query = $this->connection->table($table);

        $total = $query->count();

        if ($sortBy) {
            $query->orderBy($sortBy, strtolower($sortDir) === 'desc' ? 'desc' : 'asc');
        }

        $items = $query->forPage($page, $perPage)->get();

        return [
            'data' => $items,
            'meta' => [
                'total' => $total,
                'page' => $page,
                'per_page' => $perPage,
                'last_page' => max(1, (int) ceil($total / $perPage)),
            ],
        ];
    }

    public function executeQuery(string $query): array
    {
        $start = microtime(true);
        $results = $this->connection->select($query);
        $executionTime = round((microtime(true) - $start) * 1000, 2);

        return [
            'results' => $results,
            'execution_time_ms' => $executionTime,
            'count' => count($results),
        ];
    }
}
